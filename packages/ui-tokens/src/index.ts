export const colors = {
  primary: '#2454FF',
  primaryDark: '#173AC7',
  surface: '#FFFFFF',
  surfaceMuted: '#F4F6FB',
  border: '#E2E6EF',
  textPrimary: '#111827',
  textMuted: '#6B7280',
  success: '#1B9E5A',
  warning: '#C77F00',
  danger: '#D6423C',
} as const;

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
} as const;

export const typography = {
  fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
  sizeSm: 13,
  sizeBase: 15,
  sizeLg: 20,
  sizeXl: 28,
} as const;

export const roleLabels: Record<string, string> = {
  admin: 'Admin',
  hr_manager: 'HR Manager',
  people_manager: 'People Manager',
  employee: 'Employee',
};
