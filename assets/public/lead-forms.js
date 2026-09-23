(() => {
  'use strict';

  const cfg = window.WPRCOpportunityForms || {};
  const t = (key, fallback) => (cfg.i18n && cfg.i18n[key]) || fallback;


  const updateFloatingField = (field) => {
    if (!field) return;
    const control = field.querySelector('input:not([type="checkbox"]):not([type="hidden"]), textarea, select');
    if (!control) return;

    const hasValue = control.tagName === 'SELECT'
      ? String(control.value || '').length > 0
      : String(control.value || '').trim().length > 0;

    field.classList.toggle('has-value', hasValue);
  };

  const enhanceFloatingFields = (root = document) => {
    root.querySelectorAll('.wprc-field--floating').forEach((field) => {
      const control = field.querySelector('input:not([type="checkbox"]):not([type="hidden"]), textarea, select');
      if (!control) return;

      updateFloatingField(field);
      ['input', 'change', 'blur'].forEach((eventName) => {
        control.addEventListener(eventName, () => updateFloatingField(field));
      });
    });
  };

  const resetTurnstile = (form) => {
    if (!window.turnstile || !form) return;
    const widget = form.querySelector('.cf-turnstile');
    if (!widget) return;

    try {
      window.turnstile.reset(widget);
    } catch (error) {
      // Keep the form usable even if the widget API is not ready yet.
    }
  };

  const scrollToFormGroup = (form) => {
    const target = document.getElementById('grp-form') || (form && form.closest('#grp-form')) || form;
    if (!target || typeof target.scrollIntoView !== 'function') return;

    target.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
      inline: 'nearest',
    });
  };

  const setNotice = (notice, message, type) => {
    if (!notice) return;

    notice.hidden = true;
    notice.textContent = '';
    notice.classList.remove('is-info', 'is-error', 'is-success');

    if (!message) return;

    notice.textContent = message;
    notice.hidden = false;

    if (type === 'success') {
      notice.classList.add('is-success');
    } else if (type === 'error') {
      notice.classList.add('is-error');
    } else {
      notice.classList.add('is-info');
    }
  };

  const setFormState = (form, state) => {
    const body = form.querySelector('[data-wprc-form-body]');
    const submit = form.querySelector('button[type="submit"]');

    form.classList.toggle('is-submitting', state === 'submitting');
    form.classList.toggle('is-submitted', state === 'success');
    form.setAttribute('aria-busy', state === 'submitting' ? 'true' : 'false');

    if (body) {
      body.hidden = state === 'submitting' || state === 'success';
    }

    if (submit) {
      if (!submit.dataset.originalText) submit.dataset.originalText = submit.textContent || '';
      submit.disabled = state === 'submitting';
      submit.textContent = state === 'submitting' ? t('sending', 'Envoi en cours…') : submit.dataset.originalText;
    }
  };

  const bind = (form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      scrollToFormGroup(form);

      const notice = form.querySelector('[data-wprc-form-notice]');
      const body = new window.URLSearchParams(new window.FormData(form));
      body.set('action', 'wprc_opportunity_form_submit');
      body.set('nonce', cfg.nonce || '');
      const params = new window.URLSearchParams(window.location.search || '');
      ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((key) => {
        if (params.has(key) && !body.has(key)) body.set(key, params.get(key));
      });

      setNotice(notice, t('sending', 'Envoi en cours…'), 'info');

      setFormState(form, 'submitting');

      window.fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body,
      })
        .then((response) => response.json())
        .then((payload) => {
          if (!payload || !payload.success) {
            throw new Error((payload && payload.data && payload.data.message) || t('error', 'La demande n’a pas pu être envoyée. Merci de réessayer.'));
          }

          form.reset();
          enhanceFloatingFields(form);
          setFormState(form, 'success');
          setNotice(notice, (payload.data && payload.data.message) || '', 'success');
          scrollToFormGroup(form);
        })
        .catch((error) => {
          setFormState(form, 'idle');
          resetTurnstile(form);
          setNotice(notice, error.message || t('error', 'La demande n’a pas pu être envoyée. Merci de réessayer.'), 'error');
          scrollToFormGroup(form);
        });
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    enhanceFloatingFields(document);
    document.querySelectorAll('[data-wprc-opportunity-form]').forEach(bind);
  });
})();
