<?php
$dossier = $dossier ?? null;
$status = $status ?? null;
$val = static fn (string $k, string $d = ''): string => e((string) old($k, $dossier[$k] ?? $d));
?>
<section class="py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="h3 mb-1">Vérification de l'entreprise</h1>
            <p class="text-secondary mb-4">Pour garantir la confiance sur la plateforme, chaque entreprise est vérifiée avant de pouvoir publier des offres.</p>

            <?php if ($status === 'verified'): ?>
                <div class="lulu-alert lulu-alert-success mb-4"><i class="bi bi-patch-check-fill"></i><div><strong>Entreprise vérifiée.</strong> Vous pouvez publier des offres et gérer vos candidatures.</div></div>
            <?php elseif ($status === 'pending' && $dossier): ?>
                <div class="lulu-alert lulu-alert-info mb-4"><i class="bi bi-hourglass-split"></i><div><strong>Dossier en cours d'examen.</strong> Notre équipe revient vers vous sous 24–48h. Vous pouvez mettre à jour vos informations ci-dessous.</div></div>
            <?php elseif ($status === 'rejected'): ?>
                <div class="lulu-alert lulu-alert-danger mb-4"><i class="bi bi-x-octagon-fill"></i><div><strong>Dossier non validé.</strong> <?= !empty($dossier['admin_note']) ? e((string) $dossier['admin_note']) : 'Merci de corriger et soumettre à nouveau.' ?></div></div>
            <?php else: ?>
                <div class="lulu-alert lulu-alert-warning mb-4"><i class="bi bi-shield-exclamation"></i><div>Complétez votre dossier ci-dessous pour demander la vérification.</div></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/entreprise/verification')) ?>" enctype="multipart/form-data" class="card shadow-sm">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="legal_name">Raison sociale <span class="text-danger">*</span></label>
                            <input class="form-control" id="legal_name" name="legal_name" value="<?= $val('legal_name') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="registration_number">Numéro d'immatriculation (RCCM, SIRET…)</label>
                            <input class="form-control" id="registration_number" name="registration_number" value="<?= $val('registration_number') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="country">Pays</label>
                            <select class="form-select" id="country" name="country">
                                <option value="">—</option>
                                <?php foreach (Reference::countries() as $c): ?>
                                    <option value="<?= e($c) ?>" <?= (string) old('country', $dossier['country'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contact_phone">Téléphone professionnel</label>
                            <input class="form-control" id="contact_phone" name="contact_phone" value="<?= $val('contact_phone') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="website">Site web</label>
                            <input class="form-control" id="website" name="website" placeholder="https://…" value="<?= $val('website') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sector">Secteur d'activité</label>
                            <input class="form-control" id="sector" name="sector" value="<?= $val('sector') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Présentation de l'entreprise</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= $val('description') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="document">Justificatif (RCCM, registre du commerce, statuts…)</label>
                            <input class="form-control" type="file" id="document" name="document" accept=".pdf,image/jpeg,image/png">
                            <?php if (!empty($dossier['document_path'])): ?>
                                <div class="form-text"><i class="bi bi-check-circle text-success me-1"></i>Document déjà fourni — laissez vide pour le conserver.</div>
                            <?php else: ?>
                                <div class="form-text">PDF ou image (8 Mo max). Renforce vos chances de validation rapide.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-send-check me-1"></i><?= $dossier ? 'Mettre à jour mon dossier' : 'Soumettre pour vérification' ?></button>
                </div>
            </form>
        </div>
    </div>
</section>
