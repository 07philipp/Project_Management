<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['project_id']) && isset($_POST['client_id'])) {
    $projectId = $_POST['project_id'];
    $clientId = $_POST['client_id'];

    include('mysql.php');
    require_once "log.php";

    $updateQuery = "UPDATE project SET project_client_id = '$clientId' WHERE project_id = '$projectId'";
    $result = $mysql->query($updateQuery);

    if ($result) {
        echo "Kunde wurde geändert.";
        logSQL($mysql, $_SESSION['username'], "change project $projectId client $clientId");
    } else {
        echo "Kunde konnte nicht geändert werden.";
    }
} else {
    exit;
}
