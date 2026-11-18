import type { ReactNode } from 'react';

export interface Column<T> {
  key: string;
  header: string;
  cell: (row: T) => ReactNode;
  align?: 'left' | 'right';
}

/** Tabel editorial: garis tegas di header, garis tipis antarbaris; di layar kecil berubah jadi daftar bertumpuk. */
export function DataTable<T>({ columns, rows, rowKey, caption }: { columns: Column<T>[]; rows: T[]; rowKey: (row: T) => string | number; caption: string }) {
  const [first, ...rest] = columns;

  return (
    <>
      <table className="hidden w-full border-collapse text-sm md:table">
        <caption className="sr-only">{caption}</caption>
        <thead>
          <tr className="border-b-2 border-fg text-left">
            {columns.map((c) => (
              <th key={c.key} scope="col" className={`py-2 pr-4 font-semibold ${c.align === 'right' ? 'text-right' : ''}`}>{c.header}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={rowKey(row)} className="border-b border-rule">
              {columns.map((c) => (
                <td key={c.key} className={`py-3 pr-4 align-top ${c.align === 'right' ? 'text-right tabular-nums' : ''}`}>{c.cell(row)}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>

      <ul className="md:hidden" aria-label={caption}>
        {rows.map((row) => (
          <li key={rowKey(row)} className="border-b border-rule py-3">
            <div className="font-medium">{first.cell(row)}</div>
            <dl className="mt-1 grid grid-cols-[7rem_1fr] gap-x-3 gap-y-0.5 text-sm">
              {rest.map((c) => (
                <div key={c.key} className="contents">
                  <dt className="text-muted">{c.header}</dt>
                  <dd>{c.cell(row)}</dd>
                </div>
              ))}
            </dl>
          </li>
        ))}
      </ul>
    </>
  );
}
