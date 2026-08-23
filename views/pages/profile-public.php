<?php
$profile = $profile ?? [];
$dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
$categories = $dec($profile['categories'] ?? '[]');
$skills = $dec($profile['skills'] ?? '[]');
$languages = $dec($profile['languages'] ?? '[]');
$portfolio = $dec($profile['portfolio'] ?? '[]');
$certifications = $dec($profile['certifications'] ?? '[]');
$displayName = (string) ($profile['display_name'] ?? $profile['name'] ?? 'Profil');
$initials = $displayName !== '' ? mb_strtoupper(mb_substr(trim($displayName), 0, 1)) : '?';
if (preg_match('/\s(\S)/u', trim($displayName), $m)) { $initials .= mb_strtoupper($m[1]); }
$rate = $profile['hourly_rate'] ?? null;
$isEntreprise = (string) ($profile['role'] ?? '') === 'entreprise';
// Extrait [libellé, url|null] d'une entrée « Titre — https://… » ou d'un lien brut.
$parseLink = static function (string $s): array {
    $s = trim($s);
    if (preg_match('#https?://[^\s]+#i', $s, $m)) {
        $url = rtrim($m[0], '.,;)');
        $label = trim(str_replace($m[0], '', $s), " \t—–-|:•");
        if ($label === '') { $label = (string) preg_replace('#^https?://(www\.)?#i', '', $url); }
        return [$label, $url];
    }
    return [$s, null];
};
?>
<section class="py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-4">
                        <?php if (!empty($profile['photo_path'])): ?>
                            <img class="<?= $isEntreprise ? 'rounded' : 'rounded-circle' ?>" style="width:80px;height:80px;object-fit:cover;" src="<?= e(url('/' . ltrim((string) $profile['photo_path'], '/'))) ?>" alt="<?= e($displayName) ?>" loading="lazy" decoding="async">
                        <?php else: ?>
                            <span class="avatar-circle" style="width:80px;height:80px;font-size:1.6rem;<?= $isEntreprise ? 'border-radius:16px;' : '' ?>"><?= e($initials) ?></span>
                        <?php endif; ?>
                        <div>
                            <h1 class="h3 mb-1"><?= e($displayName) ?></h1>
                            <p class="text-secondary mb-0">
                                <i class="bi bi-geo-alt me-1"></i><?= e((string) ($profile['location'] ?? 'Non renseignée')) ?>
                                <?php if (!$isEntreprise && $rate !== null && $rate !== ''): ?><span class="mx-2">·</span><span class="text-primary fw-semibold"><?= e(money((float) $rate)) ?>/h</span><?php endif; ?>
                                <?php if (!empty($profile['availability'])): ?><span class="mx-2">·</span><i class="bi bi-clock me-1"></i><?= e((string) $profile['availability']) ?><?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($categories) || !empty($skills)): ?>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php foreach ($categories as $c): ?><span class="badge badge-soft-primary"><?= e((string) $c) ?></span><?php endforeach; ?>
                            <?php foreach ($skills as $s): ?><span class="badge text-bg-secondary"><?= e((string) $s) ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="h6 text-uppercase text-secondary mb-2" style="letter-spacing:.05em;"><?= $isEntreprise ? 'À propos de l\'entreprise' : 'À propos' ?></h2>
                    <p class="mb-0"><?= nl2br(e((string) ($profile['bio'] ?? 'Aucune description disponible.'))) ?></p>
                </div>
            </div>

            <?php if (!empty($services)): ?>
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3"><i class="bi bi-briefcase me-1"></i>Services proposés</h2>
                        <div class="row g-3">
                            <?php foreach ($services as $s): ?>
                                <div class="col-sm-6">
                                    <div class="border rounded-3 h-100 d-flex flex-column overflow-hidden">
                                        <?php if (!empty($s['image_path'])): ?>
                                            <img src="<?= e(url('/' . ltrim((string) $s['image_path'], '/'))) ?>" alt="<?= e((string) $s['title']) ?>" style="height:120px;object-fit:cover;" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                        <div class="p-3 d-flex flex-column flex-grow-1">
                                        <div class="fw-semibold mb-1"><?= e((string) $s['title']) ?></div>
                                        <?php if (!empty($s['category'])): ?><div class="mb-2"><span class="badge badge-soft-primary"><?= e((string) $s['category']) ?></span></div><?php endif; ?>
                                        <?php if (!empty($s['description'])): ?><p class="text-secondary small mb-2"><?= nl2br(e(mb_strimwidth((string) $s['description'], 0, 140, '…'))) ?></p><?php endif; ?>
                                        <div class="d-flex flex-wrap gap-3 small mt-auto pt-2">
                                            <span class="text-primary fw-semibold"><?= e(Service::formatPrice($s['price'] !== null ? (float) $s['price'] : null, (string) $s['price_type'])) ?></span>
                                            <?php if (!empty($s['delivery_days'])): ?><span class="text-secondary"><i class="bi bi-clock me-1"></i><?= (int) $s['delivery_days'] ?> j</span><?php endif; ?>
                                        </div>
                                        </div><!-- /.p-3 -->
                                    </div><!-- /.border -->
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($portfolio)): ?>
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3"><i class="bi bi-collection me-1"></i>Portfolio</h2>
                        <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                            <?php foreach ($portfolio as $item): [$label, $url] = $parseLink((string) $item); ?>
                                <li class="d-flex gap-2"><i class="bi bi-arrow-right-short text-primary"></i>
                                    <?php if ($url !== null): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener nofollow"><?= e($label) ?> <i class="bi bi-box-arrow-up-right small"></i></a><?php else: ?><span><?= e($label) ?></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($certifications)): ?>
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3"><i class="bi bi-patch-check me-1"></i>Certifications</h2>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($certifications as $cert): [$label, $url] = $parseLink((string) $cert); ?>
                                <?php if ($url !== null): ?>
                                    <a class="badge badge-soft-success text-decoration-none" href="<?= e($url) ?>" target="_blank" rel="noopener nofollow"><i class="bi bi-patch-check me-1"></i><?= e($label) ?> <i class="bi bi-box-arrow-up-right"></i></a>
                                <?php else: ?>
                                    <span class="badge badge-soft-success"><?= e($label) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 88px;">
                <div class="card-body p-4">
                    <?php if (!empty($languages)): ?>
                        <h3 class="h6 mb-2"><i class="bi bi-translate me-1"></i>Langues</h3>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php foreach ($languages as $lang): ?><span class="badge text-bg-secondary"><?= e((string) $lang) ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-center">
                        <?php if (is_auth() && (int) ($profile['user_id'] ?? 0) !== (int) current_user_id()): ?>
                            <a class="btn btn-primary w-100 mb-2" href="<?= e(url('/messages/nouveau/' . (int) ($profile['user_id'] ?? 0))) ?>"><i class="bi bi-chat-dots me-1"></i>Contacter</a>
                            <form method="post" action="<?= e(url('/favorites/' . (int) ($profile['user_id'] ?? 0) . '/toggle')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-bookmark-plus me-1"></i>Ajouter aux favoris</button>
                            </form>
                        <?php elseif (!is_auth()): ?>
                            <p class="text-secondary small mb-3">Connectez-vous pour contacter ce profil.</p>
                            <a class="btn btn-primary w-100" href="<?= e(url('/login')) ?>">Se connecter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
