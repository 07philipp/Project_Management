<?php
session_start();
if (!isset($_SESSION["user_id"])) {
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

require 'mysql.php';
require_once "log.php";

// Determine action (create, download, restore, delete)
$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
$backupDir = '../backups/';

// Create a folder name for the backup
$backupFolderName = $database . '_backup_' . date('Y-m-d_H-i-s');
$backupFolderPath = $backupDir . $backupFolderName; // This will hold the SQL and folders

if ($action == 'create') {
    // Create a new backup folder
    createBackup($mysql, $backupDir, $database, $backupFolderPath, $backupFolderName);
    logSQL($mysql, $_SESSION['username'], "createt backup $backupFolderName");
} elseif ($action == 'download') {
    // Download backup
    $filename = isset($_GET['file']) ? $_GET['file'] : '';
    if (!empty($filename)) {
        downloadBackup($filename, $backupDir, $mysql);
    } else {
        $_SESSION['error_message'] = "Ungültiger file";
        header("Location: backup/");
        exit;
    }
} elseif ($action == 'restore') {
    // Restore backup
    $backupFile = isset($_POST['backup_file']) ? $_POST['backup_file'] : '';
    restoreBackup($mysql, $backupFile, $backupDir, $database);
    logSQL($mysql, $_SESSION['username'], "restore backup backupFile");
} elseif ($action == 'delete') {
    // Delete backup
    $backupFile = isset($_POST['backup_file']) ? $_POST['backup_file'] : '';
    deleteBackup($backupFile, $backupDir);
    logSQL($mysql, $_SESSION['username'], "delete backup backupFile");
}

// Function to create a backup (SQL + directories into a single folder, then zipped)
function createBackup($mysql, $backupDir, $database, $backupFolderPath, $backupFolderName)
{
    try {
        // Step 1: Create the backup folder if it doesn't exist
        if (!file_exists($backupFolderPath)) {
            mkdir($backupFolderPath, 0777, true);
        }

        // Step 2: Create the SQL backup inside the backup folder
        $sqlBackupFile = $backupFolderPath . "/" . $database . "_backup_" . date("Y-m-d_H-i-s") . ".sql";
        $tables = $mysql->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        $backupContent = '';
        foreach ($tables as $table) {
            $createTableStmt = $mysql->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC)['Create Table'];
            $backupContent .= "\n\n$createTableStmt;\n\n";

            $rows = $mysql->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $values = array_map([$mysql, 'quote'], array_values($row));
                $backupContent .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
        }

        // Save the SQL content to the backup folder
        file_put_contents($sqlBackupFile, $backupContent);

        // Step 3: Copy the directories to the backup folder
        recursiveCopy("projects/uploads", $backupFolderPath . '/uploads');


        // Step 4: Create a ZIP file from the backup folder directly in the backups directory
        $zipFile = $backupDir . basename($backupFolderPath) . ".zip";
        zipFolder($backupFolderPath, $zipFile, $backupFolderName);

        // Step 5: Cleanup - Remove the temporary backup folder after zipping
        recursiveDelete($backupFolderPath);

        $_SESSION['error_message'] = "Backup wurde erstellt.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Fatal error:" . $e->getMessage();
    }
}

// Function to recursively copy directories
function recursiveCopy($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst);

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Function to create a ZIP file from a folder
function zipFolder($folderPath, $zipFilePath, $backupFolderName)
{
    $zip = new ZipArchive();

    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        $_SESSION['error_message'] = "Feher bei erstelllen der zip: $zipFilePath";
        header("Location: backup/");
        exit;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath), RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get the relative path and add it to the zip
            $filePath = $file->getRealPath();
            $relativePath = substr($file, strlen($folderPath));

            $zip->addFile($filePath, $backupFolderName . $relativePath);
        }
    }

    $zip->close();
}

// Function to recursively delete a directory
function recursiveDelete($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            recursiveDelete($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// Function to download a backup ZIP file
function downloadBackup($filename, $backupDir, $mysql)
{
    $file = $backupDir . $filename;

    if (file_exists($file)) {
        logSQL($mysql, $_SESSION['username'], "download backup $filename");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        $_SESSION['error_message'] = "Backup wurde nicht gefunden.";
        header("Location: backup/");
        exit;
    }
}

// Function to restore a backup (only the SQL database here)
function restoreBackup($mysql, $backupFile, $backupDir, $database)
{
    try {
        // Step 1: Unzip the backup file
        $zipFilePath = $backupDir . $backupFile;
        $extractPath = $backupDir . 'temp_restore/';
        $extractedPath = $backupDir . 'temp_restore/' . substr($backupFile, 0, -4) . '/';

        // Create the extraction folder
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0777, true);
        }

        // Extract the ZIP file
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            $_SESSION['error_message'] = "zip kann nicht geöfnet werden.";
            header("Location: backup/");
            exit;
        }

        // Step 2: Restore the SQL database
        // Find the SQL file in the extracted folder
        $sqlFile = glob($extractedPath . '*.sql')[0]; // Assuming there's only one SQL file
        if (!$sqlFile) {
            $_SESSION['error_message'] = "SQL backup nicht gefunden.";
            header("Location: backup/");
            exit;
        }

        // Read the SQL content
        $sqlContent = file_get_contents($sqlFile);

        // Drop the existing database and recreate it
        $mysql->exec("DROP DATABASE IF EXISTS `$database`;");
        $mysql->exec("CREATE DATABASE IF NOT EXISTS `$database`;");
        $mysql->exec("USE `$database`;");
        $mysql->exec($sqlContent);

        // Step 3: Restore the directories
        $srcDir = $extractedPath . "/uploads";
        deleteAllDirectories("projects/uploads/");
        if (file_exists($srcDir)) {
            recursiveCopy($srcDir, "projects/uploads/");
        }

        // Step 4: Cleanup - remove the temporary extracted files
        recursiveDelete($extractedPath);

        $_SESSION['error_message'] = "Backup wurde geladen.";
        return true;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Backup kann nicht geladen werden:" . $e->getMessage();
        return false;
    }
}


// Function to delete a backup
function deleteBackup($filename, $backupDir)
{
    $file = $backupDir . $filename;

    if (file_exists($file)) {
        unlink($file);
        return true;
    } else {
        $_SESSION['error_message'] = "Backup konnte nicht gefunden werden.";
        return false;
    }
}

function deleteAllDirectories($dir)
{
    // Ensure the directory exists
    if (!is_dir($dir)) {
        $_SESSION['error_message'] = "Fatal error: Directory does not exist: $dir";
        return false;
    }

    // Open the directory
    $items = scandir($dir);

    foreach ($items as $item) {
        // Skip '.' and '..'
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $dir . '/' . $item;

        // Check if the item is a directory
        if (is_dir($path)) {
            // Recursively delete the directory
            deleteDirectory($path);
        }
    }

    return true;
}

function deleteDirectory($dir)
{
    // Ensure the directory exists
    if (!is_dir($dir)) {
        return false;
    }

    // Open the directory
    $items = scandir($dir);

    foreach ($items as $item) {
        // Skip '.' and '..'
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $dir . '/' . $item;

        if (is_dir($path)) {
            // Recursively delete subdirectories
            deleteDirectory($path);
        } else {
            // Delete files
            unlink($path);
        }
    }

    // Remove the now-empty directory
    rmdir($dir);

    return true;
}

header("Location: backup/");
exit;
