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
include 'mysql.php'; // Include the database connection file
require_once "log.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $projectNumber = $_POST['projectNumber'];

    if (isset($_POST["addorder"])) {
        // Insert a new order into the database
        insertOrder($mysql, $projectNumber, 'Neuer Auftrag');
        $_SESSION['error_message'] = "Neuer Auftrag erfolgreich hinzugefügt.";
        logSQL($mysql, $_SESSION['username'], "add order $projectNumber");
    } elseif (isset($_POST["deleteorder"])) {
        // Delete an order from the database
        if (checkOrders($mysql, $projectNumber) > 1) {
            deleteOrder($mysql);
            $_SESSION['error_message'] = "Auftrag erfolgreich gelöcht.";
            logSQL($mysql, $_SESSION['username'], "delete order $projectNumber");
        }
    }

    // Redirect to the form page with data in the URL
    if (isset($_POST["back"])) {
        header("Location: edit_project/?id=$projectNumber");
    } else {
        header("Location: projects/?id=$projectNumber");
    }
    exit;
}

// Database function
function deleteOrder($conn)
{
    $query = "DELETE FROM `order` WHERE order_id = :orderId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':orderId', getHighestOrderId($conn));
    $stmt->execute();
}

function getHighestOrderId($conn)
{
    $query = "SELECT MAX(order_id) as highest_order_id FROM `order`";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $highestOrderId = $result['highest_order_id'] ?? 0;
    return $highestOrderId;
}

function insertOrder($conn, $projectNumber, $orderName)
{
    $settingQ = "SELECT setting FROM settings WHERE setting_type = 'hourly_wage'";
    $setting = $conn->query($settingQ)->fetchColumn();
    $orderId = time();
    $orderCheck = 'running';
    $query = "INSERT INTO `order` (order_id, order_project_id, order_order, order_amount, order_hourly_wage, order_checked) 
    VALUES (:order_id, :order_project_id, :order_order, 0, :order_hourly_wage, :order_checked)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':order_project_id', $projectNumber);
    $stmt->bindParam(':order_order', $orderName);
    $stmt->bindParam(':order_hourly_wage', $setting);
    $stmt->bindParam(':order_checked', $orderCheck);
    $stmt->execute();
}

function checkOrders($conn, $projectNumber)
{
    $query = "SELECT COUNT(*) as num_orders FROM `order` WHERE order_project_id = :projectNumber";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':projectNumber', $projectNumber);
    $stmt->execute();
    $result = $stmt->fetch();
    $numOrders = $result['num_orders'];
    return $numOrders;
}
