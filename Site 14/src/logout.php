<?php
session_start();
// Destroy the session and clear all session data
session_unset();
session_destroy();

// Redirect the user to the login page
header("Location: login.php");
exit();
?>