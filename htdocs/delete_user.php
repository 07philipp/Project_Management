<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
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
include 'mysql.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];

    try {
        // Check if user has projects
        $projectsQuery = "SELECT COUNT(*) FROM project WHERE project_user_id = :user_id";
        $stmt = $mysql->prepare($projectsQuery);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $projectCount = $stmt->fetchColumn();

        if ($projectCount > 0) {
            $_SESSION['error_message'] = "Nutzer kann nicht gelöscht werden, da Projekte vorhanden sind.";
            echo "<script>window.history.back();</script>";
            exit;
        }

        $selectQuery = "SELECT user_name FROM user WHERE user_id = :user_id";
        $stmt = $mysql->prepare($selectQuery);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($users) {
            $userName = $users['user_name'];

            $user_folder_path = "users/$userName";
            $user_index_file = "$user_folder_path/index.php";

            // Delete the client index.php file
            if (file_exists($user_index_file)) {
                unlink($user_index_file);
            }

            // Delete the client folder
            if (is_dir($user_folder_path)) {
                rmdir($user_folder_path);
            }

            $deleteQuery = "DELETE FROM user WHERE user_id = :user_id";
            $stmt = $mysql->prepare($deleteQuery);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            require_once "session_loguot.php";
            logout_user($user_id, $mysql);

            $_SESSION['error_message'] = "Nutzer wurde gelöcht.";
            require_once "log.php";
            logSQL($mysql, $_SESSION['username'], "delete user $userName");
        }else{
            $_SESSION['error_message'] = "Nutzer nicht gefunden.";
        }
        header("Location: users/");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Fehler beim löchen des Nutzers:". $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Fehler beim löchen des Ordners:". $e->getMessage();
    }
}
exit;
