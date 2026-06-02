<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI'];

    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 2) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['id'])) {
    $projectNumber = $_GET['id'];
    include('../mysql.php');

    $projectQuery = "SELECT project_name, project_client_id, project_address, project_description, project_due_date, project_created_date
        FROM project
        WHERE project_id = '$projectNumber'";
    $projectResult = $mysql->query($projectQuery);
    if ($projectResult) {
        $projectData = $projectResult->fetch(PDO::FETCH_ASSOC);
        $projectName = $projectData['project_name'];
        $projectClientId = $projectData['project_client_id'];
        $projectAddress = $projectData['project_address'];
        $projectDescription = $projectData['project_description'];
        $projectDate = $projectData['project_due_date'];
        $createdDate = $projectData['project_created_date'];

        $clientQuery = "SELECT *
            FROM client
            WHERE client_id = '$projectClientId'";
        $clientResult = $mysql->query($clientQuery);
        $clientData = $clientResult->fetch(PDO::FETCH_ASSOC);
        $clientName = $clientData['client_name'];
        $clientAdress = $clientData['client_address'];
        $clientLocation = $clientData['client_location'];
        $clientCompany = $clientData['client_company'];
        $clientGender = $clientData['client_gender'];

        // Orders abfragen
        $ordersQuery = "SELECT o.order_id, o.order_order, o.order_amount, o.order_hourly_wage
                    FROM `order` o
                    WHERE o.order_project_id = '$projectNumber'";
        $ordersResult = $mysql->query($ordersQuery);

        // Times abfragen
        $timesQuery = "SELECT order_id, SUM(duration) AS total_duration
                    FROM `time`
                    WHERE project_id = '$projectNumber'
                    GROUP BY order_id";
        $timeResult = $mysql->query($timesQuery);

        // Arrays zur Speicherung der Ergebnisse
        $orders = array();
        $times = array();

        // Orders in das Array speichern
        if ($ordersResult) {
            while ($order = $ordersResult->fetch(PDO::FETCH_ASSOC)) {
                $orders[$order['order_id']] = $order;
                $orders[$order['order_id']]['total_duration'] = 0;  // Standardwert für total_duration
            }
        }

        // Times summieren und zu den passenden Orders hinzufügen
        if ($timeResult) {
            while ($time = $timeResult->fetch(PDO::FETCH_ASSOC)) {
                $orderId = $time['order_id'];
                if (isset($orders[$orderId])) {
                    $orders[$orderId]['total_duration'] = $time['total_duration'];
                }
            }
        }
    } else {
        echo 'Error: Unable to retrieve project data';
    }
} else {
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Rechnungsformular</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/option_search.js"></script>
    <script>
        function toggleNewClientFields(inputField) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../get_client.php?client_id=' + inputField.value, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var clientData = JSON.parse(xhr.responseText);
                    updateClientFields(clientData);
                    var gender = clientData.client_gender;
                    if (gender == 'Männlich') {
                        document.getElementById('male').checked = true;
                    } else if (gender == 'Weiblich') {
                        document.getElementById('female').checked = true;
                    }
                } else {
                    console.log('Error: ' + xhr.statusText);
                }
            };
            xhr.send();
        }

        function updateClientFields(clientData) {
            document.getElementById('name').value = clientData.client_name;
            document.getElementById('address').value = clientData.client_address;
            document.getElementById('location').value = clientData.client_location;
            document.getElementById('company').value = clientData.client_company;
        }

        var toggle = false; // Set initial value
        function toggleForm() {
            var form = document.getElementById("discount");
            form.style.display = (form.style.display === "none") ? "block" : "none";
            toggle = !toggle; // Update the global variable
            document.getElementById('discountB').value = toggle.toString();
        }

        function saveChanges(inputField) {
            var option = document.getElementById("client_id");
            var params = new URLSearchParams();
            params.append("client_id", option.value);
            params.append(inputField.name, inputField.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_client_process.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }

        function saveChangesOrder(inputField, orderId) {
            var imname = inputField.id;
            if (imname.replace(/\d+/g, '') === "price") {
                var priceF = inputField.name;
                let num = parseFloat(priceF.match(/\d+(\.\d+)?/)[0]);
                var amountF = document.getElementById("amount" + num)
                var houer = document.getElementById("time" + num);

                amountF.value = (houer.value) * (inputField.value);

                var params = new URLSearchParams();
                params.append("order_id", orderId);
                params.append(amountF.id.replace(/\d+/g, ''), amountF.value);

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "../edit_amount.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.addEventListener("load", function() {
                    console.log(xhr.responseText);
                });
                xhr.send(params.toString());
            }

            var params = new URLSearchParams();
            params.append("order_id", orderId);
            params.append(inputField.id.replace(/\d+/g, ''), inputField.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_amount.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }

        function saveChangesDiscount(inputField) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../get_amount.php?project_id=' + encodeURIComponent('<?php echo $projectNumber; ?>'), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var invoiceAmount = parseFloat(xhr.responseText) || 0;
                    const discountPercentField = document.getElementById('discount_amount_per');
                    const discountAmountField = document.getElementById('discount_amount_num');

                    // Get the values from the fields
                    let discountPercent = parseFloat(discountPercentField.value) || 0;
                    let discountAmount = parseFloat(discountAmountField.value) || 0;

                    if (inputField.id === 'discount_amount_per') {
                        // If the percentage field is changed, calculate the amount
                        discountAmount = invoiceAmount * (discountPercent / 100);

                        // Ensure discount amount does not exceed the invoice amount
                        if (discountAmount > invoiceAmount) {
                            discountAmount = invoiceAmount;
                            discountPercentField.value = (discountAmount / invoiceAmount * 100).toFixed(2); // Adjust percentage
                        }

                        discountAmountField.value = discountAmount.toFixed(2);
                    } else if (inputField.id === 'discount_amount_num') {
                        // If the amount field is changed, calculate the percentage
                        if (invoiceAmount > 0) {
                            discountPercent = (discountAmount / invoiceAmount) * 100;

                            // Ensure discount percentage does not exceed 100%
                            if (discountPercent > 100) {
                                discountPercent = 100;
                                discountAmount = invoiceAmount; // Adjust amount
                            }

                            discountPercentField.value = discountPercent.toFixed(2);
                            discountAmountField.value = discountAmount.toFixed(2);
                        } else {
                            // Handle the case where invoiceAmount is 0 or invalid
                            discountPercentField.value = '0';
                            discountAmountField.value = '0';
                        }
                    }
                } else {
                    console.log('Error: ' + xhr.statusText);
                }
            };
            xhr.send();
        }
    </script>
