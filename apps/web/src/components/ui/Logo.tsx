/** Tanda Classora: buku terbuka, topi wisuda, bintang. Pengganti SVG; berkas logo asli ada di assets/brand/. */
export function LogoMark({ size = 32 }: { size?: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 48 48" role="img" aria-label="Classora" fill="none">
      <path d="M4 15 24 5l20 10-20 10L4 15Z" fill="var(--fg)" />
      <path d="M11 22v6c0 3 6.5 6 13 6s13-3 13-6v-6l-13 6.5L11 22Z" fill="var(--fg)" opacity=".85" />
      <path d="M5 33c6-3 13-2 19 3 6-5 13-6 19-3v8c-6-3-13-2-19 3-6-5-13-6-19-3v-8Z" fill="var(--primary)" />
      <path d="m24 17.5 1.4 3.1 3.1 1.4-3.1 1.4L24 26.5l-1.4-3.1-3.1-1.4 3.1-1.4L24 17.5Z" fill="var(--accent)" />
    </svg>
  );
}

export function Wordmark({ name = 'Classora' }: { name?: string }) {
  return (
    <span className="flex items-center gap-2">
      <LogoMark size={28} />
      <span className="font-serif text-xl font-semibold tracking-tight">{name}</span>
    </span>
  );
}
