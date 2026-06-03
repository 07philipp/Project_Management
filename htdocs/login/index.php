<?php
session_start();

// Check if session exists
if (isset($_SESSION["username"])) {
  header("Location: ../");
  exit;
}

// Check if cookie exists
if (isset($_COOKIE['username'])) {
  $username = $_COOKIE['username'];
  
  define('INCLUDE_GUARD', true);
  require_once '../mysql.php';

  $stmt = $mysql->prepare("SELECT * FROM user WHERE user_name = :username");
  $stmt->bindParam(":username", $username);
  $stmt->execute();
  $user = $stmt->fetch();

  if ($user) {
    $_SESSION['username'] = $username;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['permission_level'] = $user['permission_level'];

    // Save session to database
    $session_id = session_id();
    $stmt = $mysql->prepare("INSERT INTO user_sessions (session_id, user_id) VALUES (:session_id, :user_id) ON DUPLICATE KEY UPDATE last_activity = NOW()");
    $stmt->bindParam(':session_id', $session_id);
    $stmt->bindParam(':user_id', $user['user_id']);
    $stmt->execute();
    header("Location: ../");
    exit;
  }
}

// Destroy session if cookie and session are not valid
session_destroy();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['b'])) {
  $back = $_GET['b'];
}
?>

<!DOCTYPE html>
<html lang="de" dir="ltr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/login.css">
  <link rel="stylesheet" href="../css/notification.css">
  <script src="../js/notification.js"></script>
  <script src="../js/password.js"></script>
  <title>Login</title>
</head>

<body>
  <div class="container">
    <?php
    define('INCLUDE_GUARD', true);
    require_once '../mysql.php';

    // Login form submission handling
    if (isset($_POST['username']) && isset($_POST['password'])) {
      $username = $_POST['username'];
      $password = $_POST['password'];
      $remember = isset($_POST['remember']) ? true : false;

      // Verify user credentials using the mysql.php functions
      $stmt = $mysql->prepare("SELECT * FROM user WHERE user_name = :username");
      $stmt->bindParam(":username", $username);
      $stmt->execute();
      $user = $stmt->fetch();

      if ($user && password_verify($password, $user['password'])) {
        // Login successful, create a session and redirect
        session_start();
        $_SESSION['high_admin_name'] = $high_admin_name;
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['permission_level'] = $user['permission_level']; // Store permission level
    
        if ($remember) {
          // Set a cookie to keep the user logged in
          setcookie('username', $username, time() + (86400 * 30), "/"); // 30 Tage
        } else {
          // Clear the cookie if not remembering
          setcookie('username', '', time() - 3600, "/");
        }

        // Save session to database
        $session_id = session_id();
        $stmt = $mysql->prepare("INSERT INTO user_sessions (session_id, user_id) VALUES (:session_id, :user_id) ON DUPLICATE KEY UPDATE last_activity = NOW()");
        $stmt->bindParam(':session_id', $session_id);
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();

        if (isset($_POST['back'])) {
          $back = $_POST['back'];
          // Überprüfen, dass die URL nicht mit http:// oder https:// beginnt (um externe Umleitungen zu verhindern)
          if (strpos($back, 'http://') === false && strpos($back, 'https://') === false) {
            // Weiterleitung zur gewünschten Seite
            header("Location: ..$back");
            exit;
          } else {
            // Fallback: Weiterleitung zur Startseite, falls der Parameter unsicher ist
            header("Location: ../");
            exit;
          }
        } else {
          // Weiterleitung zur Startseite, wenn keine GET-Variable oder Methode unzulässig ist
          header("Location: ../");
          exit;
        }
      } else {
        // Login failed, display error message
        echo "<script>
          document.addEventListener('DOMContentLoaded', function() {
              showNotification('Benutzer oder Password falsch');
          });</script>";
      }
    }
    ?>
    <div id="form-container">
      <h1>Login</h1>
      <form method="post">
        <input type="hidden" name="back" value="<?php echo isset($back) ? h($back) : ''; ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder="Username" required><br>
        <label for="password">Password:</label>
        <div style="position: relative;">
          <input type="password" id="password" name="password" placeholder="Passwort" required>
          <span id="togglePw1" onclick="togglePasswordLogin()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">show</span>
        </div>
        <div class="comb-container">
          <label for="remember">Angemeldet bleiben</label>
          <input type="checkbox" id="remember" name="remember">
        </div><br>
        <button type="submit">Login</button>
        <div id="notification" class="notification" onclick="hideNotification()">
          <p id="notification-message"></p>
          <div id="progress-bar" class="progress-bar"></div>
        </div>
      </form>
    </div>
  </div>
  <div id="notification" class="notification" onclick="hideNotification()">
    <p id="notification-message"></p>
    <div id="progress-bar" class="progress-bar"></div>
  </div>
</body>

</html>
