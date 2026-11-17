/* Sharely: copy link, print and the system share sheet. Buttons arrive hidden and are shown only when they can work. */
(() => {
  'use strict';
  if (document.documentElement.dataset.sharelyLoadedActions) return;
  document.documentElement.dataset.sharelyLoadedActions = '1';

  const canCopy = () => !!(navigator.clipboard && navigator.clipboard.writeText);
  const canShare = () => typeof navigator.share === 'function';

  const announce = (button, text) => {
    const box = button.closest('.sharely');
    const status = (box && box.querySelector('[data-sharely-status]')) || document.querySelector('[data-sharely-status]');
    if (!status || !text) return;
    status.textContent = '';
    setTimeout(() => { status.textContent = text; }, 50);
  };

  const done = (button) => {
    button.classList.add('is-done');
    clearTimeout(button.sharelyTimer);
    button.sharelyTimer = setTimeout(() => button.classList.remove('is-done'), 2000);
  };

  const run = async (button) => {
    const action = button.dataset.sharelyAction;
    const url = button.dataset.sharelyUrl || location.href;
    if (action === 'copy') {
      try {
        await navigator.clipboard.writeText(url);
        announce(button, button.dataset.sharelyDone);
        done(button);
      } catch (e) { /* the reader can still copy the address bar */ }
    } else if (action === 'print') {
      window.print();
    } else if (action === 'sheet') {
      try {
        await navigator.share({ title: button.dataset.sharelyTitle || document.title, url });
      } catch (e) { /* AbortError: the reader closed the sheet; nothing else to do either way */ }
    }
  };

  const init = () => {
    document.querySelectorAll('button[data-sharely-action]').forEach((button) => {
      const action = button.dataset.sharelyAction;
      const ok = action === 'print' || (action === 'copy' && canCopy()) || (action === 'sheet' && canShare());
      if (ok) button.hidden = false;
    });
    document.addEventListener('click', (event) => {
      const button = event.target.closest && event.target.closest('button[data-sharely-action]');
      if (button) run(button);
    });
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
