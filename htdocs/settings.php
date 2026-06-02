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
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
include 'mysql.php';
require_once "log.php";

require_once '../vendor/autoload.php'; // PhpWord laden

$uploadDir = 'docx/'; // Verzeichnis für die Uploads

// Action aus POST-Daten übernehmen
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'upload') {
    // Datei-Upload
    uploadFile($uploadDir, $mysql);
} elseif ($action == 'download') {
    // Datei herunterladen
    $filename = isset($_POST['file']) ? $_POST['file'] : '';
    if (!empty($filename)) {
        downloadFile($filename, $uploadDir);
        logSQL($mysql, $_SESSION['username'], "settings download $filename");
    } else {
        $_SESSION['error_message'] = "Ungültige Datei.";
        header("Location: settings/");
        exit;
    }
} elseif ($action == 'save_all_settings') {
    // Backup Einstellungen speichern
    $backup_schedule = isset($_POST['backup_schedule']) ? $_POST['backup_schedule'] : 'daily';
    $stmt = $mysql->prepare("UPDATE settings SET setting = :backup_schedule WHERE setting_type = 'backup_schedule'");
    $stmt->execute([':backup_schedule' => $backup_schedule]);
    logSQL($mysql, $_SESSION['username'], "settings backup $backup_schedule");

    // E-Mail Einstellungen speichern
    $newSubject = isset($_POST['email_subject']) ? $_POST['email_subject'] : '';
    $newBody = isset($_POST['email_body']) ? $_POST['email_body'] : '';

    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'email_subject'");
    $stmt->execute([':setting' => $newSubject]);

    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'email_body'");
    $stmt->execute([':setting' => $newBody]);
    logSQL($mysql, $_SESSION['username'], "settings email sub $newSubject bod $newBody");

    // Erlaubte Dateiendungen speichern
    $newExtensions = isset($_POST['allowed_extensions']) ? $_POST['allowed_extensions'] : '';
    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'allowed_extensions'");
    $stmt->execute([':setting' => $newExtensions]);
    logSQL($mysql, $_SESSION['username'], "settings update extensions $newExtensions");

    // Projekt-ID Vorlage speichern
    $projectIdTemp = isset($_POST['project_id_temp']) ? $_POST['project_id_temp'] : '';
    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'project_id_temp'");
    $stmt->execute([':setting' => $projectIdTemp]);
    logSQL($mysql, $_SESSION['username'], "settings update project_id_template $projectIdTemp");

    // Projekt-ID Zähler speichern
    $projectIdCount = isset($_POST['project_id_count']) ? intval($_POST['project_id_count']) : 0;
    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'project_id_count'");
    $stmt->execute([':setting' => $projectIdCount]);
    logSQL($mysql, $_SESSION['username'], "settings update project_id_count $projectIdCount");

    // Stundenlohn speichern
    $hourlyWage = isset($_POST['hourly_wage']) ? intval($_POST['hourly_wage']) : 0;
    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'hourly_wage'");
    $stmt->execute([':setting' => $hourlyWage]);
    logSQL($mysql, $_SESSION['username'], "settings update hourly_wage $hourlyWage");

    // Globale Projekt-Todos speichern
    $globalTodosRaw = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'global_todos'")->fetchColumn();
    $globalTodos = json_decode($globalTodosRaw, true) ?? [];

    if (!empty($_POST['new_global_todo'])) {
        $newTodo = trim($_POST['new_global_todo']);
        if ($newTodo !== "") {
            $globalTodos[] = $newTodo;
            logSQL($mysql, $_SESSION['username'], "settings added global todo: $newTodo");
        }
    }

    if (isset($_POST['delete_global_todo'])) {
        $deleteIndex = intval($_POST['delete_global_todo']);
        if (isset($globalTodos[$deleteIndex])) {
            $removedTodo = $globalTodos[$deleteIndex];
            unset($globalTodos[$deleteIndex]);
            $globalTodos = array_values($globalTodos); // Reindizieren
            logSQL($mysql, $_SESSION['username'], "settings removed global todo: $removedTodo");
        }
    }

    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'global_todos'");
    $stmt->execute([':setting' => json_encode($globalTodos)]);

    // Einstellung: neue Todos auch bei neuen Projekten anwenden
    $applyNewProjects = isset($_POST['apply_new_projects']) ? '1' : '0';
    $stmt = $mysql->prepare("UPDATE settings SET setting = :setting WHERE setting_type = 'apply_new_projects'");
    $stmt->execute([':setting' => $applyNewProjects]);

    // Feedback-Nachricht an den Benutzer
    $_SESSION['error_message'] = "Einstellungen wurden erfolgreich gespeichert.";

    // Redirect zurück zur Einstellungsseite
    header("Location: settings/");
    exit;
}



