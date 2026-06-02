<?php
session_start();

if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI'];
    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
}

if ($_SESSION["permission_level"] < 4) {
    echo "<script>window.history.back();</script>";
    exit;
}

define('INCLUDE_GUARD', true);

include('../mysql.php');

if (!isset($_GET["id"])) {
    echo "Kein Nutzer angegeben";
    exit;
}

$userId = $_GET["id"];

$stmt = $mysql->prepare("SELECT user_id, user_name FROM user WHERE user_id = :id");
$stmt->bindParam(":id", $userId);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Nutzer nicht gefunden";
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Passwort ändern</title>

<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/login.css">
<link rel="stylesheet" href="../css/notification.css">

<script src="../js/notification.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    var errorMessage = "<?php echo isset($_SESSION['error_message']) ? addslashes($_SESSION['error_message']) : ''; ?>";

    if (errorMessage) {
        showNotification(errorMessage);
        <?php unset($_SESSION['error_message']); ?>
    }

});
</script>

</head>

<body>

<div class="container">

<div id="form-container">

<h1><?php echo htmlspecialchars($user["user_name"]); ?></h1>

<form action="../change_pw_admin.php" method="post">

<input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">

<label>Neues Passwort:</label>
<input type="password" name="password_new" required>

<label>Neues Passwort bestätigen:</label>
<input type="password" name="confirm_password_new" required>

<button type="submit">Passwort ändern</button>

<button type="button" onclick="window.location.href='../users/'">
Zurück
</button>

</form>

</div>

</div>

<div id="notification" class="notification" onclick="hideNotification()">
<p id="notification-message"></p>
<div id="progress-bar" class="progress-bar"></div>
</div>

</body>
</html>