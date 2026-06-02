<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI']; 
    
    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 3) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/notification.js"></script>
    <script src="../js/password.js"></script>
    <title>Account erstellen</title>
</head>

<body>
    <div class="container">
        <?php
        if (isset($_POST["submit"])) {
            require("../mysql.php");
            $stmt = $mysql->prepare("SELECT * FROM user WHERE user_name = :user"); // Username überprüfen
            $stmt->bindParam(":user", $_POST["username"]);
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count == 0) {
                // Username ist frei
                if ($_POST["pw"] == $_POST["pw2"]) {
                    // User anlegen
                    $pw = 1;
                    $ID = time();
                    $stmt = $mysql->prepare("INSERT INTO user (user_id, user_name, password, permission_level) VALUES (:id, :user, :pw, :pl)");
                    $stmt->bindParam(":user", $_POST["username"]);
                    $hash = password_hash($_POST["pw"], PASSWORD_BCRYPT);
                    $stmt->bindParam(":id", $ID);
                    $stmt->bindParam(":pw", $hash);
                    $stmt->bindParam(":pl", $pw);
                    $stmt->execute();
                
                    header("Location: ../");

                    $error = "Dein Account wurde angelegt";
                } else {
                    $error = "Die Passwörter stimmen nicht überein";
                }
            } else {
                $error = "Der Username ist bereits vergeben";
            }
            echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . $error . "');
        });</script>";
        }
        ?>
        <div id="form-container">
            <h1>Account erstellen</h1>
            <form method="post">
                <input type="text" name="username" placeholder="Username" required><br>
                <div style="position: relative;">
                    <input type="password" id="pw" name="pw" placeholder="Passwort" required onkeyup="validatePasswords()">
                    <span id="togglePw1" onclick="togglePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">👁</span>
                </div>
                <br>
                <div style="position: relative;">
                    <input type="password" id="pw2" name="pw2" placeholder="Passwort wiederholen" required onkeyup="validatePasswords()">
                </div>
                <p id="password-message" style="font-size: 14px;"></p>
                <button type="submit" name="submit">Erstellen</button>
                <button onclick="window.location.href='../';">Zurück zur Homepage</button>
            </form>
            <br>
            <div id="notification" class="notification" onclick="hideNotification()">
                <p id="notification-message"></p>
                <div id="progress-bar" class="progress-bar"></div>
            </div>
        </div>
    </div>
    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>
</body>

</html>