<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/");
    exit;
}


// Beispiel Status-Liste (falls nicht aus DB)
$statuses = [
    'in_progress' => 'In Bearbeitung',
    'completed' => 'Abgeschlossen',
    'invoice_sent' => 'Rechnung gesendet',
    'paid' => 'Bezahlt',
    'archived' => 'Archiviert'
];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Projektliste exportieren</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>



    <h1>Projektliste exportieren</h1>

    <div class="">

        <form method="post" action="../export.php">

            <!-- ================= STATUS FILTER ================= -->
            <div class="space">
                <h2>Status auswählen</h2>

                <div class="grin">
                   <?php foreach ($statuses as $key => $label): ?>
    			<input 
        			type="checkbox" 
        			name="status[]" 
        			value="<?= $key ?>" 
        			id="status_<?= $key ?>"
        			<?= $key !== 'archived' ? 'checked' : '' ?>
    			>
    			<label for="status_<?= $key ?>" class="status-badge status-<?= $key ?>">
        			<?= $label ?>
    			</label>
			<?php endforeach; ?>
                </div>
            </div>

            <!-- ================= SPALTEN ================= -->
            <div class="space">
                <h2>Spalten auswählen</h2>

                <div class="grid">
			<input type="checkbox" name="columns[]" value="project_id" checked>
			<span>Projekt-ID</span>

                        <input type="checkbox" name="columns[]" value="project_name" checked>
                        <span>Projektname</span>

			<input type="checkbox" name="columns[]" value="client_name" checked>
			<span>Kunde</span>

			<input type="checkbox" name="columns[]" value="project_status" checked>
			<span>Status</span>

			<input type="checkbox" name="columns[]" value="user_name">
			<span>Bearbeiter</span>

			<input type="checkbox" name="columns[]" value="project_due_date">
			<span>Fällig am</span>
                </div>
            </div>
             <br>

            <!-- ================= FARBOPTION ================= -->
            <div class="space">
                <label>
                    <input type="checkbox" name="with_colors" value="1" checked>
                    Status-Farben im PDF anzeigen
                </label>
            </div>
             <br>

            <!-- ================= BUTTON ================= -->
            <div class="space">
                <button type="submit">PDF herunterladen</button>
            </div>

        </form>

    </div>

    <br>

    <a href="../" class="back-to-home-link">
        Zurück zur Hauptseite
    </a>



</body>
</html>