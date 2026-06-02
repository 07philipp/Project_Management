<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 3) {
    echo "<script>
                        window.history.back();
                        alert('Keine berechtigung.');
                      </script>";
    exit;
}
include('mysql.php');

if (isset($_POST['user_id']) && isset($_POST['permission'])) {
    $userId = $_POST['user_id'];
    $newPermission = $_POST['permission'];


    // Update the user's permission level
    $updateQuery = "UPDATE user SET permission_level = :permission WHERE user_id = :id";
    $stmt = $mysql->prepare($updateQuery);
    $stmt->bindParam(':permission', $newPermission);
    $stmt->bindParam(':id', $userId);
    if ($stmt->execute()) {
        echo "Berechtigungen wurden erfolgreich übernommen.";
    } else {
        echo "Ein Fehler ist aufgetreten.";
    }
} else {
    echo 'Invalid request';
}