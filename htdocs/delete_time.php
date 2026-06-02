<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

if ($_SESSION["permission_level"] == 2) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $project_id = $_POST['p_id'];
        $time_id = $_POST['id'];
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
    $project_id = $_POST['p_id'];
    $time_id = $_POST['id'];
    include("mysql.php");
}
require_once("log.php");


$orderQ = "SELECT order_id FROM `time` WHERE time_id = '$time_id'";
$order_id = $mysql->query($orderQ)->fetchColumn();

$timeQ = "SELECT duration FROM `time` WHERE time_id = '$time_id'";
$timeM = $mysql->query($timeQ)->fetchColumn();
$time = round($timeM / 60);

$selectOrderAmountQuery = "SELECT order_amount FROM `order` WHERE order_id = :order_id";
$stmt = $mysql->prepare($selectOrderAmountQuery);
$stmt->bindParam(":order_id", $order_id);
$stmt->execute();
$existingAmount = $stmt->fetchColumn();

// Berechne den Stundenlohn
$countQ = "SELECT order_hourly_wage FROM `order` WHERE order_id = '$order_id'";
$hourlyWage = $mysql->query($countQ)->fetchColumn();

$subAmount = $hourlyWage * $time;

$inamount = $existingAmount - $subAmount;

$sql = "UPDATE `order` SET order_amount = :order_amount
        WHERE order_id = :order_id";
$stmt = $mysql->prepare($sql);
$stmt->bindParam(":order_amount", $inamount);
$stmt->bindParam(":order_id", $order_id);

$stmt->execute();

$sql = "DELETE FROM `time` 
    WHERE time_id = :time_id AND project_id = :project_id";
$stmt = $mysql->prepare($sql);
$stmt->bindParam(":project_id", $project_id);
$stmt->bindParam(":time_id", $time_id);

$stmt->execute();
$_SESSION['error_message'] = "Zeiten gelöcht.";
logSQL($mysql, $_SESSION['username'], "delete time $time_id in $project_id");

header("Location: edit_time/?id=$project_id");
