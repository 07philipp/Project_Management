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
    if (!empty($_POST["new_client_name"])) {
        $clientId = time();
        $clientName = $_POST["new_client_name"];
        $clientAddress = isset($_POST["new_client_address"]) ? $_POST["new_client_address"] : '';
        $clientEMail = isset($_POST["new_client_e_mail_address"]) ? $_POST["new_client_e_mail_address"] : '';
        $clientLocation = isset($_POST["new_client_location"]) ? $_POST["new_client_location"] : '';
        $clientCompany = isset($_POST["new_client_company"]) ? $_POST["new_client_company"] : '';
        $clientGender = isset($_POST["new_client_gender"]) ? $_POST["new_client_gender"] : '';
        $clientPhone = isset($_POST["new_client_phone"]) ? trim($_POST["new_client_phone"]) : '';
        $clientMobile = isset($_POST["new_client_mobile"]) ? trim($_POST["new_client_mobile"]) : '';

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
        require_once "log.php";
        logSQL($mysql, $_SESSION['username'], "createt user $clientId ($clientName)");
        header("Location: clients/?id=$clientId");
        exit;
    } else {
        echo "<script>window.history.back();</script>";
        $_SESSION['error_message'] = "Bitte alle Felder ausfüllen.";
        exit;
    }
}
