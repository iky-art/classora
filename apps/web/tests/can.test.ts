import { describe, expect, it } from 'vitest';
import { can } from '../src/lib/can';
import { addDays, range } from '../src/lib/dates';

describe('can', () => {
  it('mengizinkan hanya permission yang dimiliki', () => {
    expect(can(['students.view'], 'students.view')).toBe(true);
    expect(can(['students.view'], 'students.manage')).toBe(false);
    expect(can(undefined, 'students.view')).toBe(false);
  });
});

describe('dates', () => {
  it('menghitung minggu mulai Senin', () => {
    expect(range('week', new Date('2026-11-18T10:00:00'))).toEqual({ from: '2026-11-16', to: '2026-11-22' });
  });
  it('menghitung rentang bulan', () => {
    expect(range('month', new Date('2026-02-10T10:00:00'))).toEqual({ from: '2026-02-01', to: '2026-02-28' });
  });
  it('menambah hari lintas bulan', () => {
    expect(addDays('2026-11-28', 5)).toBe('2026-12-03');
  });
});
