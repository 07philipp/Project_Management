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
                        console.log('Keine Berechtigung.');
                      </script>";
    exit;
}
include '../mysql.php';

// Fetch the current backup schedule from the database
$currentScheduleStmt = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'backup_schedule'");
$currentSchedule = $currentScheduleStmt->fetchColumn();

// Abfrage zum Abrufen des letzten Backups aus der Datenbank
$lastBackupQuery = "SELECT setting FROM settings WHERE setting_type = 'last_backup'";
$lastBackupResult = $mysql->query($lastBackupQuery)->fetchColumn();

// E-Mail Einstellungen abrufen
$emailSubjectQuery = "SELECT setting FROM settings WHERE setting_type = 'email_subject'";
$emailSubject = $mysql->query($emailSubjectQuery)->fetchColumn();

$emailBodyQuery = "SELECT setting FROM settings WHERE setting_type = 'email_body'";
$emailBody = $mysql->query($emailBodyQuery)->fetchColumn();

$allowedExtensionsQuery = "SELECT setting FROM settings WHERE setting_type = 'allowed_extensions'";
$allowedExtensions = $mysql->query($allowedExtensionsQuery)->fetchColumn();

$pID_settingQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_temp'";
$projectIdTemp = $mysql->query($pID_settingQ)->fetchColumn();

$countQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_count'";
$projectIdCount = $mysql->query($countQ)->fetchColumn();


$countQ = "SELECT setting FROM settings WHERE setting_type = 'hourly_wage'";
$hourlyWage = $mysql->query($countQ)->fetchColumn();

