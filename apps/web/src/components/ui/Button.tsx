import type { ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'quiet' | 'danger';
const styles: Record<Variant, string> = {
  primary: 'bg-primary text-primary-fg border-primary hover:brightness-110',
  quiet: 'bg-transparent text-fg border-rule hover:border-fg',
  danger: 'bg-transparent text-danger border-danger hover:bg-danger hover:text-bg',
};

export function Button({ variant = 'primary', className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant }) {
  return (
    <button
      type="button"
      {...props}
      className={`inline-flex min-h-11 items-center justify-center gap-2 rounded-sm border px-4 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50 ${styles[variant]} ${className}`}
    />
  );
}
