const pad = (n: number) => String(n).padStart(2, '0');

export const toISO = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
export const today = () => toISO(new Date());

export function range(preset: 'day' | 'week' | 'month', ref = new Date()): { from: string; to: string } {
  if (preset === 'day') return { from: toISO(ref), to: toISO(ref) };
  if (preset === 'month') return { from: toISO(new Date(ref.getFullYear(), ref.getMonth(), 1)), to: toISO(new Date(ref.getFullYear(), ref.getMonth() + 1, 0)) };
  const day = (ref.getDay() + 6) % 7; // Senin = 0
  const start = new Date(ref);
  start.setDate(ref.getDate() - day);
  const end = new Date(start);
  end.setDate(start.getDate() + 6);
  return { from: toISO(start), to: toISO(end) };
}

export const formatDate = (iso: string) =>
  new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

export function addDays(iso: string, days: number): string {
  const d = new Date(iso + 'T00:00:00');
  d.setDate(d.getDate() + days);
  return toISO(d);
}
