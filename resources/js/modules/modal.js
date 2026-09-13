import { json } from './http';
import { toast } from './toast';

export function initModal() {
    const modal = document.getElementById('edit-translation-modal');
    if (!modal) return;
    const form = modal.querySelector('[data-translation-form]');
    const textarea = form.elements.translatedValue;
    const suggest = modal.querySelector('[data-suggest]');
    const save = modal.querySelector('[data-save]');
    const updateAll = modal.querySelector('[data-update-all]');
    const preview = modal.querySelector('[data-suggestion-preview]');
    const error = modal.querySelector('[data-modal-error]');
    const loading = modal.querySelector('[data-modal-loading]');
    const content = modal.querySelector('[data-modal-content]');
    const aiLoading = modal.querySelector('[data-suggestion-loading]');
    let request;
    let record;
    let trigger;
    let generation = 0;
    let revision = 0;
    let suggestion;
    textarea.addEventListener('input', () => revision++);
    const setBusy = (busy) => {
        suggest.disabled = save.disabled = updateAll.disabled = busy;
        aiLoading.hidden = !busy;
        suggest.setAttribute('aria-busy', String(busy));
    };
    const reportError = (exception) => {
        if (exception.name === 'AbortError') return;
        error.textContent = exception.message;
        error.hidden = false;
        toast(exception.message, 'WARNING', 5000);
    };
    const withFilters = (url) => {
        const target = new URL(url, location.href);
        target.search = location.search;
        return target.href;
    };
    document.querySelectorAll('[data-translation-url]').forEach(button => {
        button.addEventListener('click', async () => {
            request?.abort();
            request = new AbortController();
            const current = ++generation;
            trigger = button;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            loading.hidden = false;
            content.hidden = error.hidden = preview.hidden = true;
            record = null;
            revision = 0;
            setBusy(false);
            modal.querySelector('#translation-key').textContent = 'Translation';
            modal.showModal();
            try {
                const url = new URL(button.dataset.translationUrl, location.href);
                url.searchParams.set('example_language', document.getElementById('translateLanguageExampleId').value);
                const data = await json(url, { signal: request.signal });
                if (current !== generation) return;
                record = data;
                modal.querySelector('#translation-key').textContent = data.key;
                textarea.value = data.value;
                modal.querySelector('[data-example]').textContent = data.example?.value || 'No translation example is available.';
                form.action = withFilters(data.save_url);
                updateAll.formAction = withFilters(data.update_all_url);
                updateAll.hidden = !data.can_update_all;
                suggest.hidden = !data.can_suggest;
                const examples = modal.querySelector('[data-examples]');
                examples.replaceChildren();
                data.examples.forEach(example => {
                    const details = document.createElement('details');
                    const summary = document.createElement('summary');
                    summary.textContent = example.code;
                    const value = document.createElement('p');
                    value.className = 'whitespace-pre-wrap p-2';
                    value.textContent = example.value;
                    details.append(summary, value);
                    examples.append(details);
                });
                content.hidden = false;
                textarea.focus();
            } catch (exception) {
                if (current === generation) reportError(exception);
            } finally {
                if (current === generation) loading.hidden = true;
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    });
    suggest.addEventListener('click', async () => {
        if (!record) return;
        const current = generation;
        const before = textarea.value;
        const version = revision;
        error.hidden = preview.hidden = true;
        setBusy(true);
        try {
            const data = await json(record.suggest_url, {
                method: 'POST', signal: request.signal,
                body: { example_language: record.example.language_id },
            });
            if (current !== generation) return;
            suggestion = data.value;
            if (revision === 0 && version === revision && textarea.value === before) {
                textarea.value = suggestion;
                revision++;
            } else {
                modal.querySelector('[data-suggestion-text]').textContent = suggestion;
                preview.hidden = false;
                toast('Your draft was kept. Use the suggestion when ready.', 'INFO', 5000);
            }
        } catch (exception) {
            if (current === generation) reportError(exception);
        } finally {
            if (current === generation) setBusy(false);
        }
    });
    modal.querySelector('[data-use-suggestion]').addEventListener('click', () => {
        textarea.value = suggestion;
        revision++;
        preview.hidden = true;
        textarea.focus();
    });
    modal.querySelector('[data-modal-close]').addEventListener('click', () => modal.close());
    modal.addEventListener('click', event => {
        if (event.target !== modal) return;
        const box = modal.querySelector('.modal-box').getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) modal.close();
    });
    modal.addEventListener('close', () => {
        generation++;
        request?.abort();
        setBusy(false);
        trigger?.focus();
    });
    window.addEventListener('pagehide', () => request?.abort());
}
