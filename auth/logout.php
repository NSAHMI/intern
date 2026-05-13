<?php
// Logout handler placeholder
session_start();
session_unset();
session_destroy();
header('Location: /intern/index.php');
exit;
