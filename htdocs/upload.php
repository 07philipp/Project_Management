<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] == 2) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        include("mysql.php");
        $projectNumber = $_POST['p_id'];
        $projectStmt = $mysql->prepare(
            'SELECT project_user_id FROM project WHERE project_id = :project_id'
        );
        $projectStmt->execute([':project_id' => $projectNumber]);
        if ($projectStmt) {
            $projectData = $projectStmt->fetch(PDO::FETCH_ASSOC);
            $projectUserId = $projectData['project_user_id'];
            if ($projectUserId != $_SESSION['user_id']) {
                echo "Keine berechtigung.";
                exit;
            }
        }
    }
} elseif ($_SESSION["user_id"] == 1) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
require_once "log.php";

if (!isset($mysql)) {
    include 'mysql.php';
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$project_id = isset($_POST['p_id']) ? $_POST['p_id'] : '';
$projectFolder = "projects/uploads/";
$uploadDir = $projectFolder . $project_id."/"; // Verzeichnis, in das die Dateien hochgeladen werden


// Überprüfen, ob das Upload-Verzeichnis existiert, wenn nicht, wird es erstellt
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Aktion: Datei hochladen
if ($action == 'create') {
    // Abrufen der zulässigen Dateiendungen
    $extensionsStmt = $mysql->prepare(
        'SELECT setting FROM settings WHERE setting_type = :setting_type'
    );
    $extensionsStmt->execute([':setting_type' => 'allowed_extensions']);
    $allowedExtensions = explode(',', $extensionsStmt->fetchColumn());
    if (isset($_FILES['fileToUpload'])) {
        $fileName = basename($_FILES['fileToUpload']['name']);
        $targetFile = $uploadDir . $fileName;

        // Dateierweiterung überprüfen (nur bestimmte Dateitypen erlauben)
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!in_array($fileType, $allowedExtensions)) {
            $_SESSION['error_message'] = "Diese Dateien ist nicht erlaubt.";
            header(header: "Location: projects/?id=$project_id");
            exit;
        }

        // Datei hochladen
        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $targetFile)) {
            $_SESSION['error_message'] = "Die Datei " . h($fileName) . " wurde erfolgreich hochgeladen.";
            logSQL($mysql, $_SESSION['username'], "project $project_id upload $fileName");
        } else {
            $_SESSION['error_message'] = "Fehler beim Hochladen der Datei.";
        }
    }
}

// Aktion: Datei herunterladen
elseif ($action == 'download') {
    $fileName = isset($_POST['file']) ? $_POST['file'] : '';

    if (!empty($fileName)) {
        $filePath = $uploadDir . $fileName;

        if (file_exists($filePath)) {
            // Header zum Herunterladen der Datei
            logSQL($mysql, $_SESSION['username'], "project $project_id download $fileName");
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            flush(); // Alle Puffer leeren
            readfile($filePath); // Dateiinhalt ausgeben
            exit;
        } else {
            $_SESSION['error_message'] = "Datei nicht gefunden.";
        }
    } else {
        $_SESSION['error_message'] = "Ungültiger Dateiname.";
    }
}

// Aktion: Datei löschen
elseif ($action == 'delete') {
    $fileName = isset($_POST['file']) ? $_POST['file'] : '';

    if (!empty($fileName)) {
        $filePath = $uploadDir . $fileName;

        if (file_exists($filePath)) {
            unlink($filePath); // Datei löschen
            $_SESSION['error_message'] = "Datei " . h($fileName) . " wurde erfolgreich gelöscht.";
            logSQL($mysql, $_SESSION['username'], "project $project_id delete $fileName");
        } else {
            $_SESSION['error_message'] = "Datei nicht gefunden.";
        }
    } else {
        $_SESSION['error_message'] = "Ungültiger Dateiname.";
    }
}
header(header: "Location: projects/?id=$project_id");
exit;