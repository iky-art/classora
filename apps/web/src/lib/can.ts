/** Hanya untuk menyembunyikan menu. Otorisasi sebenarnya selalu diputuskan backend. */
export function can(permissions: readonly string[] | undefined, permission: string): boolean {
  return !!permissions && permissions.includes(permission);
}

export const STATUS_LABEL = {
  present: 'Hadir',
  sick: 'Sakit',
  excused: 'Izin',
  absent: 'Alpa',
} as const;

export const MODE_LABEL = {
  report_only: 'Lapor yang tidak hadir',
  full_attendance: 'Isi absensi seluruh kelas',
} as const;

export const REPORT_STATUS_LABEL = {
  pending: 'Menunggu',
  approved: 'Disetujui',
  rejected: 'Ditolak',
} as const;
