import type { ReactNode } from 'react';
import { ApiError } from '../../lib/api';

export function Loading({ label = 'Memuat…' }: { label?: string }) {
  return <p role="status" className="py-10 text-sm text-muted">{label}</p>;
}

export function Empty({ title, children }: { title: string; children?: ReactNode }) {
  return (
    <div className="border-y border-rule py-10">
      <p className="font-serif text-lg">{title}</p>
      {children && <p className="mt-1 max-w-prose text-sm text-muted">{children}</p>}
    </div>
  );
}

export function ErrorState({ error, retry }: { error: unknown; retry?: () => void }) {
  const message = error instanceof ApiError ? error.message : 'Data tidak dapat dimuat. Periksa koneksi lalu coba lagi.';
  return (
    <div role="alert" className="border-l-2 border-danger py-2 pl-4">
      <p className="text-sm">{message}</p>
      {retry && <button onClick={retry} className="mt-1 text-sm underline">Coba lagi</button>}
    </div>
  );
}

export function Notice({ tone = 'ok', children }: { tone?: 'ok' | 'error'; children: ReactNode }) {
  return (
    <p role={tone === 'error' ? 'alert' : 'status'} className={`border-l-2 py-1 pl-3 text-sm ${tone === 'error' ? 'border-danger text-danger' : 'border-primary'}`}>
      {children}
    </p>
  );
}
