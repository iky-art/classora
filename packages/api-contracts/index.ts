import { z } from 'zod';

export const attendanceStatus = z.enum(['present', 'sick', 'excused', 'absent']);

export const loginSchema = z.object({
  email: z.string().min(1, 'Email wajib diisi').email('Format email tidak valid'),
  password: z.string().min(1, 'Kata sandi wajib diisi'),
});

export const studentSchema = z.object({
  nis: z.string().trim().min(1, 'NIS wajib diisi').max(30),
  nisn: z.string().trim().max(20).optional().or(z.literal('')),
  name: z.string().trim().min(1, 'Nama wajib diisi').max(255),
  gender: z.enum(['L', 'P']),
});

export const classSchema = z.object({
  name: z.string().trim().min(1, 'Nama kelas wajib diisi').max(60),
  academic_year_id: z.coerce.number().int().positive('Pilih tahun ajaran'),
  grade_id: z.coerce.number().int().positive('Pilih tingkat'),
});

export type LoginInput = z.infer<typeof loginSchema>;
export type StudentInput = z.infer<typeof studentSchema>;
