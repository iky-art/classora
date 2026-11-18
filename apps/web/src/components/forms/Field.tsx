import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes } from 'react';

const control = 'min-h-11 w-full rounded-sm border border-rule bg-surface px-3 text-sm text-fg placeholder:text-muted';

export function Field({ label, error, hint, children }: { label: string; error?: string; hint?: string; children: ReactNode }) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block font-medium">{label}</span>
      {children}
      {hint && !error && <span className="mt-1 block text-muted">{hint}</span>}
      {error && <span role="alert" className="mt-1 block text-danger">{error}</span>}
    </label>
  );
}

export function TextInput({ label, error, hint, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string; error?: string; hint?: string }) {
  return (
    <Field label={label} error={error} hint={hint}>
      <input {...props} aria-invalid={!!error} className={control} />
    </Field>
  );
}

export function SelectInput({ label, error, children, ...props }: SelectHTMLAttributes<HTMLSelectElement> & { label: string; error?: string }) {
  return (
    <Field label={label} error={error}>
      <select {...props} aria-invalid={!!error} className={control}>{children}</select>
    </Field>
  );
}
