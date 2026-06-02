<?php
session_start();
if (!isset($_SESSION["username"])) {
    $current_path = $_SERVER['REQUEST_URI'];

    header("Location: ../login/?b=" . urlencode($current_path));
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 3) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
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

    return "<span class='status-badge status-$status$size'>$text</span>";
}


$show_user = false;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {

    $userId = $_GET['id'];

    $userQuery = "SELECT user_id, user_name, permission_level FROM `user` WHERE user_name = :id";
    $stmt = $mysql->prepare($userQuery);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $userName = $userData['user_name'];
        $userPermission = $userData['permission_level'];
        $userProjectID = $userData['user_id'];
    } else {
        echo 'Error: Unable to retrieve user data';
        exit;
    }
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($userName); ?></title>
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="../css/form.css">
        <script src="../js/toggle.js"></script>
        <script src="../js/option_search.js"></script>
    </head>

    <body>
        <div class="users-container">
            <h1><?php echo htmlspecialchars($userName); ?></h1>
            <p>Benutzer-ID: <?php echo htmlspecialchars($userData['user_id']); ?></p>
            <p>Benutzername: <?php echo htmlspecialchars($userName); ?></p>
            <p>Berechtigungsstufe: <?php echo htmlspecialchars($userPermission); ?></p>

            <h2>Projekte:</h2>
            <ul>
                <?php
                $query = "SELECT * FROM project WHERE project_user_id = :userId";
                $stmt = $mysql->prepare($query);
                $stmt->bindParam(':userId', $userProjectID);
                $stmt->execute();
                if ($stmt->rowCount() == 0) {
                    echo 'Keine Projekte gefunden';
                } else {
                    while ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if($project['project_status'] != "archived"){
                            echo "<li><a href='../projects/?id=".$project['project_id'] ."'>
                            " . htmlspecialchars($project['project_name']) . "</a>"
                            . renderProjectStatusBadge($project['project_status']) . "</li>";
                        }
                    }
                }
                ?>
            </ul>

            <h2>Neues Projekt erstellen:</h2>
            <form class="projectForm" id="projectForm" action='../add_project.php' method='post'>
                <input type='hidden' name='user_id' value='<?php echo $userId; ?>'>
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
                        <input id="gender_male" name="new_client_gender" type="radio" value="Männlich" required>
                        <label for="gender_male">Männlich</label>
                        <input id="gender_female" name="new_client_gender" type="radio" value="Weiblich">
                        <label for="gender_female">Weiblich</label>
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
                <input type="date" value="<?php $date = date("Y-m-d", time());
                                            echo "$date"; ?>" id="due_date" name="due_date">
                <br>
                <button type="submit">Projekt erstellen</button>

            </form>

            <br>
            <div>
                <form class="delete_use" action="../delete_user.php" method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <button class="link" type="submit">Benutzer löschen</button>
                </form>
                <button onclick="location.href='../users/'">Benutzer</button>
                <button onclick="location.href='../'">Zurück zur Startseite</button>
            </div>
        </div>
    </body>

    </html>
<?php exit;} 


$userQuery = "SELECT u.user_id, u.user_name, u.permission_level 
                    FROM user u";
$userResult = $mysql->query($userQuery);

$projectsQuery = "SELECT p.project_id, p.project_name, u.user_id 
                    FROM project p 
                    JOIN user u ON p.project_user_id = u.user_id";
$projectsResult = $mysql->query($projectsQuery);

$ordersQuery = "SELECT o.order_id, o.order_project_id, o.order_order, o.order_amount, o.order_checked, p.project_user_id
                    FROM `order` o 
                    JOIN project p ON p.project_id = o.order_project_id";
$ordersResult = $mysql->query($ordersQuery);

$users = array();
$projects = array();
$orders = array();


if ($userResult && $projectsResult && $ordersResult) {
    while ($user = $userResult->fetch(PDO::FETCH_ASSOC)) {
        $users[$user['user_id']] = $user;
    }

    while ($project = $projectsResult->fetch(PDO::FETCH_ASSOC)) {
        $projects[$project['user_id']][] = $project;
    }

    while ($order = $ordersResult->fetch(PDO::FETCH_ASSOC)) {
        $orders[$order['project_user_id']][] = $order;
    }
}


?>

<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutzer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/search.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/project_search.js"></script>
    <script src="../js/update.js"></script>
    <script src="../js/notification.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var errorMessage = "<?php echo isset($_SESSION['error_message']) ? addslashes($_SESSION['error_message']) : ''; ?>";
            if (errorMessage) {
                showNotification(errorMessage);
                <?php unset($_SESSION['error_message']); ?>
            }
        });

        function updatePermission(userId, newPermission) {
            if (confirm(`Bist du sicher das du die berechtigung zu ${newPermission} ändern möchtest?`)) {

                var params = new URLSearchParams();
                params.append("user_id", userId);
                params.append("permission", newPermission);

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "../update_permission.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.addEventListener("load", function() {
                    var errorMessage = xhr.responseText;
                    if (errorMessage) {
                        showNotification(errorMessage);
                    }
                });
                xhr.send(params.toString());
            }


        }
    </script>