// Prüfen, was tatsächlich zurückgegeben wird
$projectIdCount = intval($projectIdCount);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/notification.js"></script>
    <title>Einstellungen</title>
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
    <h1>Einstellungen</h1>
    <a class="space back-to-home-link" href="../">Zurück zur Hauptseite</a>
    <a class="space back-to-home-link" href="../admin/">Admin Panel</a>

    <h2>Rechnungs Vorlagen</h2>

    <div class="settings-container">
    <h2>Globale Projekt-Todos</h2>
        <div class="settings-container">

        <form action="../settings.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="template_type" value="invoice">
            <label for="invoice_file">Vorlage</label>
            <input type="file" name="file" id="invoice_file" accept=".docx" required>
            <button type="submit">Hochladen</button>
        </form>

        <?php
        $path = '../docx/';
        if (file_exists($path . 'vorlage.docx')) {
            echo '<form action="../settings.php" method="post">';
            echo '<input type="hidden" name="action" value="download">';
            echo '<input type="hidden" name="file" value="vorlage.docx">';
            echo '<button type="submit">Herunterladen</button>';
            echo '</form><br>';
        }
        ?>
    </div>

    <div class="settings-container">
        <form action="../settings.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="template_type" value="invoice_discount">
            <label for="invoice_discount_file">Vorlage Rabattlabel</label>
            <input type="file" name="file" id="invoice_discount_file" accept=".docx" required>
            <button type="submit">Hochladen</button>
        </form>
        <?php
        if (file_exists($path . 'vorlage_rabat.docx')) {
            echo '<form action="../settings.php" method="post">';
            echo '<input type="hidden" name="action" value="download">';
            echo '<input type="hidden" name="file" value="vorlage_rabat.docx">';
            echo '<button type="submit">Herunterladen</button>';
            echo '</form><br>';
        }
        ?>
    </div>
    </div>
    <br>
    
    <h2>Todo</h2>
    <div class="settings-container">
    <form action="../edit_checklist.php" method="post">
    <input type="hidden" name="action" value="edit_settings">
    <label for="add_global_todo">Neues Globales Todo:</label>
            <input type="text" id="new_global_todo" name="new_global_todo" class="input" placeholder="Neues Todo...">
            <button type="submit" name="add_global_todo">Hinzufügen</button>
            <br><br>
            <label>Vorhandene Todos:</label>
            <ul>
                <?php
                $globalTodosRaw = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'global_todos'")->fetchColumn();
                $globalTodos = json_decode($globalTodosRaw, true) ?? [];

                foreach ($globalTodos as $todo) {
                    $todoId = htmlspecialchars($todo['id']);
                    $todoName = htmlspecialchars($todo['name']);
                    echo "<li>$todoName 
                        <button type='submit' name='delete_global_todo' value='$todoId' style='background-color: transparent; color: red; padding-left: 0px;' >X</button>
                    </li>";
                }                
                ?>
            </ul>
            <br>
            <label for="apply_global_todos">Alle Projecte aktualisieren</label>
            <input type="checkbox" id="apply_global_todos" name="apply_global_todos" checked>
            <br>
            <br>
            </form>
            <form action="../settings.php" method="post">
            <label for="apply_new_projects">Beim neuen Prjecten anwenden</label>
            <input type="checkbox" id="apply_new_projects" name="apply_new_projects" value="1"
                <?php
                $applyTodos = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'apply_new_projects'")->fetchColumn();
                echo ($applyTodos == '1') ? 'checked' : '';
                ?>>
        </div>
        <br>

        <input type="hidden" name="action" value="save_all_settings">

        <!-- Backup Section -->
        <h2>Backup</h2>
        <div class="settings-container">
            <label for="backup_schedule">Backup Intervall:</label>
            <select name="backup_schedule" id="backup_schedule">
                <option value="daily" <?php echo ($currentSchedule == 'daily') ? 'selected' : ''; ?>>Täglich</option>
                <option value="weekly" <?php echo ($currentSchedule == 'weekly') ? 'selected' : ''; ?>>Wöchentlich
                </option>
                <option value="monthly" <?php echo ($currentSchedule == 'monthly') ? 'selected' : ''; ?>>Monatlich
                </option>
            </select>
        </div>
        <br>


        <!-- E-Mail Section -->
        <h2>E-Mail</h2>
        <div class="settings-container">
            <label for="email_subject">Betreff der E-Mail:</label>
            <input type="text" id="email_subject" name="email_subject"
                value="<?php echo htmlspecialchars($emailSubject); ?>" class="input" >
            <br>
            <label for="email_body">Inhalt der E-Mail:</label>
            <textarea id="email_body" name="email_body" rows="4" class="input"
                ><?php echo htmlspecialchars($emailBody); ?></textarea>
        </div>
        <br>


        <!-- Allowed File Extensions Section -->
        <h2>Zulässige Dateiendungen</h2>
        <div class="settings-container">
            <label for="allowed_extensions">Erlaubte Dateiendungen (kommagetrennt):</label>
            <input type="text" id="allowed_extensions" name="allowed_extensions"
                value="<?php echo htmlspecialchars($allowedExtensions); ?>" class="input" required>
        </div>
        <br>

        <!-- Project ID Counter Section -->
        <h2>Projekt-ID Vorlage</h2>
        <div class="settings-container">
            <label for="project_id_temp">Projekt-ID Vorlage (Hinweis: !count wird durch die Zählernummer ersetzt, !time
                durch das Erstellungsdatum):</label>
            <input type="text" id="project_id_temp" name="project_id_temp"
                value="<?php echo htmlspecialchars($projectIdTemp); ?>" class="input" required>
            <label for="project_id_count">Projekt-ID Zähler (Aktuelle Zählernummer):</label>
            <input type="number" id="project_id_count" name="project_id_count"
                value="<?php echo htmlspecialchars($projectIdCount); ?>" class="input" required>
        </div>
        <br>
        <h2>Stundenlohn</h2>
        <div class="settings-container">
            <label for="hourly_wage">Standert Stundenlohn</label>
            <input type="number" name="hourly_wage" id="hourly_wage" value="<?php echo htmlspecialchars($hourlyWage); ?>" class="input" required>
        </div>
        <br>


        <!-- Save Button -->
        <button type="submit">Speichern</button>
    </form>


    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>
</body>

</html>