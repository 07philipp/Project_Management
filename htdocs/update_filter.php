<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
}else{
    define('INCLUDE_GUARD', true);
}
include('mysql.php');

$statuses = $_POST['status'] ?? [];

$json = json_encode($statuses);

$stmt = $mysql->prepare("UPDATE user SET project_filter = ? WHERE user_id = ?");
$stmt->execute([$json, $_SESSION['user_id']]);

header("Location: ../");
exit;
