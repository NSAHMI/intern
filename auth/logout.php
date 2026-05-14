<?php
/**
 * User Logout
 * Internship Management System
 */

session_start();
session_unset();
session_destroy();

header('Location: /index.php');
exit;