</head>

<body>
    <h1>Auftragsformular</h1>
    <h2><?php echo "$projectName"; ?></h2>
    <form action="../generate_word.php" method="POST">
        <table class="not" id="not">
            <tr>
                <th>Aufträge</th>
                <th>Arbeitszeit in Stunden</th>
                <th>Stundenlohn in €</th>
                <th>Preis in €</th>
            </tr>
            <?php if (isset($orders)) {
                $count = 0;
                foreach ($orders as $order) {
                    $count = 1 + $count;
                    $orderTime = ($order['total_duration'] > 60) ? round($order['total_duration'] / 60) : 0;
                    echo "
                    <tr>
                        <td>
                            <ul>
                                <input class='input' id='order' name='order_order' type='text' value='" . $order['order_order'] . "' onchange='saveChangesOrder(this, " . $order['order_id'] . ")' />
                            </ul>
                        </td>
                        <td>
                            <input class='input' id='time" . $count . "' name='order_time" . $count . "' type='number' value='" . $orderTime . "' readonly/>
                        </td>
                        <td>
                            <input class='input' id='price" . $count . "' name='order_price" . $count . "' type='number' value='" . $order['order_hourly_wage'] . "' onchange='saveChangesOrder(this, " . $order['order_id'] . ")'/>
                        </td>
                        <td>
                            <ul>
                                <input class='input' id='amount" . $count . "' name='order_amount' type='number' value='" . $order['order_amount'] . "' onchange='saveChangesOrder(this, " . $order['order_id'] . ")' />
                            </ul>
                        </td>
                    </tr>
                ";
                }
            } ?>
        </table>
        <div style="
        background-color: #fff; 
        padding: 20px;
        border: 1px solid #ddd;
        margin-top: 20px;
        margin-bottom: 20px;
        ">
            <h2>Sendungsadresse</h2>
            <label style="display: block; margin-bottom: 5px;" for="client">Auftraggeber:</label>
            <div class="select-wrapper">
                <input type="text" id="searchInput" class="search-input" onchange="searchFields(this)" placeholder="Suchen...">
                <select style="margin-bottom: 10px;" style="margin-bottom: 10px; margin-left: 15px;" id="client_id" name="client_id" class="custom-select" onchange="toggleNewClientFields(this)" required>
                    <?php
                    echo "<option class='search-option used' id='client_id' name='client_id' value='" . $clientData['client_id'] . "'>" . $clientName . "</option>";

                    try {
                        $selectQuery = "SELECT * FROM client";
                        $result = $mysql->query($selectQuery);

                        if ($result->rowCount() > 0) {
                            $clients = $result->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($clients as $client) {
                                if ($client['client_id'] != $clientData['client_id']) {
                                    echo "<option class='search-option' id='client_id' name='client_id' value='" . $client['client_id'] . "'>" . $client['client_name'] . "</option>";
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                    }
                    ?>
                </select>
            </div>

            <label style="display: block; margin-bottom: 5px;" for="name">Kundenname:</label>
            <input style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="text" name="name" id="name" value="<?php echo $clientName; ?>" onchange="saveChanges(this)" required /><br>

            <label style="display: block; margin-bottom: 5px;" for="address">Adresse:</label>
            <input style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="text" name="address" id="address" value="<?php echo $clientAdress; ?>" onchange="saveChanges(this)" required /><br>

            <label style="display: block; margin-bottom: 5px;" for="complocationany">Ort:</label>
            <input style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="text" name="location" id="location" value="<?php echo $clientLocation; ?>" onchange="saveChanges(this)" required /><br>

            <label style="display: block; margin-bottom: 5px;" for="company">Unternehmen:</label>
            <input style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="text" name="company" id="company" value="<?php echo $clientCompany; ?>" onchange="saveChanges(this)" required /><br>

            <label style="margin-right: 100%;" for="gender">Geschlecht:</label>
            <input style="margin-top: 10px;" type="radio" id="male" name="gender" value="Männlich" onchange="saveChanges(this)" <?php if ($clientGender == 'Männlich') {
                                                                                                                                    echo 'checked';
                                                                                                                                } ?> /> Männlich
            <input style="margin-left: 20px; margin-bottom: 15px;" type="radio" id="female" name="gender" value="Weiblich" onchange="saveChanges(this)" <?php if ($clientGender == 'Weiblich') {
                                                                                                                                                            echo 'checked';
                                                                                                                                                        } ?> /> Weiblich
             <input style="margin-left: 20px; margin-bottom: 15px;" type="radio" id="female" name="gender" value="Familie" onchange="saveChanges(this)" <?php if ($clientGender == 'Familie') {
                                                                                                                                                            echo 'checked';
                                                                                                                                                        } ?> /> Familie                                                                                                                                            

            <label style="display: block; margin-bottom: 5px;" for="company">Zeitraum:</label>
            <input style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="text" name="period" id="period" value="<?php echo date('d.m.Y', $createdDate) . ' - ' . date('d.m.Y', strtotime($projectDate)); ?>" required /><br>
            <div>
                <button class="link" type="button" onclick="toggleForm()">Rabatt hinzufügen</button>
            </div>

            <a id="discount" style="display: none;">
                <input type="hidden" name="discountB" value="false" id="discountB">
                <h2>Rabatt</h2>
                <label style="display: block; margin-bottom: 5px;" for="discount">Rabatt:</label>
                <input style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="text" id="discount" name="discount"><br>
                <label style="display: block; margin-bottom: 5px;" for="discount_amount">Rabatthöhe in %:</label>
                <input onchange="saveChangesDiscount(this);" style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="number" id="discount_amount_per" name="discount_amount_per"><br>
                <label style="display: block; margin-bottom: 5px;" for="discount_amount">Rabatthöhe in €:</label>
                <input onchange="saveChangesDiscount(this);" style="width: 100%;max-width: 300px;padding: 8px;margin-bottom: 10px;border: 1px solid #ccc;border-radius: 4px;" type="number" id="discount_amount_num" name="discount_amount_num"><br>
            </a>

            <input type="hidden" name="projectNumber" id="projectNumber" value='<?php echo "$projectNumber"; ?> ' /><br>
            <button class='link' type="submit">Rechnung schreiben</button>

        </div>
    </form>
    <?php if (isset($_GET['back'])) { ?>
        <a class="link" href='../'>Zurück</a>
    <?php } else { ?>
        <a class="link" href='../projects/?id=<?php echo "$projectNumber"; ?>'>Zurück</a>
    <?php } ?>
</body>

</html>