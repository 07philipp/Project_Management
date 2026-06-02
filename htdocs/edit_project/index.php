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

    $projectStmt = $mysql->prepare(
        'SELECT project_name, project_client_id, project_address, project_description, project_user_id, project_due_date
         FROM project
         WHERE project_id = :project_id'
    );
    $projectStmt->execute([':project_id' => $projectNumber]);
    if ($projectStmt) {
        $projectData = $projectStmt->fetch(PDO::FETCH_ASSOC);
        $projectName = $projectData['project_name'];
        $projectClientId = $projectData['project_client_id'];
        $projectAddress = $projectData['project_address'];
        $projectDescription = $projectData['project_description'];
        $projectUserId = $projectData['project_user_id'];
        $projectDate = $projectData['project_due_date'];

        $clientStmt = $mysql->prepare('SELECT * FROM client WHERE client_id = :client_id');
        $clientStmt->execute([':client_id' => $projectClientId]);
        $clientData = $clientStmt->fetch(PDO::FETCH_ASSOC);
        $clientName = $clientData['client_name'];

        $userStmt = $mysql->prepare('SELECT * FROM user WHERE user_id = :user_id');
        $userStmt->execute([':user_id' => $projectUserId]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userName = $userData['user_name'];

        $ordersStmt = $mysql->prepare(
            'SELECT order_id, order_project_id, order_order, order_amount
             FROM `order`
             WHERE order_project_id = :project_id'
        );
        $ordersStmt->execute([':project_id' => $projectNumber]);
        $ordersResult = $ordersStmt;

        $orders = array();

        if ($ordersResult) {
            while ($order = $ordersResult->fetch(PDO::FETCH_ASSOC)) {
                $orders[$order['order_project_id']][] = $order;
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
<html lang="de" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project bearbeiten</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/option_search.js"></script>
    <script src="../js/notification.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var errorMessage = <?= pm_json_script(pm_take_flash_message()) ?>;
            if (errorMessage) {
                showNotification(errorMessage);
            }
        });

        function saveChangesOrder(inputField, orderId) {
            var params = new URLSearchParams();
            params.append("order_id", orderId);
            params.append(inputField.name, inputField.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_amount.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }

        function saveChanges(inputField) {
            var params = new URLSearchParams();
            params.append("project_id", <?= pm_json_script($projectNumber) ?>);
            params.append(inputField.name, inputField.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_project_process.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }

        function changeClient(inputField) {
            var clientId = inputField.value;
            var projectId = <?= pm_json_script($projectNumber) ?>;

            var params = new URLSearchParams();
            params.append("project_id", projectId);
            params.append("client_id", clientId);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_project_client.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                var errorMessage = xhr.responseText;
                    if (errorMessage) {
                        showNotification(errorMessage);
                    }
            });
            xhr.send(params.toString());
        }

        function changeUser(inputField) {
            var usertId = inputField.value;
            var projectId = <?= pm_json_script($projectNumber) ?>;

            var params = new URLSearchParams();
            params.append("project_id", projectId);
            params.append("user_id", usertId);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_project_user.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                var errorMessage = xhr.responseText;
                    if (errorMessage) {
                        showNotification(errorMessage);
                    }
            });
            xhr.send(params.toString());
        }
    </script>
</head>

