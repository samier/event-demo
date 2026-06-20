/** Read a cookie value by name (used for Laravel's XSRF token). */
function cookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

export interface PostResult<T> {
    ok: boolean;
    status: number;
    data: T;
}

/**
 * POST JSON to the app with Laravel's CSRF token attached. Returns the parsed body
 * and status without throwing, so callers can handle 422 validation errors inline.
 */
export async function postJson<T = unknown>(
    url: string,
    body: Record<string, unknown>,
): Promise<PostResult<T>> {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': cookie('XSRF-TOKEN') ?? '',
        },
        body: JSON.stringify(body),
    });

    const data = (await res.json().catch(() => ({}))) as T;

    return { ok: res.ok, status: res.status, data };
}
