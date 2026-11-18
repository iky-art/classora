export type AttendanceStatus = 'present' | 'sick' | 'excused' | 'absent';
export type OperatorMode = 'report_only' | 'full_attendance';
export type ReportStatus = 'pending' | 'approved' | 'rejected';

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}

export interface ApiErrorBody {
  error: { code: string; message: string; details?: Record<string, string[]> | null };
}

export interface MeResponse {
  user: { id: number; name: string; email: string; is_super_admin: boolean };
  memberships: { school: { id: number; name: string; slug: string }; roles: string[] }[];
}

export interface Branding {
  app_name: string | null;
  primary_color: string | null;
  login_title: string | null;
  login_message: string | null;
  report_header: string | null;
}

export interface OperatorAssignmentSummary {
  id: number;
  class: { id: number; name: string };
  mode: OperatorMode;
  starts_on: string;
  ends_on: string;
}

export interface TenantContextData {
  school: { id: number; name: string; slug: string };
  roles: string[];
  permissions: string[];
  branding: Branding | null;
  operator_assignments: OperatorAssignmentSummary[];
}

export interface Student {
  id: number;
  nis: string;
  nisn: string | null;
  name: string;
  gender: 'L' | 'P';
  status: string;
  birth_date: string | null;
  class?: { id: number; name: string } | null;
}

export interface ClassItem {
  id: number;
  name: string;
  academic_year?: string | null;
  grade?: string | null;
  department?: string | null;
  homeroom_teacher?: string | null;
  students_count?: number;
}

export interface RosterRow {
  student_id: number;
  name: string;
  nis: string;
  status: AttendanceStatus | null;
  note: string | null;
}

export interface SessionDetail {
  data: { id: number; class: { id: number; name: string }; session_date: string; period_key: string };
  roster: RosterRow[];
}

export interface OperatorAssignment {
  id: number;
  class: { id: number; name: string };
  student: { id: number; name: string; nis: string };
  mode: OperatorMode;
  starts_on: string;
  ends_on: string;
  status: string;
}

export interface OperatorReport {
  id: number;
  assignment_id: number;
  class?: { id: number; name: string };
  mode?: OperatorMode;
  report_date: string;
  status: ReportStatus;
  revision: number;
  items?: { student_id: number; status: AttendanceStatus; note: string | null }[];
  reviews?: { decision: string; note: string | null; revision: number; at: string }[];
}

export interface RecapRow {
  student_id: number;
  name: string;
  nis: string;
  present: number;
  sick: number;
  excused: number;
  absent: number;
  total: number;
}
