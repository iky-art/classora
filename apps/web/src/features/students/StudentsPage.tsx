import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { Paginated, Student } from '@classora/shared-types';
import { studentSchema } from '@classora/api-contracts';
import { ApiError, api } from '../../lib/api';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { DataTable } from '../../components/data-table/DataTable';
import { Button } from '../../components/ui/Button';
import { SelectInput, TextInput } from '../../components/forms/Field';
import { Empty, ErrorState, Loading, Notice } from '../../components/ui/States';

export function StudentsPage() {
  const { can } = useSession();
  const qc = useQueryClient();
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const [adding, setAdding] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [done, setDone] = useState('');

  const list = useQuery({
    queryKey: ['students', q, page],
    queryFn: () => api<Paginated<Student>>('/students', { query: { q, page } }),
    placeholderData: (prev) => prev,
  });

  const create = useMutation({
    mutationFn: (body: unknown) => api('/students', { method: 'POST', body }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['students'] }); setAdding(false); setDone('Siswa ditambahkan.'); },
    onError: (e) => { if (e instanceof ApiError && e.details) setErrors(Object.fromEntries(Object.entries(e.details).map(([k, v]) => [k, v[0]]))); },
  });

  function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const f = new FormData(e.currentTarget);
    const parsed = studentSchema.safeParse({ nis: f.get('nis'), nisn: f.get('nisn') ?? '', name: f.get('name'), gender: f.get('gender') });
    if (!parsed.success) { setErrors(Object.fromEntries(parsed.error.issues.map((i) => [String(i.path[0]), i.message]))); return; }
    setErrors({});
    create.mutate({ ...parsed.data, nisn: parsed.data.nisn || null });
  }

  return (
    <>
      <PageHeader title="Siswa" intro="Data siswa sekolahmu. Siswa yang diarsipkan tetap menyimpan riwayat kelas dan absensinya."
        actions={can('students.manage') && <Button onClick={() => { setAdding((v) => !v); setDone(''); }}>{adding ? 'Tutup formulir' : 'Tambah siswa'}</Button>} />

      {done && <Notice>{done}</Notice>}

      {adding && (
        <form onSubmit={submit} noValidate className="mb-8 grid gap-4 border-b border-rule pb-6 md:grid-cols-4">
          <TextInput label="NIS" name="nis" error={errors.nis} />
          <TextInput label="NISN (opsional)" name="nisn" error={errors.nisn} />
          <TextInput label="Nama lengkap" name="name" error={errors.name} />
          <SelectInput label="Jenis kelamin" name="gender" defaultValue="L" error={errors.gender}>
            <option value="L">Laki-laki</option><option value="P">Perempuan</option>
          </SelectInput>
          <div className="md:col-span-4"><Button type="submit" disabled={create.isPending}>{create.isPending ? 'Menyimpan…' : 'Simpan siswa'}</Button></div>
        </form>
      )}

      <div className="mb-4 max-w-sm">
        <TextInput label="Cari nama, NIS, atau NISN" value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} />
      </div>

      {list.isLoading ? <Loading /> : list.isError ? <ErrorState error={list.error} retry={() => list.refetch()} /> :
        list.data!.data.length === 0 ? <Empty title={q ? 'Tidak ada siswa yang cocok' : 'Belum ada siswa'}>{q ? 'Coba kata kunci lain.' : 'Tambahkan siswa pertama lewat tombol di atas.'}</Empty> : (
          <>
            <DataTable caption="Daftar siswa" rows={list.data!.data} rowKey={(s) => s.id} columns={[
              { key: 'name', header: 'Nama', cell: (s) => s.name },
              { key: 'nis', header: 'NIS', cell: (s) => s.nis },
              { key: 'class', header: 'Kelas', cell: (s) => s.class?.name ?? '—' },
              { key: 'gender', header: 'L/P', cell: (s) => s.gender },
              { key: 'status', header: 'Status', cell: (s) => s.status },
            ]} />
            <div className="mt-4 flex items-center gap-4 text-sm">
              <Button variant="quiet" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</Button>
              <span>Halaman {list.data!.meta.current_page} dari {list.data!.meta.last_page} ({list.data!.meta.total} siswa)</span>
              <Button variant="quiet" disabled={page >= list.data!.meta.last_page} onClick={() => setPage(page + 1)}>Berikutnya</Button>
            </div>
          </>
        )}
    </>
  );
}
