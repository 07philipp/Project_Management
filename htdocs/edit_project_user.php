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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['project_id']) && isset($_POST['user_id'])) {
    $projectId = $_POST['project_id'];
    $userId = $_POST['user_id'];

    include('mysql.php');
    require_once "log.php";

    $stmt = $mysql->prepare(
        'UPDATE project SET project_user_id = :user_id WHERE project_id = :project_id'
    );
    $result = $stmt->execute([
        ':user_id' => $userId,
        ':project_id' => $projectId,
    ]);

    if ($result) {
        echo"Nutzer wurde geändert.";
        logSQL($mysql, $_SESSION['username'], "change project $projectId user $userId");
    } else {
        echo "Nutzer konnte nicht geändert werden.";
    }
} else {
    exit;
}
