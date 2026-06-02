<?php
if (!defined('INCLUDE_GUARD')) {
    die('Direct access not allowed');
}
require_once __DIR__ . '/../config.php';
$user_name = $high_admin_name;
$sqldatabase = $database;

try {
    $mysql = new PDO("mysql:host=$host", $user, $password);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database, if it doesn't exist
    $createDbQuery = "CREATE DATABASE IF NOT EXISTS $database";
    $mysql->exec($createDbQuery);

    // Use the created or existing database
    $mysql->exec("USE $database");

    // Create the user table, if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `user` (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        permission_level TINYINT(1) NOT NULL DEFAULT 1,
	project_filter JSON
    )";
    $mysql->exec($createTableQuery);

    // Create the high admin user
    $Query = "SELECT COUNT(*) FROM `user` WHERE permission_level = :permission_level";
    $permission_level = 5;
    $stmt = $mysql->prepare($Query);
    $stmt->bindParam(":permission_level", $permission_level);
    $stmt->execute();
    if ($stmt->fetchColumn() < 1) {
        $hash = password_hash($high_admin_pw, PASSWORD_BCRYPT);
        $ID = time();
        $query = "INSERT INTO user (user_id, user_name, password, permission_level) VALUES (:id, :user_name, :hash, :permission_level)";
        $stmt = $mysql->prepare($query);
        $stmt->bindParam(":id", $ID);
        $stmt->bindParam(":user_name", $user_name);
        $stmt->bindParam(":hash", $hash);
        $stmt->bindParam(":permission_level", $permission_level);
        $stmt->execute();
    }

    // Erstellen der benötigten Tabellen
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `settings` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_type VARCHAR(255) NOT NULL,
        setting VARCHAR(255) NOT NULL
    )";
    $mysql->exec($createTableQuery);

    // Prüfen, ob der 'backup_schedule'-Eintrag bereits existiert
    $checkBackupScheduleQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'backup_schedule'";
    $backupScheduleExists = $mysql->query($checkBackupScheduleQuery)->fetchColumn();

    if ($backupScheduleExists == 0) {
        $insertBackupScheduleQuery = "INSERT INTO settings (setting_type, setting) 
                                  VALUES ('backup_schedule', 'daily')";
        $mysql->exec($insertBackupScheduleQuery);
    }

    // Prüfen, ob der 'last_backup'-Eintrag bereits existiert
    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'last_backup'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                              VALUES ('last_backup', NOW())";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'email_subject'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('email_subject', '');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'email_body'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('email_body', '');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'allowed_extensions'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('allowed_extensions', 'docx, pdf');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'project_id_temp'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('project_id_temp', 'Project !count - !time');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'project_id_count'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('project_id_count', '1');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'hourly_wage'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('hourly_wage', '100');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'global_todos'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('global_todos', '');";
        $mysql->exec($insertLastBackupQuery);
    }

    $checkLastBackupQuery = "SELECT COUNT(*) FROM settings WHERE setting_type = 'apply_new_projects'";
    $lastBackupExists = $mysql->query($checkLastBackupQuery)->fetchColumn();

    if ($lastBackupExists == 0) {
        $insertLastBackupQuery = "INSERT INTO settings (setting_type, setting) 
                                VALUES ('apply_new_projects', '1');";
        $mysql->exec($insertLastBackupQuery);
    }


    $createTableQuery = "CREATE TABLE IF NOT EXISTS `log` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        action VARCHAR(255) NOT NULL,
        time DATETIME NOT NULL
    )";
    $mysql->exec($createTableQuery);

    $createTableQuery = "CREATE TABLE IF NOT EXISTS `user_sessions` (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $mysql->exec($createTableQuery);

    // Create the client table, if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `client` (
        client_id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255) NOT NULL,
        client_address VARCHAR(255) NOT NULL,
        client_e_mail VARCHAR(255) NOT NULL,
        client_location VARCHAR(255) NOT NULL,
        client_company VARCHAR(255) NOT NULL,
        client_gender VARCHAR(255) NOT NULL,
        client_phone VARCHAR(255) NOT NULL,
        client_mobile VARCHAR(255) NOT NULL
    )";
    $mysql->exec($createTableQuery);

    // Create the project table, if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `project` (
        project_id VARCHAR(255) PRIMARY KEY,
        project_name VARCHAR(255) NOT NULL,
        project_client_id VARCHAR(255) NOT NULL,
        project_address VARCHAR(255) NOT NULL,
        project_description VARCHAR(255) NOT NULL,
        project_user_id VARCHAR(255) NOT NULL,
        project_due_date VARCHAR(255) NOT NULL,
        project_created_date VARCHAR(255) NOT NULL,
        project_status enum('in_progress','completed','invoice_sent','paid','archived') NOT NULL DEFAULT 'in_progress',
        invoice_sent_date date DEFAULT NULL,
        invoice_paid_date date DEFAULT NULL,
        completed_date date DEFAULT NULL,
        archived_date date DEFAULT NULL
    )";
    $mysql->exec($createTableQuery);

    // Create the order table, if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `order` (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_project_id VARCHAR(255) NOT NULL,
        order_order VARCHAR(255) NOT NULL,
        order_amount VARCHAR(255) NOT NULL,
        order_hourly_wage VARCHAR(255) NOT NULL,
        order_checked VARCHAR(255) NOT NULL
    )";
    $mysql->exec($createTableQuery);

    // Create the order table, if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `time` (
        time_id VARCHAR(255) PRIMARY KEY,
        start_time DATETIME,
        end_time DATETIME,
        project_id  VARCHAR(255) NOT NULL,
        order_id  VARCHAR(255) NOT NULL,
        user_id  VARCHAR(255) NOT NULL,
        duration FLOAT GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, start_time, end_time)) STORED
    )";
    $mysql->exec($createTableQuery);

    // Create the order table, if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `checklist` (
    checklist_id BIGINT NOT NULL,
    project_id VARCHAR(255) NOT NULL,
    checklist_name VARCHAR(255) NOT NULL,
    is_done BOOLEAN NOT NULL DEFAULT 0,
    is_global BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (checklist_id, project_id)
    )";
    $mysql->exec($createTableQuery);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
