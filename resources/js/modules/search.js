export function initSearch() {
    document.querySelectorAll('[data-search]').forEach(form => {
        const input = form.elements.search;
        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form.requestSubmit(), 350);
        });
        form.addEventListener('submit', () => clearTimeout(timer));
        window.addEventListener('pagehide', () => clearTimeout(timer));
    });
}