// Funktionen für Dateiverwaltung
function uploadFile($uploadDir, $mysql)
{
    // Überprüfen, ob eine Datei hochgeladen wurde
    if (isset($_FILES['file'])) {
        $fileType = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if ($fileType != 'docx') {
            $_SESSION['error_message'] = "Es dürfen nur .docx Dateien hochgeladen werden.";
            header("Location: settings/");
            exit;
        }

        // Verzeichnis erstellen, falls es noch nicht existiert
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Template-Typ ermitteln
        $templateType = isset($_POST['template_type']) ? $_POST['template_type'] : '';
        $filename = '';

        switch ($templateType) {
            case 'invoice':
                $filename = 'invoice.docx';
                break;
            case 'invoice_discount':
                $filename = 'invoice_discount.docx';
                break;
            // Weitere Template-Typen hinzufügen
            default:
                $_SESSION['error_message'] = "Ungültiger Template-Typ.";
                header("Location: settings/");
                exit;
        }

        // Datei verschieben
        $uploadFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
            $_SESSION['error_message'] = "Die Datei wurde erfolgreich hochgeladen.";
            logSQL($mysql, $_SESSION['username'], "settings bill up $uploadFile");
        } else {
            $_SESSION['error_message'] = "Es gab ein Problem beim Hochladen der Datei.";
        }
    } else {
        $_SESSION['error_message'] = "Keine Datei hochgeladen.";
    }
}

function downloadFile($filename, $uploadDir)
{
    $file = $uploadDir . basename($filename);
    if (file_exists($file)) {
        // Dateiheader setzen
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        $_SESSION['error_message'] = "Die Datei existiert nicht.";
        exit;
    }
}

function eMail($newSubject, $newBody, $mysql)
{
    // Updates durchführen
    $updateSubjectQuery = "UPDATE settings SET setting = :setting WHERE setting_type = 'email_subject'";
    $stmt = $mysql->prepare($updateSubjectQuery);
    $stmt->bindParam(':setting', $newSubject);
    $stmt->execute();

    $updateBodyQuery = "UPDATE settings SET setting = :setting WHERE setting_type = 'email_body'";
    $stmt = $mysql->prepare($updateBodyQuery);
    $stmt->bindParam(':setting', $newBody);
    $stmt->execute();

    $_SESSION['error_message'] = "Einstellungen wurden aktualisiert.";
}

function allowedUploadFile($uploadDir, $mysql)
{
    // Abrufen der zulässigen Dateiendungen
    $extensionsStmt = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'allowed_extensions'");
    $allowedExtensions = explode(',', $extensionsStmt->fetchColumn());

    // Überprüfen, ob eine Datei hochgeladen wurde
    if (isset($_FILES['file'])) {
        $fileType = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

        // Überprüfen, ob die Dateiendung erlaubt ist
        if (!in_array($fileType, $allowedExtensions)) {
            $_SESSION['error_message'] = "Es dürfen nur folgende Dateitypen hochgeladen werden: " . implode(', ', $allowedExtensions);
            header("Location: settings/");
            exit;
        }

        // Verzeichnis erstellen, falls es noch nicht existiert
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Template-Typ ermitteln
        $templateType = isset($_POST['template_type']) ? $_POST['template_type'] : '';
        $filename = '';

        switch ($templateType) {
            case 'invoice':
                $filename = 'invoice.' . $fileType;
                break;
            case 'invoice_discount':
                $filename = 'invoice_discount.' . $fileType;
                break;
            default:
                $_SESSION['error_message'] = "Ungültiger Template-Typ.";
                header("Location: settings/");
                exit;
        }

        // Datei verschieben
        $uploadFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
            $_SESSION['error_message'] = "Die Datei wurde erfolgreich hochgeladen.";
            logSQL($mysql, $_SESSION['username'], "settings bill up $uploadFile");
        } else {
            $_SESSION['error_message'] = "Es gab ein Problem beim Hochladen der Datei.";
        }
    } else {
        $_SESSION['error_message'] = "Keine Datei hochgeladen.";
    }
}


header("Location: settings/");
exit;