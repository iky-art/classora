import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { api } from '../../lib/api';
import { STATUS_LABEL } from '../../lib/can';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { ErrorState, Loading } from '../../components/ui/States';

interface Dashboard {
  students?: number; teachers?: number; classes?: number; pending_reports?: number;
  attendance_today?: Record<'present' | 'sick' | 'excused' | 'absent', number>;
  recent_activity?: { action: string; created_at: string }[];
}

const ACTION_LABEL: Record<string, string> = {
  'attendance.saved': 'Absensi disimpan',
  'attendance.corrected': 'Absensi dikoreksi',
  'student.created': 'Siswa ditambahkan',
  'operator_report.submitted': 'Laporan petugas dikirim',
  'operator_report.approved': 'Laporan petugas disetujui',
  'operator_report.rejected': 'Laporan petugas ditolak',
  'class.enrolled': 'Anggota kelas diperbarui',
};

export function DashboardPage() {
  const { ctx } = useSession();
  const q = useQuery({ queryKey: ['dashboard', ctx?.school.slug], queryFn: () => api<{ data: Dashboard }>('/dashboard').then((r) => r.data) });

  if (q.isLoading) return <Loading />;
  if (q.isError) return <ErrorState error={q.error} retry={() => q.refetch()} />;
  const d = q.data!;
  const today = d.attendance_today;
  const total = today ? Object.values(today).reduce((a, b) => a + b, 0) : 0;

  return (
    <>
      <PageHeader title={ctx?.school.name ?? 'Beranda'} intro="Ringkasan hari ini sesuai peranmu." />

      {typeof d.pending_reports === 'number' && d.pending_reports > 0 && (
        <p className="mb-6 border-l-2 border-accent pl-4">
          {d.pending_reports} laporan petugas kelas menunggu diperiksa. <Link to="/laporan-petugas" className="underline">Periksa sekarang</Link>
        </p>
      )}

      <dl className="grid grid-cols-3 gap-x-6 border-b border-rule pb-6">
        {([['Siswa aktif', d.students], ['Guru', d.teachers], ['Kelas', d.classes]] as const).filter(([, v]) => v !== undefined).map(([label, v]) => (
          <div key={label}><dt className="text-sm text-muted">{label}</dt><dd className="font-serif text-4xl tabular-nums">{v}</dd></div>
        ))}
      </dl>

      {today && (
        <section className="mt-8">
          <h2 className="text-xl">Kehadiran hari ini</h2>
          {total === 0 ? (
            <p className="mt-2 text-sm text-muted">Belum ada absensi yang tercatat hari ini.</p>
          ) : (
            <>
              <div className="mt-3 flex h-3 overflow-hidden rounded-sm border border-rule" role="img" aria-label={`Hadir ${today.present} dari ${total} tercatat`}>
                <div className="bg-primary" style={{ width: `${(today.present / total) * 100}%` }} />
                <div className="bg-accent" style={{ width: `${((today.sick + today.excused) / total) * 100}%` }} />
                <div className="bg-danger" style={{ width: `${(today.absent / total) * 100}%` }} />
              </div>
              <ul className="mt-3 flex flex-wrap gap-x-6 text-sm">
                {(Object.keys(STATUS_LABEL) as (keyof typeof STATUS_LABEL)[]).map((k) => <li key={k}>{STATUS_LABEL[k]} <b className="tabular-nums">{today[k]}</b></li>)}
              </ul>
            </>
          )}
        </section>
      )}

      {d.recent_activity && (
        <section className="mt-8">
          <h2 className="text-xl">Aktivitas terbaru</h2>
          {d.recent_activity.length === 0 ? <p className="mt-2 text-sm text-muted">Belum ada aktivitas.</p> : (
            <ul className="mt-2 border-t border-rule">
              {d.recent_activity.map((a, i) => (
                <li key={i} className="flex justify-between border-b border-rule py-2 text-sm">
                  <span>{ACTION_LABEL[a.action] ?? a.action}</span>
                  <time className="text-muted" dateTime={a.created_at}>{new Date(a.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</time>
                </li>
              ))}
            </ul>
          )}
        </section>
      )}
    </>
  );
}
