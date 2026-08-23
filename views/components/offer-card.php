<?php
$offer = $offer ?? [];
$type = (string) ($offer['type'] ?? 'emploi');
$typeLabels = ['emploi' => 'Emploi', 'mission' => 'Mission', 'stage' => 'Stage'];
?>
<div class="card card-hover h-100">
    <div class="card-body p-4 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <span class="badge badge-soft-primary"><i class="bi bi-briefcase me-1"></i><?= e(t($typeLabels[$type] ?? ucfirst($type))) ?></span>
            <?php if (!empty($offer['remote_ok'])): ?>
                <span class="badge badge-soft-success"><i class="bi bi-house-door me-1"></i><?= t('Télétravail') ?></span>
            <?php endif; ?>
        </div>
        <h3 class="h6 mb-1"><?= e((string) ($offer['title'] ?? 'Offre')) ?></h3>
        <p class="text-secondary small mb-3">
            <i class="bi bi-geo-alt me-1"></i><?= e((string) ($offer['location'] ?? t('Non précisé'))) ?>
            <?php if (!empty($offer['contract_type'])): ?> · <?= e((string) $offer['contract_type']) ?><?php endif; ?>
        </p>
        <p class="text-secondary small flex-grow-1 mb-3"><?= e(mb_strimwidth((string) ($offer['description'] ?? ''), 0, 140, '…')) ?></p>
        <a class="btn btn-outline-primary btn-sm align-self-start" href="<?= e(url('/offres/' . (int) ($offer['id'] ?? 0))) ?>"><?= t('Voir l\'offre') ?> <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
</div>
