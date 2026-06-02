<?php
if (!defined('INCLUDE_GUARD')) {
    die('Direct access not allowed');
}

/**
 * Escape output for HTML/text attributes (XSS).
 */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * JSON-safe value for embedding in JavaScript (script tags / inline handlers).
 */
function pm_json_script($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}

/**
 * Read and clear the session flash message (string or array from validation).
 */
function pm_take_flash_message(): string
{
    $flash = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']);

    if (is_array($flash)) {
        return implode("\n", $flash);
    }

    return (string) $flash;
}

/**
 * Read a single settings row value.
 */
function pm_fetch_setting(PDO $mysql, string $type): ?string
{
    $stmt = $mysql->prepare('SELECT setting FROM settings WHERE setting_type = :type');
    $stmt->execute([':type' => $type]);
    $value = $stmt->fetchColumn();

    return $value === false ? null : (string) $value;
}

/**
 * Insert default settings row when missing (bootstrap / migrations).
 */
function pm_ensure_setting(PDO $mysql, string $type, string $default): void
{
    $stmt = $mysql->prepare('SELECT COUNT(*) FROM settings WHERE setting_type = :type');
    $stmt->execute([':type' => $type]);

    if ((int) $stmt->fetchColumn() === 0) {
        $insert = $mysql->prepare(
            'INSERT INTO settings (setting_type, setting) VALUES (:type, :setting)'
        );
        $insert->execute([':type' => $type, ':setting' => $default]);
    }
}

/**
 * Require an authenticated session; sets INCLUDE_GUARD for mysql.php include.
 *
 * @param string $redirect Relative redirect target (e.g. "login/" or "../login/?b=...")
 */
function pm_require_login(string $redirect = 'login/'): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }

    if (!defined('INCLUDE_GUARD')) {
        define('INCLUDE_GUARD', true);
    }
}

/**
 * Require permission_level >= $minLevel; optionally go back via JS (existing UX).
 */
function pm_require_permission(int $minLevel, bool $jsBack = true): void
{
    if (($_SESSION['permission_level'] ?? 0) < $minLevel) {
        if ($jsBack) {
            echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
        }
        exit;
    }
}

/**
 * True when the given user owns the project (project_user_id).
 */
function pm_assert_project_owner(PDO $mysql, string $projectId, int $userId): bool
{
    $stmt = $mysql->prepare(
        'SELECT project_user_id FROM project WHERE project_id = :project_id'
    );
    $stmt->execute([':project_id' => $projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row !== false && (string) $row['project_user_id'] === (string) $userId;
}

require_once __DIR__ . '/../config.php';
$user_name = $high_admin_name;
$sqldatabase = $database;

try {
    $mysql = new PDO("mysql:host=$host", $user, $password);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $mysql->exec("CREATE DATABASE IF NOT EXISTS $database");
    $mysql->exec("USE $database");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `user` (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        permission_level TINYINT(1) NOT NULL DEFAULT 1,
	project_filter JSON
    )");

    $permission_level = 5;
    $stmt = $mysql->prepare('SELECT COUNT(*) FROM `user` WHERE permission_level = :permission_level');
    $stmt->execute([':permission_level' => $permission_level]);

    if ($stmt->fetchColumn() < 1) {
        $hash = password_hash($high_admin_pw, PASSWORD_BCRYPT);
        $ID = time();
        $stmt = $mysql->prepare(
            'INSERT INTO user (user_id, user_name, password, permission_level)
             VALUES (:id, :user_name, :hash, :permission_level)'
        );
        $stmt->execute([
            ':id' => $ID,
            ':user_name' => $user_name,
            ':hash' => $hash,
            ':permission_level' => $permission_level,
        ]);
    }

    $mysql->exec("CREATE TABLE IF NOT EXISTS `settings` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_type VARCHAR(255) NOT NULL,
        setting VARCHAR(255) NOT NULL
    )");

    pm_ensure_setting($mysql, 'backup_schedule', 'daily');
    pm_ensure_setting($mysql, 'last_backup', date('Y-m-d H:i:s'));
    pm_ensure_setting($mysql, 'email_subject', '');
    pm_ensure_setting($mysql, 'email_body', '');
    pm_ensure_setting($mysql, 'allowed_extensions', 'docx, pdf');
    pm_ensure_setting($mysql, 'project_id_temp', 'Project !count - !time');
    pm_ensure_setting($mysql, 'project_id_count', '1');
    pm_ensure_setting($mysql, 'hourly_wage', '100');
    pm_ensure_setting($mysql, 'global_todos', '');
    pm_ensure_setting($mysql, 'apply_new_projects', '1');

    $mysql->exec("CREATE TABLE IF NOT EXISTS `log` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        action VARCHAR(255) NOT NULL,
        time DATETIME NOT NULL
    )");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `user_sessions` (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `client` (
        client_id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255) NOT NULL,
        client_address VARCHAR(255) NOT NULL,
        client_e_mail VARCHAR(255) NOT NULL,
        client_location VARCHAR(255) NOT NULL,
        client_company VARCHAR(255) NOT NULL,
        client_gender VARCHAR(255) NOT NULL,
        client_phone VARCHAR(255) NOT NULL,
        client_mobile VARCHAR(255) NOT NULL
    )");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `project` (
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
    )");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `order` (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_project_id VARCHAR(255) NOT NULL,
        order_order VARCHAR(255) NOT NULL,
        order_amount VARCHAR(255) NOT NULL,
        order_hourly_wage VARCHAR(255) NOT NULL,
        order_checked VARCHAR(255) NOT NULL
    )");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `time` (
        time_id VARCHAR(255) PRIMARY KEY,
        start_time DATETIME,
        end_time DATETIME,
        project_id  VARCHAR(255) NOT NULL,
        order_id  VARCHAR(255) NOT NULL,
        user_id  VARCHAR(255) NOT NULL,
        duration FLOAT GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, start_time, end_time)) STORED
    )");

    $mysql->exec("CREATE TABLE IF NOT EXISTS `checklist` (
    checklist_id BIGINT NOT NULL,
    project_id VARCHAR(255) NOT NULL,
    checklist_name VARCHAR(255) NOT NULL,
    is_done BOOLEAN NOT NULL DEFAULT 0,
    is_global BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (checklist_id, project_id)
    )");

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
