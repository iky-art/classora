import { Outlet } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { BottomNav, SideNav } from '../../components/navigation/Nav';
import { Wordmark } from '../../components/ui/Logo';
import { currentTheme, setTheme, type Theme } from '../../lib/theme';
import { useSession } from '../../stores/session';

export function AppLayout() {
  const { ctx, me, signOut } = useSession();
  const [theme, setT] = useState<Theme>(currentTheme());
  const name = ctx?.branding?.app_name || 'Classora';

  useEffect(() => {
    const c = ctx?.branding?.primary_color;
    if (c) document.documentElement.style.setProperty('--primary', c);
    return () => { document.documentElement.style.removeProperty('--primary'); };
  }, [ctx?.branding?.primary_color]);

  const toggle = () => { const next: Theme = theme === 'dark' ? 'light' : 'dark'; setTheme(next); setT(next); };

  return (
    <div className="mx-auto min-h-dvh max-w-7xl md:grid md:grid-cols-[14rem_1fr]">
      <aside className="hidden border-r border-rule px-5 py-6 md:block">
        <Wordmark name={name} />
        <p className="mt-3 text-sm text-muted">{ctx?.school.name}</p>
        <div className="mt-8"><SideNav /></div>
      </aside>

      <div className="min-w-0">
        <div className="flex items-center justify-between border-b border-rule px-4 py-3 md:px-8">
          <div className="md:hidden"><Wordmark name={name} /></div>
          <p className="hidden text-sm text-muted md:block">{me?.user.name}</p>
          <div className="flex items-center gap-4 text-sm">
            <button onClick={toggle} className="min-h-11 underline-offset-4 hover:underline" aria-label="Ganti tema terang atau gelap">
              {theme === 'dark' ? 'Tema terang' : 'Tema gelap'}
            </button>
            <button onClick={signOut} className="min-h-11 underline-offset-4 hover:underline">Keluar</button>
          </div>
        </div>
        <main className="page-enter px-4 py-6 pb-24 md:px-8 md:pb-10"><Outlet /></main>
      </div>
      <BottomNav />
    </div>
  );
}
