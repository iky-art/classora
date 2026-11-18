import { NavLink } from 'react-router-dom';
import { useSession } from '../../stores/session';

interface Item { to: string; label: string; show: boolean }

export function useNavItems(): Item[] {
  const { can, ctx } = useSession();
  const isOperator = (ctx?.operator_assignments.length ?? 0) > 0;
  return [
    { to: '/', label: 'Beranda', show: can('dashboard.view') },
    { to: '/siswa', label: 'Siswa', show: can('students.view') },
    { to: '/kelas', label: 'Kelas', show: can('classes.view') },
    { to: '/absensi', label: 'Absensi', show: can('attendance.view') },
    { to: '/petugas-kelas', label: 'Petugas Kelas', show: isOperator },
    { to: '/laporan-petugas', label: 'Laporan Petugas', show: can('operator_reports.review') },
    { to: '/rekap', label: 'Rekap', show: can('reports.view') },
    { to: '/pengaturan', label: 'Branding', show: can('branding.manage') },
  ].filter((i) => i.show);
}

export function SideNav() {
  const items = useNavItems();
  return (
    <nav aria-label="Menu utama" className="flex flex-col">
      {items.map((i) => (
        <NavLink key={i.to} to={i.to} end={i.to === '/'}
          className={({ isActive }) => `border-l-2 py-2 pl-3 text-sm ${isActive ? 'border-primary font-semibold' : 'border-transparent text-muted hover:text-fg'}`}>
          {i.label}
        </NavLink>
      ))}
    </nav>
  );
}

export function BottomNav() {
  const items = useNavItems();
  return (
    <nav aria-label="Menu utama" className="fixed inset-x-0 bottom-0 z-10 flex overflow-x-auto border-t border-rule bg-bg pb-[env(safe-area-inset-bottom)] md:hidden">
      {items.map((i) => (
        <NavLink key={i.to} to={i.to} end={i.to === '/'}
          className={({ isActive }) => `min-h-12 shrink-0 px-4 py-3 text-sm ${isActive ? 'border-t-2 border-primary font-semibold' : 'border-t-2 border-transparent text-muted'}`}>
          {i.label}
        </NavLink>
      ))}
    </nav>
  );
}
