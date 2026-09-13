export function initFilters() {
    document.querySelectorAll('[data-toggle]').forEach(button => {
        const panel = document.getElementById(button.dataset.toggle);
        if (!panel) return;
        button.addEventListener('click', () => {
            const opening = panel.hidden || panel.classList.contains('hidden');
            panel.classList.remove('hidden');
            panel.hidden = !opening;
            button.setAttribute('aria-expanded', String(opening));
        });
    });
    const key = `languages:state-filters:${location.pathname}`;
    try {
        if (sessionStorage.getItem(key)) {
            sessionStorage.removeItem(key);
            const panel = document.getElementById('state-filter-options');
            if (panel) {
                panel.hidden = false;
                document.querySelector('[data-toggle="state-filter-options"]').setAttribute('aria-expanded', 'true');
            }
        }
    } catch (_) {}
    document.querySelectorAll('[data-state-filter]').forEach(form => form.addEventListener('submit', () => {
        try { sessionStorage.setItem(key, 'open'); } catch (_) {}
    }));
    document.querySelectorAll('[data-filter-form]').forEach(form => {
        form.addEventListener('change', () => form.requestSubmit());
    });
    document.querySelectorAll('[data-autosave]').forEach(form => {
        form.querySelector('[data-save-fallback]').hidden = true;
        form.addEventListener('change', () => form.requestSubmit());
    });
}
