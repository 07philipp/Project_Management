<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

if ($_SESSION["permission_level"] == 2) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $project_id = $_POST['id'];
        $time_id = $_POST['o_id'];

        include("mysql.php");

        $projectQuery = "SELECT project_user_id 
                FROM project
                WHERE project_id = '$project_id'";
        $projectResult = $mysql->query($projectQuery);
        if ($projectResult) {
            $projectData = $projectResult->fetch(PDO::FETCH_ASSOC);
            $projectUserId = $projectData['project_user_id'];
            if ($projectUserId != $_SESSION['user_id']) {
                echo "Keine berechtigung.";
                exit;
            }
        }
    }
} elseif ($_SESSION["user_id"] == 1) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
} else {
    $project_id = $_POST['id'];
    $time_id = $_POST['t_id'];
    include("mysql.php");
}
require_once("log.php");
$start = isset($_POST['start']) ? $_POST['start'] : null;
$end = isset($_POST['end']) ? $_POST['end'] : null;

if ($start !== null) {
    $start = date("Y-m-d H:i:s", strtotime($start));
    $timeQuery = "UPDATE `time` SET start_time = :start WHERE time_id = :time_id AND project_id = :project_id";
    $stmt = $mysql->prepare($timeQuery);
    $stmt->bindParam(':start', $start);
    $stmt->bindParam(':time_id', $time_id);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit time $time_id in $project_id to $start");
}

if ($end !== null) {
    $end = date("Y-m-d H:i:s", strtotime($end));
    $timeQuery = "UPDATE `time` SET end_time = :end WHERE time_id = :time_id AND project_id = :project_id";
    $stmt = $mysql->prepare($timeQuery);
    $stmt->bindParam(':end', $end);
    $stmt->bindParam(':time_id', $time_id);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit time $time_id in $project_id to $end");
}

