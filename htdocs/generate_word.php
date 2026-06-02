<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
}else{
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 2) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
require_once '../vendor/autoload.php'; // Composer Autoloader für PHPWord

include "mysql.php";
require_once "log.php";

use PhpOffice\PhpWord\TemplateProcessor;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['projectNumber'])) {
    $projectNumber = $_POST['projectNumber'];
    include('mysql.php');

    $projectQuery = "SELECT project_name, project_client_id, project_address, project_description 
        FROM project
        WHERE project_id = :projectNumber";
    $stmt = $mysql->prepare($projectQuery);
    $stmt->execute(['projectNumber' => $projectNumber]);
    $projectData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($projectData) {
        $projectName = $projectData['project_name'];
        $projectClientId = $projectData['project_client_id'];
        $projectAddress = $projectData['project_address'];
        $projectDescription = $projectData['project_description'];

        $ordersQuery = "SELECT order_id, order_order, order_amount 
                        FROM `order` 
                        WHERE order_project_id = :projectNumber";
        $stmt = $mysql->prepare($ordersQuery);
        $stmt->execute(['projectNumber' => $projectNumber]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lesitungList = '';
        $leistungsbeschreibungList = '';
        $preisList = '';
        $invoiceAmount = 0;
        foreach ($orders as $index => $order) {
            $orderPrice = $_POST['order_price' . ($index + 1)];
            $orderTime = $_POST['order_time' . ($index + 1)];
            $preisList .= $order['order_amount'] . "\n";
            $lesitungList .= $order['order_order'] . "\n";
            $leistungsbeschreibungList .= (($orderTime * $orderPrice == $order['order_amount']) ? ($orderTime . " Stunde" . ($orderTime = 1 ? "" : "n") . " je " . $orderPrice . "€") : "") . "\n";
            $invoiceAmount += intval($order['order_amount']);
        }
    } else {
        echo 'Error: Unable to retrieve project data';
        exit;
    }
} else {
    exit;
}

$client = $_POST['name'];
$clientGender = $_POST['gender'];
$clientAddress = $_POST['address'];
$clientLocation = $_POST['location'];
$projectPeriod = $_POST['period'];
$clientCompany = $_POST['company'];

$templateFilePath = $_POST['discountB'] === "true" ? 'docx/vorlage_rabat.docx' : 'docx/vorlage.docx';
$discount = 0;
$discountAmount = 0;

if ($_POST['discountB'] === "true") {
    $discount = $_POST['discount'];
    $discountAmount = $_POST['discount_amount_num'];
}

$finalAmount = ($invoiceAmount - $discountAmount) * 1.16;

switch ($clientGender) {
    case "Männlich":
        $clientName = "Herr " . $client;
        $clientName_A = "geehrter " . $clientName;
        break;

    case "Weiblich":
        $clientName = "Frau " . $client;
        $clientName_A = "geehrte " . $clientName;
        break;

    case "Familie":
        $clientName = "Familie " . $client;
        $clientName_A = "sehr geehrte " . $clientName;
        break;

    default:
        // Falls kein Geschlecht angegeben ist
        $clientName = $client;
        $clientName_A = "Sehr geehrte/r " . $clientName;
        break;
}

// Daten für die Platzhalter
$data = [
    'name' => $projectName,
    'auftraggeber' => $clientName,
    'auftraggeber_anrede' => $clientName_A,
    'adresse' => $clientAddress,
    'ort' => $clientLocation,
    'unternehmen' => $clientCompany,
    'zeitraum' => $projectPeriod,
    'rechnungsnummer' => date('md') . sprintf('%03d', rand(1, 99)),
    'datum' => date('d.m.Y'),
    'mwst' => str_replace('.', ',', number_format(($invoiceAmount - $discountAmount) * 0.16, 2)),
    'lesitung' => str_replace('.', ',', $lesitungList),
    'stunden' => str_replace('.', ',', $leistungsbeschreibungList),
    'preis' => str_replace('.', ',', $preisList),
    'summenetto' => str_replace('.', ',', $invoiceAmount),
    'rechnungsbetrag' => str_replace('.', ',', $invoiceAmount - $discountAmount),
    'höheinsgesammt' => str_replace('.', ',', number_format($finalAmount, 2)),
];

if ($_POST['discountB'] === "true") {
    $data['rabat'] = $discount;
    $data['rabatprozent'] = $_POST['discount_amount_per'];
    $data['rabatsumme'] = str_replace('.', ',', $discountAmount);
}

$templateProcessor = new TemplateProcessor($templateFilePath);

// Ersetze Platzhalter im Word-Dokument
foreach ($data as $placeholder => $value) {
    $templateProcessor->setValue($placeholder, $value);
}

// Speichere das Word-Dokument als DOCX-Datei
$docxFilePath = "rechnung_$projectName.docx";
$templateProcessor->saveAs($docxFilePath);

// Sende die Datei zum Download
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . basename($docxFilePath) . '"');
readfile($docxFilePath);
unlink($docxFilePath);
logSQL($mysql, $_SESSION['username'], "created bill $projectNumber");
exit;

