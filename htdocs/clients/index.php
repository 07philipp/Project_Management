<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI'];

    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
include '../mysql.php';
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

    return "<span class='status-badge status-" . h($status) . h($size) . "'>" . h($text) . "</span>";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $clientId = $_GET['id'];

    $clientQuery = "SELECT client_name, client_address, client_e_mail, client_location, client_company, client_gender, client_phone, client_mobile FROM client WHERE client_id = :id";
    $stmt = $mysql->prepare($clientQuery);
    $stmt->bindParam(':id', $clientId);
    $stmt->execute();
    $clientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($clientData) {
        $clientName = $clientData['client_name'];
        $clientAddress = $clientData['client_address'];
        $clientEMail = $clientData['client_e_mail'];
        $clientLocation = $clientData['client_location'];
        $clientCompany = $clientData['client_company'];
        $clientGender = $clientData['client_gender'];
        $clientPhone = $clientData['client_phone'];
        $clientMobile = $clientData['client_mobile'];
    } else {
        echo 'Error: Unable to retrieve client data';
        exit;
    }
    // E-Mail Einstellungen abrufen
    $emailSubjectQuery = "SELECT setting FROM settings WHERE setting_type = 'email_subject'";
    $emailSubject = $mysql->query($emailSubjectQuery)->fetchColumn();

    $emailBodyQuery = "SELECT setting FROM settings WHERE setting_type = 'email_body'";
    $emailBody = $mysql->query($emailBodyQuery)->fetchColumn();
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($clientName); ?></title>
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="../css/form.css">
        <link rel="stylesheet" href="../css/notification.css">
        <script src="../js/notification.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var errorMessage = <?= pm_json_script(pm_take_flash_message()) ?>;
                if (errorMessage) {
                    showNotification(errorMessage);
                }
            });
        </script>
    </head>

    <body>
        <h1><?php echo h($clientName); ?></h1>
        <p>Adresse: <?php echo h($clientAddress); ?></p>
        <td></td>

        <p>E-Mail: <a id='sendEmailButton' class='email-button' href='#'><?php echo h($clientEMail); ?></a></p>
        <p>Ort: <?php echo h($clientLocation); ?></p>
        <p>Unternehmen: <?php echo h($clientCompany); ?></p>
        <p>Geschäftsnummer: <?php if ($clientPhone != "") { echo "<a href='tel:" . h($clientPhone) . "'>" . h($clientPhone) . "</a>"; } else { echo "N/A"; } ?></p>
        <p>Mobilnummer: <?php if ($clientMobile != "") { echo "<a href='tel:" . h($clientMobile) . "'>" . h($clientMobile) . "</a>"; } else { echo "N/A"; } ?></p>
        <p>Geschlecht: <?php echo h($clientGender); ?></p>
        <script>
            document.getElementById('sendEmailButton').addEventListener('click', function() {
                const emailAddress = <?= pm_json_script($clientEMail) ?>;
                const subject = <?= pm_json_script($emailSubject) ?>;
                const body = <?= pm_json_script($emailBody) ?>;

                // Öffnet das E-Mail-Programm mit den vorausgefüllten Details
                const mailtoLink = `mailto:${encodeURIComponent(emailAddress)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
                window.location.href = mailtoLink;
            });
        </script>
        <h2>Projekte:</h2>
        <ul>
            <?php
            $query = "SELECT * FROM project WHERE project_client_id = :clientId";
            $stmt = $mysql->prepare($query);
            $stmt->bindParam(':clientId', $clientId);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                echo 'Keine Projekte gefunden';
            } else {
                while ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if($project['project_status'] != "archived"){
                        echo "<li><a href='../projects/?id=" . h($project['project_id']) . "'>"
                        . h($project['project_name']) . "</a>"
                        . renderProjectStatusBadge($project['project_status']) . "</li>";
                    }
                }
            }
            ?>
        </ul>
        <?php if (3 <= $_SESSION['permission_level']) { ?>
            <h2>Neues Projekt erstellen:</h2>
            <form class="projectForm" id="projectForm" action='../add_project.php' method='post'>
                <input type='hidden' name='client_id' value='<?php echo h($clientId); ?>'>
                <label for='project_name'>Projektname:</label>
                <input type='text' name='project_name' required>
                <br>
                <label for='project_order'>Auftrag:</label>
                <input type='text' name='project_order'>
                <br>
                <label for='project_address'>Adresse:</label>
                <input type='text' name='project_address'>
                <br>
                <label for='project_description'>Beschreibung:</label>
                <input type='text' name='project_description'>
                <br>
                <label for="due_date">Abgabedatum:</label>
                <input type="date" id="due_date" name="due_date">
                <br>
                <button type='submit'>Projekt erstellen</button>
            </form>
            <br>
            <div class="space">
                <form class="edit_client" action="../edit_client" method="get" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo h($clientId); ?>">
                    <button class="link" type="submit">Kunden bearbeiten</button>
                </form>
                <form class="delete_client" action="../delete_client.php" method="post" style="display:inline;">
                    <input type="hidden" name="client_id" value="<?php echo h($clientId); ?>">
                    <button class="link" type="submit">Kunden löschen</button>
                </form>
            </div>
            <div>
            <?php } ?>
            <button onclick="location.href='../clients'">Kunden</button>
            <button onclick="location.href='../'">Zurück zur Startseite</button>
            </div>
            <div id="notification" class="notification" onclick="hideNotification()">
                <p id="notification-message"></p>
                <div id="progress-bar" class="progress-bar"></div>
            </div>
    </body>

    </html>
<?php
    exit;
}

// Query to retrieve clients from the database
$clientsQuery = "SELECT c.client_id, c.client_name, c.client_address, c.client_e_mail, c.client_location, c.client_company, c.client_gender, c.client_phone, c.client_mobile 
                  FROM client c";
$clientsResult = $mysql->query($clientsQuery);

// Query to retrieve projects for each client
$projectsQuery = "SELECT p.project_id, p.project_name, c.client_id 
                  FROM project p 
                  JOIN client c ON p.project_client_id = c.client_id";
$projectsResult = $mysql->query($projectsQuery);

$clients = array();
$projects = array();

if ($clientsResult && $projectsResult) {
    // Store client data in an array
    while ($client = $clientsResult->fetch(PDO::FETCH_ASSOC)) {
        $clients[$client['client_id']] = $client;
    }

    // Store project data in an array
    while ($project = $projectsResult->fetch(PDO::FETCH_ASSOC)) {
        $projects[$project['client_id']][] = $project;
    }
}

$emailSubjectQuery = "SELECT setting FROM settings WHERE setting_type = 'email_subject'";
$emailSubject = $mysql->query($emailSubjectQuery)->fetchColumn();

$emailBodyQuery = "SELECT setting FROM settings WHERE setting_type = 'email_body'";
$emailBody = $mysql->query($emailBodyQuery)->fetchColumn();

?>

<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunden</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notification.css">
    <link rel="stylesheet" href="../css/search.css">
    <script src="../js/notification.js"></script>
    <script src="../js/project_search.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var errorMessage = <?= pm_json_script(pm_take_flash_message()) ?>;
            if (errorMessage) {
                showNotification(errorMessage);
            }
        });

        function sendMail(value) {
            const emailAddress = value;
            const subject = <?= pm_json_script($emailSubject) ?>;
            const body = <?= pm_json_script($emailBody) ?>;

            const mailtoLink = `mailto:${encodeURIComponent(emailAddress)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            window.location.href = mailtoLink;
        };
    </script>
</head>

<body>

    <h2>Kunden</h2>
    <a class="space back-to-home-link" href="../">Zurück zur Hauptseite</a>

    <div class="projectSearch" style="max-width: 330px;">
        <div class="filter_select-wrapper">
            <input type="text" id="projectSearchInput" class="filter_search-input" placeholder="Suchen..." oninput="searchProjectField(this, 'client_name_select')">
            <select id="client_name_select" name="client_name_select" class="filter_custom-select" onchange="searchProject(this, 0)">
                <option class='filter_search-option' value="" selected>Alle Kunden</option>
                <?php
                try {
                    $selectQuery = "SELECT * FROM client";
                    $result = $mysql->query($selectQuery);

                    if ($result->rowCount() > 0) {
                        $clients = $result->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($clients as $client) {
                            echo "<option value='" . h($client['client_name']) . "' class='filter_search-option'>" . h($client['client_name']) . "</option>";
                        }
                    }
                } catch (PDOException $e) {
                    echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                }
                ?>
            </select>
        </div>
    </div>
    <table id="projectTable">
        <tr>
            <th>Kundenname</th>
            <th>Rechnungs Adresse</th>
            <th>E-Mail Adresse</th>
            <th>Ort</th>
            <th>Unternehmen</th>
            <th>Telefonie</th>
            <th>Geschlecht</th>
            <th>Projekte</th>
            <?php if (3 <= $_SESSION["permission_level"]) { ?>
                <th>Aktionen</th>
            <?php } ?>
        </tr>
        <?php foreach ($clients as $client) { ?>
            <tr class="project-row">
                <td><a href="../clients/?id=<?php echo h($client['client_id']); ?>"><?php echo h($client['client_name']); ?></a></td>
                <td><?php echo h($client['client_address']); ?></td>
                <td><button type="button" class="linkButton" onclick="sendMail(<?= pm_json_script($client['client_e_mail']) ?>)"><?php echo h($client['client_e_mail']); ?></button></td>
                <td><?php echo h($client['client_location']); ?></td>
                <td><?php echo h($client['client_company']); ?></td>

                <td>
                    <p>Geschäftsnummer: <?php if ($client['client_phone'] != "") { echo "<a href='tel:" . h($client['client_phone']) . "'>" . h($client['client_phone']) . "</a>"; } else { echo "N/A"; } ?></p>
                    <p>Mobilnummer: <?php if ($client['client_mobile'] != "") { echo "<a href='tel:" . h($client['client_mobile']) . "'>" . h($client['client_mobile']) . "</a>"; } else { echo "N/A"; } ?></p>
                </td>

                <td><?php echo h($client['client_gender']); ?></td>
                <td>
                    <ul>
                        <?php if (isset($projects[$client['client_id']])) {
                            foreach ($projects[$client['client_id']] as $project) { ?>
                                <li><a href="../projects/?id=<?php echo h($project['project_id']); ?>"><?php echo h($project['project_name']); ?></a></li>
                        <?php }
                        } else {
                            echo "Keine Projekte gefunden";
                        } ?>
                    </ul>
                </td>
                <?php if (3 <= $_SESSION["permission_level"]) { ?>
                    <td>
                        <form class="delete_client" action="../delete_client.php" method="post" style="display:inline;"                         
                        onsubmit="return confirm('Bist du sicher, dass du diesen Kunden wirklich löschen willst?\n\nDieser Vorgang kann NICHT rückgängig gemacht werden!');">
                            <input type="hidden" name="client_id" value="<?php echo h($client['client_id']); ?>">
                            <button class="space link" type="submit">Kunden löschen</button>
                        </form>
                        <form class="edit_client" action="../edit_client/" method="get" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo h($client['client_id']); ?>">
                            <input type="hidden" name="back" value="back">
                            <button class="link" type="submit">Kunden bearbeiten</button>
                        </form>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
    </table>
    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>
</body>

</html>