<body>
    <table>
        <tr>
            <th>Projektname</th>
            <th>Auftraggeber</th>
            <th>Betreuer</th>
            <th>Aufträge</th>
            <th>Betrag</th>
            <th>Adresse</th>
            <th>Beschreibung</th>
            <th>Abgabedatum</th>
        </tr>
        <tr>
            <td><input class='input' name="project" type="text" value='<?php echo h($projectName); ?>' onchange="saveChanges(this)"></td>
            <td>
                <div class="select-wrapper_S">
                    <input type="text" id="searchInput" class="search-input_S" onchange="searchFields(this)" placeholder="Suchen...">
                    <br>
                    <select style="margin-bottom: 10px; margin-left: 15px;" id="client_id" name="client_id" class="custom-select_S" onchange="changeClient(this)" required>
                        <?php
                        echo "<option class='search-option_S used' id='client_id' name='client_id' value='" . h($clientData['client_id']) . "' >" . h($clientName) . "</option>";

                        try {
                            $selectQuery = "SELECT * FROM client";
                            $result = $mysql->query($selectQuery);

                            if ($result->rowCount() > 0) {
                                $clients = $result->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($clients as $client) {
                                    if ($client['client_id'] != $clientData['client_id']) {
                                        echo "<option id='client_id' name='client_id' value='" . h($client['client_id']) . "' class='search-option_S'>" . h($client['client_name']) . "</option>";
                                    }
                                }
                            }
                        } catch (PDOException $e) {
                            echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                        }
                        ?>
                    </select>
                </div>
            </td>
            <td>
                <div class="select-wrapper_S">
                    <input type="text" id="searchInput" class="search-input_S" onchange="searchUser(this)" placeholder="Suchen...">
                    <br>
                    <select style="margin-bottom: 10px; margin-left: 15px;" id="user_id" name="user_id" class="custom-select_S" onchange="changeUser(this)" required>
                        <?php
                        echo "<option class='search-option_S used' id='user_id' name='user_id' value='" . h($userData['user_id']) . "' >" . h($userName) . "</option>";

                        try {
                            $selectQueryU = "SELECT * FROM user";
                            $resultU = $mysql->query($selectQueryU);

                            if ($resultU->rowCount() > 0) {
                                $users = $resultU->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($users as $user) {
                                    if ($user['user_id'] != $userData['user_id']) {
                                        if ($user['user_name'] != $user_name && $user['permission_level'] != 1) {
                                            echo "<option id='user_id' name='user_id' value='" . h($user['user_id']) . "' class='search-option_S'>" . h($user['user_name']) . "</option>";
                                        }
                                    }
                                }
                            }
                        } catch (PDOException $e) {
                            echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                        }
                        ?>
                    </select>
                </div>
            </td>
            <td>
                <ul>
                    <?php if (isset($orders[$projectNumber])) {
                        foreach ($orders[$projectNumber] as $order) { ?>
                            <li><input class='input' name='order' type='text' value="<?php echo h($order['order_order']); ?>" onchange="saveChangesOrder(this, <?php echo pm_json_script($order['order_id']); ?>)" /></li>
                    <?php }
                    } ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php if (isset($orders[$projectNumber])) {
                        foreach ($orders[$projectNumber] as $order) { ?>
                            <li><input class='input' name='amount' type='number' value="<?php echo h($order['order_amount']); ?>" onchange="saveChangesOrder(this, <?php echo pm_json_script($order['order_id']); ?>)" /></li>
                    <?php }
                    } ?>
                </ul>
            </td>
            <td><input class='input' name="adress" type="text" value='<?php echo h($projectAddress); ?>' onchange="saveChanges(this)"></td>
            <td><input class='input' name="des" type="text" value='<?php echo h($projectDescription); ?>' onchange="saveChanges(this)"></td>
            <td><input class='input' name="date" type="date" value="<?php echo h($projectDate); ?>" onchange="saveChanges(this);" style="max-width: 110px;"></td>
        </tr>
        <tr>
            <td>
            </td>
            <td>
            </td>
            <td>
            </td>
            <form action='../edit_order.php' method='post'>
                <input type='hidden' name='projectNumber' value='<?php echo h($projectNumber); ?>'>
                <input type='hidden' name='back' value='edit'>
                <td>
                    <button name="addorder" type="submit">Auftrag hinzufügen</button>
                </td>
                <td>
                    <button name="deleteorder" type="submit">Auftrag löchen</button>
                </td>
            </form>
            <td>
            </td>
            <td>
            </td>
        </tr>
    </table>
    <a class="link" href='../projects/?id=<?php echo h($projectNumber); ?>'>Fertig</a>

    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>

</body>

</html>