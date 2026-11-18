import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ApiError, api } from '../../lib/api';
import { PageHeader } from '../../components/ui/PageHeader';
import { Button } from '../../components/ui/Button';
import { TextInput } from '../../components/forms/Field';
import { ErrorState, Loading, Notice } from '../../components/ui/States';

interface BrandingData { entitled: boolean; branding: Record<string, string | null> | null }

export function BrandingPage() {
  const qc = useQueryClient();
  const [msg, setMsg] = useState<{ tone: 'ok' | 'error'; text: string } | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const q = useQuery({ queryKey: ['branding'], queryFn: () => api<{ data: BrandingData }>('/settings/branding').then((r) => r.data) });

  const save = useMutation({
    mutationFn: (body: unknown) => api('/settings/branding', { method: 'PUT', body }),
    onSuccess: () => { setMsg({ tone: 'ok', text: 'Branding disimpan.' }); setErrors({}); qc.invalidateQueries({ queryKey: ['branding'] }); qc.invalidateQueries({ queryKey: ['context'] }); },
    onError: (e) => {
      if (e instanceof ApiError && e.details) setErrors(Object.fromEntries(Object.entries(e.details).map(([k, v]) => [k, v[0]])));
      setMsg({ tone: 'error', text: e instanceof ApiError ? e.message : 'Gagal menyimpan.' });
    },
  });

  if (q.isLoading) return <Loading />;
  if (q.isError) return <ErrorState error={q.error} retry={() => q.refetch()} />;
  const { entitled, branding } = q.data!;

  function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const f = Object.fromEntries(new FormData(e.currentTarget)) as Record<string, string>;
    save.mutate(Object.fromEntries(Object.entries(f).map(([k, v]) => [k, v === '' ? null : v])));
  }

  return (
    <>
      <PageHeader title="Branding" intro="Nama aplikasi, warna utama, dan identitas laporan PDF sekolahmu." />
      {!entitled && <Notice tone="error">White Label adalah fitur paket Pro. Sekolahmu memakai branding Classora; formulir di bawah dikunci.</Notice>}
      {msg && <Notice tone={msg.tone}>{msg.text}</Notice>}
      <form onSubmit={submit} className="mt-4 grid max-w-2xl gap-4" noValidate>
        <fieldset disabled={!entitled || save.isPending} className="grid gap-4">
          <TextInput label="Nama aplikasi" name="app_name" defaultValue={branding?.app_name ?? ''} error={errors.app_name} />
          <TextInput label="Warna utama" name="primary_color" defaultValue={branding?.primary_color ?? ''} placeholder="#315C4C" hint="Format #RRGGBB. Pastikan kontras dengan teks putih." error={errors.primary_color} />
          <TextInput label="Judul halaman masuk" name="login_title" defaultValue={branding?.login_title ?? ''} error={errors.login_title} />
          <TextInput label="Pesan halaman masuk" name="login_message" defaultValue={branding?.login_message ?? ''} error={errors.login_message} />
          <TextInput label="Kop laporan PDF" name="report_header" defaultValue={branding?.report_header ?? ''} error={errors.report_header} />
          <div><Button type="submit">Simpan branding</Button></div>
        </fieldset>
      </form>
      <p className="mt-6 max-w-prose text-sm text-muted">Unggah logo dan favicon belum tersedia di versi ini.</p>
    </>
  );
}
