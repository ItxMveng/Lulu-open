<?php
if (!isset($_SESSION['user_id'])) return;

require_once __DIR__ . '/../models/Subscription.php';
$subscription = new Subscription();
$status = $subscription->getUserSubscriptionStatus($_SESSION['user_id']);

if (!$status) return;

$showNotification = false;
$notificationClass = '';
$notificationMessage = '';

if ($status['subscription_status'] === 'Actif' && $status['subscription_end_date']) {
    $daysLeft = ceil((strtotime($status['subscription_end_date']) - time()) / 86400);
    
    if ($daysLeft <= 7 && $daysLeft > 0) {
        $showNotification = true;
        $notificationClass = 'subscription-warning';
        $notificationMessage = "⚠️ URGENT: Votre abonnement expire dans $daysLeft jours. <a href='/lulu/subscription.php' style='color:white;text-decoration:underline;'>Renouvelez maintenant</a>";
    } elseif ($daysLeft <= 0) {
        $showNotification = true;
        $notificationClass = 'subscription-expired';
        $notificationMessage = "❌ Votre abonnement a expiré. <a href='/lulu/subscription.php' style='color:white;text-decoration:underline;'>Renouveler</a>";
    }
}

if (isset($_SESSION['subscription_activated']) && $_SESSION['subscription_activated']) {
    $showNotification = true;
    $notificationClass = 'subscription-success';
    $notificationMessage = "🎉 Félicitations ! Votre abonnement est ACTIF jusqu'au " . date('d/m/Y', strtotime($status['subscription_end_date'])) . ". Commencez à utiliser toutes les fonctionnalités !";
    unset($_SESSION['subscription_activated']);
}
?>

<?php if ($showNotification): ?>
<style>
.subscription-notification {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    padding: 15px;
    text-align: center;
    font-weight: bold;
    z-index: 9999;
    animation: slideDown 0.5s ease;
}
.subscription-warning {
    background: #dc3545;
    color: white;
}
.subscription-expired {
    background: #721c24;
    color: white;
}
.subscription-success {
    background: #28a745;
    color: white;
}
@keyframes slideDown {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}
.close-notification {
    float: right;
    cursor: pointer;
    font-size: 20px;
    margin-left: 15px;
}
</style>

<div class="subscription-notification <?= $notificationClass ?>" id="subscriptionNotification">
    <span class="close-notification" onclick="document.getElementById('subscriptionNotification').style.display='none'">&times;</span>
    <?= $notificationMessage ?>
</div>
<?php endif; ?>
