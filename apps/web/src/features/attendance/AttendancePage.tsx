import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AttendanceStatus, ClassItem, Paginated, SessionDetail } from '@classora/shared-types';
import { ApiError, api } from '../../lib/api';
import { STATUS_LABEL } from '../../lib/can';
import { today } from '../../lib/dates';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { Button } from '../../components/ui/Button';
import { SelectInput, TextInput } from '../../components/forms/Field';
import { Empty, ErrorState, Loading, Notice } from '../../components/ui/States';

const ORDER: AttendanceStatus[] = ['present', 'sick', 'excused', 'absent'];

export function AttendancePage() {
  const { can } = useSession();
  const qc = useQueryClient();
  const [classId, setClassId] = useState('');
  const [date, setDate] = useState(today());
  const [sessionId, setSessionId] = useState<number | null>(null);
  const [marks, setMarks] = useState<Record<number, AttendanceStatus>>({});
  const [reason, setReason] = useState('');
  const [msg, setMsg] = useState<{ tone: 'ok' | 'error'; text: string } | null>(null);

  const classes = useQuery({ queryKey: ['classes'], queryFn: () => api<Paginated<ClassItem>>('/classes') });
  const session = useQuery({ queryKey: ['session', sessionId], queryFn: () => api<SessionDetail>(`/attendance/sessions/${sessionId}`), enabled: sessionId !== null });

  useEffect(() => {
    if (!session.data) return;
    setMarks(Object.fromEntries(session.data.roster.filter((r) => r.status).map((r) => [r.student_id, r.status as AttendanceStatus])));
  }, [session.data]);

  const open = useMutation({
    mutationFn: () => api<SessionDetail>('/attendance/sessions', { method: 'POST', body: { class_id: Number(classId), session_date: date } }),
    onSuccess: (r) => { setMsg(null); setReason(''); setSessionId(r.data.id); qc.setQueryData(['session', r.data.id], r); },
    onError: (e) => setMsg({ tone: 'error', text: e instanceof ApiError ? (e.firstDetail ?? e.message) : 'Gagal membuka sesi.' }),
  });

  const save = useMutation({
    mutationFn: () => api<{ data: { created: number; corrected: number; unchanged: number } }>(`/attendance/sessions/${sessionId}/records`, {
      method: 'POST',
      body: { records: Object.entries(marks).map(([student_id, status]) => ({ student_id: Number(student_id), status })), reason: reason || undefined },
    }),
    onSuccess: (r) => { setMsg({ tone: 'ok', text: `Tersimpan: ${r.data.created} baru, ${r.data.corrected} dikoreksi.` }); qc.invalidateQueries({ queryKey: ['session', sessionId] }); },
    onError: (e) => setMsg({ tone: 'error', text: e instanceof ApiError ? (e.firstDetail ?? e.message) : 'Gagal menyimpan.' }),
  });

  const roster = session.data?.roster ?? [];
  const markAll = (s: AttendanceStatus) => setMarks(Object.fromEntries(roster.map((r) => [r.student_id, s])));
  const changesExisting = roster.some((r) => r.status && marks[r.student_id] && marks[r.student_id] !== r.status);

  return (
    <>
      <PageHeader title="Absensi" intro="Pilih kelas dan tanggal, lalu isi status tiap siswa. Mengubah catatan yang sudah ada dihitung sebagai koreksi dan wajib beralasan." />

      <div className="mb-6 grid items-end gap-4 border-b border-rule pb-6 md:grid-cols-[1fr_12rem_auto]">
        <SelectInput label="Kelas" value={classId} onChange={(e) => { setClassId(e.target.value); setSessionId(null); }}>
          <option value="">Pilih kelas…</option>{classes.data?.data.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
        </SelectInput>
        <TextInput label="Tanggal" type="date" max={today()} value={date} onChange={(e) => { setDate(e.target.value); setSessionId(null); }} />
        <Button disabled={!classId || !date || open.isPending || !can('attendance.record')} onClick={() => open.mutate()}>Buka daftar hadir</Button>
      </div>

      {msg && <Notice tone={msg.tone}>{msg.text}</Notice>}
      {classes.isError && <ErrorState error={classes.error} />}

      {sessionId === null ? (
        <Empty title="Belum ada daftar hadir yang dibuka">Pilih kelas dan tanggal, lalu tekan “Buka daftar hadir”.</Empty>
      ) : session.isLoading ? <Loading /> : session.isError ? <ErrorState error={session.error} retry={() => session.refetch()} /> :
        roster.length === 0 ? <Empty title="Tidak ada siswa terdaftar pada tanggal ini" /> : (
          <>
            <div className="mb-2 flex flex-wrap items-center gap-2 text-sm">
              <span className="mr-2 font-medium">Tandai semua:</span>
              {ORDER.map((s) => <Button key={s} variant="quiet" className="min-h-9 px-3" onClick={() => markAll(s)}>{STATUS_LABEL[s]}</Button>)}
            </div>
            <ul className="border-t-2 border-fg">
              {roster.map((r) => (
                <li key={r.student_id} className="border-b border-rule py-3">
                  <fieldset>
                    <legend className="mb-2 font-medium">{r.name} <span className="text-sm font-normal text-muted">{r.nis}</span></legend>
                    <div className="grid grid-cols-4 gap-1">
                      {ORDER.map((s) => (
                        <label key={s} className={`flex min-h-11 cursor-pointer items-center justify-center rounded-sm border text-sm ${marks[r.student_id] === s ? 'border-primary bg-primary font-semibold text-primary-fg' : 'border-rule'} has-[:focus-visible]:outline has-[:focus-visible]:outline-2`}>
                          <input type="radio" className="sr-only" name={`s-${r.student_id}`} checked={marks[r.student_id] === s} onChange={() => setMarks({ ...marks, [r.student_id]: s })} />
                          {STATUS_LABEL[s]}
                        </label>
                      ))}
                    </div>
                  </fieldset>
                </li>
              ))}
            </ul>
            {changesExisting && <div className="mt-4 max-w-md"><TextInput label="Alasan koreksi" value={reason} onChange={(e) => setReason(e.target.value)} hint="Wajib karena ada status yang berubah dari catatan sebelumnya." /></div>}
            <Button className="mt-4" disabled={Object.keys(marks).length === 0 || save.isPending || (changesExisting && !reason)} onClick={() => save.mutate()}>
              {save.isPending ? 'Menyimpan…' : 'Simpan absensi'}
            </Button>
          </>
        )}
    </>
  );
}
