<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

$type = $_GET['type'] ?? 'client';
$step = $_GET['step'] ?? '1';

header("Location: views/auth/register.php?type=$type&step=$step");
exit;
?>