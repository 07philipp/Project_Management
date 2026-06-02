<?php
session_start();
if (!isset($_SESSION["user_id"])) {
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

include '../mysql.php';

// Define the backup directory path
$backupDir = '../../backups/';

// Find all ZIP files in the backup directory
$backups = glob($backupDir . "*.zip");

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/notification.js"></script>
    <title>Backup-Management</title>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fetch error message from PHP session
            var errorMessage = "<?php echo isset($_SESSION['error_message']) ? addslashes($_SESSION['error_message']) : ''; ?>";
            if (errorMessage) {
                showNotification(errorMessage);
                // Clear the session error message
                <?php unset($_SESSION['error_message']); ?>
            }
        });
    </script>
</head>

<body>
    <h1>Backup-Management</h1>
    <a class="space back-to-home-link" href="../">Zurück zur Hauptseite</a>
    <a class="space back-to-home-link" href="../admin/">Admin Panel</a>
    <form method="POST" action="../backup.php">
        <input type="hidden" name="action" value="create">
        <button type="submit" name="create_backup">Backup jetzt erstellen</button>
    </form>

    <h2>Verfügbare Backups</h2>
    <ul>
        <?php
        if (!empty($backups)) {
            foreach ($backups as $backup): ?>
                <li>
                    <?php echo basename($backup); ?>
                    <form method="POST" style="display:inline;" action="../backup.php">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="backup_file" value="<?php echo basename($backup); ?>">
                        <button type="submit" name="restore_backup"
                        onclick="return confirm('Möchten Sie dieses Backup wirklich wiederherstellen? Daten können verloren gehen.')">Backup wiederherstellen</button>
                    </form>
                    <form method="POST" style="display:inline;" action="../backup.php">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="backup_file" value="<?php echo basename($backup); ?>">
                        <button type="submit" name="delete_backup"
                            onclick="return confirm('Möchten Sie dieses Backup wirklich löschen?')">Löschen</button>
                    </form>
                    <?php echo "<a style='display: inline-block;' class='link' href='../backup.php?action=download&file=" . basename($backup) . "'>Download</a>"; ?>
                </li>
                <br>
            <?php endforeach;
        } else {
            echo "Keine backups gefunden.";
        } ?>
    </ul>

    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>
</body>

</html>