import type { MeResponse, TenantContextData } from '@classora/shared-types';
import { api } from '../lib/api';

export const authService = {
  login: (email: string, password: string) => api<{ data: MeResponse }>('/auth/login', { method: 'POST', body: { email, password } }),
  logout: () => api<void>('/auth/logout', { method: 'POST' }),
  me: () => api<{ data: MeResponse }>('/auth/me').then((r) => r.data),
  context: () => api<{ data: TenantContextData }>('/auth/context').then((r) => r.data),
};
