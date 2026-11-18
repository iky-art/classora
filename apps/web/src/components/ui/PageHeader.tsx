import type { ReactNode } from 'react';

export function PageHeader({ title, intro, actions }: { title: string; intro?: string; actions?: ReactNode }) {
  return (
    <header className="mb-6 flex flex-wrap items-end justify-between gap-3 border-b-2 border-fg pb-3">
      <div>
        <h1 className="text-3xl md:text-4xl">{title}</h1>
        {intro && <p className="mt-1 max-w-prose text-sm text-muted">{intro}</p>}
      </div>
      {actions}
    </header>
  );
}