</head>

<body>
    <h1>Nutzer</h1>
    <a class="space back-to-home-link" href="../">Zurück zur Hauptseite</a>
    <a class="space back-to-home-link" href="../admin/">Admin Panel</a>


    <div class="projectSearch" style="max-width: 335px;">
        <div class="filter_select-wrapper">
            <input type="text" id="projectSearchInput" class="filter_search-input" placeholder="Suchen..."
                oninput="searchProjectField(this, 'user_name_select')">
            <select id="user_name_select" name="user_name_select" class="filter_custom-select"
                onchange="searchProject(this, 0)">
                <option class='filter_search-option' value="" selected>Alle Benutzer</option>
                <?php
                try {
                    $selectQuery = "SELECT * FROM user";
                    $result = $mysql->query($selectQuery);

                    if ($result->rowCount() > 0) {
                        $users = $result->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($users as $user) {
                            if ($user['user_name'] != $user_name) {
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
    </div>
    <table id="projectTable">
        <tr>
            <th>Nutzername</th>
            <th>Nutzerfortschritt</th>
            <th>Projekte</th>
            <th>Berechtigung</th>
            <th>Aktionen</th>
        </tr>
        <?php foreach ($users as $user) {
            $userId = $user['user_id']; ?>
            <tr class="project-row" <?php if ($_SESSION["username"] == $user['user_name']) {
                                        echo "style='background-color: #fff;'";
                                    } ?>>
                <td><?php
                    if ($user['user_name'] != $user_name) { ?>
                        <a href="?id=<?php echo $user['user_name']; ?>"><?php echo htmlspecialchars($user['user_name']); ?></a>
                </td>
                <td>

                    <?php
                        if ($user['permission_level'] != 1) {
                            if (isset($projects[$userId])) {
                                $totalOrders = count($orders[$userId]);
                                $completedOrders = 0;

                                foreach ($orders[$userId] as $order) {
                                    if ($order['order_checked'] == "checked") {
                                        $completedOrders++;
                                    }
                                }


                                $progress = ($totalOrders > 0) ? ($completedOrders / $totalOrders) * 100 : 0;
                                $progress = round($progress, 1);
                                $progressBarId = "progress-bar-bar-$userId";
                                $progressTextId = "progress-bar-text-$userId";
                    ?>

                            <div class="progress-bar-wrapper">
                                <div class="progress-bar-container">
                                    <div id="<?php echo $progressBarId; ?>" class="progress-bar-bar">
                                        <span id="<?php echo $progressTextId; ?>" class="progress-bar-text">0%</span>
                                    </div>
                                </div>
                                <div class="progress-info">
                                    <a><?php echo $completedOrders . "/" . $totalOrders; ?></a>
                                </div>
                            </div>

                            <script>
                                updateProgressBar(<?php echo $progress; ?>, "<?php echo $progressBarId; ?>", "<?php echo $progressTextId; ?>");
                            </script>

                    <?php
                            } else {
                                echo 'Keine Projecte';
                            }
                        } else {
                            echo 'Keine Projecte';
                        }
                    ?>


                </td>
            <?php } else {
                        echo "$user_name
                    <td></td>";
                    } ?>
            <td>
                <ul>
                    <?php if (isset($projects[$userId])) {
                        foreach ($projects[$userId] as $project) { ?>
                            <li><a
                                    href="../projects/?id=<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></a>
                            </li>
                    <?php }
                    } else {
                        echo "Keine Projekte gefunden";
                    } ?>
                </ul>
            </td>
            <td>
                <?php
                if ($user['user_name'] != $user_name) {
                ?>
                    <select id="permission" name="permission"
                        onchange="updatePermission(<?php echo $userId; ?>, this.value);">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($user['permission_level'] == $i) ? 'class="used" selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                <?php
                } else {
                    echo "5";
                }
                ?>
            </td>
            <td>
                <?php
                if ($user['user_name'] != $user_name) {
                ?>
                    <form class="delete_user" action="../delete_user.php" method="post" style="display:inline;" 
                    onsubmit="return confirm('Bist du sicher, dass du diesen Nutzer wirklich löschen willst?\n\nDieser Vorgang kann NICHT rückgängig gemacht werden!');">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        <button class="space link" type="submit">Nutzer löschen</button>
                    </form>

                    <a class="space link" href="../change_password_admin/?id=<?php echo $userId; ?>">
                        Passwort ändern
                    </a>

                    <?php
                    require_once '../session_loguot.php';
                    if (is_user_logged_in($userId, $mysql)) {
                    ?>
                        <form class="logout_user" action="../session_loguot.php" method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                            <button class="space link" type="submit">Nutzer abmelden</button>
                        </form>
                <?php
                    }
                }
                ?>
            </td>
            </tr>
        <?php } ?>
    </table>
    <div id="notification" class="notification" onclick="hideNotification()">
        <p id="notification-message"></p>
        <div id="progress-bar" class="progress-bar"></div>
    </div>
</body>

</html>