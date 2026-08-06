<?php
$profile = $profile ?? [];
$categories = json_decode((string) ($profile['categories'] ?? '[]'), true) ?: [];
$skills = json_decode((string) ($profile['skills'] ?? '[]'), true) ?: [];
$displayName = (string) ($profile['display_name'] ?? $profile['name'] ?? 'Profil');
$initials = $displayName !== '' ? mb_strtoupper(mb_substr(trim($displayName), 0, 1)) : '?';
if (preg_match('/\s(\S)/u', trim($displayName), $m)) { $initials .= mb_strtoupper($m[1]); }
$rate = $profile['hourly_rate'] ?? null;
?>
<section class="py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-4">
                        <?php if (!empty($profile['photo_path'])): ?>
                            <img class="rounded-circle" style="width:72px;height:72px;object-fit:cover;" src="<?= e(url('/' . ltrim((string) $profile['photo_path'], '/'))) ?>" alt="<?= e($displayName) ?>">
                        <?php else: ?>
                            <span class="avatar-circle" style="width:72px;height:72px;font-size:1.5rem;"><?= e($initials) ?></span>
                        <?php endif; ?>
                        <div>
                            <h1 class="h3 mb-1"><?= e($displayName) ?></h1>
                            <p class="text-secondary mb-0">
                                <i class="bi bi-geo-alt me-1"></i><?= e((string) ($profile['location'] ?? 'Non renseignée')) ?>
                                <?php if ($rate !== null && $rate !== ''): ?><span class="mx-2">·</span><span class="text-primary fw-semibold"><?= e((string) $rate) ?> €/h</span><?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($categories) || !empty($skills)): ?>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php foreach ($categories as $category): ?><span class="badge badge-soft-primary"><?= e((string) $category) ?></span><?php endforeach; ?>
                            <?php foreach ($skills as $skill): ?><span class="badge text-bg-secondary"><?= e((string) $skill) ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="h6 text-uppercase text-secondary mb-2" style="letter-spacing:.05em;">À propos</h2>
                    <p class="mb-0"><?= nl2br(e((string) ($profile['bio'] ?? 'Aucune description disponible.'))) ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-4 text-center">
                    <?php if (is_auth() && (int) ($profile['user_id'] ?? 0) !== (int) current_user_id()): ?>
                        <i class="bi bi-chat-dots text-primary d-block mb-2" style="font-size: 2rem;"></i>
                        <p class="text-secondary small mb-3">Intéressé par ce profil ?</p>
                        <a class="btn btn-primary w-100" href="<?= e(url('/messages')) ?>">Envoyer un message</a>
                    <?php elseif (!is_auth()): ?>
                        <p class="text-secondary small mb-3">Connectez-vous pour contacter ce profil.</p>
                        <a class="btn btn-primary w-100" href="<?= e(url('/login')) ?>">Se connecter</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
