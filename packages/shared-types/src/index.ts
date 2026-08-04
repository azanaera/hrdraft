export type Role = 'admin' | 'hr_manager' | 'people_manager' | 'employee';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: Role;
  employment_id: number | null;
}

export type EmploymentType = 'hourly' | 'salaried';
export type EmploymentStatus = 'active' | 'on_leave' | 'terminated';

export interface Person {
  id: number;
  person_number: string;
  first_name: string;
  last_name: string;
  personal_email: string | null;
}

export interface CurrentAssignment {
  id: number;
  department: string;
  location: string;
  position: string;
  manager_employment_id: number | null;
  effective_start_date: string;
}

export interface CompensationRecord {
  id: number;
  pay_type: 'hourly' | 'salary';
  rate_amount: number;
  pay_frequency: 'weekly' | 'biweekly' | 'semimonthly' | 'monthly' | 'annual';
  currency: string;
  effective_date: string;
  end_date: string | null;
  reason: string;
  notes: string | null;
}

export interface Employment {
  id: number;
  employee_number: string;
  employment_status: EmploymentStatus;
  employment_type: EmploymentType;
  hire_date: string;
  termination_date: string | null;
  person: Person;
  current_assignment: CurrentAssignment | null;
  current_compensation: CompensationRecord | null;
}

export interface EmployeeEvent {
  id: number;
  employment_id: number | null;
  event_type: string;
  event_date: string;
  summary: string;
  payload: Record<string, unknown> | null;
  actor: string | null;
  visibility: 'all_hr' | 'manager_and_above' | 'admin_only';
  created_at: string;
}

export type TimeOffStatus = 'pending' | 'approved' | 'denied' | 'cancelled';

export interface TimeOffPolicy {
  id: number;
  name: string;
  applies_to: 'hourly' | 'salaried' | 'all';
  accrual_method: string;
  accrual_rate: number;
  max_balance: number | null;
  is_active: boolean;
}

export interface TimeOffRequest {
  id: number;
  employment_id: number;
  employee_name: string | null;
  policy: string | null;
  start_date: string;
  end_date: string;
  hours_requested: number;
  status: TimeOffStatus;
  requested_at: string;
  decided_by: string | null;
  decided_at: string | null;
  decision_notes: string | null;
}

export interface TimeOffBalance {
  policy: string;
  policy_id: number;
  balance_hours: number;
  as_of_date: string;
}

export interface DocumentCategory {
  id: number;
  name: string;
  requires_signature: boolean;
  applicable_to: 'employee' | 'candidate' | 'all';
}

export interface DocumentRecord {
  id: number;
  title: string;
  category: string | null;
  category_id: number;
  requires_signature: boolean | null;
  current_version: {
    id: number;
    version_number: number;
    mime_type: string;
    file_size: number;
    uploaded_at: string;
  } | null;
  uploaded_by: string | null;
  created_at: string;
}

export interface OnboardingTaskItem {
  id: number;
  title: string;
  task_type: string;
  status: 'pending' | 'in_progress' | 'completed' | 'waived';
  completed_at: string | null;
}

export interface OnboardingWorkflow {
  id: number;
  employment_id: number;
  template: string | null;
  status: 'not_started' | 'in_progress' | 'completed';
  started_at: string | null;
  completed_at: string | null;
  tasks: OnboardingTaskItem[];
}

export interface JobRequisition {
  id: number;
  title: string;
  department: string | null;
  location: string | null;
  status: 'draft' | 'open' | 'on_hold' | 'filled' | 'closed';
  employment_type: EmploymentType;
  target_pay_range_min: number | null;
  target_pay_range_max: number | null;
  hiring_manager: string | null;
  applications_count: number | null;
  opened_at: string | null;
  closed_at: string | null;
}

export interface Application {
  id: number;
  requisition_id: number;
  requisition_title: string | null;
  candidate: {
    id: number;
    name: string;
    email: string;
    is_former_employee: boolean;
    possible_former_employee_id: number | null;
  } | null;
  current_stage: string | null;
  status: 'active' | 'rejected' | 'withdrawn' | 'hired';
  applied_at: string;
  hired_employment_id: number | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    total: number;
  };
  links?: unknown;
}

// --- Offboarding (mirrors OnboardingWorkflow/OnboardingTaskItem) ---
export interface OffboardingTaskItem {
  id: number;
  title: string;
  task_type: string;
  status: 'pending' | 'in_progress' | 'completed' | 'waived';
  completed_at: string | null;
}

export interface OffboardingWorkflow {
  id: number;
  employment_id: number;
  template: string | null;
  status: 'not_started' | 'in_progress' | 'completed';
  started_at: string | null;
  completed_at: string | null;
  tasks: OffboardingTaskItem[];
}

// --- Notifications ---
export interface AppNotification {
  id: number;
  type: string;
  title: string;
  body: string;
  related_employment_id: number | null;
  read_at: string | null;
  created_at: string;
}

// --- Banking / e-signature / background checks ---
export interface BankingInfo {
  provider: string;
  account_last_four: string;
  account_type: 'checking' | 'savings';
  verified: boolean;
}

export interface BackgroundCheckStatus {
  check_type: 'background_check' | 'e_verify';
  status: 'pending' | 'clear' | 'flagged';
  resolved_at: string | null;
}

// --- Reporting / dashboard ---
export interface DashboardSummary {
  headcount: number;
  open_requisitions: number;
  pending_time_off_requests: number;
  recent_hires: Array<{ employment_id: number; name: string | null; date: string | null }>;
}

export interface TurnoverReport {
  period: { from: string; to: string };
  active_headcount: number;
  termination_count: number;
  turnover_rate: number;
  average_tenure_days: number;
  rehire_rate: number;
  terminations_by_department: Array<{ label: string; count: number }>;
  terminations_by_location: Array<{ label: string; count: number }>;
  terminations_by_reason: Array<{ reason: string; count: number }>;
}

// --- Admin config entities ---
export interface AdminLocation {
  id: number;
  name: string;
  code: string;
  city: string | null;
  state: string | null;
  country: string;
  minimum_wage: number | null;
  is_active: boolean;
}

export interface AdminDepartment {
  id: number;
  name: string;
  code: string;
  parent_department_id: number | null;
  is_active: boolean;
}

export interface AdminPosition {
  id: number;
  title: string;
  department_id: number;
  default_employment_type: EmploymentType;
  is_active: boolean;
}

// --- Data import ---
export interface ImportRowPreview {
  data: Record<string, string | null>;
  errors: string[];
  valid: boolean;
}

export interface ImportPreviewResult {
  rows: ImportRowPreview[];
  valid_count: number;
  error_count: number;
}

export interface ImportCommitResult {
  created: Array<{ row: number; employment_id: number }>;
  failed: Array<{ row: number; errors: string[] }>;
}
