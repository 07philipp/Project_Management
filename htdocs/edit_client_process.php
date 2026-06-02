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
include("mysql.php");

$client_id = $_POST['client_id'];
$name = isset($_POST['name']) ? $_POST['name'] : null;
$adress = isset($_POST['adress']) ? $_POST['adress'] : null;
$location = isset($_POST['location']) ? $_POST['location'] : null;
$company = isset($_POST['company']) ? $_POST['company'] : null;
$gender = isset($_POST['gender']) ? $_POST['gender'] : null;

require_once "log.php";

// Update the database
if ($name !== null) {
    $orderQuery = "UPDATE `client` SET client_name = :nam WHERE client_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':nam', $name);
    $orderStmt->bindParam(':id', $client_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit client $client_id name $name");
}

if ($adress !== null) {
    $orderQuery = "UPDATE `client` SET client_address = :add WHERE client_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':add', $adress);
    $orderStmt->bindParam(':id', $client_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit client $client_id adress $adress");
}

if ($location !== null) {
    $orderQuery = "UPDATE `client` SET client_location = :loc WHERE client_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':loc', $location);
    $orderStmt->bindParam(':id', $client_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit client $client_id location $location");
}

if ($company !== null) {
    $orderQuery = "UPDATE `client` SET client_company = :comp WHERE client_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':comp', $company);
    $orderStmt->bindParam(':id', $client_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit client $client_id company $company");
}

if ($gender !== null) {
    $orderQuery = "UPDATE `client` SET client_gender = :gen WHERE client_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':gen', $gender);
    $orderStmt->bindParam(':id', $client_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit client $client_id gender $gender");
}
