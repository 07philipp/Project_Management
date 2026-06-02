<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
}else{
    define('INCLUDE_GUARD', true);
}
include('mysql.php');

$clientId = $_GET['client_id'];

$stmt = $mysql->prepare('SELECT * FROM client WHERE client_id = :client_id');
$stmt->execute([':client_id' => $clientId]);
$clientData = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($clientData);
