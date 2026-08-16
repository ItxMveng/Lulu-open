<div id="cookieConsent" class="cookie-consent" role="dialog" aria-label="Consentement aux cookies" hidden>
    <div class="cookie-consent-inner">
        <div class="flex-grow-1">
            <strong class="d-block mb-1"><i class="bi bi-shield-lock me-1"></i><?= t('Respect de votre vie privée') ?></strong>
            <p class="mb-0 small text-secondary">Nous utilisons des cookies pour assurer le bon fonctionnement du site et améliorer votre expérience.
                <a href="<?= e(url('/privacy')) ?>"><?= t('En savoir plus') ?></a>.</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <button class="btn btn-outline-secondary btn-sm" data-cookie="refuse"><?= t('Refuser') ?></button>
            <button class="btn btn-primary btn-sm" data-cookie="accept"><?= t('Accepter') ?></button>
        </div>
    </div>
</div>
<script>
(function () {
    var KEY = 'lulu_cookie_consent';
    function getCookie(n){ return document.cookie.split('; ').find(function(r){return r.indexOf(n+'=')===0;}); }
    var banner = document.getElementById('cookieConsent');
    if (!getCookie(KEY)) { banner.hidden = false; }
    banner.querySelectorAll('[data-cookie]').forEach(function (b) {
        b.addEventListener('click', function () {
            var val = b.getAttribute('data-cookie');
            document.cookie = KEY + '=' + val + '; path=/; max-age=' + (60*60*24*180) + '; SameSite=Lax';
            banner.hidden = true;
        });
    });
})();
</script>
