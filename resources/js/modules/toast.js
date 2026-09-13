const TYPES = ['SUCCESS', 'DELETED', 'INFO', 'WARNING'];
const colors = {
    SUCCESS: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    DELETED: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    INFO: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    WARNING: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
};

export function toast(message, type = 'SUCCESS', duration = 3000) {
    const host = document.getElementById('toasts');
    if (!host || !message) return;
    if (!TYPES.includes(type)) type = 'INFO';
    const element = document.createElement('div');
    element.className = `toast ${type} flex gap-4 p-4 rounded-lg shadow max-w-md ${colors[type]}`;
    element.setAttribute('role', 'status');
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = 'Close';
    close.setAttribute('aria-label', 'Dismiss notification');
    const timer = setTimeout(() => element.remove(), Number.isFinite(duration) ? Math.max(0, duration) : 3000);
    close.addEventListener('click', () => { clearTimeout(timer); element.remove(); });
    element.append(text, close);
    host.append(element);
}

export function initToasts() {
    const data = JSON.parse(document.getElementById('toast-data')?.textContent ?? 'null');
    if (data) toast(data.message, data.type, data.duration);
    document.addEventListener('languages:toast', ({ detail }) => toast(detail.message, detail.type, detail.duration));
}
