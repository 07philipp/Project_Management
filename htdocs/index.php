<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
include 'mysql.php';

$stmt = $mysql->prepare("SELECT project_filter FROM user WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$selectedStatuses = [];

if (!empty($user['project_filter'])) {
    $selectedStatuses = json_decode($user['project_filter'], true);
}


// Query to retrieve projects from the database
$params = [];

$projectsQuery = "
SELECT p.project_id, p.project_name, c.client_id, c.client_name,
       p.project_address, p.project_description, p.project_due_date,
       u.user_name, u.user_id,
       p.project_status, p.completed_date,
       p.invoice_sent_date, p.invoice_paid_date
FROM project p
JOIN client c ON p.project_client_id = c.client_id
JOIN user u ON p.project_user_id = u.user_id
";

if (!empty($selectedStatuses)) {
    $placeholders = implode(',', array_fill(0, count($selectedStatuses), '?'));
    $projectsQuery .= " WHERE p.project_status IN ($placeholders)";
    $params = $selectedStatuses;
}

$stmt = $mysql->prepare($projectsQuery);
$stmt->execute($params);
$projectsResult = $stmt;

// Query to retrieve orders and amounts for each project
$ordersQuery = "SELECT o.order_id, o.order_project_id, o.order_order, o.order_amount, o.order_checked
                FROM `order` o 
                JOIN project p ON p.project_id = o.order_project_id";
$ordersResult = $mysql->query($ordersQuery);

$projects = array();
$orders = array();


if ($projectsResult && $ordersResult) {
    // Store project data in an array
    while ($project = $projectsResult->fetch(PDO::FETCH_ASSOC)) {
        $projects[$project['project_id']] = $project;
    }

    // Store order data in an array
    while ($order = $ordersResult->fetch(PDO::FETCH_ASSOC)) {
        $orders[$order['order_project_id']][] = $order;
    }
}
?>
<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dropdown.css">
    <link rel="stylesheet" href="css/form.css">
    <link rel="stylesheet" href="css/notification.css">
    <link rel="stylesheet" href="css/search.css">
    <script src="js/option_search.js"></script>
    <script src="js/project_search.js"></script>
    <script src="js/toggle.js"></script>
    <script src="js/notification.js"></script>
    <script src="js/update.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch error message from PHP session
            var errorMessage = "<?php echo isset($_SESSION['error_message']) ? addslashes($_SESSION['error_message']) : ''; ?>";
            if (errorMessage) {
                showNotification(errorMessage);
                // Clear the session error message
                <?php unset($_SESSION['error_message']); ?>
            }
        });

        

    </script>
</head>

