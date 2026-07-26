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
        </div>
    </div>
</section>