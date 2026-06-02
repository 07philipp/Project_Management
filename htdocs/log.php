<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();  
    if (!isset($_SESSION["user_id"])) {
        header("Location: login/");
        exit;
    } else {
        define('INCLUDE_GUARD', true);
    }
}else{
    if (!isset($_SESSION["user_id"])) {
        header("Location: login/");
        exit;
    }
}
if ($_SESSION["permission_level"] <= 3) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
require 'mysql.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'down') {
    downloadTable($mysql, 'log');  // z.B. die Tabelle 'log' herunterladen

    header("Location: log/");
    exit;

} elseif ($action == 'clear') {
    clearTable($mysql, 'log');  // z.B. die Tabelle 'log' leeren
    logSQL($mysql, $_SESSION['username'], "clear log");

    header("Location: log/");
    exit;
}

function clearTable($mysql, $tableName)
{
    $query = "TRUNCATE TABLE " . $tableName;
    $stmt = $mysql->prepare($query);
    $stmt->execute();
}

function downloadTable($mysql, $tableName)
{
    // Die entsprechenden Spalten (user_id, action, time) abrufen
    $query = "SELECT user_id, action, time FROM " . $tableName;
    $stmt = $mysql->prepare($query);
    $stmt->execute();

    // Alle Zeilen abrufen
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dateinamen und Header für den Download setzen
    $fileName = $tableName . "_data_" . date("Y-m-d") . ".txt";
    logSQL($mysql, $_SESSION['username'], "download log");
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    // Inhalt der Tabelle im gewünschten Format: [time](user)action
    foreach ($rows as $row) {
        $formattedRow = $row['time'] . "[" . $row['user_id'] . "]     " . $row['action'];
        echo $formattedRow . "\n";
    }

    exit;
}

function logSQL($mysql, $userId, $log)
{
    $insertLog = "INSERT INTO `log` (user_id, action, time) 
                                VALUES (:user_id, :action, NOW());";
    $stmt = $mysql->prepare($insertLog);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':action', $log);
    $stmt->execute();
}