import { Navigate, Outlet, createBrowserRouter } from 'react-router-dom';
import { AppLayout } from '../layouts/AppLayout';
import { LoginPage, SchoolPicker } from '../../features/auth/LoginPage';
import { DashboardPage } from '../../features/dashboard/DashboardPage';
import { StudentsPage } from '../../features/students/StudentsPage';
import { ClassDetailPage, ClassesPage } from '../../features/classes/ClassesPage';
import { AttendancePage } from '../../features/attendance/AttendancePage';
import { PetugasKelasPage } from '../../features/student-operators/PetugasKelasPage';
import { OperatorReviewPage } from '../../features/student-operators/OperatorReviewPage';
import { ReportsPage } from '../../features/reports/ReportsPage';
import { BrandingPage } from '../../features/settings/BrandingPage';
import { Loading } from '../../components/ui/States';
import { useSession } from '../../stores/session';

function Protected() {
  const { me, ctx, loading, needsSchool } = useSession();
  if (loading) return <div className="mx-auto max-w-md p-10"><Loading /></div>;
  if (!me) return <Navigate to="/masuk" replace />;
  if (needsSchool) return <SchoolPicker />;
  if (me && !ctx) return <div className="mx-auto max-w-md p-10"><p role="alert">Akunmu belum memiliki akses aktif ke sekolah mana pun. Hubungi admin sekolah.</p></div>;
  return <AppLayout />;
}

/** Menyembunyikan halaman bila tidak ada permission; backend tetap memvalidasi setiap request. */
function Guard({ allow, children }: { allow: boolean; children: React.ReactNode }) {
  return allow ? <>{children}</> : <Navigate to="/" replace />;
}

function Gate({ perm, children }: { perm: string; children: React.ReactNode }) {
  const { can } = useSession();
  return <Guard allow={can(perm)}>{children}</Guard>;
}

function OperatorGate({ children }: { children: React.ReactNode }) {
  const { ctx } = useSession();
  return <Guard allow={(ctx?.operator_assignments.length ?? 0) > 0}>{children}</Guard>;
}

function Home() {
  const { can, ctx } = useSession();
  if (can('dashboard.view')) return <DashboardPage />;
  return <Navigate to={(ctx?.operator_assignments.length ?? 0) > 0 ? '/petugas-kelas' : '/masuk'} replace />;
}

export const router = createBrowserRouter([
  { path: '/masuk', element: <LoginPage /> },
  {
    element: <Protected />,
    children: [
      { index: true, element: <Home /> },
      { path: 'siswa', element: <Gate perm="students.view"><StudentsPage /></Gate> },
      { path: 'kelas', element: <Gate perm="classes.view"><ClassesPage /></Gate> },
      { path: 'kelas/:id', element: <Gate perm="classes.view"><ClassDetailPage /></Gate> },
      { path: 'absensi', element: <Gate perm="attendance.view"><AttendancePage /></Gate> },
      { path: 'petugas-kelas', element: <OperatorGate><PetugasKelasPage /></OperatorGate> },
      { path: 'laporan-petugas', element: <Gate perm="operator_reports.review"><OperatorReviewPage /></Gate> },
      { path: 'rekap', element: <Gate perm="reports.view"><ReportsPage /></Gate> },
      { path: 'pengaturan', element: <Gate perm="branding.manage"><BrandingPage /></Gate> },
      { path: '*', element: <Navigate to="/" replace /> },
    ],
  },
]);
