<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI']; 
    
    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

include('../mysql.php');

$userId = $_SESSION["user_id"];

$userQuery = "SELECT user_id, user_name, permission_level FROM user WHERE user_id = :id";
$stmt = $mysql->prepare($userQuery);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    echo 'Error: Unable to retrieve user data';
    exit;
}


?>
<!DOCTYPE html>
<html lang="de" dir="ltr">


<head>
    <title>Passwor ändern</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/notification.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var errorMessage = <?= pm_json_script(pm_take_flash_message()) ?>;
            if (errorMessage) {
                showNotification(errorMessage);
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <div id="form-container">
            <h1><?php echo h($userData['user_name']); ?></h1>
            <form action="../change_pw.php" method="post">
                <label for="password">Aktuelles Passwort:</label>
                <input type="password" name="password"><br>

                <label for="password_new">Neues Passwort:</label>
                <input type="password" name="password_new"><br>

                <label for="confirm_password_new">Neues Passwort bestätigen:</label>
                <input type="password" name="confirm_password_new"><br>

                <button type="submit" name="submit">Ändern</button>
                <button type="button" onclick="window.location.href='../';">Zurück zur Homepage</button>
            </form>
        </div>
    </div>
    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>

</body>

</html>