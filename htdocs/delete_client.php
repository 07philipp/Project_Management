<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
} else {
    define('INCLUDE_GUARD', true);
}
if ($_SESSION["permission_level"] <= 2) {
    echo "<script>
                        window.history.back();
                        console.log('Keine berechtigung.');
                      </script>";
    exit;
}
include 'mysql.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_id = $_POST["client_id"];

    try {
        // Check if client has projects
        $projectsQuery = "SELECT COUNT(*) FROM project WHERE project_client_id = :client_id";
        $stmt = $mysql->prepare($projectsQuery);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();
        $projectCount = $stmt->fetchColumn();

        if ($projectCount > 0) {
            $_SESSION['error_message'] = "Kunde kann nicht gelöscht werden, da Projekte vorhanden sind.";
            echo "<script>window.history.back();</script>";
            exit;
        }

        // Delete client from database
        $deleteQuery = "DELETE FROM client WHERE client_id = :client_id";
        $stmt = $mysql->prepare($deleteQuery);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();
        
        $_SESSION['error_message'] = "Kunde wurde gelöcht";
        require_once "log.php";
        logSQL($mysql, $_SESSION['username'], "delete client $client_id");
        header("Location: clients/");
        exit;
    } catch (PDOException $e) {
        echo "<script>window.history.back();</script>";
        $_SESSION['error_message'] = "Error deleting client: " . $e->getMessage();
    } catch (Exception $e) {
        echo "<script>window.history.back();</script>";
        $_SESSION['error_message'] = "Error deleting client folder or file: " . $e->getMessage();
    }
}
exit;
