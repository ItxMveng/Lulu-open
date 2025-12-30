<?php
require_once 'config/config.php';
require_once 'controllers/PageController.php';

$controller = new PageController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleContactForm();
} else {
    $controller->contact();
}
