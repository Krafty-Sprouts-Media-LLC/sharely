/* Sharely: asks the reader for their Mastodon (or Misskey, Sharkey, Firefish, Iceshrimp) server and shares there. */
(() => {
  'use strict';
  const html = document.documentElement;
  if (html.dataset.sharelyLoadedMastodon) return;
  html.dataset.sharelyLoadedMastodon = '1';

  const KEY = 'sharely.mastodon.server';
  let words = null;

  const word = (key) => {
    if (!words) {
      const el = document.querySelector('[data-sharely-words]');
      try { words = JSON.parse((el && el.getAttribute('data-sharely-words')) || '{}'); } catch (e) { words = {}; }
    }
    return words[key] || '';
  };

  const load = () => { try { return localStorage.getItem(KEY) || ''; } catch (e) { return ''; } };
  const save = (host) => { try { localStorage.setItem(KEY, host); } catch (e) { /* storage is off */ } };

  /** Returns the bare host ("mastodon.social" or "host.example:8443"), or '' when it is not one. */
  const cleanHost = (value) => {
    let v = String(value || '').trim().toLowerCase();
    v = v.replace(/^[a-z][a-z0-9+.-]*:\/\//, '').replace(/\/+$/, '');
    if (v.includes('@')) v = v.slice(v.lastIndexOf('@') + 1); // "@me@host" handles
    return /^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+(?::\d{1,5})?$/.test(v) ? v : '';
  };

  const share = (host, text) => {
    window.open('https://' + host + '/share?text=' + encodeURIComponent(text), '_blank', 'noopener');
  };

  let dialog = null;
  let current = { text: '', fallback: '', back: null };

  const el = (tag, attrs, text) => {
    const node = document.createElement(tag);
    Object.entries(attrs || {}).forEach(([k, v]) => node.setAttribute(k, v));
    if (text) node.textContent = text;
    return node;
  };

  const build = () => {
    dialog = el('dialog', { class: 'sharely-dialog', 'aria-labelledby': 'sharely-mastodon-title' });
    const form = el('form', { class: 'sharely-dialog-form', novalidate: '' });
    const input = el('input', {
      id: 'sharely-mastodon-server', name: 'server', type: 'text', inputmode: 'url',
      autocomplete: 'off', autocapitalize: 'none', spellcheck: 'false', class: 'sharely-input',
      'aria-describedby': 'sharely-mastodon-hint sharely-mastodon-error', required: '',
    });
    const error = el('p', { id: 'sharely-mastodon-error', class: 'sharely-dialog-error', 'aria-live': 'polite' });
    const actions = el('div', { class: 'sharely-dialog-actions' });
    const submit = el('button', { type: 'submit', class: 'sharely-button sharely-button--primary' }, word('share'));
    const cancel = el('button', { type: 'button', class: 'sharely-button' }, word('cancel'));
    actions.append(submit, cancel);
    form.append(
      el('h2', { id: 'sharely-mastodon-title', class: 'sharely-dialog-title' }, word('mastodonTitle')),
      el('label', { for: 'sharely-mastodon-server', class: 'sharely-label' }, word('mastodonServer')),
      input,
      el('p', { id: 'sharely-mastodon-hint', class: 'sharely-dialog-hint' }, word('mastodonHint')),
      error,
      actions,
    );
    dialog.append(form);
    document.body.append(dialog);

    cancel.addEventListener('click', () => dialog.close());
    input.addEventListener('input', () => { error.textContent = ''; input.removeAttribute('aria-invalid'); });
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const host = cleanHost(input.value);
      if (!host) {
        error.textContent = word('badServer');
        input.setAttribute('aria-invalid', 'true');
        input.focus();
        return;
      }
      save(host);
      dialog.close();
      share(host, current.text);
    });
    dialog.addEventListener('close', () => {
      if (current.back && document.contains(current.back)) current.back.focus();
    });
  };

  const open = (text, fallback, back) => {
    if (typeof HTMLDialogElement !== 'function') return false;
    if (!dialog) build();
    current = { text, fallback, back };
    const input = dialog.querySelector('input');
    input.value = load() || fallback || '';
    input.removeAttribute('aria-invalid');
    dialog.querySelector('.sharely-dialog-error').textContent = '';
    dialog.showModal();
    input.focus();
    input.select();
    return true;
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest && event.target.closest('a[data-sharely-mastodon]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) return;
    if (open(link.dataset.sharelyText || '', link.dataset.sharelyServer || '', link)) event.preventDefault();
  });

  // highlight.js asks for the dialog with the quoted text.
  document.addEventListener('sharely:mastodon', (event) => {
    const link = document.querySelector('a[data-sharely-mastodon]');
    const back = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    open((event.detail && event.detail.text) || '', (link && link.dataset.sharelyServer) || '', back);
  });

  // The no-JavaScript page's own form: remember the server.
  const enhanceForms = () => {
    document.querySelectorAll('form[data-sharely-mastodon-form]').forEach((form) => {
      const input = form.querySelector('input[name="server"]');
      if (!input) return;
      const saved = load();
      if (saved && !input.value) input.value = saved;
      form.addEventListener('submit', () => {
        const host = cleanHost(input.value);
        if (host) save(host);
      });
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', enhanceForms);
  else enhanceForms();
})();
