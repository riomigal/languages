import { json } from './http';
import { toast } from './toast';
import { visiblePoll } from './polling';

export function initNotifications() {
    const host = document.getElementById('notifications');
    if (!host) return;
    const list = host.querySelector('[data-notification-items]');
    let mutation = false;
    let revision = 0;
    let warned = false;
    function render(data) {
        host.querySelector('[data-count]').textContent = data.notifications.length;
        list.replaceChildren();
        data.notifications.forEach(notification => {
            const item = document.createElement('li');
            const message = document.createElement('p');
            message.textContent = notification.message;
            const time = document.createElement('small');
            time.textContent = notification.date_time;
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = 'Mark as read';
            button.className = 'block text-blue-700 dark:text-blue-300 p-2';
            button.addEventListener('click', () => read(notification.read_url, button));
            item.append(message, time, button);
            list.append(item);
        });
    }
    async function read(url, button) {
        if (mutation) return;
        mutation = true;
        revision++;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        try { render(await json(url, { method: 'POST', body: { read: true } })); }
        catch (error) { toast(error.message, 'WARNING'); }
        finally {
            mutation = false;
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }
    const poller = visiblePoll(async signal => {
        if (mutation) return;
        const version = revision;
        try {
            const data = await json(host.dataset.url, { signal });
            if (version === revision) render(data);
            warned = false;
        } catch (error) {
            if (error.name === 'AbortError') return;
            if ([401, 403, 419].includes(error.status)) poller.stop();
            if (!warned) toast('Notifications could not be refreshed.', 'WARNING');
            warned = true;
        }
    }, 5000);
    const all = host.querySelector('[data-read-all]');
    all.addEventListener('click', () => read(host.dataset.readAllUrl, all));
    poller.start();
}
