<?php $offer = $offer ?? []; ?>
<section class="py-4">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-lg-5">
            <span class="badge text-bg-secondary mb-3"><?= e((string) ($offer['status'] ?? 'active')) ?></span>
            <h1 class="mb-3"><?= e((string) ($offer['title'] ?? 'Offre')) ?></h1>
            <div class="row g-3 text-secondary mb-4">
                <div class="col-md-3">Type: <?= e((string) ($offer['type'] ?? '')) ?></div>
                <div class="col-md-3">Contrat: <?= e((string) ($offer['contract_type'] ?? '')) ?></div>
                <div class="col-md-3">Lieu: <?= e((string) ($offer['location'] ?? '')) ?></div>
                <div class="col-md-3">Télétravail: <?= !empty($offer['remote_ok']) ? 'Oui' : 'Non' ?></div>
            </div>
            <div><?= nl2br(e((string) ($offer['description'] ?? ''))) ?></div>

            <div class="mt-4 pt-3 border-top">
                <?php if (!is_auth()): ?>
                    <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Connectez-vous pour postuler</a>
                <?php elseif (current_role() === 'client'): ?>
                    <a class="btn btn-primary" href="<?= e(url('/offres/' . ($offer['id'] ?? 0) . '/postuler')) ?>">Postuler à cette offre</a>
                <?php elseif (current_role() === 'entreprise'): ?>
                    <span class="text-secondary small">Vous consultez cette offre en tant qu'entreprise.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>