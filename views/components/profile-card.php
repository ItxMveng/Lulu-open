<?php
$profile = $profile ?? [];
$displayName = (string) ($profile['display_name'] ?? $profile['name'] ?? 'Profil');
$initials = mb_strtoupper(mb_substr(trim($displayName), 0, 1));
if (preg_match('/\s(\S)/u', trim($displayName), $m)) {
    $initials .= mb_strtoupper($m[1]);
}
$rate = $profile['hourly_rate'] ?? null;
?>
<div class="card card-hover h-100">
    <div class="card-body p-4 d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="avatar-circle"><?= e($initials) ?></span>
            <div class="min-w-0">
                <h3 class="h6 mb-0 text-truncate"><?= e($displayName) ?></h3>
                <p class="text-secondary small mb-0"><i class="bi bi-geo-alt me-1"></i><?= e((string) ($profile['location'] ?? 'Non précisé')) ?></p>
            </div>
            <?php if (isset($profile['ai_score'])): ?>
                <span class="badge badge-soft-success ms-auto"><i class="bi bi-stars me-1"></i><?= e((string) $profile['ai_score']) ?></span>
            <?php endif; ?>
        </div>
        <p class="text-secondary small flex-grow-1 mb-3"><?= e(mb_strimwidth((string) ($profile['bio'] ?? 'Aucune bio disponible.'), 0, 140, '…')) ?></p>
        <div class="d-flex justify-content-between align-items-center">
            <?php if ($rate !== null && $rate !== ''): ?>
                <span class="fw-semibold text-primary"><?= e((string) $rate) ?> €<span class="text-secondary small fw-normal">/h</span></span>
            <?php else: ?><span></span><?php endif; ?>
            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/profile/' . (int) ($profile['user_id'] ?? 0))) ?>">Voir le profil</a>
        </div>
    </div>
</div>
