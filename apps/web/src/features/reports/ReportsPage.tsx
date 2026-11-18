import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import type { ClassItem, Paginated, RecapRow } from '@classora/shared-types';
import { api } from '../../lib/api';
import { exportExcel, exportPdf } from '../../lib/export';
import { formatDate, range } from '../../lib/dates';
import { useSession } from '../../stores/session';
import { PageHeader } from '../../components/ui/PageHeader';
import { DataTable } from '../../components/data-table/DataTable';
import { Button } from '../../components/ui/Button';
import { SelectInput, TextInput } from '../../components/forms/Field';
import { Empty, ErrorState, Loading } from '../../components/ui/States';

export function ReportsPage() {
  const { ctx } = useSession();
  const [{ from, to }, setRange] = useState(range('month'));
  const [classId, setClassId] = useState('');

  const classes = useQuery({ queryKey: ['classes'], queryFn: () => api<Paginated<ClassItem>>('/classes') });
  const recap = useQuery({
    queryKey: ['recap', from, to, classId],
    queryFn: () => api<{ data: RecapRow[] }>('/reports/attendance', { query: { from, to, class_id: classId } }).then((r) => r.data),
    enabled: !!from && !!to && from <= to,
  });

  const rows = recap.data ?? [];
  const className = classes.data?.data.find((c) => String(c.id) === classId)?.name ?? 'Semua kelas';
  const school = ctx?.branding?.report_header || ctx?.school.name || 'Classora';
  const headers = ['NIS', 'Nama', 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Total'];
  const table = rows.map((r) => [r.nis, r.name, r.present, r.sick, r.excused, r.absent, r.total]);
  const subtitle = `${school} · ${className} · ${formatDate(from)} – ${formatDate(to)}`;

  return (
    <>
      <PageHeader title="Rekap kehadiran" intro="Rekap per siswa pada rentang tanggal. Hasil export mengikuti filter yang sedang tampil." />

      <div className="mb-6 grid items-end gap-4 border-b border-rule pb-6 md:grid-cols-4">
        <TextInput label="Dari" type="date" value={from} onChange={(e) => setRange({ from: e.target.value, to })} />
        <TextInput label="Sampai" type="date" value={to} onChange={(e) => setRange({ from, to: e.target.value })} />
        <SelectInput label="Kelas" value={classId} onChange={(e) => setClassId(e.target.value)}>
          <option value="">Semua kelas</option>{classes.data?.data.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
        </SelectInput>
        <div className="flex flex-wrap gap-2">
          {(['day', 'week', 'month'] as const).map((p) => <Button key={p} variant="quiet" className="min-h-9 px-3" onClick={() => setRange(range(p))}>{{ day: 'Hari ini', week: 'Minggu ini', month: 'Bulan ini' }[p]}</Button>)}
        </div>
      </div>

      {from > to ? <p role="alert" className="text-danger">Tanggal awal harus sebelum tanggal akhir.</p> : recap.isLoading ? <Loading /> : recap.isError ? <ErrorState error={recap.error} retry={() => recap.refetch()} /> : rows.length === 0 ? (
        <Empty title="Tidak ada data kehadiran">Belum ada absensi tercatat pada rentang dan kelas ini.</Empty>
      ) : (
        <>
          <div className="mb-3 flex gap-2">
            <Button variant="quiet" onClick={() => exportExcel(`rekap-${from}-${to}`, subtitle, headers, table)}>Unduh Excel</Button>
            <Button variant="quiet" onClick={() => exportPdf(`rekap-${from}-${to}`, 'Rekap Kehadiran', subtitle, headers, table)}>Unduh PDF</Button>
          </div>
          <DataTable caption="Rekap kehadiran per siswa" rows={rows} rowKey={(r) => r.student_id} columns={[
            { key: 'name', header: 'Nama', cell: (r) => r.name }, { key: 'nis', header: 'NIS', cell: (r) => r.nis },
            { key: 'p', header: 'Hadir', align: 'right', cell: (r) => r.present }, { key: 's', header: 'Sakit', align: 'right', cell: (r) => r.sick },
            { key: 'i', header: 'Izin', align: 'right', cell: (r) => r.excused }, { key: 'a', header: 'Alpa', align: 'right', cell: (r) => r.absent },
          ]} />
        </>
      )}
    </>
  );
}
