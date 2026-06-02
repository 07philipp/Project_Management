<?php

session_start();

if (!isset($_SESSION["username"])) {
    exit;
}

if ($_SESSION["permission_level"] < 4) {
    exit;
}
define('INCLUDE_GUARD', true);

include('mysql.php');

$userId = $_POST["user_id"] ?? null;
$password = $_POST["password_new"] ?? "";
$password2 = $_POST["confirm_password_new"] ?? "";

if (!$userId) {
    $_SESSION["error_message"] = "Kein Nutzer angegeben";
    header("Location: users/");
    exit;
}

if ($password !== $password2) {

    $_SESSION["error_message"] = "Die Passwörter stimmen nicht überein";
    header("Location: change_password_admin/?id=".$userId);
    exit;

}

if (strlen($password) < 4) {

    $_SESSION["error_message"] = "Passwort zu kurz";
    header("Location: change_password_admin/?id=".$userId);
    exit;

}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $mysql->prepare("UPDATE user SET password = :pw WHERE user_id = :id");
$stmt->bindParam(":pw", $hash);
$stmt->bindParam(":id", $userId);
$stmt->execute();

$_SESSION["error_message"] = "Passwort erfolgreich geändert";

header("Location: users/");
exit;