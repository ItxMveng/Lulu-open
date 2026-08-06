<?php
$offer = $offer ?? [];
$type = (string) ($offer['type'] ?? 'emploi');
$typeLabels = ['emploi' => 'Emploi', 'mission' => 'Mission', 'stage' => 'Stage'];
?>
<section class="py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <a class="text-secondary small d-inline-flex align-items-center mb-3" href="<?= e(url('/search?tab=offres')) ?>"><i class="bi bi-arrow-left me-1"></i>Retour aux offres</a>
            <div class="card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge badge-soft-primary"><i class="bi bi-briefcase me-1"></i><?= e($typeLabels[$type] ?? ucfirst($type)) ?></span>
                        <?php if (!empty($offer['remote_ok'])): ?><span class="badge badge-soft-success"><i class="bi bi-house-door me-1"></i>Télétravail</span><?php endif; ?>
                        <span class="badge text-bg-secondary"><?= e((string) ($offer['status'] ?? 'active')) ?></span>
                    </div>
                    <h1 class="h3 mb-3"><?= e((string) ($offer['title'] ?? 'Offre')) ?></h1>
                    <div class="text-secondary mb-4 d-flex flex-wrap gap-3">
                        <span><i class="bi bi-geo-alt me-1"></i><?= e((string) ($offer['location'] ?? 'Non précisé')) ?></span>
                        <?php if (!empty($offer['contract_type'])): ?><span><i class="bi bi-file-text me-1"></i><?= e((string) $offer['contract_type']) ?></span><?php endif; ?>
                        <?php if (!empty($offer['salary_min']) || !empty($offer['salary_max'])): ?>
                            <span><i class="bi bi-cash-coin me-1"></i><?= e((string) ($offer['salary_min'] ?? '')) ?><?= !empty($offer['salary_max']) ? ' – ' . e((string) $offer['salary_max']) : '' ?> €</span>
                        <?php endif; ?>
                    </div>
                    <hr>
                    <h2 class="h6 text-uppercase text-secondary mb-3" style="letter-spacing:.05em;">Description du poste</h2>
                    <div class="offer-body"><?= nl2br(e((string) ($offer['description'] ?? ''))) ?></div>
                </div>
            </div>
        </div>

        <!-- Colonne action -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 88px;">
                <div class="card-body p-4 text-center">
                    <?php if (!is_auth()): ?>
                        <p class="text-secondary small mb-3">Connectez-vous en tant que candidat pour postuler à cette offre.</p>
                        <a class="btn btn-primary w-100 mb-2" href="<?= e(url('/login')) ?>">Se connecter</a>
                        <a class="btn btn-outline-secondary w-100" href="<?= e(url('/register')) ?>">Créer un compte</a>
                    <?php elseif (current_role() === 'client'): ?>
                        <i class="bi bi-send-check text-primary d-block mb-2" style="font-size: 2rem;"></i>
                        <p class="text-secondary small mb-3">Envoyez votre candidature avec votre CV et une lettre de motivation.</p>
                        <a class="btn btn-primary btn-lg w-100" href="<?= e(url('/offres/' . (int) ($offer['id'] ?? 0) . '/postuler')) ?>">Postuler maintenant</a>
                    <?php elseif (current_role() === 'entreprise'): ?>
                        <p class="text-secondary small mb-0"><i class="bi bi-info-circle me-1"></i>Vous consultez cette offre en tant qu'entreprise.</p>
                    <?php else: ?>
                        <p class="text-secondary small mb-0">Consultation administrateur.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
