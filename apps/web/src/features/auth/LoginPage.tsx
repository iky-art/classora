import { useState, type FormEvent } from 'react';
import { Navigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { loginSchema } from '@classora/api-contracts';
import { ApiError } from '../../lib/api';
import { authService } from '../../services/auth';
import { useSession } from '../../stores/session';
import { Button } from '../../components/ui/Button';
import { TextInput } from '../../components/forms/Field';
import { Notice } from '../../components/ui/States';
import { LogoMark } from '../../components/ui/Logo';

export function LoginPage() {
  const { me } = useSession();
  const qc = useQueryClient();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState('');
  const [busy, setBusy] = useState(false);

  if (me) return <Navigate to="/" replace />;

  async function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setFormError('');
    const form = new FormData(e.currentTarget);
    const parsed = loginSchema.safeParse({ email: form.get('email'), password: form.get('password') });
    if (!parsed.success) {
      setErrors(Object.fromEntries(parsed.error.issues.map((i) => [String(i.path[0]), i.message])));
      return;
    }
    setErrors({});
    setBusy(true);
    try {
      await authService.login(parsed.data.email, parsed.data.password);
      await qc.invalidateQueries({ queryKey: ['me'] });
    } catch (err) {
      if (err instanceof ApiError && err.details) setErrors(Object.fromEntries(Object.entries(err.details).map(([k, v]) => [k, v[0]])));
      else setFormError(err instanceof ApiError ? err.message : 'Tidak dapat terhubung ke server.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="mx-auto grid min-h-dvh max-w-5xl items-center gap-10 px-5 py-10 md:grid-cols-2">
      <div>
        <LogoMark size={56} />
        <h1 className="mt-5 text-5xl md:text-6xl">Classora</h1>
        <p className="mt-3 max-w-sm font-serif text-xl">Kelola Sekolah, Lebih Mudah.</p>
      </div>
      <form onSubmit={submit} noValidate className="space-y-4 border-t-2 border-fg pt-6">
        <h2 className="text-2xl">Masuk</h2>
        {formError && <Notice tone="error">{formError}</Notice>}
        <TextInput label="Email" name="email" type="email" autoComplete="username" error={errors.email} />
        <TextInput label="Kata sandi" name="password" type="password" autoComplete="current-password" error={errors.password} />
        <Button type="submit" disabled={busy} className="w-full">{busy ? 'Memeriksa…' : 'Masuk'}</Button>
      </form>
    </div>
  );
}

export function SchoolPicker() {
  const { me, selectSchool } = useSession();
  return (
    <div className="mx-auto max-w-md px-5 py-16">
      <h1 className="text-3xl">Pilih sekolah</h1>
      <p className="mt-1 text-sm text-muted">Akunmu terdaftar di lebih dari satu sekolah.</p>
      <ul className="mt-6 border-t-2 border-fg">
        {me?.memberships.map((m) => (
          <li key={m.school.id} className="border-b border-rule">
            <button onClick={() => selectSchool(m.school.slug)} className="flex min-h-14 w-full items-center justify-between text-left hover:text-primary">
              <span className="font-medium">{m.school.name}</span>
              <span className="text-sm text-muted">{m.roles.join(', ')}</span>
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}
