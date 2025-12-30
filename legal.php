<?php
require_once 'config/config.php';
require_once 'controllers/PageController.php';

$controller = new PageController();
$controller->legal();
