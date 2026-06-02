<?php
session_start();
session_unset();
session_destroy();

// Remove the cookie if it exists
if (isset($_COOKIE['username'])) {
    setcookie('username', '', time() - 3600, "/");
}

header("Location: /");

