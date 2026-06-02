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
        $order_id = $_POST['order_id'];
        $user_id = $_SESSION['user_id'];

        include("mysql.php");

        $projectStmt = $mysql->prepare(
            'SELECT project_user_id FROM project WHERE project_id = :project_id'
        );
        $projectStmt->execute([':project_id' => $project_id]);
        if ($projectStmt) {
            $projectData = $projectStmt->fetch(PDO::FETCH_ASSOC);
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
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];
    include("mysql.php");
}

require_once("log.php");

$start = date("Y-m-d H:i:s", strtotime($_POST['start']));
$end = date("Y-m-d H:i:s", strtotime($_POST['end']));


$t_id = time();
$sql = "INSERT INTO time (time_id, start_time, end_time, project_id, order_id, user_id) 
        VALUES (:time_id, :start, :end, :project_id, :order_id, :user_id)";
$stmt = $mysql->prepare($sql);
$stmt->bindParam(":time_id", $t_id);
$stmt->bindParam(":start", $start);
$stmt->bindParam(":end", $end);
$stmt->bindParam(":project_id", $project_id);
$stmt->bindParam(":order_id", $order_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$start = strtotime($_POST['start']);
$end = strtotime($_POST['end']);

// Berechne den Stundenlohn
$wageStmt = $mysql->prepare('SELECT order_hourly_wage FROM `order` WHERE order_id = :order_id');
$wageStmt->execute([':order_id' => $order_id]);
$hourlyWage = $wageStmt->fetchColumn();

// Berechne die Differenz in Sekunden
$timeDifferenceInSeconds = $end - $start;

// Wandle die Differenz von Sekunden in Stunden um
$timeDifferenceInHours = $timeDifferenceInSeconds / 3600;

// Berechne den Betrag (Lohn)
$amount = $hourlyWage * $timeDifferenceInHours;

$newAmount = number_format($amount, 2);

$selectOrderAmountQuery = "SELECT order_amount FROM `order` WHERE order_id = :order_id";
$stmt = $mysql->prepare($selectOrderAmountQuery);
$stmt->bindParam(":order_id", $order_id);
$stmt->execute();
$existingAmount = $stmt->fetchColumn();

$inamount = $existingAmount + $newAmount;

$sql = "UPDATE `order` SET order_amount = :order_amount
        WHERE order_id = :order_id";
$stmt = $mysql->prepare($sql);
$stmt->bindParam(":order_amount", $inamount);
$stmt->bindParam(":order_id", $order_id);

$stmt->execute();
logSQL($mysql, $_SESSION['username'], "add time $t_id in $project_id");
$_SESSION['error_message'] = "Arbeitszeit wurde hinzugefügt.";

header("Location: projects/?id=$project_id");
