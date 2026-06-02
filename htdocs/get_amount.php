<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
}else{
    define('INCLUDE_GUARD', true);
}
include('mysql.php');

$projectNumber = $_GET['project_id'];

$ordersQuery = "SELECT order_id, order_order, order_amount 
            FROM `order` 
            WHERE order_project_id = :projectNumber";
            $stmt = $mysql->prepare($ordersQuery);
            $stmt->execute(['projectNumber' => $projectNumber]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $invoiceAmount = 0;
            foreach ($orders as $index => $order) {
                $invoiceAmount += intval($order['order_amount']);
            }

echo json_encode($invoiceAmount);