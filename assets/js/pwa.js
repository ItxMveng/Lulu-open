(function () {
  'use strict';
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
    });
  }

  var KEY = 'lulu-pwa-dismissed';
  var en = (document.documentElement.lang || 'fr').indexOf('en') === 0;
  var ua = navigator.userAgent;
  var isIOS = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
  var isAndroid = /android/i.test(ua);
  var isMobile = isIOS || isAndroid;

  var T = en
    ? { title: 'Install LULU-OPEN', body: 'Add the app to your home screen for quick access to offers and applications.', install: 'Install', later: 'Not now', btn: 'Install the app',
        ios: 'Tap the Share button, then “Add to Home Screen”.',
        android: 'Open the browser menu (⋮), then tap “Install app” or “Add to Home screen”.',
        desktop: 'Use the install icon in the address bar of your browser.' }
    : { title: 'Installer LULU-OPEN', body: 'Ajoutez l’application à votre écran d’accueil pour retrouver offres et candidatures en un geste.', install: 'Installer', later: 'Plus tard', btn: 'Installer l’app',
        ios: 'Touchez le bouton Partager, puis « Sur l’écran d’accueil ».',
        android: 'Ouvrez le menu du navigateur (⋮), puis touchez « Installer l’application » ou « Ajouter à l’écran d’accueil ».',
        desktop: 'Utilisez l’icône d’installation dans la barre d’adresse de votre navigateur.' };

  function dismissed() { try { var t = +localStorage.getItem(KEY); return t && Date.now() - t < 7 * 864e5; } catch (e) { return false; } }
  function remember() { try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {} }
  function standalone() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true; }
  function help() { return isIOS ? T.ios : (isAndroid ? T.android : T.desktop); }

  if (standalone()) return;

  function closeBanner() { var b = document.querySelector('.pwa-banner'); if (b) b.remove(); }

  function banner(mode) {
    closeBanner();
    var native = mode === 'native';
    var el = document.createElement('div');
    el.className = 'pwa-banner';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-label', T.title);
    el.innerHTML = '<div class="pwa-banner__icon" aria-hidden="true">L</div><div class="pwa-banner__text"><strong></strong><span></span></div><div class="pwa-banner__actions"></div>';
    el.querySelector('strong').textContent = T.title;
    el.querySelector('span').textContent = native ? T.body : help();
    var actions = el.querySelector('.pwa-banner__actions');
    if (native) {
      var ok = document.createElement('button'); ok.type = 'button'; ok.className = 'pwa-btn pwa-btn--primary'; ok.textContent = T.install; actions.appendChild(ok);
      ok.addEventListener('click', function () {
        var p = window.__pwaPrompt; if (!p) return; p.prompt();
        p.userChoice.then(function () { window.__pwaPrompt = null; el.remove(); });
      });
    }
    var no = document.createElement('button'); no.type = 'button'; no.className = 'pwa-btn'; no.textContent = native ? T.later : 'OK'; actions.appendChild(no);
    no.addEventListener('click', function () { remember(); el.remove(); });
    document.body.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('is-visible'); });
  }

  function requestInstall() {
    if (window.__pwaPrompt) { banner('native'); window.__pwaPrompt.prompt(); window.__pwaPrompt.userChoice.then(function () { window.__pwaPrompt = null; closeBanner(); }); }
    else { banner('help'); }
  }

  // Bouton permanent (pied de page / menu) : data-pwa-install
  function wireButtons() {
    document.querySelectorAll('[data-pwa-install]').forEach(function (b) {
      b.hidden = false;
      b.addEventListener('click', function (e) { e.preventDefault(); requestInstall(); });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wireButtons); else wireButtons();

  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); window.__pwaPrompt = e; scheduleAuto(); });
  window.addEventListener('appinstalled', function () { closeBanner(); remember(); document.querySelectorAll('[data-pwa-install]').forEach(function (b) { b.hidden = true; }); });

  // Le bandeau cookies (bas de l'écran) passe avant : on attend qu'il soit traité.
  function cookieBannerVisible() { var c = document.getElementById('cookieConsent'); return !!c && !c.hidden; }
  var autoTimer = null;
  function scheduleAuto() {
    if (autoTimer || dismissed()) return;
    var waited = 0;
    autoTimer = setInterval(function () {
      waited += 1000;
      if (cookieBannerVisible() && waited < 60000) return;
      clearInterval(autoTimer);
      if (!document.querySelector('.pwa-banner')) banner(window.__pwaPrompt ? 'native' : 'help');
    }, 1000);
  }

  // Sur mobile, sans événement natif (iOS, navigateur non Chrome) : instructions manuelles.
  if (isMobile && !dismissed()) setTimeout(function () { if (!window.__pwaPrompt) scheduleAuto(); }, 5000);
})();
