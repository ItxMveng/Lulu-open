<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/sidebar.php';
require_once '../../controllers/PaymentController.php';

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../login.php');
    exit;
}

// Redirection vers la nouvelle interface de paiement Stripe
header('Location: ../../views/payments.php');
exit;
?>