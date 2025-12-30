<?php
require_once 'config/config.php';
require_once 'controllers/SearchController.php';

$controller = new SearchController();
$controller->profile();
?>