<body onload="sortProjects('id_asc');">
    <div class="dropdown">
        <button class="dropbtn">Mehr</button>
        <div class="dropdown-content">
            <?php
            if (4 <= $_SESSION["permission_level"]) {
                echo "<a href='admin/'>Admin</a>
                <a href='register/'>Neuer Account</a>";
            }

            if (5 != $_SESSION["permission_level"]) {
                echo "<a href='change_password'>Password ändern</a>";
            }
            ?>
    
            <a href="logout.php">Abmelden</a>
        </div>
    </div>

    <h1>Projektverwaltung</h1>


    <?php
    if (3 <= $_SESSION["permission_level"]) {
        echo "<button onclick='toggleProjectForm()'>Projekt hinzufügen</button>
        <button onclick='toggleClientForm()''>Kunden hinzufügen</button>";
    }
    ?>
    <button onclick="window.location.href='clients/';">Kunden</button>
    <button onclick="window.location.href='download/';">Download</button>


    <form class="clientForm" id="clientForm" action="add_client.php" method="post" style="display: none;">
        <label for="new_client_name">Kundenname:</label>
        <input id="new_client_name" name="new_client_name" type="text" required>
        <br>

        <label for="new_client_address">Rechnungsadresse:</label>
        <input id="new_client_address" name="new_client_address" type="text">
        <br>

        <label for="new_client_e_mail_address">E-Mail Adresse:</label>
        <input id="new_client_e_mail_address" name="new_client_e_mail_address" type="email">
        <br>

        <label for="new_client_location">Ort:</label>
        <input id="new_client_location" name="new_client_location" type="text">
        <br>

        <label for="new_client_company">Unternehmen:</label>
        <input id="new_client_company" name="new_client_company" type="text">
        <br>

        <label for="new_client_phone">Geschäftsnummer:</label>
        <input id="new_client_phone" name="new_client_phone" type="tel">
        <br>

        <label for="new_client_mobile">Mobilnummer:</label>
        <input id="new_client_mobile" name="new_client_mobile" type="tel">
        <br>

        <label>Geschlecht:</label>
        <div class="gender">
            <input id="gender_male" name="new_client_gender" type="radio" value="Männlich" required>
            <label for="gender_male">Männlich</label>
            <input id="gender_female" name="new_client_gender" type="radio" value="Weiblich">
            <label for="gender_female">Weiblich</label>
            <input id="gender_female" name="new_client_gender" type="radio" value="Familie">
            <label for="gender_female">Familie</label>
        </div>
        <br>

        <button type="submit">Kunden erstellen</button>
    </form>

    <form class="projectForm" id="projectForm" action="add_project.php" method="post" style="display: none;">
        <label for="project_name">Projektname:</label>
        <input id="project_name" name="project_name" required>
        <br>

        <label for="client">Auftraggeber:</label>
        <div class="select-wrapper">
            <input type="text" id="searchInput" class="search-input" onchange="searchFields(this)"
                placeholder="Suchen...">
            <select id="client_id" name="client_id" class="custom-select" onchange="toggleNewClientFields()" required>
                <option class="search-option" value="neu">Neuen Auftraggeber anlegen</option>
                <?php

                try {
                    $selectQuery = "SELECT * FROM client";
                    $result = $mysql->query($selectQuery);

                    if ($result->rowCount() > 0) {
                        $clients = $result->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($clients as $client) {
                            echo "<option id='client_id' name='client_id' value='" . $client['client_id'] . "' class='search-option'>" . $client['client_name'] . "</option>";
                        }
                    }
                } catch (PDOException $e) {
                    echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                }
                ?>
            </select>
        </div>

        <div id="newClientFields" style="display: block;">
            <label for="new_client_name">Name:</label>
            <input type="text" id="new_client_name" name="new_client_name">

            <br>
            <label for="new_client_address">Rechnungsadresse:</label>
            <input type="text" id="new_client_address" name="new_client_address">
            <br>

            <label for="new_client_e_mail_address">E-Mail Adresse:</label>
            <input id="new_client_e_mail_address" name="new_client_e_mail_address" type="email">
            <br>

            <label for="new_client_location">Ort:</label>
            <input id="new_client_location" name="new_client_location" type="text">
            <br>

            <label for="new_client_company">Unternehmen:</label>
            <input id="new_client_company" name="new_client_company" type="text">
            <br>

            <label for="new_client_phone">Geschäftsnummer:</label>
            <input id="new_client_phone" name="new_client_phone" type="tel">
            <br>

            <label for="new_client_mobile">Mobilnummer:</label>
            <input id="new_client_mobile" name="new_client_mobile" type="tel">
            <br>

            <label>Geschlecht:</label>
            <div class="gender">
                <input id="gender_male" name="new_client_gender" type="radio" value="Männlich">
                <label for="gender_male">Männlich</label>
                <input id="gender_female" name="new_client_gender" type="radio" value="Weiblich">
                <label for="gender_female">Weiblich</label>
                <input id="gender_female" name="new_client_gender" type="radio" value="Familie">
                <label for="gender_female">Familie</label>
            </div>
        </div>

        <br>
        <?php
        $pID_settingQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_temp'";
        $pID_setting = $mysql->query($pID_settingQ)->fetchColumn();
        $countQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_count'";
        $count = $mysql->query($countQ)->fetchColumn();

        $currentTime = date("d.m.Y");

        $output = str_replace('!time', $currentTime, $pID_setting);
        $output = str_replace('!count', $count, $output);
        ?>
        <label for="project_id">Project ID:</label>
        <input id="project_id" name="project_id" value="<?php echo $output; ?>" required>
        <br>
        <label for="project_order">Auftrag:</label>
        <input id="project_order" name="project_order">
        <br>
        <label for="project_address">Adresse:</label>
        <input id="project_address" name="project_address">
        <br>
        <label for="project_description">Beschreibung:</label>
        <input id="project_description" name="project_description">
        <br>
        <label for="due_date">Abgabedatum:</label>
        <input type="date" id="due_date" name="due_date">
        <br>
        <label for="user_id">Betreuung:</label>
        <div class="select-wrapper">
            <input type="text" id="searchInput" class="search-input" onchange="searchUser(this)"
                placeholder="Suchen...">
            <select id="user_id" name="user_id" class="custom-select" required>
                <?php

                try {
                    $selectQuery = "SELECT * FROM user";
                    $result = $mysql->query($selectQuery);

                    if ($result->rowCount() > 0) {
                        $users = $result->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($users as $user) {
                            if ($user['user_name'] != $user_name && $user['permission_level'] != 1) {
                                echo "<option id='user_id' name='user_id' value='" . $user['user_id'] . "' class='search-option'>" . $user['user_name'] . "</option>";
                            }
                        }
                    }
                } catch (PDOException $e) {
                    echo "<script>console.log('Fehler beim Abrufen der Daten:" . $e->getMessage() . "');</script>";
                }
                ?>
            </select>
        </div>
        <br>
        <button type="submit">Projekt erstellen</button>
    </form>

    <h2>Projektliste</h2>

    <div class="projectSearch">
        <input type="text" id="projectSearchInput" class="filter_search-input" placeholder="Projekt-ID oder Projektname"
            onchange="filterProjects(this)">

        <div class="filter_select-wrapper">
            <input type="text" id="projectSearchInput" class="filter_search-input" placeholder="Suchen..."
                oninput="searchProjectField(this, 'client_name_select')">
            <select id="client_name_select" name="client_name_select" class="filter_custom-select"
                onchange="searchProject(this, 2)">
                <option class='filter_search-option' value="" selected>Alle Kunden</option>
                <?php
                try {
                    $selectQuery = "SELECT * FROM client";
                    $result = $mysql->query($selectQuery);

                    if ($result->rowCount() > 0) {
                        $clients = $result->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($clients as $client) {
                            echo "<option value='" . $client['client_name'] . "' class='filter_search-option'>" . $client['client_name'] . "</option>";
                        }
                    }
                } catch (PDOException $e) {
                    echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                }
                ?>
            </select>
        </div>

        <div class="filter_select-wrapper">
            <input type="text" id="projectSearchInput" class="filter_search-input" placeholder="Suchen..."
                oninput="searchProjectField(this, 'user_name_select')">
            <select id="user_name_select" name="user_name_select" class="filter_custom-select"
                onchange="searchProject(this, 3)">
                <option class='filter_search-option' value="" selected>Alle Benutzer</option>
                <?php
                try {
                    $selectQuery = "SELECT * FROM `user`";
                    $result = $mysql->query($selectQuery);

                    if ($result->rowCount() > 0) {
                        $users = $result->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($users as $user) {
                            if ($user['user_name'] != $user_name && $user['permission_level'] != 1) {
                                echo "<option value='" . $user['user_name'] . "' class='filter_search-option'>" . $user['user_name'] . "</option>";
                            }
                        }
                    }
                } catch (PDOException $e) {
                    echo "Fehler beim Abrufen der Daten: " . $e->getMessage();
                }
                ?>
            </select>
        </div>

        <div class="filter_select-wrapper">
            <select onchange="sortProjects(this.value)">
                <option value="id_asc" selected>Projekt-ID ▲</option>
                <option value="id_desc">Projekt-ID ▼</option>
                <option value="name_asc">Projektname A–Z</option>
                <option value="name_desc">Projektname Z–A</option>
                <option value="date_asc">Abgabedatum früh → spät</option>
                <option value="date_desc">Abgabedatum spät → früh</option>
            </select>
        </div>

       <form method="POST" action="update_filter.php" id="filterForm" class="filter_search_filter">


            <label>
                <input type="checkbox" name="status[]" value="in_progress"
                <?= in_array('in_progress', $selectedStatuses) ? 'checked' : '' ?>
                onchange="this.form.submit()">
                In Bearbeitung
            </label>

            <label>
                <input type="checkbox" name="status[]" value="completed"
                <?= in_array('completed', $selectedStatuses) ? 'checked' : '' ?>
                onchange="this.form.submit()">
                Abgeschlossen
            </label>

            <label>
                <input type="checkbox" name="status[]" value="invoice_sent"
                <?= in_array('invoice_sent', $selectedStatuses) ? 'checked' : '' ?>
                onchange="this.form.submit()">
                Rechnung gestellt
            </label>

            <label>
                <input type="checkbox" name="status[]" value="paid"
                <?= in_array('paid', $selectedStatuses) ? 'checked' : '' ?>
                onchange="this.form.submit()">
                Bezahlt
            </label>

            <label>
                <input type="checkbox" name="status[]" value="archived"
                <?= in_array('archived', $selectedStatuses) ? 'checked' : '' ?>
                onchange="this.form.submit()">
                Archiv
            </label>

        </form>
        </form>

        <button id="clearProject" onclick="clearProjectFilter();">Löchen</button>
    </div>

    <table id="projectTable">
        <tr>
            <th>Projektnummer</th>
            <th>Projektname</th>
            <th>Auftraggeber</th>
            <th>Betreuung</th>
            <th>Aufträge</th>
            <th>Status</th>
            <th>Abgabedatum</th>
            <?php if (3 <= $_SESSION["permission_level"]) { ?>
                <th>Aktionen</th>
            <?php } ?>
        </tr>
        <?php
        if ($projects) {
            foreach ($projects as $project) {
                $projectId = $project['project_id'];
        ?>
                <tr class="project-row status-<?php echo $project['project_status']; ?>"
    data-status="<?php echo $project['project_status']; ?>">
                    <td><?php echo $projectId; ?></td>
                    <td><a href="projects/?id=<?php echo $projectId; ?>"><?php echo $project['project_name']; ?></a></td>
                    <td><a href="clients/?id=<?php echo $project['client_id']; ?>"><?php echo $project['client_name']; ?></a></td>
                    <td><a href="users/?id=<?php echo $project['user_name']; ?>"><?php echo $project['user_name']; ?></a></td>
                    <td>
                        <ul>
                            <?php if (isset($orders[$projectId])) {
                                foreach ($orders[$projectId] as $order) {
                                    if ($order['order_order'] == "") { ?>
                                        <li>N/A</li>
                                    <?php } else { ?>
                                        <li><?php echo $order['order_order']; ?></li>
                            <?php }
                                }
                            } ?>
                        </ul>
                    </td>

                    <td class="status-<?php echo $project['project_status']; ?>">
                        <?php
                        if (isset($orders[$projectId])) {
                            $totalOrders = count($orders[$projectId]);
                            $completedOrders = 0;

                            foreach ($orders[$projectId] as $order) {
                                if ($order['order_checked'] == "checked") {
                                    $completedOrders++;
                                }
                            }

                            $progress = ($totalOrders > 0) ? ($completedOrders / $totalOrders) * 100 : 0;
                            $progress = round($progress, 1);
                            $progressBarId = "progress-bar-bar-$projectId";
                            $progressTextId = "progress-bar-text-$projectId";

                            $progressBar= '
                            <div class="progress-bar-wrapper">
                                <div class="progress-bar-container">
                                    <div id="' . $progressBarId . '" class="progress-bar-bar">
                                        <span id="' . $progressTextId . '" class="progress-bar-text">0%</span>
                                    </div>
                                </div>
                                <div class="progress-info">
                                    <a>' . $completedOrders . "/" . $totalOrders . '</a>
                                </div>
                            </div>

                            <script>
                                updateProgressBar(' . $progress . ', "' . $progressBarId . '", "' . $progressTextId . '");
                            </script>

                        ';

                        } else {
                            echo 'Keine Daten';
                        }

                        echo match ($project['project_status']) {
                            'in_progress', 'completed' => $progressBar,

                            'invoice_sent' => 'Rechnung gestellt<br><small>am ' .
                                date('d.m.Y', strtotime($project['invoice_sent_date'])) .
                                '</small>',

                            'paid' => 'Bezahlt<br><small>am ' .
                                date('d.m.Y', strtotime($project['invoice_paid_date'])) .
                                '</small>',

                            'archived' => 'Archiviert<br><small>am ' .
                                date('d.m.Y', strtotime(
                                    $project['invoice_paid_date'] ?? $project['archived_date']
                                )) .
                                '</small>',
                        };

                        ?>
                    </td>
                    <td><?php if (($project['project_status'] === 'in_progress' || $project['project_status'] === 'completed') && $project['project_due_date'] !== '') { 
                        echo date('d.m.Y', strtotime($project['project_due_date'])); 
                    }else{ ?>
                    <p>---</p>
                    <?php } ?></td>

                    <?php if (3 <= $_SESSION["permission_level"]) { ?>
                    <td>
                        <div class="space">
                            <?php if ($project['project_status'] === 'in_progress' || $project['project_status'] === 'completed') { ?>
                                <!-- Rechnung erstellen -->
                                <form class="write_bill" action="write_bill" method="get">
                                    <input type="hidden" name="id" value="<?php echo $projectId; ?>">
                                    <input type="hidden" name="back" value="back">
                                    <button class="link" type="submit">Rechnung erstellen</button>
                                </form>
                            <?php } ?>
                            <?php if ($project['project_status'] === 'completed') { ?>
                                <br>
                                <!-- Rechnung gestellt -->
                                <form action="update_project_status.php" method="post">
                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                    <input type="hidden" name="action" value="toggle_invoice_sent">
                                    <button class="link" type="submit">Rechnung gestellt</button>
                                </form>
                            <?php } ?>
                            <?php if ($project['project_status'] === 'invoice_sent') { ?>
                                <br>
                                <!-- Rechnung als bezahlt markieren -->
                                <form action="update_project_status.php" method="post">
                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                    <input type="hidden" name="action" value="toggle_paid">
                                    <button class="link" type="submit">Rechnung bezahlt</button>
                                </form>
                            <?php } ?>
                            <?php if ($project['project_status'] === 'paid') { ?>
                                <br>
                                <!-- Rechnung als bezahlt markieren -->
                                <form action="update_project_status.php" method="post">
                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                    <input type="hidden" name="action" value="archive">
                                    <button class="link" type="submit">Archivieren</button>
                                </form>
                            <?php } ?>
                            <?php if ($project['project_status'] === 'archived') { ?>
                                <br>
                                <!-- Rechnung als bezahlt markieren -->
                                <form action="update_project_status.php" method="post">
                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                    <input type="hidden" name="action" value="unarchive">
                                    <button class="link" type="submit">Archivierung aufheben</button>
                                </form>
                            <?php } ?>

                            <?php if ($project['project_status'] === 'in_progress' || $project['project_status'] === 'completed') { ?>
                                <br>
                                <!-- Löschen immer erlaubt außer Archiv -->
                                <form class="delete_project"
                                    action="delete_project.php"
                                    method="post"
                                    onsubmit="return confirm('Bist du sicher, dass du dieses Projekt wirklich löschen willst?\n\nDieser Vorgang kann NICHT rückgängig gemacht werden!');">
                                    
                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                    <button class="link danger" type="submit">
                                        Projekt löschen
                                    </button>
                                </form>

                            <?php } ?>

                        </div>
                    </td>
                    <?php } ?>
                </tr>
        <?php }
        } ?>
    </table>
    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>

</body>

</html>