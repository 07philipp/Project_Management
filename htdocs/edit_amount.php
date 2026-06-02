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
        $order_id = $_POST['order_id'];
        include("mysql.php");
        $orderStmt = $mysql->prepare(
            'SELECT order_project_id, order_checked FROM `order` WHERE order_id = :order_id'
        );
        $orderStmt->execute([':order_id' => $order_id]);
        if ($orderStmt) {
            $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
            $projectNumber = $orderData['order_project_id'];
            $orderChecked = $orderData['order_checked'];
            $projectStmt = $mysql->prepare(
                'SELECT project_user_id FROM project WHERE project_id = :project_id'
            );
            $projectStmt->execute([':project_id' => $projectNumber]);
            if ($projectStmt) {
                $projectData = $projectStmt->fetch(PDO::FETCH_ASSOC);
                $projectUserId = $projectData['project_user_id'];
                if ($projectUserId != $_SESSION['user_id']) {
                    echo "Keine berechtigung.";
                    exit;
                }
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
    $order_id = $_POST['order_id'];
    include("mysql.php");
    $orderStmt = $mysql->prepare(
        'SELECT order_project_id, order_checked FROM `order` WHERE order_id = :order_id'
    );
    $orderStmt->execute([':order_id' => $order_id]);
    if ($orderStmt) {
        $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
        $orderChecked = $orderData['order_checked'];
        $projectId = $orderData['order_project_id'];
    }
}
require_once "log.php";

$order_order = isset($_POST['order']) ? $_POST['order'] : null;
$order_amount = isset($_POST['amount']) ? $_POST['amount'] : null;
$order_hourly = isset($_POST['price']) ? $_POST['price'] : null;
$order_checked = isset($_POST['check']) ? (isset($orderChecked) && $orderChecked == "checked" ? "running" : "checked") : null;

// Update the database
if ($order_order !== null) {
    $orderQuery = "UPDATE `order` SET order_order = :order_order WHERE order_id = :order_id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':order_order', $order_order);
    $orderStmt->bindParam(':order_id', $order_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit order $order_id to $order_order");
}

if ($order_amount !== null) {
    $orderQuery = "UPDATE `order` SET order_amount = :order_amount WHERE order_id = :order_id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':order_amount', $order_amount);
    $orderStmt->bindParam(':order_id', $order_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit amount $order_id to $order_amount");
}

if ($order_hourly !== null) {
    $orderQuery = "UPDATE `order` SET order_hourly_wage = :order_hourly WHERE order_id = :order_id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':order_hourly', $order_hourly);
    $orderStmt->bindParam(':order_id', $order_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit hourly $order_id to $order_hourly");
}

if ($order_checked !== null) {
    $orderQuery = "UPDATE `order` SET order_checked = :order_checked WHERE order_id = :order_id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':order_checked', $order_checked);
    $orderStmt->bindParam(':order_id', $order_id);
    $orderStmt->execute();


    // Alle Orders dieses Projekts holen
    $stmt = $mysql->prepare("
        SELECT order_checked 
        FROM `order` 
        WHERE order_project_id = :pid
    ");
    $stmt->execute([':pid' => $projectId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($orders);
    $done  = 0;

    foreach ($orders as $o) {
        if ($o['order_checked'] === 'checked') {
            $done++;
        }
    }

    $progress = ($total > 0) ? round(($done / $total) * 100) : 0;

    // Aktuellen Projektstatus holen
    $stmt = $mysql->prepare("
        SELECT project_status 
        FROM project 
        WHERE project_id = :pid
    ");
    $stmt->execute([':pid' => $projectId]);
    $currentStatus = $stmt->fetchColumn();

    // AUTOMATISCH: in completed wechseln
    $newStatus = ($currentStatus === 'in_progress' && $progress == 100) ? 'completed' : 'in_progress';

    if($newStatus != $currentStatus){

        $stmt = $mysql->prepare("
            UPDATE project 
            SET project_status = :status,
                completed_date = NOW()
            WHERE project_id = :pid
        ");
        $stmt->execute([':status' => $newStatus, ':pid' => $projectId]);

        if($newStatus === 'in_progress'){
            $stmt = $mysql->prepare("
                UPDATE project 
                SET completed_date = NULL
                WHERE project_id = :pid
            ");
            $stmt->execute([':pid' => $projectId]);
            logSQL($mysql, $_SESSION['username'], "checked order $order_id to $order_checked new status $newStatus delete date");

        }else{
            logSQL($mysql, $_SESSION['username'], "checked order $order_id to $order_checked new status $newStatus");
        }
    }else{
        logSQL($mysql, $_SESSION['username'], "checked order $order_id to $order_checked now $progress %");
    }
}
        

