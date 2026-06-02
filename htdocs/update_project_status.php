<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 2) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
include 'mysql.php';
require_once 'log.php';

$pid = $_POST['project_id'];
$action = $_POST['action'];

// Aktuellen Status holen
$stmt = $mysql->prepare("
    SELECT project_status, completed_date, invoice_sent_date, invoice_paid_date
    FROM project WHERE project_id = :id
");
$stmt->execute([':id' => $pid]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) exit;

/* ===========================
   RECHNUNG GESTELLT TOGGLE
=========================== */
if ($action === 'toggle_invoice_sent') {

    // darf nicht, wenn bezahlt
    if ($p['invoice_paid_date']) {
        exit;
    }

    if ($p['invoice_sent_date']) {
        // 🔄 rückgängig
        $mysql->prepare("
            UPDATE project
            SET invoice_sent_date = NULL,
                project_status = 'completed'
            WHERE project_id = :id
        ")->execute([':id' => $pid]);

        logSQL($mysql, $_SESSION['username'], "invoice_sent undone for project $pid");
    } else {
        // ✅ setzen
        $mysql->prepare("
            UPDATE project
            SET invoice_sent_date = NOW(),
                project_status = 'invoice_sent'
            WHERE project_id = :id
        ")->execute([':id' => $pid]);

        logSQL($mysql, $_SESSION['username'], "invoice_sent set for project $pid");
    }
}

/* ===========================
   BEZAHLT TOGGLE
=========================== */
if ($action === 'toggle_paid') {

    // darf nicht, wenn archiviert
    if ($p['project_status'] === 'archived') {
        exit;
    }

    if ($p['invoice_paid_date']) {
        // 🔄 rückgängig
        $mysql->prepare("
            UPDATE project
            SET invoice_paid_date = NULL,
                project_status = 'invoice_sent'
            WHERE project_id = :id
        ")->execute([':id' => $pid]);

        logSQL($mysql, $_SESSION['username'], "payment undone for project $pid");
    } else {
        // nur wenn Rechnung gestellt
        if (!$p['invoice_sent_date']) exit;

        $mysql->prepare("
            UPDATE project
            SET invoice_paid_date = NOW(),
                project_status = 'paid'
            WHERE project_id = :id
        ")->execute([':id' => $pid]);

        logSQL($mysql, $_SESSION['username'], "payment set for project $pid");
    }
}

/* ===========================
   ARCHIVIEREN
=========================== */
if ($action === 'archive') {

    if ($p['project_status'] !== 'paid') exit;

    $mysql->prepare("
        UPDATE project
        SET project_status = 'archived'
        WHERE project_id = :id
    ")->execute([':id' => $pid]);

    logSQL($mysql, $_SESSION['username'], "project $pid archived");
}

/* ===========================
   ARCHIV AUFHEBEN
=========================== */
if ($action === 'unarchive') {

    if ($p['project_status'] !== 'archived') exit;

    $mysql->prepare("
        UPDATE project
        SET project_status = 'paid'
        WHERE project_id = :id
    ")->execute([':id' => $pid]);

    logSQL($mysql, $_SESSION['username'], "project $pid unarchived");
}

header("Location: ".$_SERVER['HTTP_REFERER']);
exit;