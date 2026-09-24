(function () {
  'use strict';
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
    });
  }

  var KEY = 'lulu-pwa-dismissed';
  var en = (document.documentElement.lang || 'fr').indexOf('en') === 0;
  var T = en
    ? { title: 'Install LULU-OPEN', body: 'Add the app to your home screen for quick access to offers and applications.', install: 'Install', later: 'Not now', ios: 'On iPhone: tap Share, then “Add to Home Screen”.' }
    : { title: 'Installer LULU-OPEN', body: 'Ajoutez l’application à votre écran d’accueil pour retrouver offres et candidatures en un geste.', install: 'Installer', later: 'Plus tard', ios: 'Sur iPhone : touchez Partager, puis « Sur l’écran d’accueil ».' };

  function dismissed() { try { var t = +localStorage.getItem(KEY); return t && Date.now() - t < 14 * 864e5; } catch (e) { return false; } }
  function remember() { try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {} }
  function standalone() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true; }
  if (standalone() || dismissed()) return;

  function banner(showInstall) {
    var el = document.createElement('div');
    el.className = 'pwa-banner';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-label', T.title);
    el.innerHTML = '<div class="pwa-banner__icon" aria-hidden="true">L</div><div class="pwa-banner__text"><strong></strong><span></span></div><div class="pwa-banner__actions"></div>';
    el.querySelector('strong').textContent = T.title;
    el.querySelector('span').textContent = showInstall ? T.body : T.ios;
    var actions = el.querySelector('.pwa-banner__actions');
    if (showInstall) {
      var ok = document.createElement('button'); ok.type = 'button'; ok.className = 'pwa-btn pwa-btn--primary'; ok.textContent = T.install; actions.appendChild(ok);
      ok.addEventListener('click', function () {
        var p = window.__pwaPrompt; if (!p) return; p.prompt();
        p.userChoice.finally(function () { window.__pwaPrompt = null; el.remove(); });
      });
    }
    var no = document.createElement('button'); no.type = 'button'; no.className = 'pwa-btn'; no.textContent = T.later; actions.appendChild(no);
    no.addEventListener('click', function () { remember(); el.remove(); });
    document.body.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('is-visible'); });
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); window.__pwaPrompt = e;
    setTimeout(function () { if (!document.querySelector('.pwa-banner')) banner(true); }, 4000);
  });
  window.addEventListener('appinstalled', function () { var b = document.querySelector('.pwa-banner'); if (b) b.remove(); remember(); });

  var ua = navigator.userAgent;
  if (/iphone|ipad|ipod/i.test(ua) && /safari/i.test(ua) && !/crios|fxios/i.test(ua)) {
    setTimeout(function () { if (!document.querySelector('.pwa-banner')) banner(false); }, 6000);
  }
})();
