import { createContext, useContext, type ReactNode } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import type { MeResponse, TenantContextData } from '@classora/shared-types';
import { ApiError, school } from '../lib/api';
import { authService } from '../services/auth';
import { can } from '../lib/can';

interface Session {
  me: MeResponse | null;
  ctx: TenantContextData | null;
  loading: boolean;
  needsSchool: boolean;
  can: (permission: string) => boolean;
  selectSchool: (slug: string) => void;
  signOut: () => Promise<void>;
}

const SessionContext = createContext<Session | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const qc = useQueryClient();

  const meQuery = useQuery({
    queryKey: ['me'],
    queryFn: authService.me,
    retry: false,
  });
  const me = meQuery.error instanceof ApiError && meQuery.error.status === 401 ? null : (meQuery.data ?? null);

  // Pilih sekolah: yang tersimpan (jika masih valid) atau satu-satunya membership.
  const stored = school.get();
  const slugs = me?.memberships.map((m) => m.school.slug) ?? [];
  const activeSlug = stored && slugs.includes(stored) ? stored : slugs.length === 1 ? slugs[0] : null;
  if (activeSlug && stored !== activeSlug) school.set(activeSlug);

  const ctxQuery = useQuery({
    queryKey: ['context', activeSlug],
    queryFn: authService.context,
    enabled: !!me && !!activeSlug,
    retry: false,
  });

  const value: Session = {
    me,
    ctx: ctxQuery.data ?? null,
    loading: meQuery.isLoading || (!!activeSlug && ctxQuery.isLoading),
    needsSchool: !!me && !activeSlug && slugs.length > 1,
    can: (p) => can(ctxQuery.data?.permissions, p),
    selectSchool: (slug) => {
      school.set(slug);
      qc.removeQueries({ predicate: (q) => q.queryKey[0] !== 'me' });
      qc.invalidateQueries({ queryKey: ['context'] });
    },
    signOut: async () => {
      await authService.logout().catch(() => undefined);
      school.clear();
      qc.clear();
      window.location.assign('/masuk');
    },
  };

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): Session {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error('useSession harus dipakai di dalam SessionProvider');
  return ctx;
}
