import type { ApiErrorBody } from '@classora/shared-types';

const BASE = '/api/v1';
const SCHOOL_KEY = 'classora.school';

export class ApiError extends Error {
  constructor(
    public status: number,
    public code: string,
    message: string,
    public details?: Record<string, string[]> | null,
  ) {
    super(message);
  }

  /** Pesan pertama dari validasi, jika ada. */
  get firstDetail(): string | undefined {
    return this.details ? Object.values(this.details)[0]?.[0] : undefined;
  }
}

export const school = {
  get: () => localStorage.getItem(SCHOOL_KEY),
  set: (slug: string) => localStorage.setItem(SCHOOL_KEY, slug),
  clear: () => localStorage.removeItem(SCHOOL_KEY),
};

let csrfReady = false;

async function ensureCsrf() {
  if (csrfReady) return;
  await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
  csrfReady = true;
}

function xsrfToken(): string {
  const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
  return m ? decodeURIComponent(m[1]) : '';
}

type Options = { method?: 'GET' | 'POST' | 'PUT' | 'DELETE'; body?: unknown; query?: Record<string, string | number | undefined | null> };

export async function api<T>(path: string, { method = 'GET', body, query }: Options = {}): Promise<T> {
  const mutating = method !== 'GET';
  if (mutating) await ensureCsrf();

  const qs = query
    ? '?' + new URLSearchParams(Object.entries(query).filter(([, v]) => v !== undefined && v !== null && v !== '').map(([k, v]) => [k, String(v)])).toString()
    : '';

  const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (mutating) headers['X-XSRF-TOKEN'] = xsrfToken();
  const slug = school.get();
  if (slug) headers['X-School'] = slug;

  const res = await fetch(BASE + path + (qs === '?' ? '' : qs), {
    method,
    headers,
    credentials: 'include',
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (res.status === 204) return undefined as T;
  const data = (await res.json().catch(() => null)) as (T & Partial<ApiErrorBody>) | null;
  if (!res.ok) {
    const err = data?.error;
    throw new ApiError(res.status, err?.code ?? 'unknown', err?.message ?? 'Terjadi kesalahan. Coba lagi.', err?.details);
  }
  return data as T;
}
