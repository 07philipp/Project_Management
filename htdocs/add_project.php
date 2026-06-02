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
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include 'mysql.php';
    require_once "log.php";

    $projectName = $_POST["project_name"];
    $projectOrder = isset($_POST["project_order"]) ? $_POST["project_order"] : '';
    $projectDescription = isset($_POST["project_description"]) ? $_POST["project_description"] : '';
    $projectAddress = isset($_POST["project_address"]) ? $_POST["project_address"] : '';
    $projectId = $_POST["project_id"];
    $dueDate = isset($_POST["due_date"]) ? $_POST["due_date"] : '';
    $user = isset($_POST["user_id"]) ? $_POST["user_id"] : '';
    $projectAmount = "0";

    $sql = "SELECT COUNT(*) FROM `project` WHERE project_id = :id";
    $stmt = $mysql->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $exists = $stmt->fetchColumn();


    if ($exists > 0) {
        $errors[] = "Projekt ID bereits verwendet.";
    }
    if (empty($projectId)) {
        $errors[] = "Bitte geben Sie die Project ID an.";
    }
    if (!empty($errors)) {
        echo "<script>window.history.back();</script>";
        $_SESSION['error_message'] = $errors;
    }

    if ($_POST["client_id"] == "neu") {
        $clientId = time();
        $clientName = isset($_POST["new_client_name"]) ? trim($_POST["new_client_name"]) : '';
        $clientAddress = isset($_POST["new_client_address"]) ? trim($_POST["new_client_address"]) : '';
        $clientEMail = isset($_POST["new_client_e_mail_address"]) ? trim($_POST["new_client_e_mail_address"]) : '';
        $clientLocation = isset($_POST["new_client_location"]) ? trim($_POST["new_client_location"]) : '';
        $clientCompany = isset($_POST["new_client_company"]) ? trim($_POST["new_client_company"]) : '';
        $clientGender = isset($_POST["new_client_gender"]) ? trim($_POST["new_client_gender"]) : '';
        $clientPhone = isset($_POST["new_client_phone"]) ? trim($_POST["new_client_phone"]) : '';
        $clientMobile = isset($_POST["new_client_mobile"]) ? trim($_POST["new_client_mobile"]) : '';

        // Fehlerprüfungen
        $errors = [];
        if (empty($clientName)) {
            $errors[] = "Bitte geben Sie den Namen des Kunden an.";
        }


        // Fehler ausgeben
        if (!empty($errors)) {
            echo "<script>window.history.back();</script>";
            $_SESSION['error_message'] = $errors;
        }

        try {
            $insertQuery = "INSERT INTO client (client_id, client_name, client_address, client_e_mail, client_location, client_company, client_gender, client_phone, client_mobile) 
                    VALUES (:id, :name, :address, :mail, :location, :company, :gender, :phone, :mobile)";
            $stmt = $mysql->prepare($insertQuery);
            $stmt->bindParam(':id', $clientId);
            $stmt->bindParam(':name', $clientName);
            $stmt->bindParam(':address', $clientAddress);
            $stmt->bindParam(':mail', $clientEMail);
            $stmt->bindParam(':location', $clientLocation);
            $stmt->bindParam(':company', $clientCompany);
            $stmt->bindParam(':gender', $clientGender);
            $stmt->bindParam(':phone', $clientPhone);
            $stmt->bindParam(':mobile', $clientMobile);
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>window.history.back();</script>";
            $_SESSION['error_message'] = "Fehler beim Erstellen des neuen Kunden: " . $e->getMessage();
            exit;
        }
        logSQL($mysql, $_SESSION['username'], "createt user $clientId ($clientName)");
    } else {
        $clientId = $_POST["client_id"];
        $selectQuery = "SELECT * FROM client WHERE client_id = :client_id";
        $stmt = $mysql->prepare($selectQuery);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            $projectClient = $client["client_name"];
            $projectClientAddress = $client["client_address"];
        }
    }

    $pNum = time();
    $checked = "running";
    $settingQ = "SELECT setting FROM settings WHERE setting_type = 'hourly_wage'";
    $setting = $mysql->query($settingQ)->fetchColumn();

    $insertQuery = "INSERT INTO `order` (order_id, order_project_id, order_order, order_amount, order_hourly_wage, order_checked) 
    VALUES (:id, :project, :name, :address, :hourly_wage, :checked)";
    $stmt = $mysql->prepare($insertQuery);
    $stmt->bindParam(':id', $pNum);
    $stmt->bindParam(':project', $projectId);
    $stmt->bindParam(':name', $projectOrder);
    $stmt->bindParam(':address', $projectAmount);
    $stmt->bindParam(':hourly_wage', $setting);
    $stmt->bindParam(':checked', $checked);
    $stmt->execute();

    $insertQuery = "INSERT INTO `project` (project_id, project_name, project_client_id, project_address, project_description, project_user_id, project_due_date, project_created_date, project_status, completed_date, invoice_sent_date, invoice_paid_date) 
    VALUES (:project_id, :project_name, :project_client_id, :project_address, :project_description, :user, :due_date, :created_date, 'in_progress', NULL, NULL, NULL)";
    $stmt = $mysql->prepare($insertQuery);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':project_name', $projectName);
    $stmt->bindParam(':project_client_id', $clientId);
    $stmt->bindParam(':project_address', $projectAddress);
    $stmt->bindParam(':project_description', $projectDescription);
    $stmt->bindParam(':user', $user);
    $stmt->bindParam(':due_date', $dueDate);
    $stmt->bindParam(':created_date', $pNum);
    $stmt->execute();


    $pID_settingQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_temp'";
    $pID_setting = $mysql->query($pID_settingQ)->fetchColumn();
    $countQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_count'";
    $count = $mysql->query($countQ)->fetchColumn();

    $currentTime = date("d.m.Y");

    $output = str_replace('!time', $currentTime, $pID_setting);
    $output = str_replace('!count', $count, $output);

    if ($output == $projectId) {
        $countQ = "SELECT setting FROM settings WHERE setting_type = 'project_id_count'";
        $count = $mysql->query($countQ)->fetchColumn();
        $count++;

        $insertQuery = "UPDATE `settings` setting_type 
        SET setting = :setting 
        WHERE setting_type = 'project_id_count'";
        $stmt = $mysql->prepare($insertQuery);
        $stmt->bindParam(':setting', $count);
        $stmt->execute();
    }

    logSQL($mysql, $_SESSION['username'], "createt project $projectId ($projectName)");
    header("Location: projects/?id=$projectId");
}
