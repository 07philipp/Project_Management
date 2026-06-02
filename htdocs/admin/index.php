<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI'];
    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
}
if ($_SESSION["permission_level"] <= 3) {
    echo "<script>
                        window.history.back();
                        console.log('Keine Berechtigung.');
                      </script>";
    exit;
}
define('INCLUDE_GUARD', true);
include "../mysql.php";

/* ---------- HELFER ---------- */
function kv($stmt,$k,$v){
    $a=[];
    while($r=$stmt->fetch(PDO::FETCH_ASSOC)){
        $a[$r[$k]]=(float)$r[$v];
    }
    return $a;
}

/* ---------- KPIs ---------- */
$avgPayDays = $mysql->query("
    SELECT AVG(DATEDIFF(invoice_paid_date, completed_date))
    FROM project
    WHERE invoice_paid_date IS NOT NULL
")->fetchColumn();

$totalPaid = $mysql->query("
    SELECT SUM(o.order_amount)
    FROM project p
    JOIN `order` o ON o.order_project_id=p.project_id
    WHERE p.project_status='paid'
")->fetchColumn();

$openProjects = $mysql->query("
    SELECT COUNT(*) FROM project WHERE project_status!='archived'
")->fetchColumn();

$openInvoices = $mysql->query("
    SELECT COUNT(*) FROM project
    WHERE project_status IN ('completed','invoice_sent')
")->fetchColumn();

/* ---------- DIAGRAMME ---------- */

// 1 Status aktuell
$statusNow = kv($mysql->query("
    SELECT project_status,COUNT(*) c 
    FROM project 
    WHERE project_status!='archived'
    GROUP BY project_status
"),'project_status','c');

// 2 Status pro Tag
function perDay($mysql,$field){
    return kv($mysql->query("
        SELECT DATE($field) d,COUNT(*) c
        FROM project
        WHERE $field IS NOT NULL
        AND project_status!='archived'
        GROUP BY d
        ORDER BY d
    "),'d','c');
}
$completedDay = perDay($mysql,'completed_date');
$billSend      = perDay($mysql,'invoice_sent_date');
$paidDay      = perDay($mysql,'invoice_paid_date');

// 3 Aktive Status
$statusActive = kv($mysql->query("
    SELECT project_status,COUNT(*) c
    FROM project
    WHERE project_status!='archived'
    GROUP BY project_status
"),'project_status','c');

// 4 Projekte erstellt
$createdDay = kv($mysql->query("
    SELECT DATE(project_created_date) d,COUNT(*) c
    FROM project
    WHERE project_status!='archived'
    GROUP BY d 
    ORDER BY d
"),'d','c');

// 5 Projektwert
$projectValue = kv($mysql->query("
    SELECT p.project_name,SUM(o.order_amount) v
    FROM project p
    JOIN `order` o ON o.order_project_id=p.project_id
    WHERE p.project_status!='archived'
    GROUP BY p.project_id
"),'project_name','v');

// 6 Projektzeit
$projectTime = kv($mysql->query("
    SELECT p.project_name,SUM(t.duration)/60 h
    FROM project p
    JOIN time t ON t.project_id=p.project_id
    WHERE p.project_status!='archived'
    GROUP BY p.project_id
"),'project_name','h');

// 7 Geld pro Tag
$paidMoneyDay = kv($mysql->query("
    SELECT DATE(p.invoice_paid_date) d,SUM(o.order_amount) v
    FROM project p
    JOIN `order` o ON o.order_project_id=p.project_id
    WHERE p.invoice_paid_date IS NOT NULL
    AND p.project_status!='archived'
    GROUP BY d ORDER BY d
"),'d','v');

// 8 Geldstatus
$moneyStatus = kv($mysql->query("
    SELECT
        CASE WHEN project_status='paid'
        THEN 'Bezahlt' ELSE 'Offen' END s,
        SUM(o.order_amount) v
    FROM project p
    JOIN `order` o ON o.order_project_id=p.project_id
    WHERE p.project_status!='archived'
    GROUP BY s
"),'s','v');

// 9 Zeit pro User
$timeUser = kv($mysql->query("
    SELECT u.user_name,SUM(t.duration)/60 h
    FROM time t
    JOIN user u ON u.user_id=t.user_id
    GROUP BY u.user_name
"),'user_name','h');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/stats.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<h1>Admin Dashboard</h1>
<br>
<div class="admin-links">
    <a class="link" href="../backup">Backup</a>
    <a class="link" href="../log">Log</a>
    <a class="link" href="../settings">Einstellungen</a>
    <a class="link" href="../users">Nutzer</a>
</div>
<a class="back-to-home-link" href="../">Zurück zur Hauptseite</a>
<br>
<br>
<div class="stats-grid">
    <div class="kpi">Ø Zahlungsdauer<br><b><?=round($avgPayDays,1)?> Tage</b></div>
    <div class="kpi">Gesamt bezahlt<br><b><?=round($totalPaid,2)?> €</b></div>
    <div class="kpi">Aktive Projekte<br><b><?=$openProjects?></b></div>
    <div class="kpi">Offene Rechnungen<br><b><?=$openInvoices?></b></div>
</div>

<div style="margin-bottom:15px">
<b>Zeitraum:</b>
<select onchange="applyRange(this.value)">
    <option value="all">Alles</option>
    <option value="7">Letzte 7 Tage</option>
    <option value="30">Letzte 30 Tage</option>
    <option value="365">Letztes Jahr</option>
</select>
</div>

<div class="charts">

    <div class="box pie">
        <div class="chart-title">Projektstatus aktuell</div>
        <div class="chart-wrap">
            <canvas id="c1"></canvas>
        </div>
    </div>

    <div class="box">
        <div class="chart-title">Projektstatus im Zeitverlauf</div>
        <div class="chart-wrap">
            <canvas id="c2"></canvas>
        </div>
    </div>

    <div class="box pie">
        <div class="chart-title">Aktive Projekte (Status)</div>
        <div class="chart-wrap">
            <canvas id="c3"></canvas>
        </div>
    </div>

    <div class="box">
        <div class="chart-title">Projekt-Erstellungen pro Tag</div>
        <div class="chart-wrap">
            <canvas id="c4"></canvas>
        </div>
    </div>

    <div class="box pie">
        <div class="chart-title">Projektwert</div>
        <div class="chart-wrap">
            <canvas id="c5"></canvas>
        </div>
    </div>

    <div class="box pie">
        <div class="chart-title">Zeitaufwand pro Projekt</div>
        <div class="chart-wrap">
            <canvas id="c6"></canvas>
        </div>
    </div>

    <div class="box">
        <div class="chart-title">Bezahlter Umsatz pro Tag</div>
        <div class="chart-wrap">
            <canvas id="c7"></canvas>
        </div>
    </div>

    <div class="box pie">
        <div class="chart-title">Umsatzstatus (offen / bezahlt)</div>
        <div class="chart-wrap">
            <canvas id="c8"></canvas>
        </div>
    </div>

    <div class="box">
        <div class="chart-title">Zeitaufwand pro Nutzer</div>
        <div class="chart-wrap">
            <canvas id="c9"></canvas>
        </div>
    </div>

</div>


<script>
const donut = (id, labels, data) =>
    new Chart(id, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',           // 👈 Donut-Loch
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

const line=(id,l,d)=>new Chart(id,{type:'line',data:{labels:l,datasets:d}})
const bar=(id,l,d)=>new Chart(id,{type:'bar',data:{labels:l,datasets:d}})

const chartStatus = donut(c1,<?=json_encode(array_keys($statusNow))?>,<?=json_encode(array_values($statusNow))?>)

const chartTimeline = line(c2,<?=json_encode(array_keys($completedDay))?>,[
{label:'Abgeschlossen',data:<?=json_encode(array_values($completedDay))?>},
{label:'Rechnung gesendet',data:<?=json_encode(array_values($billSend))?>},
{label:'Bezahlt',data:<?=json_encode(array_values($paidDay))?>}
])

donut(c3,<?=json_encode(array_keys($statusActive))?>,<?=json_encode(array_values($statusActive))?>)
line(c4,<?=json_encode(array_keys($createdDay))?>,[{label:'Projekte erstellt',data:<?=json_encode(array_values($createdDay))?>}])
donut(c5,<?=json_encode(array_keys($projectValue))?>,<?=json_encode(array_values($projectValue))?>)
donut(c6,<?=json_encode(array_keys($projectTime))?>,<?=json_encode(array_values($projectTime))?>)
line(c7,<?=json_encode(array_keys($paidMoneyDay))?>,[{label:'€ bezahlt',data:<?=json_encode(array_values($paidMoneyDay))?>}])
donut(c8,<?=json_encode(array_keys($moneyStatus))?>,<?=json_encode(array_values($moneyStatus))?>)
bar(c9,<?=json_encode(array_keys($timeUser))?>,[{label:'Stunden',data:<?=json_encode(array_values($timeUser))?>}])
</script>

</body>
</html>
