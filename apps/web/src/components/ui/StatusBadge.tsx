import type { AttendanceStatus, ReportStatus } from '@classora/shared-types';
import { REPORT_STATUS_LABEL, STATUS_LABEL } from '../../lib/can';

const tone: Record<string, string> = {
  present: 'border-primary text-primary',
  sick: 'border-accent bg-accent/20',
  excused: 'border-fg',
  absent: 'border-danger text-danger',
  pending: 'border-accent bg-accent/20',
  approved: 'border-primary text-primary',
  rejected: 'border-danger text-danger',
};

export function StatusBadge({ status }: { status: AttendanceStatus | ReportStatus }) {
  const label = (STATUS_LABEL as Record<string, string>)[status] ?? (REPORT_STATUS_LABEL as Record<string, string>)[status];
  return <span className={`inline-block rounded-sm border px-2 py-0.5 text-xs font-medium ${tone[status]}`}>{label}</span>;
}
