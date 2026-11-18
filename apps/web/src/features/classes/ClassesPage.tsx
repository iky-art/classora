import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import type { ClassItem, Paginated, Student } from '@classora/shared-types';
import { classSchema } from '@classora/api-contracts';
import { ApiError, api } from '../../lib/api';
import { today } from '../../lib/dates';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { DataTable } from '../../components/data-table/DataTable';
import { Button } from '../../components/ui/Button';
import { SelectInput, TextInput } from '../../components/forms/Field';
import { Empty, ErrorState, Loading, Notice } from '../../components/ui/States';

export function ClassesPage() {
  const { can } = useSession();
  const qc = useQueryClient();
  const [adding, setAdding] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const list = useQuery({ queryKey: ['classes'], queryFn: () => api<Paginated<ClassItem>>('/classes') });
  const years = useQuery({ queryKey: ['ref', 'years'], queryFn: () => api<{ data: { id: number; name: string }[] }>('/reference/academic-years'), enabled: adding });
  const grades = useQuery({ queryKey: ['ref', 'grades'], queryFn: () => api<{ data: { id: number; name: string }[] }>('/reference/grades'), enabled: adding });

  const create = useMutation({
    mutationFn: (body: unknown) => api('/classes', { method: 'POST', body }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['classes'] }); setAdding(false); },
    onError: (e) => { if (e instanceof ApiError && e.details) setErrors(Object.fromEntries(Object.entries(e.details).map(([k, v]) => [k, v[0]]))); },
  });

  function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const f = new FormData(e.currentTarget);
    const parsed = classSchema.safeParse({ name: f.get('name'), academic_year_id: f.get('academic_year_id'), grade_id: f.get('grade_id') });
    if (!parsed.success) { setErrors(Object.fromEntries(parsed.error.issues.map((i) => [String(i.path[0]), i.message]))); return; }
    setErrors({});
    create.mutate(parsed.data);
  }

  return (
    <>
      <PageHeader title="Kelas" intro="Kelas per tahun ajaran. Anggota yang pindah kelas tetap tercatat di riwayatnya."
        actions={can('classes.manage') && <Button onClick={() => setAdding((v) => !v)}>{adding ? 'Tutup formulir' : 'Tambah kelas'}</Button>} />

      {adding && (
        <form onSubmit={submit} noValidate className="mb-8 grid gap-4 border-b border-rule pb-6 md:grid-cols-3">
          <TextInput label="Nama kelas" name="name" placeholder="mis. X-A" error={errors.name} />
          <SelectInput label="Tahun ajaran" name="academic_year_id" defaultValue="" error={errors.academic_year_id}>
            <option value="" disabled>Pilih…</option>{years.data?.data.map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}
          </SelectInput>
          <SelectInput label="Tingkat" name="grade_id" defaultValue="" error={errors.grade_id}>
            <option value="" disabled>Pilih…</option>{grades.data?.data.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
          </SelectInput>
          {years.data && years.data.data.length === 0 && <p className="text-sm text-muted md:col-span-3">Belum ada tahun ajaran. Tahun ajaran dan tingkat perlu dibuat terlebih dahulu (pengelolaannya belum ada di antarmuka ini).</p>}
          <div className="md:col-span-3"><Button type="submit" disabled={create.isPending}>Simpan kelas</Button></div>
        </form>
      )}

      {list.isLoading ? <Loading /> : list.isError ? <ErrorState error={list.error} retry={() => list.refetch()} /> :
        list.data!.data.length === 0 ? <Empty title="Belum ada kelas">Tambahkan kelas pertama untuk mulai mengelola anggota dan absensi.</Empty> : (
          <DataTable caption="Daftar kelas" rows={list.data!.data} rowKey={(c) => c.id} columns={[
            { key: 'name', header: 'Kelas', cell: (c) => <Link className="underline underline-offset-4" to={`/kelas/${c.id}`}>{c.name}</Link> },
            { key: 'year', header: 'Tahun ajaran', cell: (c) => c.academic_year ?? '—' },
            { key: 'homeroom', header: 'Wali kelas', cell: (c) => c.homeroom_teacher ?? '—' },
            { key: 'count', header: 'Siswa', align: 'right', cell: (c) => c.students_count ?? 0 },
          ]} />
        )}
    </>
  );
}

