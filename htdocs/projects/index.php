<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI'];

    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

// Check if project ID is passed in the URL
if (!isset($_GET['id'])) {
    echo "Error: No project ID provided.";
    exit;
}

function renderProjectStatusBadge(string $status, bool $large = false): string
{
    $labels = [
        'in_progress'   => 'In Arbeit',
        'completed'     => 'Abgeschlossen',
        'invoice_sent'  => 'Rechnung gestellt',
        'paid'          => 'Bezahlt',
        'archived'      => 'Archiviert'
    ];

    $text = $labels[$status] ?? $status;
    $size = $large ? ' large' : '';

    return "<span class='status-badge status-$status$size'>$text</span>";
}


$projectNumber = $_GET['id'];

include('../mysql.php');
require_once('../log.php');

$uploadFolder = "uploads/$projectNumber/";
if (!is_dir($uploadFolder)) {
    mkdir($uploadFolder, 0777, true);
    file_put_contents("$uploadFolder/.htaccess", "Order deny,allow\nDeny from all\n");
}

$projectQuery = "SELECT project_name, project_client_id, project_address, project_description, project_user_id, project_due_date, project_created_date, 
project_status, completed_date, invoice_sent_date, invoice_paid_date
FROM project
WHERE project_id = '$projectNumber'";
$projectResult = $mysql->query($projectQuery);
if ($projectResult) {
    $projectData = $projectResult->fetch(PDO::FETCH_ASSOC);
    $projectName = $projectData['project_name'];
    $projectClientId = $projectData['project_client_id'];
    $projectAddress = $projectData['project_address'];
    $projectDescription = $projectData['project_description'];
    $projectUserId = $projectData['project_user_id'];
    $date = $projectData['project_due_date'];
    $crea_date = $projectData['project_created_date'];
    $projectStatus = $projectData['project_status'];
    $completedDate = $projectData['completed_date'];
    $invoiceSent   = $projectData['invoice_sent_date'];
    $invoicePaid   = $projectData['invoice_paid_date'];

    $lockProject = in_array($projectStatus, ['invoice_sent', 'paid', 'archived']);


    $clientQuery = "SELECT client_name 
    FROM client
    WHERE client_id = '$projectClientId'";
    $clientResult = $mysql->query($clientQuery);
    $clientData = $clientResult->fetch(PDO::FETCH_ASSOC);
    $clientName = $clientData['client_name'];


    $Query = "SELECT user_name 
    FROM user
    WHERE user_id = '$projectUserId'";
    $Result = $mysql->query($Query);
    $Data = $Result->fetch(PDO::FETCH_ASSOC);
    $projectUserName = $Data['user_name'];

    $ordersQuery = "SELECT order_id, order_project_id, order_order, order_amount, order_checked
                     FROM `order` 
                     WHERE order_project_id = '$projectNumber'";
    $ordersResult = $mysql->query($ordersQuery);

    $orders = array();

    if ($ordersResult) {
        while ($order = $ordersResult->fetch(PDO::FETCH_ASSOC)) {
            $orders[$order['order_project_id']][] = $order;
        }
    }
} else {
    echo 'Error: Unable to retrieve project data';
}
if (isset($_GET['file'])) {
    $projectFolder = "uploads/";
    $uploadDir = $projectFolder . $projectNumber . "/"; // Verzeichnis, in das die Dateien hochgeladen werden
    $fileName = isset($_GET['file']) ? $_GET['file'] : '';

    if (!empty($fileName)) {
        $filePath = $uploadDir . $fileName;

        if (file_exists($filePath)) {
            // Header zum Herunterladen der Datei
            logSQL($mysql, $_SESSION['username'], "project $projectNumber ($projectName) download $fileName by link");
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            flush(); // Alle Puffer leeren
            readfile($filePath); // Dateiinhalt ausgeben
        } else {
            $_SESSION['error_message'] = "Datei nicht gefunden.";
        }
    } else {
        $_SESSION['error_message'] = "Ungültiger Dateiname.";
    }
}


$timeQuery = "SELECT u.user_id, u.user_name, o.order_order, t.start_time, t.end_time, t.duration, t.time_id
            FROM `time` t 
            JOIN `user` u ON t.user_id = u.user_id 
            JOIN `order` o ON t.order_id = o.order_id 
            WHERE t.project_id =  '$projectNumber'";
$timeResult = $mysql->query($timeQuery);

$times = array();


