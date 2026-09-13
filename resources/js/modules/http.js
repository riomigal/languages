export async function json(url, { method = 'GET', body, signal } = {}) {
    const response = await fetch(url, {
        method, signal, credentials: 'same-origin', cache: 'no-store',
        headers: {
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
            ...(method !== 'GET' ? { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } : {}),
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(data.message || 'The request failed. Please try again.');
        error.status = response.status;
        throw error;
    }
    return data;
}
