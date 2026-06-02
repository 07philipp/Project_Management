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

$clientQuery = "SELECT * FROM client WHERE client_id = '$clientId'";
$clientResult = $mysql->query($clientQuery);
$clientData = $clientResult->fetch(PDO::FETCH_ASSOC);

echo json_encode($clientData);
