<?php
/**
 * Audit log helpers (logSQL) and admin actions (download/clear).
 * Included from other scripts: only functions are loaded.
 * POST to log.php directly: session, permission, and actions run below.
 */

/** Whitelist table names used by log export/clear (SQL injection guard). */
function pm_log_resolve_table(string $tableName): string
{
    static $allowed = ['log'];

    if (!in_array($tableName, $allowed, true)) {
        throw new InvalidArgumentException('Invalid log table: ' . $tableName);
    }

    return $tableName;
}

function clearTable(PDO $mysql, string $tableName): void
{
    $table = pm_log_resolve_table($tableName);
    $mysql->exec('TRUNCATE TABLE `' . $table . '`');
}

function downloadTable(PDO $mysql, string $tableName): void
{
    $table = pm_log_resolve_table($tableName);

    $stmt = $mysql->prepare('SELECT user_id, action, time FROM `' . $table . '`');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fileName = $table . '_data_' . date('Y-m-d') . '.txt';
    logSQL($mysql, $_SESSION['username'], 'download log');

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    foreach ($rows as $row) {
        echo $row['time'] . '[' . $row['user_id'] . ']     ' . $row['action'] . "\n";
    }

    exit;
}

function logSQL(PDO $mysql, string $userId, string $log): void
{
    $stmt = $mysql->prepare(
        'INSERT INTO `log` (user_id, action, time) VALUES (:user_id, :action, NOW())'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $log,
    ]);
}

// Admin download/clear — only when this file handles the HTTP request (not when required elsewhere).
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: login/');
        exit;
    }

    if (!defined('INCLUDE_GUARD')) {
        define('INCLUDE_GUARD', true);
    }

    require 'mysql.php';

    pm_require_permission(4);

    $action = $_POST['action'] ?? '';

    if ($action === 'down') {
        downloadTable($mysql, 'log');
        header('Location: log/');
        exit;
    }

    if ($action === 'clear') {
        clearTable($mysql, 'log');
        logSQL($mysql, $_SESSION['username'], 'clear log');
        header('Location: log/');
        exit;
    }
}
