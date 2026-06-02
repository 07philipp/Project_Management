<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
}else{
    define('INCLUDE_GUARD', true);
}
include('mysql.php');
require_once '../libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if (!isset($_SESSION["user_id"])) {
    exit("Kein Zugriff.");
}

$statuses = $_POST['status'] ?? [];
$columns = $_POST['columns'] ?? [];
$withColors = isset($_POST['with_colors']);

if (empty($columns)) {
    exit("Mindestens eine Spalte auswählen.");
}

$params = [];
$query = "
SELECT p.project_id, p.project_name,
       c.client_name,
       u.user_name,
       p.project_status,
       p.project_due_date
FROM project p
JOIN client c ON p.project_client_id = c.client_id
JOIN user u ON p.project_user_id = u.user_id
";

if (!empty($statuses)) {
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $query .= " WHERE p.project_status IN ($placeholders)";
    $params = $statuses;
}

$stmt = $mysql->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Status-Farben ---------- */

$statusColors = [
    'in_progress' => '#dbeafe',
    'completed' => '#fde68a',
    'invoice_sent' => '#fed7aa',
    'paid' => '#bbf7d0',
    'archived' => '#e5e7eb'
];

/* ---------- Spalten-Namen für PDF ---------- */

$columnLabels = [
    'project_id' => 'Projekt-ID',
    'project_name' => 'Projektname',
    'client_name' => 'Kunde',
    'user_name' => 'Bearbeiter',
    'project_status' => 'Status',
    'project_due_date' => 'Fällig am'
];

$statusLabels = [
    'in_progress' => 'In Bearbeitung',
    'completed' => 'Abgeschlossen',
    'invoice_sent' => 'Rechnung gesendet',
    'paid' => 'Bezahlt',
    'archived' => 'Archiviert'
];

/* ---------- HTML aufbauen ---------- */

$html = "
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background: #f3f4f6; }
</style>

<h2>Projektliste</h2>
<p>Erstellt am: " . date('d.m.Y H:i') . "</p>

<table>
<tr>";

foreach ($columns as $col) {
    $html .= "<th>" . htmlspecialchars($columnLabels[$col] ?? $col) . "</th>";
}

$html .= "</tr>";

foreach ($projects as $project) {

    $bg = '';
    if ($withColors && isset($statusColors[$project['project_status']])) {
        $bg = "background:" . $statusColors[$project['project_status']];
    }

    $html .= "<tr style='$bg'>";

    foreach ($columns as $col) {

        $value = $project[$col] ?? '';

	if ($col === 'project_status' && isset($statusLabels[$value])) {
    	$value = $statusLabels[$value];
	}

	if ($col === 'project_due_date' && $value) {
    	$value = date('d.m.Y', strtotime($value));
	}

        $html .= "<td>" . htmlspecialchars($value) . "</td>";
    }

    $html .= "</tr>";
}

$html .= "</table>";

/* ---------- PDF generieren ---------- */

try {

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream("Projektliste_" . date('Y-m-d') . ".pdf", [
        "Attachment" => true
    ]);

} catch (Exception $e) {
    echo "Fehler bei PDF-Erstellung: ";
    echo $e->getMessage();
}
exit;