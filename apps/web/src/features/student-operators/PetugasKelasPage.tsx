import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AttendanceStatus, OperatorAssignmentSummary, OperatorReport, Paginated, RosterRow } from '@classora/shared-types';
import { ApiError, api } from '../../lib/api';
import { MODE_LABEL, STATUS_LABEL } from '../../lib/can';
import { formatDate, today } from '../../lib/dates';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { Button } from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { Empty, ErrorState, Loading, Notice } from '../../components/ui/States';

const ORDER: AttendanceStatus[] = ['present', 'sick', 'excused', 'absent'];

/** Menu siswa petugas kelas. Hanya muncul bila ada penugasan aktif (diputuskan backend lewat konteks). */
export function PetugasKelasPage() {
  const { ctx } = useSession();
  const assignments = ctx?.operator_assignments ?? [];
  const reports = useQuery({ queryKey: ['operator-reports', 'mine'], queryFn: () => api<Paginated<OperatorReport>>('/student-operators/reports') });

  if (assignments.length === 0) return <Empty title="Kamu belum menjadi petugas kelas">Guru akan menunjukmu bila ada penugasan. Menu ini akan muncul otomatis.</Empty>;

  return (
    <>
      <PageHeader title="Petugas Kelas" intro="Laporkan kehadiran kelasmu hari ini. Guru yang menetapkan status resmi." />
      {assignments.map((a) => <ReportForm key={a.id} assignment={a} reports={reports.data?.data ?? []} />)}

      <section className="mt-10">
        <h2 className="mb-2 text-xl">Laporan yang pernah kamu kirim</h2>
        {reports.isLoading ? <Loading /> : reports.isError ? <ErrorState error={reports.error} /> : (reports.data?.data.length ?? 0) === 0 ? <Empty title="Belum ada laporan" /> : (
          <ul className="border-t border-rule">
            {reports.data!.data.map((r) => (
              <li key={r.id} className="flex items-center justify-between border-b border-rule py-3 text-sm">
                <span>{r.class?.name} · {formatDate(r.report_date)}{r.revision > 1 ? ` · revisi ${r.revision}` : ''}</span>
                <StatusBadge status={r.status} />
              </li>
            ))}
          </ul>
        )}
      </section>
    </>
  );
}

function ReportForm({ assignment, reports }: { assignment: OperatorAssignmentSummary; reports: OperatorReport[] }) {
  const qc = useQueryClient();
  const date = today();
  const existing = reports.find((r) => r.assignment_id === assignment.id && r.report_date === date);
  const [marks, setMarks] = useState<Record<number, AttendanceStatus>>({});
  const [msg, setMsg] = useState<{ tone: 'ok' | 'error'; text: string } | null>(null);

  // Daftar siswa lewat endpoint khusus petugas (siswa tidak punya akses ke data kelas umum).
  const roster = useQuery({
    queryKey: ['operator-roster', assignment.class.id],
    queryFn: async () => {
      const r = await api<{ data: { id: number; name: string; nis: string }[] }>(`/student-operators/assignments/${assignment.id}/roster`);
      return r.data.map<RosterRow>((m) => ({ student_id: m.id, name: m.name, nis: m.nis, status: null, note: null }));
    },
    enabled: !existing || existing.status === 'rejected',
  });

  const submit = useMutation({
    mutationFn: () => {
      const entries = Object.entries(marks).map(([student_id, status]) => ({ student_id: Number(student_id), status }));
      const items = assignment.mode === 'report_only' ? entries.filter((e) => e.status !== 'present') : entries;
      return existing?.status === 'rejected'
        ? api(`/student-operators/reports/${existing.id}/resubmit`, { method: 'POST', body: { items } })
        : api('/student-operators/reports', { method: 'POST', body: { assignment_id: assignment.id, report_date: date, items } });
    },
    onSuccess: () => { setMsg({ tone: 'ok', text: 'Laporan terkirim. Menunggu pemeriksaan guru.' }); qc.invalidateQueries({ queryKey: ['operator-reports'] }); },
    onError: (e) => setMsg({ tone: 'error', text: e instanceof ApiError ? (e.firstDetail ?? e.message) : 'Gagal mengirim laporan.' }),
  });

  const rows = roster.data ?? [];
  const full = assignment.mode === 'full_attendance';
  const options = full ? ORDER : (['sick', 'excused', 'absent'] as AttendanceStatus[]);

  return (
    <section className="mb-8">
      <h2 className="text-xl">Kelas {assignment.class.name}</h2>
      <p className="text-sm text-muted">{MODE_LABEL[assignment.mode]} · berlaku sampai {formatDate(assignment.ends_on)}</p>

      {existing && existing.status !== 'rejected' ? (
        <p className="mt-3 flex items-center gap-2">Laporan hari ini: <StatusBadge status={existing.status} /></p>
      ) : (
        <>
          {existing?.status === 'rejected' && <Notice tone="error">Laporan hari ini ditolak guru. Perbaiki lalu kirim ulang.</Notice>}
          {msg && <Notice tone={msg.tone}>{msg.text}</Notice>}
          {roster.isLoading ? <Loading /> : roster.isError ? <ErrorState error={roster.error} retry={() => roster.refetch()} /> : (
            <ul className="mt-3 border-t-2 border-fg">
              {rows.map((r) => (
                <li key={r.student_id} className="border-b border-rule py-3">
                  <fieldset>
                    <legend className="mb-2 font-medium">{r.name}</legend>
                    <div className={`grid gap-1 ${full ? 'grid-cols-4' : 'grid-cols-4'}`}>
                      {!full && (
                        <label className={`flex min-h-11 cursor-pointer items-center justify-center rounded-sm border text-sm ${!marks[r.student_id] || marks[r.student_id] === 'present' ? 'border-primary bg-primary font-semibold text-primary-fg' : 'border-rule'}`}>
                          <input type="radio" className="sr-only" name={`o-${assignment.id}-${r.student_id}`} checked={!marks[r.student_id] || marks[r.student_id] === 'present'} onChange={() => { const { [r.student_id]: _, ...rest } = marks; setMarks(rest); }} />Hadir
                        </label>
                      )}
                      {options.map((s) => (
                        <label key={s} className={`flex min-h-11 cursor-pointer items-center justify-center rounded-sm border text-sm ${marks[r.student_id] === s ? 'border-primary bg-primary font-semibold text-primary-fg' : 'border-rule'}`}>
                          <input type="radio" className="sr-only" name={`o-${assignment.id}-${r.student_id}`} checked={marks[r.student_id] === s} onChange={() => setMarks({ ...marks, [r.student_id]: s })} />{STATUS_LABEL[s]}
                        </label>
                      ))}
                    </div>
                  </fieldset>
                </li>
              ))}
            </ul>
          )}
          <Button className="mt-4" disabled={submit.isPending || rows.length === 0 || (full && Object.keys(marks).length < rows.length)} onClick={() => submit.mutate()}>
            {existing?.status === 'rejected' ? 'Kirim ulang laporan' : 'Kirim laporan'}
          </Button>
          {full && <p className="mt-2 text-sm text-muted">Mode ini mewajibkan status untuk seluruh siswa ({Object.keys(marks).length}/{rows.length} terisi).</p>}
        </>
      )}
    </section>
  );
}
