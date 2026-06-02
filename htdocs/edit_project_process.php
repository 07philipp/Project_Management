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
                    console.log('Keine berechtigung.');
                      </script>";
    exit;
}
include("mysql.php");
require_once "log.php";

$project_id = $_POST['project_id'];
$project_name = isset($_POST['project']) ? $_POST['project'] : null;
$project_adress = isset($_POST['adress']) ? $_POST['adress'] : null;
$project_des = isset($_POST['des']) ? $_POST['des'] : null;
$project_date = isset($_POST['date']) ? $_POST['date'] : null;

// Update the database
if ($project_name !== null) {
    $orderQuery = "UPDATE `project` SET project_name = :name WHERE project_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':name', $project_name);
    $orderStmt->bindParam(':id', $project_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit project $project_id project_name $project_name");
}

if ($project_adress !== null) {
    $orderQuery = "UPDATE `project` SET project_address = :adress WHERE project_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':adress', $project_adress);
    $orderStmt->bindParam(':id', $project_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit project $project_id project_adress $project_adress");
}

if ($project_des !== null) {
    $orderQuery = "UPDATE `project` SET project_description = :des WHERE project_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':des', $project_des);
    $orderStmt->bindParam(':id', $project_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit project $project_id project_des $project_des");
}

if ($project_date !== null) {
    $orderQuery = "UPDATE `project` SET project_due_date = :date WHERE project_id = :id";
    $orderStmt = $mysql->prepare($orderQuery);
    $orderStmt->bindParam(':date', $project_date);
    $orderStmt->bindParam(':id', $project_id);
    $orderStmt->execute();
    logSQL($mysql, $_SESSION['username'], "edit project $project_id project_date $project_date");
}