if ($timeResult) {
    while ($time = $timeResult->fetch(PDO::FETCH_ASSOC)) {
        $times[$time['time_id']] = $time;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $projectName; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/notification.js"></script>
    <script src="../js/option_search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch error message from PHP session
            var errorMessage = "<?php echo isset($_SESSION['error_message']) ? addslashes($_SESSION['error_message']) : ''; ?>";
            if (errorMessage) {
                showNotification(errorMessage);
                // Clear the session error message
                <?php unset($_SESSION['error_message']); ?>
            }
            var toggleTime = "<?php echo isset($_SESSION['stopping_time']) ? $_SESSION['stopping_time'] : false; ?>";

            const timeS = document.getElementById('time_select');
            timeS.style.display = toggleTime ? 'none' : 'block';
        });

        function saveChanges(inputField, orderId) {
            var params = new URLSearchParams();
            params.append('order_id', orderId);
            params.append(inputField.name, inputField.value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../edit_amount.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.addEventListener('load', function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }

        function copyDownloadLink(projectNumber, file) {
            // Generate the full URL
            var baseURL = window.location.origin; // Automatically get the domain or IP of the site
            var downloadURL = baseURL + "/projects/?id=" + projectNumber + "&file=" + encodeURIComponent(file);

            // Copy the URL to the clipboard
            navigator.clipboard.writeText(downloadURL).then(function() {
                var errorMessage = "Download-Link kopiert.";
                if (errorMessage) {
                    showNotification(errorMessage);
                }
            }, function(err) {
                var errorMessage = "Fehler beim Kopieren des Links.";
                if (errorMessage) {
                    showNotification(errorMessage);
                }
            });
        }
        </script>
</head>

<body>
    <h1><?php echo $projectName; ?></h1>
    <p>Projectnummer: <?php echo $projectNumber; ?></p>
    <p>Auftraggeber: <a href='../clients/?id=<?php echo $projectClientId; ?>'><?php echo $clientName; ?></a></p>
    <p>Betreuer: <a href='../users/?id=<?php echo $projectUserName; ?>'><?php echo $projectUserName; ?></a></p>
    <p>Beschreibung: <?php echo $projectDescription; ?></p>
    <p>Adresse: <?php echo $projectAddress; ?></p>
    <p>Abgabe: <?php if (($projectStatus === 'in_progress' || $projectStatus === 'completed') && $date !== '') { 
                        echo date('d.m.Y', strtotime($project['project_due_date'])); 
                    }else{ ?>
                    ---
                    <?php } ?></p>
    <p>Erstellt: <?php echo date('d.m.Y', $crea_date); ?></p>
    <p>
        Status:
        <?= renderProjectStatusBadge($projectStatus, true); ?>
    </p>

    <table>
        <tr>
            <th>Todos</th>
            <?php
            if (2 <= $_SESSION['permission_level'] || $projectUserId == $_SESSION['user_id']) {
            ?><th>Aufträge</th>
            <th></th><?php
                    } ?>
            <?php if (3 <= $_SESSION['permission_level']) { ?>
                <th></th>
                <th>Aktionen</th>
            <?php } ?>
        </tr>
        <tr>
            <td>
            <?php
                // Alle Todos für dieses Projekt laden (global + project-spezifisch)
                $stmt = $mysql->prepare("SELECT * FROM checklist WHERE project_id = :pid");
                $stmt->execute([':pid' => $projectNumber]);
                $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo "<ul>";
                foreach ($todos as $todo) {
                    $checked = $todo['is_done'] ? "checked" : "";

                    echo "<li>
                            <form action='../edit_checklist.php?action=edit' method='POST' style='display:inline;'>
                                <input type='hidden' name='checklist_id' value='{$todo['checklist_id']}'>
                                <input type='hidden' name='project_id' value='{$projectNumber}'>
                                <input type='hidden' name='title' value='{$todo['checklist_name']}'>
                                <input type='checkbox' name='is_done' onChange='this.form.submit()' $checked>
                                {$todo['checklist_name']}
                            </form>

                            <form action='../edit_checklist.php?action=delete_project' method='POST' style='display:inline;'>
                                <input type='hidden' name='checklist_id' value='{$todo['checklist_id']}'>
                                <input type='hidden' name='project_id' value='{$projectNumber}'>
                                <button type='submit' style='background-color: transparent; color: red; padding-left: 0px;'>X</button>
                            </form>
                        </li>";
                }
                echo "</ul>";
                ?>   
            </td>
            <td>
                <ul>
                    <?php if (isset($orders[$projectNumber])) {
                        foreach ($orders[$projectNumber] as $order) { ?>
                            <li><?php if (3 <= $_SESSION['permission_level']) { ?><input
                                        placeholder="N/A"
                                        class='input'
                                        name='order'
                                        type='text'
                                        value="<?php echo $order['order_order']; ?>"
                                        onchange="saveChanges(this, '<?php echo $order['order_id']; ?>')" 
                                        <?php if($lockProject){
                                            echo ' disabled'; } ?> /> <?php } else echo $order['order_amount']; ?> </li>
                    <?php   }
                    } ?>
                </ul>
            </td>
            <?php if (3 <= $_SESSION['permission_level'] || $projectUserId == $_SESSION['user_id']) { ?>
                <td>
                    <div class='checkbox-container'>
                        <?php if (isset($orders[$projectNumber])) {
                            foreach ($orders[$projectNumber] as $order) { ?>
                                <div class='checkbox-item'>
                                    <input
                                        class='input'
                                        name='check'
                                        type='checkbox'
                                        value="check"
                                        onchange="saveChanges(this, '<?php echo $order['order_id']; ?>')"
                                        <?php if ($order['order_checked'] == 'checked') {
                                            echo 'checked';
                                        }
                                        if (2 == $_SESSION['permission_level']) {
                                            if ($projectUserId != $_SESSION['user_id']) {
                                                echo ' disabled';
                                            }
                                        } elseif (1 == $_SESSION['permission_level']) {
                                            echo ' disabled';
                                        }elseif($lockProject){
                                            echo ' disabled';
                                        } ?> />
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </td>
            <?php } ?>
            <?php if (3 <= $_SESSION['permission_level'] && !$lockProject) { ?>
                <td>
                    <form action='../edit_order.php' method='post'>
                        <input type='hidden' name='projectNumber' value='<?php echo $projectNumber; ?>'>
                        <button class='space' name='addorder' type='submit'>Auftrag hinzufügen</button>
                        <button name='deleteorder' type='submit'>Auftrag löschen</button>
                    </form>
                </td>
                <td>
                    <form class='space' action='../edit_project/' method='get'>
                        <input type='hidden' name='id' value='<?php echo $projectNumber; ?>'>
                        <button class='link' type='submit'>Projekt bearbeiten</button>
                    </form>
                    <form class='space' action='../write_bill/' method='get'>
                        <input type='hidden' name='id' value='<?php echo $projectNumber; ?>'>
                        <button class='link' type='submit'>Rechnung erstellen</button>
                    </form>
                    <form class="space"
                        action="../delete_project.php"
                        method="post"
                        onsubmit="return confirm('Bist du sicher, dass du dieses Projekt wirklich löschen willst?\n\nDieser Vorgang kann NICHT rückgängig gemacht werden!');">
                        
                        <input type="hidden" name="project_id" value="<?php echo $projectNumber; ?>">
                        <button class="link danger" type="submit">
                            Projekt löschen
                        </button>
                    </form>

                </td>
            <?php }else
            echo "<td></td> <td></td>"; ?>
        </tr>
    </table>
    <br>



<?php if ($projectStatus !== 'in_progress'): ?>
    <p>Abgeschlossen am:
        <strong><?php echo date('d.m.Y', strtotime($completedDate)); ?></strong>
    </p>
<?php endif; ?>


<?php if (in_array($projectStatus, ['completed', 'invoice_sent', 'paid'])): ?>

<?php if (3 <= $_SESSION['permission_level']): ?>
<form method="post" action="../update_project_status.php">
    <input type="hidden" name="project_id" value="<?= $projectNumber ?>">
    <input type="hidden" name="action" value="toggle_invoice_sent">

    <label>
        <input type="checkbox"
            onchange="this.form.submit()"
            <?= $invoiceSent ? 'checked' : '' ?>
            <?= $invoicePaid ? 'disabled' : '' ?>
        >
        Rechnung gestellt
    </label>
</form>
<?php endif; ?>
<?php endif; ?>

<?php if ($invoiceSent): ?>
    <small>Gesendet am: <?php echo date('d.m.Y', strtotime($invoiceSent)); ?></small>
<?php endif; ?>

<br>


<?php if (in_array($projectStatus, ['invoice_sent', 'paid'])): ?>

<?php if (3 <= $_SESSION['permission_level'] && $invoiceSent): ?>
<form method="post" action="../update_project_status.php">
    <input type="hidden" name="project_id" value="<?= $projectNumber ?>">
    <input type="hidden" name="action" value="toggle_paid">

    <label>
        <input type="checkbox"
            onchange="this.form.submit()"
            <?= $invoicePaid ? 'checked' : '' ?>
        >
        Bezahlt
    </label>
</form>
<?php endif; ?>
<?php endif; ?>


<?php if ($invoicePaid): ?>
    <small>Bezahlt am: <?php echo date('d.m.Y', strtotime($invoicePaid)); ?></small>
    <br>
<?php endif; ?>





<?php if (!$lockProject): ?>

    <br>
    <h2>Neues ToDo für dieses Projekt</h2>
    <form action="../edit_checklist.php?action=create_project" method="POST">
        <input type="hidden" name="id" value="<?php echo $projectNumber; ?>">
        <input type="text" class="search-input" name="title" placeholder="Titel" required>
        <button type="submit">Hinzufügen</button>
    </form>
    <br>
    <?php endif; ?>

    <h2>Arbeitszeit</h3>
        <table>
            <tr>
                <th>Nutzer</th>
                <th>Auftrag</th>
                <th>Startzeit</th>
                <th>Endzeit</th>
                <th>Arbeitszeit in Min</th>
            </tr>
            <?php
            if ($times) {
                foreach ($times as $time) { ?>
                    <tr>
                        <td><a href="../users/?id=<?php echo $time['user_name']; ?>"><?php echo $time['user_name']; ?></a></td>
                        <td><?php echo $time['order_order']; ?></td>
                        <td><?php echo $time['start_time']; ?></td>
                        <td><?php echo $time['end_time']; ?></td>
                        <td><?php echo $time['duration']; ?></td>
                    </tr>
            <?php
                }
            }
            ?>
        </table>
        <?php if (!$lockProject): ?>

        <br>

        <form action="../add_time.php" method="post">
            <h3>Zeit hinzufügen</h3>
            <input type="hidden" id="id" name="id" value="<?php echo $projectNumber; ?>">
            <label for="start">Startzeit</label>
            <input name="start" type="datetime-local"
                required>
            <label for="stop">Endzeit</label>
            <input name="end" type="datetime-local" required></td>
            <br>
            <br>
            <div id="time_select" class="select-wrapper">
                <input type="text" id="searchInput" class="search-input" onchange="searchUser(this)"
                    placeholder="Suchen...">
                <select id="order_id" name="order_id" class="custom-select" required>
                    <?php

                    try {
                        $selectQuery = "SELECT * FROM `order` WHERE order_project_id = '$projectNumber'";
                        $result = $mysql->query($selectQuery);

                        if ($result->rowCount() > 0) {
                            $orders = $result->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($orders as $order) {
                                echo "<option id='order_id' name='order_id' value='" . $order['order_id'] . "' class='search-option'>" . $order['order_order'] . "</option>";
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<script>console.log('Fehler beim Abrufen der Daten:" . $e->getMessage() . "');</script>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit">Hinzufügen</button>
        </form>
        <br>
        <br>
        <a class="link" href="../edit_time/?id=<?php echo $projectNumber; ?>">Bearbeiten</a>
        <br>

        <?php if (3 <= $_SESSION['permission_level'] || $projectUserId == $_SESSION['user_id']) { ?>
            <h2>Hochgeladene Dateien</h2>
            <ul>
                <?php
                $files = scandir($uploadFolder);
                foreach ($files as $file) {
                    if ($file != "." && $file != ".." && $file != ".htaccess") {
                        echo "<li>
        <ul>$file
        <form action='../upload.php' method='post' style='display:inline'>
            <input type='hidden' name='p_id' value=" .  $projectNumber . ">
            <input type='hidden' name='action' value='download'>
            <input type='hidden' name='file' value='$file'>
            <button type='submit'>Herunterladen</button>
        </form>
        <form action='../upload.php' method='post' style='display:inline'>
            <input type='hidden' name='p_id' value=" .  $projectNumber . ">
            <input type='hidden' name='action' value='delete'>
            <input type='hidden' name='file' value='$file'>
            <button type='submit'>Löschen</button>
        </form>
        <button onclick='copyDownloadLink(\"" .  $projectNumber . '","' . $file . "\")'>Link kopieren</button>
        </ul>
    </li>";
                    }
                }
                ?>
            </ul>
            <br>

            <div class=".upload-container">
                <form action="../upload.php" method="post" enctype="multipart/form-data">
                    <input type='hidden' name='p_id' value='<?php echo $projectNumber; ?>'>
                    <input type="hidden" name="action" value="create">
                    <input type="file" name="fileToUpload" required>
                    <button type="submit">Datei hochladen</button>
                </form>
            </div>
            <br>
        <?php } ?>
        <br>
<?php endif; ?>

<?php if ($projectStatus === 'archived' && 3 <= $_SESSION['permission_level']): ?>

<form method="post" action="../update_project_status.php" style="margin-top:10px;">
    <input type="hidden" name="project_id" value="<?php echo $projectNumber; ?>">
    <input type="hidden" name="action" value="unarchive">

    <button type="submit" class="link">
         Archivierung aufheben
    </button>
</form>
<?php endif; ?>
 <?php if ($projectStatus === 'paid' && 3 <= $_SESSION['permission_level']): ?>
        <form action="../update_project_status.php" method="post" style="margin-top:10px;">
            <input type="hidden" name="project_id" value="<?php echo $projectNumber; ?>">
            <input type="hidden" name="action" value="archive">
            <button class="link" type="submit">Archivieren</button>
        </form>
<?php endif ?>
<br>
        <a class="back-to-home-link" href="../">Zurück zur Hauptseite</a>
        <div id="notification" class="notification" onclick="hideNotification()">
            <p id="notification-message"></p>
            <div id="progress-bar" class="progress-bar"></div>
        </div>        
        
</body>

</html>