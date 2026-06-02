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
include "../mysql.php";
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>

<body>
    <h1>Log</h1>
    
    <a class="space back-to-home-link" href="../">Zurück zur Hauptseite</a>
    <a class="space back-to-home-link" href="../admin/">Admin Panel</a>

    <div class="log-container">
        <form method="POST" action="../log.php">
            <input type="hidden" name="action" value="down">
            <button type="submit" name="download">Logs downloaden</button>
        </form>
        <form method="POST" action="../log.php">
            <input type="hidden" name="action" value="clear">
            <button type="submit" name="clear" onclick="return confirm('Möchten die Logs leeren?')">Logs leeren</button>
        </form>
    </div>
    <?php
    $query = "SELECT user_id, action, time FROM `log`";
    $stmt = $mysql->prepare($query);
    $stmt->execute();

    // Alle Zeilen abrufen
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table>
        <tr>
            <th>Zeit</th>
            <th>Benutzer</th>
            <th>Aktion</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['time']); ?></td>
                <td><a href="../users/<?php echo $row['user_id']; ?>"><?php echo $row['user_id']; ?></a></td>
                <td><?php echo htmlspecialchars($row['action']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

</body>

</html>