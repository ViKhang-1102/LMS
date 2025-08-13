<?php
include_once '../includes/auth.php';

session_unset();
session_destroy();

header("Location: /auth/login.php?success=logged_out");
exit();
?>