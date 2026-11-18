import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AttendanceStatus, ClassItem, OperatorAssignment, OperatorMode, OperatorReport, Paginated, Student } from '@classora/shared-types';
import { ApiError, api } from '../../lib/api';
import { MODE_LABEL, STATUS_LABEL } from '../../lib/can';
import { addDays, formatDate, today } from '../../lib/dates';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { Button } from '../../components/ui/Button';
import { SelectInput, TextInput } from '../../components/forms/Field';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { Empty, ErrorState, Loading, Notice } from '../../components/ui/States';

export function OperatorReviewPage() {
  const { can } = useSession();
  const qc = useQueryClient();
  const [openId, setOpenId] = useState<number | null>(null);
  const [note, setNote] = useState('');
  const [msg, setMsg] = useState<{ tone: 'ok' | 'error'; text: string } | null>(null);

  const reports = useQuery({ queryKey: ['operator-reports', 'review'], queryFn: () => api<Paginated<OperatorReport>>('/student-operators/reports', { query: { status: 'pending' } }) });
  const detail = useQuery({ queryKey: ['operator-report', openId], queryFn: () => api<{ data: OperatorReport }>(`/student-operators/reports/${openId}`), enabled: openId !== null });

  const done = (text: string) => { setMsg({ tone: 'ok', text }); setOpenId(null); setNote(''); qc.invalidateQueries({ queryKey: ['operator-reports'] }); qc.invalidateQueries({ queryKey: ['dashboard'] }); };
  const fail = (e: unknown) => setMsg({ tone: 'error', text: e instanceof ApiError ? (e.firstDetail ?? e.message) : 'Gagal memproses laporan.' });

  const approve = useMutation({ mutationFn: () => api(`/student-operators/reports/${openId}/approve`, { method: 'POST', body: { note: note || undefined } }), onSuccess: () => done('Laporan disetujui dan absensi resmi tersimpan.'), onError: fail });
  const reject = useMutation({ mutationFn: () => api(`/student-operators/reports/${openId}/reject`, { method: 'POST', body: { note } }), onSuccess: () => done('Laporan ditolak dan dikembalikan ke petugas.'), onError: fail });

  return (
    <>
      <PageHeader title="Laporan Petugas" intro="Periksa laporan dari petugas kelas. Hanya keputusanmu yang menetapkan absensi resmi." />
      {msg && <Notice tone={msg.tone}>{msg.text}</Notice>}

      <h2 className="mb-2 mt-4 text-xl">Menunggu pemeriksaan</h2>
      {reports.isLoading ? <Loading /> : reports.isError ? <ErrorState error={reports.error} retry={() => reports.refetch()} /> : reports.data!.data.length === 0 ? <Empty title="Tidak ada laporan yang menunggu" /> : (
        <ul className="border-t-2 border-fg">
          {reports.data!.data.map((r) => (
            <li key={r.id} className="border-b border-rule py-3">
              <button className="flex min-h-11 w-full items-center justify-between text-left" onClick={() => { setOpenId(openId === r.id ? null : r.id); setMsg(null); }} aria-expanded={openId === r.id}>
                <span className="font-medium">{r.class?.name} · {formatDate(r.report_date)}{r.revision > 1 ? ` · revisi ${r.revision}` : ''}</span>
                <StatusBadge status={r.status} />
              </button>
              {openId === r.id && (
                <div className="mt-2">
                  {detail.isLoading ? <Loading /> : detail.data && (
                    <>
                      <p className="mb-1 text-sm text-muted">{r.mode ? MODE_LABEL[r.mode] : ''} · {detail.data.data.items?.length ?? 0} siswa dilaporkan</p>
                      <ul className="mb-3 border-t border-rule text-sm">
                        {detail.data.data.items?.map((i) => <li key={i.student_id} className="flex justify-between border-b border-rule py-1.5"><span>Siswa #{i.student_id}{i.note ? ` — ${i.note}` : ''}</span><b>{STATUS_LABEL[i.status as AttendanceStatus]}</b></li>)}
                      </ul>
                      <div className="max-w-md"><TextInput label="Catatan untuk petugas" value={note} onChange={(e) => setNote(e.target.value)} hint="Wajib bila menolak." /></div>
                      <div className="mt-3 flex gap-3">
                        <Button disabled={approve.isPending || reject.isPending} onClick={() => approve.mutate()}>Setujui</Button>
                        <Button variant="danger" disabled={!note.trim() || approve.isPending || reject.isPending} onClick={() => reject.mutate()}>Tolak</Button>
                      </div>
                    </>
                  )}
                </div>
              )}
            </li>
          ))}
        </ul>
      )}

      {can('operators.assign') && <AssignSection />}
    </>
  );
}

function AssignSection() {
  const qc = useQueryClient();
  const [classId, setClassId] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [msg, setMsg] = useState('');

  const classes = useQuery({ queryKey: ['classes'], queryFn: () => api<Paginated<ClassItem>>('/classes') });
  const members = useQuery({ queryKey: ['class', classId], queryFn: () => api<{ members: Student[] }>(`/classes/${classId}`), enabled: !!classId });
  const assignments = useQuery({ queryKey: ['assignments'], queryFn: () => api<{ data: OperatorAssignment[] }>('/student-operators/assignments') });

  const create = useMutation({
    mutationFn: (body: unknown) => api('/student-operators/assignments', { method: 'POST', body }),
    onSuccess: () => { setMsg('Petugas kelas ditunjuk.'); setErrors({}); qc.invalidateQueries({ queryKey: ['assignments'] }); },
    onError: (e) => { if (e instanceof ApiError && e.details) setErrors(Object.fromEntries(Object.entries(e.details).map(([k, v]) => [k, v[0]]))); else setErrors({ form: e instanceof ApiError ? e.message : 'Gagal menunjuk petugas.' }); },
  });
  const revoke = useMutation({ mutationFn: (id: number) => api(`/student-operators/assignments/${id}/revoke`, { method: 'POST' }), onSuccess: () => qc.invalidateQueries({ queryKey: ['assignments'] }) });

  return (
    <section className="mt-12">
      <h2 className="mb-2 text-xl">Tunjuk petugas kelas</h2>
      {msg && <Notice>{msg}</Notice>}
      {errors.form && <Notice tone="error">{errors.form}</Notice>}
      <form className="grid gap-4 border-b border-rule pb-6 md:grid-cols-3" onSubmit={(e) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        create.mutate({ class_id: Number(classId), student_id: Number(f.get('student_id')), mode: f.get('mode'), starts_on: f.get('starts_on'), ends_on: f.get('ends_on') });
      }}>
        <SelectInput label="Kelas" value={classId} onChange={(e) => setClassId(e.target.value)} error={errors.class_id}>
          <option value="">Pilih kelas…</option>{classes.data?.data.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
        </SelectInput>
        <SelectInput label="Siswa" name="student_id" defaultValue="" error={errors.student_id}>
          <option value="" disabled>Pilih siswa…</option>{members.data?.members.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </SelectInput>
        <SelectInput label="Mode" name="mode" defaultValue="report_only" error={errors.mode}>
          {(Object.keys(MODE_LABEL) as OperatorMode[]).map((m) => <option key={m} value={m}>{MODE_LABEL[m]}</option>)}
        </SelectInput>
        <TextInput label="Mulai" name="starts_on" type="date" defaultValue={today()} error={errors.starts_on} />
        <TextInput label="Berakhir" name="ends_on" type="date" defaultValue={addDays(today(), 7)} error={errors.ends_on} />
        <div className="flex items-end"><Button type="submit" disabled={!classId || create.isPending}>Tunjuk petugas</Button></div>
      </form>

      <h3 className="mb-2 mt-6 text-lg">Penugasan</h3>
      {assignments.isLoading ? <Loading /> : (assignments.data?.data.length ?? 0) === 0 ? <Empty title="Belum ada penugasan" /> : (
        <ul className="border-t border-rule">
          {assignments.data!.data.map((a) => (
            <li key={a.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-rule py-3 text-sm">
              <span><b>{a.student.name}</b> · {a.class.name} · {MODE_LABEL[a.mode]} · {formatDate(a.starts_on)} – {formatDate(a.ends_on)}</span>
              {a.status === 'active' ? <Button variant="quiet" className="min-h-9" onClick={() => revoke.mutate(a.id)}>Cabut</Button> : <span className="text-muted">Dicabut</span>}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
