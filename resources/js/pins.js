/* Sharely: Pinterest pin buttons on article images, and an image picker for the Pinterest share button. */
(() => {
  'use strict';
  const html = document.documentElement;
  if (html.dataset.sharelyLoadedPins) return;
  html.dataset.sharelyLoadedPins = '1';

  const init = () => {
    const marker = document.querySelector('[data-sharely-pins]');
    if (!marker) return;
    const root = marker.closest('article') || marker.parentElement;
    let words = {};
    try { words = JSON.parse(document.querySelector('[data-sharely-words]').getAttribute('data-sharely-words')) || {}; } catch (e) { /* no words */ }
    const template = marker.dataset.sharelyTemplate || '';
    const min = parseInt(marker.dataset.sharelyMin || '0', 10) || 0;
    const corner = /^(top|bottom)-(start|end)$/.test(marker.dataset.sharelyCorner || '') ? marker.dataset.sharelyCorner : 'top-start';
    const icons = document.querySelector('template[data-sharely-icons]');
    if (!root || !template) return;

    const media = (img) => new URL(img.currentSrc || img.src, document.baseURI).href;
    const text = (img) => img.getAttribute('data-pin-description') || marker.dataset.sharelyDescription || img.alt.trim();
    const href = (img) => template
      .split('%25%25SHARELY_MEDIA%25%25').join(encodeURIComponent(media(img)))
      .split('%25%25SHARELY_TEXT%25%25').join(encodeURIComponent(text(img)));

    /** An image a reader may pin: not ours, not opted out, not decorative. */
    const allowed = (img) => !img.closest('.sharely')
      && !img.hasAttribute('data-sharely-nopin') && !img.hasAttribute('data-pin-nopin') && !img.hasAttribute('nopin')
      && !img.classList.contains('nopin')
      && (img.getAttribute('alt') || '').trim() !== ''
      && !!(img.currentSrc || img.src);
    const wide = (img) => Math.max(img.getBoundingClientRect().width, img.naturalWidth || 0) >= min;
    const images = () => Array.from(root.querySelectorAll('img')).filter(allowed);

    const label = (img) => (words.pinImage || '').split(':alt').join(img.alt.trim());

    const addPin = (img) => {
      if (img.closest('.sharely-pin-wrap') || !wide(img)) return;
      // A linked image: wrap the link, so the pin link is never inside another link.
      const parentLink = img.closest('a');
      const target = parentLink && root.contains(parentLink) ? parentLink : img;
      const wrap = document.createElement('span');
      wrap.className = 'sharely-pin-wrap';
      if (getComputedStyle(target).display === 'block' || getComputedStyle(img).display === 'block') wrap.classList.add('sharely-pin-wrap--block');
      target.before(wrap);
      wrap.append(target);
      const link = document.createElement('a');
      link.className = 'sharely-pin sharely-pin--' + corner;
      link.href = href(img);
      link.target = '_blank';
      link.rel = 'noopener nofollow';
      link.setAttribute('aria-label', label(img));
      const svg = icons && icons.content.querySelector('svg[data-network="pin"]');
      if (svg) {
        const copy = svg.cloneNode(true);
        copy.setAttribute('aria-hidden', 'true');
        copy.setAttribute('focusable', 'false');
        copy.classList.add('sharely-icon');
        link.append(copy);
      }
      const hidden = document.createElement('span');
      hidden.className = 'sharely-vh';
      hidden.textContent = label(img);
      link.append(hidden);
      wrap.append(link);
    };

    if (marker.dataset.sharelyButtons === '1') {
      images().forEach((img) => {
        if (img.complete && img.naturalWidth) addPin(img);
        else img.addEventListener('load', () => addPin(img), { once: true });
      });
    }

    // The picker.
    let dialog = null;
    let back = null;
    const pick = (link) => {
      let list = images().filter(wide);
      if (list.length < 2) list = images();
      if (list.length < 2 || typeof HTMLDialogElement !== 'function') return false;
      if (!dialog) {
        dialog = document.createElement('dialog');
        dialog.className = 'sharely-dialog sharely-picker';
        dialog.setAttribute('aria-labelledby', 'sharely-picker-title');
        dialog.addEventListener('close', () => { if (back && document.contains(back)) back.focus(); });
        document.body.append(dialog);
      }
      dialog.textContent = '';
      const title = document.createElement('h2');
      title.id = 'sharely-picker-title';
      title.className = 'sharely-dialog-title';
      title.textContent = words.pickImage || '';
      const grid = document.createElement('div');
      grid.className = 'sharely-picker-grid';
      list.forEach((img) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'sharely-picker-item';
        const thumb = document.createElement('img');
        thumb.src = media(img);
        thumb.alt = img.alt.trim();
        thumb.loading = 'lazy';
        button.append(thumb);
        button.addEventListener('click', () => {
          window.open(href(img), '_blank', 'noopener');
          dialog.close();
        });
        grid.append(button);
      });
      const actions = document.createElement('div');
      actions.className = 'sharely-dialog-actions';
      const cancel = document.createElement('button');
      cancel.type = 'button';
      cancel.className = 'sharely-button';
      cancel.textContent = words.cancel || '';
      cancel.addEventListener('click', () => dialog.close());
      actions.append(cancel);
      dialog.append(title, grid, actions);
      back = link;
      dialog.showModal();
      grid.querySelector('button').focus();
      return true;
    };

    document.addEventListener('click', (event) => {
      const link = event.target.closest && event.target.closest('a[data-sharely-pick]');
      if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) return;
      if (pick(link)) event.preventDefault();
    });
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
