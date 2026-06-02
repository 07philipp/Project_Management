<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI']; 
    
    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
}else{
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
    $clientNumber = $_GET['id'];
    include('../mysql.php');

    $stmt = $mysql->prepare('SELECT * FROM client WHERE client_id = :client_id');
    $stmt->execute([':client_id' => $clientNumber]);
    if ($stmt) {
        $clientData = $stmt->fetch(PDO::FETCH_ASSOC);
        $clientName = $clientData['client_name'];
        $clientAdress = $clientData['client_address'];
        $clientLocation = $clientData['client_location'];
        $clientCompany = $clientData['client_company'];
        $clientGender = $clientData['client_gender'];

        $projectStmt = $mysql->prepare(
            'SELECT * FROM project WHERE project_client_id = :client_id'
        );
        $projectStmt->execute([':client_id' => $clientNumber]);
        $projectResult = $projectStmt;

        $projects = array();

        if ($projectResult) {
            while ($project = $projectResult->fetch(PDO::FETCH_ASSOC)) {
                $projects[$project['project_client_id']][] = $project;
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
    <title>Kunden bearbeiten</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function saveChanges(inputField) {
            var params = new URLSearchParams();
            params.append("client_id", <?= pm_json_script($clientNumber) ?>);
            params.append(inputField.name, inputField.value);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_client_process.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }
    </script>
</head>

<body>
    <table>
        <tr>
            <th>Projecte</th>
            <th>Name</th>
            <th>Adresse</th>
            <th>Ort</th>
            <th>Unternehmen</th>
            <th>Geschlächt</th>
        </tr>
        <tr>
        <tr>
            <td><?php if (isset($projects[$clientNumber])) {
                    foreach ($projects[$clientNumber] as $project) { ?>
                        <a href="../projects/<?php echo h($project['project_id']); ?>"><?php echo h($project['project_name']); ?></a>
            </td>
    <?php }
                } ?>
    <td><input class='input' name="name" type="text" value='<?php echo h($clientName); ?>' onchange="saveChanges(this)"></td>

    <td><input class='input' name="adress" type="text" value='<?php echo h($clientAdress); ?>' onchange="saveChanges(this)"></td>

    <td><input class='input' name="location" type="text" value='<?php echo h($clientLocation); ?>' onchange="saveChanges(this)"></td>

    <td><input class='input' name="company" type="text" value='<?php echo h($clientCompany); ?>' onchange="saveChanges(this)"></td>

    <td>

        <div style="width: max-content;">
            <input type="radio" id="male" name="gender" value="Männlich" onchange="saveChanges(this)" <?php if ($clientGender == 'Männlich') {
                                                                                                            echo 'checked';
                                                                                                        } ?> />
            <label for="male">Männlich</label>
        </div>
        <div style="margin-top: 10px; width: max-content;">
            <input type="radio" id="female" name="gender" value="Weiblich" onchange="saveChanges(this)" <?php if ($clientGender == 'Weiblich') {
                                                                                                            echo 'checked';
                                                                                                        } ?> />
            <label for="female">Weiblich</label>
            <input type="radio" id="female" name="gender" value="Familie" onchange="saveChanges(this)" <?php if ($clientGender == 'Familie') {
                                                                                                            echo 'checked';
                                                                                                        } ?> />
            <label for="female">Familie</label>
        </div>

    </td>
        </tr>
    </table>
    <div style="margin-top: 20px;">
    <?php if (isset($_GET['back'])) { ?>
        <a class="link" href='../clients'>Fertig</a>
    <?php } else { ?>
        <a class="link" href='../clients/?id=<?php echo h($clientNumber); ?>'>Fertig</a>
    <?php } ?>
    </div>

</body>

</html>