<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ../login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}

if ($_SESSION["permission_level"] == 2) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $project_id = $_GET['id'];
        include("../mysql.php");

        $projectStmt = $mysql->prepare(
            'SELECT project_user_id FROM project WHERE project_id = :project_id'
        );
        $projectStmt->execute([':project_id' => $project_id]);
        if ($projectStmt) {
            $projectData = $projectStmt->fetch(PDO::FETCH_ASSOC);
            $projectUserId = $projectData['project_user_id'];
            if ($projectUserId != $_SESSION['user_id']) {
                echo "Keine berechtigung.";
                exit;
            }
        }
    }
} elseif ($_SESSION["user_id"] == 1) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
} else {
    $project_id = $_GET['id'];
    include("../mysql.php");
}

$timeStmt = $mysql->prepare(
    'SELECT u.user_id, u.user_name, o.order_order, t.start_time, t.end_time, t.duration, t.order_id, t.time_id
     FROM `time` t
     JOIN `user` u ON t.user_id = u.user_id
     JOIN `order` o ON t.order_id = o.order_id
     WHERE t.project_id = :project_id'
);
$timeStmt->execute([':project_id' => $project_id]);
$timeResult = $timeStmt;

$times = array();


if ($timeResult) {
    while ($time = $timeResult->fetch(PDO::FETCH_ASSOC)) {
        $times[$time['time_id']] = $time;
    }
}
?>

<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/notification.css">
    <script src="../js/notification.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch error message from PHP session
            var errorMessage = <?= pm_json_script(pm_take_flash_message()) ?>;
            if (errorMessage) {
                showNotification(errorMessage);
            }
        });

        function saveChanges(inputField, projectId) {
            var params = new URLSearchParams();
            params.append("id", projectId);
            params.append(inputField.name, inputField.value);
            params.append("t_id", inputField.id);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../edit_time.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.addEventListener("load", function() {
                console.log(xhr.responseText);
            });
            xhr.send(params.toString());
        }
    </script>
    <title>Arbeitszeit bearbeiten </title>
</head>

<body>
    <h1>Arbeitszeit bearbeiten</h1>
    <table>
        <tr>
            <th>Nutzer</th>
            <th>Auftrag</th>
            <th>Startzeit</th>
            <th>Endzeit</th>
            <th>Arbeitszeit in Min</th>
            <th>Aktionen</th>
        </tr>
        <?php
        if ($times) {
            foreach ($times as $time) {
                if ($time['end_time'] !== null) { ?>
                    <tr>
                        <td><a href="../users/?id=<?php echo h($time['user_name']); ?>"><?php echo h($time['user_name']); ?></a></td>
                        <td><?php echo h($time['order_order']); ?></td>

                        <td><input name="start" id="<?php echo h($time['time_id']); ?>" type="datetime-local"
                                value="<?php echo h(date('Y-m-d\TH:i', strtotime($time['start_time']))); ?>"
                                onchange="saveChanges(this, <?php echo pm_json_script($project_id); ?>)"></td>

                        <td><input name="end" id="<?php echo h($time['time_id']); ?>" type="datetime-local"
                                value="<?php echo h(date('Y-m-d\TH:i', strtotime($time['end_time']))); ?>"
                                onchange="saveChanges(this, <?php echo pm_json_script($project_id); ?>)"></td>
                        <td><?php echo h($time['duration']); ?></td>
                        <td>
                            <form action="../delete_time.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo h($time['time_id']); ?>">
                                <input type="hidden" name="p_id" value="<?php echo h($project_id); ?>">
                                <button type="submit">Löchen</button>
                            </form>
                        </td>
                    </tr>
        <?php
                }
            }
        }
        ?>
    </table>
    <br>
    <a class="link" href="../projects/?id=<?php echo h($project_id); ?>">Fertig</a>
</body>

</html>