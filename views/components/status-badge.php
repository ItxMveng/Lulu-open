<?php
/** @var string $status */
$status = $status ?? 'en_attente';
$labels = [
    'en_attente' => 'En attente',
    'vue' => 'Vue',
    'entretien' => 'Entretien',
    'acceptee' => 'Acceptée',
    'rejetee' => 'Refusée',
];
$label = $labels[$status] ?? ucfirst($status);
?>
<span class="status-badge status-<?= e($status) ?>"><?= e($label) ?></span>
