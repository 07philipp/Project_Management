<?php
define('INCLUDE_GUARD', true);
include 'htdocs/mysql.php';

// Fetch the backup schedule from the database
$backupScheduleStmt = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'backup_schedule'");
$backupSchedule = $backupScheduleStmt->fetchColumn();

// Get the last backup date
$lastBackupStmt = $mysql->query("SELECT setting FROM settings WHERE setting_type = 'last_backup'");
$lastBackup = $lastBackupStmt->fetchColumn();

// Set up current date and backup intervals
$now = new DateTime();
$lastBackupDate = new DateTime($lastBackup);
$interval = null;

switch ($backupSchedule) {
    case 'daily':
        $interval = new DateInterval('P1D'); // 1 day
        break;
    case 'weekly':
        $interval = new DateInterval('P7D'); // 1 week
        break;
    case 'monthly':
        $interval = new DateInterval('P1M'); // 1 month
        break;
}

// Check if it's time to make a new backup
if ($interval && $now >= $lastBackupDate->add($interval)) {
    // Create backup
    $backupDir = 'backups/';
    $dirsToBackup = ['users', 'projects', 'clients'];
    $backupFolderName = $database . '_backup_' . date('Y-m-d_H-i-s');
    $backupFolderPath = $backupDir . $backupFolderName;

    try {
        // Call createBackup() function from backup.php
        include 'htdocs/backup.php';
        createBackup($mysql, $backupDir, $database, $backupFolderPath, $dirsToBackup, $backupFolderName);

        // Update the last backup date
        $stmt = $mysql->prepare("UPDATE settings SET setting = :now WHERE setting_type = 'last_backup'");
        $stmt->execute([':now' => $now->format('Y-m-d H:i:s')]);

        echo "Backup created successfully!";
    } catch (Exception $e) {
        echo "Error creating backup: " . $e->getMessage();
    }
} else {
    echo "Backup not due yet.";
}
