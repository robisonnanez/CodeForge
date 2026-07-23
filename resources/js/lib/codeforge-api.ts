export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly data: unknown,
    ) {
        super(message);
    }
}

function csrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta?.getAttribute('content') ?? '';
}

export async function apiRequest<T>(
    url: string,
    init: RequestInit = {},
): Promise<T> {
    const headers = new Headers(init.headers);

    headers.set('Accept', 'application/json');

    if (
        !headers.has('Content-Type') &&
        init.body &&
        !(init.body instanceof FormData)
    ) {
        headers.set('Content-Type', 'application/json');
    }

    if (!['GET', 'HEAD'].includes((init.method ?? 'GET').toUpperCase())) {
        headers.set('X-CSRF-TOKEN', csrfToken());
    }

    const versionedUrl =
        url.startsWith('/api/') && !url.startsWith('/api/v1/')
            ? `/api/v1/${url.slice('/api/'.length)}`
            : url;

    const response = await fetch(versionedUrl, {
        credentials: 'same-origin',
        ...init,
        headers,
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(
            payload &&
                typeof payload === 'object' &&
                'message' in payload &&
                typeof payload.message === 'string'
                ? payload.message
                : `Request failed with status ${response.status}.`,
            response.status,
            payload,
        );
    }

    return payload as T;
}
