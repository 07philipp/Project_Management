<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login/");
    exit;
}else{
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

function deleteDir(string $dirPath): void {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST["project_id"];
    try {
        $dir = __DIR__ . "/projects/uploads/" . $project_id;

        unlink($dir . "/.htaccess");

        deleteDir($dir);
        

        // Delete project from database
        $deleteQuery = "DELETE FROM project WHERE project_id = :project_id";
        $stmt = $mysql->prepare($deleteQuery);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();

        $Query = "DELETE FROM `order` WHERE order_project_id = :project_id";
        $stmt = $mysql->prepare($Query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();

        $Query = "DELETE FROM `time` WHERE project_id = :project_id";
        $stmt = $mysql->prepare($Query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();


        $_SESSION['error_message'] = "Project wurde gelöcht.";
        require_once "log.php";
        logSQL($mysql, $_SESSION['username'], "delete project $project_id");
        header("Location: \ ");
        exit;

    } catch (PDOException $e) {
        echo "<script>window.history.back();</script>";
        $_SESSION['error_message'] = "Error deleting project: " . $e->getMessage();
    } catch (Exception $e) {
        echo "<script>window.history.back();</script>";
        $_SESSION['error_message'] = "Error deleting project folder or file: " . $e->getMessage();
    }
}