export function ClassDetailPage() {
  const { id } = useParams();
  const { can } = useSession();
  const qc = useQueryClient();
  const [picked, setPicked] = useState<number[]>([]);
  const [msg, setMsg] = useState('');

  const detail = useQuery({ queryKey: ['class', id], queryFn: () => api<{ data: ClassItem; members: Student[] }>(`/classes/${id}`) });
  const canEnroll = can('enrollments.manage') && can('classes.manage');
  const candidates = useQuery({ queryKey: ['students', 'all-active'], queryFn: () => api<Paginated<Student>>('/students', { query: { status: 'active', per_page: 100 } }), enabled: canEnroll });

  const enroll = useMutation({
    mutationFn: () => api<{ data: { added: number; moved: number } }>(`/classes/${id}/enrollments`, { method: 'POST', body: { student_ids: picked, joined_on: today() } }),
    onSuccess: (r) => { setMsg(`${r.data.added} siswa ditambahkan${r.data.moved ? `, ${r.data.moved} dipindahkan dari kelas lain (riwayat tetap tersimpan)` : ''}.`); setPicked([]); qc.invalidateQueries({ queryKey: ['class', id] }); qc.invalidateQueries({ queryKey: ['classes'] }); },
    onError: (e) => setMsg(e instanceof ApiError ? (e.firstDetail ?? e.message) : 'Gagal menyimpan.'),
  });

  if (detail.isLoading) return <Loading />;
  if (detail.isError) return <ErrorState error={detail.error} retry={() => detail.refetch()} />;
  const memberIds = new Set(detail.data!.members.map((m) => m.id));
  const options = (candidates.data?.data ?? []).filter((s) => !memberIds.has(s.id));

  return (
    <>
      <PageHeader title={`Kelas ${detail.data!.data.name}`} intro={`Wali kelas: ${detail.data!.data.homeroom_teacher ?? 'belum ditentukan'}`} />
      {msg && <Notice>{msg}</Notice>}
      <h2 className="mb-2 text-xl">Anggota ({detail.data!.members.length})</h2>
      {detail.data!.members.length === 0 ? <Empty title="Belum ada anggota" /> : (
        <DataTable caption="Anggota kelas" rows={detail.data!.members} rowKey={(s) => s.id} columns={[
          { key: 'name', header: 'Nama', cell: (s) => s.name }, { key: 'nis', header: 'NIS', cell: (s) => s.nis }, { key: 'g', header: 'L/P', cell: (s) => s.gender },
        ]} />
      )}
      {canEnroll && (
        <section className="mt-8">
          <h2 className="mb-2 text-xl">Tambah atau pindahkan anggota</h2>
          <p className="mb-2 text-sm text-muted">Siswa yang masih di kelas lain akan dipindahkan; enrollment lamanya ditutup, tidak dihapus.</p>
          <ul className="max-h-64 overflow-y-auto border-y border-rule">
            {options.map((s) => (
              <li key={s.id} className="border-b border-rule last:border-0">
                <label className="flex min-h-11 items-center gap-3">
                  <input type="checkbox" checked={picked.includes(s.id)} onChange={(e) => setPicked(e.target.checked ? [...picked, s.id] : picked.filter((x) => x !== s.id))} />
                  <span>{s.name}</span><span className="text-sm text-muted">{s.nis}{s.class ? ` · kini di ${s.class.name}` : ''}</span>
                </label>
              </li>
            ))}
            {options.length === 0 && <li className="py-3 text-sm text-muted">Tidak ada siswa aktif lain.</li>}
          </ul>
          <Button className="mt-3" disabled={picked.length === 0 || enroll.isPending} onClick={() => enroll.mutate()}>Simpan {picked.length || ''} siswa ke kelas ini</Button>
        </section>
      )}
    </>
  );
}
