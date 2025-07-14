<?php
session_start();

// Destroy all session data to log the user out
session_unset();
session_destroy();

// Redirect to the login page
header("Location: homepage.html");
exit();
?>
