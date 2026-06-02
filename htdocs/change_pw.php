<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

include('mysql.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
    $password = $_POST['password'];
    $newPassword = $_POST['password_new'];
    $confirmPassword = $_POST['confirm_password_new'];

    $userId = $_SESSION['user_id'];

    // 1. Check if the current password is correct
    $stmt = $mysql->prepare("SELECT password FROM user WHERE user_id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // 2. Check if the new password matches the confirmation password
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error_message'] = "Password stimmt nicht überein.";
            echo "<script>window.history.back();</script>";
            exit;
        }

        // 3. Change the password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE user SET password = :password WHERE user_id = :id";
        $stmt = $mysql->prepare($updateQuery);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        header("Location: / ");
        $_SESSION['error_message'] = "Password wurde geändert.";
        require_once "log.php";
        logSQL($mysql, $_SESSION['username'], "change password");
        exit;
    } else {
        $_SESSION['error_message'] = "Password ist falsch.";
        echo "<script>window.history.back();</script>";
        exit;
    }
}
