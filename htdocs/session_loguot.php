<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION["user_id"])) {
        header("Location: login/");
        exit;
    } else {
        define('INCLUDE_GUARD', true);
    }
} else {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login/");
        exit;
    }
}
if ($_SESSION["permission_level"] <= 3) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'mysql.php';
    $user_id_to_logout = $_POST['user_id'];
    logout_user($user_id_to_logout, $mysql);
    require_once "log.php";
    logSQL($mysql, $_SESSION['username'], "user $user_id_to_logou logout");
    $_SESSION['error_message'] = "Nutzer wurde abgemeldet.";
}

function logout_user($user_id, $mysql)
{
    if (is_user_logged_in($user_id, $mysql)) {

        // Get all session IDs for the user
        $stmt = $mysql->prepare("SELECT session_id FROM user_sessions WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($sessions as $session_id) {
            // Only change session ID if no session is currently active
            if (session_status() == PHP_SESSION_ACTIVE) {
                session_write_close(); // Close the current session to avoid conflict
            }

            session_id($session_id); // Set the session ID to the target session
            session_start();         // Start the session for the target user
            session_destroy();       // Destroy the session
        }

        // Remove sessions from the database
        $stmt = $mysql->prepare("DELETE FROM user_sessions WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
}

function is_user_logged_in($userId, $mysql)
{
    // Prüfe, ob eine aktive Sitzung für den Benutzer existiert
    $stmt = $mysql->prepare("SELECT COUNT(*) FROM user_sessions WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    return $count > 0;  // Gibt true zurück, wenn der Benutzer eine aktive Sitzung hat
}