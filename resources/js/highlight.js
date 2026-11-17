/* Sharely: share buttons for selected text and for block quotes in an article. */
(() => {
  'use strict';
  const html = document.documentElement;
  if (html.dataset.sharelyLoadedHighlight) return;
  html.dataset.sharelyLoadedHighlight = '1';

  const PLACEHOLDER = '%25%25SHARELY%25%25';
  const BLOCKS = 'p, li, blockquote, h2, h3, h4, h5, h6, figcaption';
  const SKIP = '.sharely, .sharely-bar, .sharely-quote';

  const json = (el, name, fallback) => {
    try { return JSON.parse((el && el.getAttribute(name)) || '') || fallback; } catch (e) { return fallback; }
  };

  const init = () => {
    const marker = document.querySelector('[data-sharely-highlight]');
    if (!marker) return;
    const root = marker.closest('article') || marker.parentElement;
    const words = json(document.querySelector('[data-sharely-words]'), 'data-sharely-words', {});
    const networks = (marker.dataset.sharelyNetworks || '').split(',').map((n) => n.trim()).filter(Boolean);
    const templates = json(marker, 'data-sharely-templates', {});
    const labels = json(marker, 'data-sharely-labels', {});
    const url = marker.dataset.sharelyUrl || location.href;
    const icons = document.querySelector('template[data-sharely-icons]');
    if (!root || !networks.length) return;

    const status = document.createElement('span');
    status.className = 'sharely-vh';
    status.setAttribute('role', 'status');
    document.body.append(status);
    const announce = (text) => {
      status.textContent = '';
      if (text) setTimeout(() => { status.textContent = text; }, 50);
    };

    const icon = (network) => {
      const svg = icons && icons.content.querySelector('svg[data-network="' + network + '"]');
      if (!svg) return null;
      const copy = svg.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      copy.setAttribute('focusable', 'false');
      copy.classList.add('sharely-icon');
      return copy;
    };

    const quoted = (text) => '“' + text + '”';

    let bar = null;      // the toolbar on screen
    let origin = null;   // what gets focus back on Escape
    let quoteButton = null;
    let pointerDown = false;
    let via = 'mouse';
    let lastText = '';

    const hide = (restore) => {
      if (!bar) return;
      const hadFocus = bar.contains(document.activeElement);
      bar.remove();
      bar = null;
      lastText = '';
      if (quoteButton) quoteButton.setAttribute('aria-expanded', 'false');
      if (restore && hadFocus && origin && document.contains(origin)) origin.focus();
      quoteButton = null;
    };

    const build = (text) => {
      const toolbar = document.createElement('div');
      toolbar.className = 'sharely-bar';
      toolbar.setAttribute('role', 'toolbar');
      toolbar.setAttribute('aria-label', words.highlightBar || '');
      const mastodonReady = !!html.dataset.sharelyLoadedMastodon;
      networks.forEach((network) => {
        const label = labels[network] || network;
        let control;
        if (network === 'copy' || (network === 'mastodon' && mastodonReady)) {
          control = document.createElement('button');
          control.type = 'button';
          control.addEventListener('click', () => {
            if (network === 'mastodon') {
              document.dispatchEvent(new CustomEvent('sharely:mastodon', { detail: { text: quoted(text) + ' ' + url } }));
              return;
            }
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(quoted(text) + ' ' + url).then(() => {
              control.classList.add('is-done');
              announce(words.copied || '');
            }, () => {});
          });
        } else {
          const href = network === 'mastodon'
            ? marker.dataset.sharelyMastodonPage
            : (templates[network] || '').split(PLACEHOLDER).join(encodeURIComponent(quoted(text)));
          if (!href) return;
          control = document.createElement('a');
          control.href = href;
          control.target = '_blank';
          control.rel = 'noopener nofollow';
        }
        control.className = 'sharely-bar-item sharely-bar-item--' + network;
        control.setAttribute('aria-label', label);
        const svg = icon(network);
        if (svg) control.append(svg);
        else control.append(document.createTextNode(label));
        toolbar.append(control);
      });
      // Keep the selection when a mouse presses a button.
      toolbar.addEventListener('mousedown', (event) => event.preventDefault());
      return toolbar;
    };

    const inside = (node) => {
      const el = node && (node.nodeType === 1 ? node : node.parentElement);
      return !!el && root.contains(el) && !el.closest(SKIP);
    };

    const place = (rect) => {
      const gap = 8;
      const w = bar.offsetWidth;
      const h = bar.offsetHeight;
      const vw = document.documentElement.clientWidth;
      let left = rect.left + rect.width / 2 - w / 2;
      left = Math.max(gap, Math.min(left, vw - w - gap));
      let top = rect.top - h - gap;
      if (top < gap) top = rect.bottom + gap;
      bar.style.left = Math.round(left + window.scrollX) + 'px';
      bar.style.top = Math.round(top + window.scrollY) + 'px';
    };

    const fromSelection = () => {
      if (pointerDown || quoteButton) return; // a quote's toolbar stays until Escape or a click elsewhere
      const sel = window.getSelection();
      if (!sel || sel.isCollapsed || !sel.rangeCount) {
        if (bar && !quoteButton && !bar.contains(document.activeElement)) hide(false);
        return;
      }
      const range = sel.getRangeAt(0);
      if (!inside(range.startContainer) || !inside(range.endContainer)) return;
      const text = sel.toString().replace(/\s+/g, ' ').trim();
      if (text.length < 3 || text.length > 1000) { if (!quoteButton) hide(false); return; }
      if (bar && text === lastText && !quoteButton) return;
      hide(false);
      lastText = text;
      bar = build(text);
      const endEl = range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement;
      const block = (endEl.closest(BLOCKS) && root.contains(endEl.closest(BLOCKS))) ? endEl.closest(BLOCKS) : endEl;
      origin = block;
      if (via === 'key') {
        bar.classList.add('sharely-bar--inline');
        block.after(bar);
        if (!block.hasAttribute('tabindex')) block.setAttribute('tabindex', '-1');
        // Start Tab from this block, so the next Tab lands in the toolbar.
        const active = document.activeElement;
        if (!active || !block.contains(active)) block.focus({ preventScroll: true });
        announce(words.highlightReady || '');
      } else {
        bar.classList.add('sharely-bar--float');
        document.body.append(bar);
        place(range.getBoundingClientRect());
      }
    };

    let timer = 0;
    const later = () => {
      clearTimeout(timer);
      timer = setTimeout(fromSelection, 150);
    };
    document.addEventListener('selectionchange', later);
    document.addEventListener('mousedown', (event) => {
      via = 'mouse';
      if (bar && !bar.contains(event.target) && !(quoteButton && quoteButton.contains(event.target))) hide(false);
      pointerDown = true;
    });
    document.addEventListener('mouseup', (event) => {
      pointerDown = false;
      if (!(event.target.closest && event.target.closest(SKIP))) setTimeout(fromSelection, 0);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && bar) { hide(true); return; }
      if (event.key !== 'Tab') via = 'key';
    });
    document.addEventListener('keyup', (event) => { if (event.key !== 'Tab' && event.key !== 'Escape') later(); });

    if (marker.dataset.sharelyQuotes !== '1') return;
    root.querySelectorAll('blockquote').forEach((quote) => {
      if (quote.closest(SKIP)) return;
      const text = quote.textContent.replace(/\s+/g, ' ').trim().slice(0, 1000);
      if (text.length < 3) return;
      const wrap = document.createElement('div');
      wrap.className = 'sharely-quote';
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'sharely-quote-button';
      button.setAttribute('aria-expanded', 'false');
      const svg = icon('share');
      if (svg) button.append(svg);
      button.append(document.createTextNode(words.shareQuote || ''));
      wrap.append(button);
      quote.after(wrap);
      button.addEventListener('click', () => {
        const open = quoteButton === button;
        hide(false);
        if (open) return;
        bar = build(text);
        bar.classList.add('sharely-bar--inline');
        button.after(bar);
        quoteButton = button;
        origin = button;
        button.setAttribute('aria-expanded', 'true');
      });
    });
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
