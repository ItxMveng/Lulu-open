<?php $dossiers = $dossiers ?? []; ?>
<div class="mb-4">
    <h1 class="h3 mb-1">Vérifications entreprises</h1>
    <p class="text-secondary mb-0"><?= count($dossiers) ?> dossier(s) en attente</p>
</div>

<?php if (empty($dossiers)): ?>
    <?php View::partial('components/empty-state', ['icon' => 'bi-patch-check', 'title' => 'Aucun dossier en attente', 'text' => 'Les nouvelles demandes de vérification apparaîtront ici.']); ?>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($dossiers as $d): ?>
            <div class="card">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1"><?= e((string) $d['legal_name']) ?></h2>
                            <div class="text-secondary small">
                                Compte : <?= e((string) $d['name']) ?> · <?= e((string) $d['email']) ?>
                                · Soumis le <?= e(date('d/m/Y', strtotime((string) $d['submitted_at']))) ?>
                            </div>
                        </div>
                        <span class="status-badge status-en_attente">En attente</span>
                    </div>

                    <div class="row g-2 small mb-3">
                        <div class="col-md-4"><span class="text-secondary">Immatriculation :</span> <strong><?= e((string) ($d['registration_number'] ?: '—')) ?></strong></div>
                        <div class="col-md-4"><span class="text-secondary">Pays :</span> <strong><?= e((string) ($d['country'] ?: '—')) ?></strong></div>
                        <div class="col-md-4"><span class="text-secondary">Téléphone :</span> <strong><?= e((string) ($d['contact_phone'] ?: '—')) ?></strong></div>
                        <div class="col-md-4"><span class="text-secondary">Secteur :</span> <strong><?= e((string) ($d['sector'] ?: '—')) ?></strong></div>
                        <div class="col-md-8"><span class="text-secondary">Site :</span> <?php if (!empty($d['website'])): ?><a href="<?= e((string) $d['website']) ?>" target="_blank" rel="noopener"><?= e((string) $d['website']) ?></a><?php else: ?>—<?php endif; ?></div>
                    </div>

                    <?php if (!empty($d['description'])): ?>
                        <p class="small text-secondary"><?= nl2br(e((string) $d['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($d['document_path'])): ?>
                        <a class="btn btn-sm btn-outline-secondary mb-3" href="<?= e(url('/' . ltrim((string) $d['document_path'], '/'))) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text me-1"></i>Voir le justificatif</a>
                    <?php endif; ?>

                    <form method="post" action="<?= e(url('/admin/verifications/' . (int) $d['id'] . '/review')) ?>" class="border-top pt-3">
                        <?= csrf_field() ?>
                        <input class="form-control form-control-sm mb-2" name="admin_note" placeholder="Note / motif (envoyé au demandeur en cas de refus)">
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-success" name="decision" value="verified" type="submit"><i class="bi bi-check-lg me-1"></i>Valider l'entreprise</button>
                            <button class="btn btn-sm btn-outline-danger" name="decision" value="rejected" type="submit" onclick="return confirm('Refuser ce dossier ?');"><i class="bi bi-x-lg me-1"></i>Refuser</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
