import type {
  AdminDepartment,
  AdminLocation,
  AdminPosition,
  Application,
  AppNotification,
  AuthUser,
  BackgroundCheckStatus,
  BankingInfo,
  DashboardSummary,
  DocumentCategory,
  DocumentRecord,
  Employment,
  EmployeeEvent,
  ImportCommitResult,
  ImportPreviewResult,
  JobRequisition,
  OffboardingWorkflow,
  OnboardingWorkflow,
  PaginatedResponse,
  TimeOffBalance,
  TimeOffPolicy,
  TimeOffRequest,
  TurnoverReport,
} from '@hris/shared-types';

export interface TokenStorage {
  getToken(): Promise<string | null> | string | null;
  setToken(token: string | null): Promise<void> | void;
}

/** Web: no-op — Sanctum's session cookie handles auth, nothing to store. */
export const cookieTokenStorage: TokenStorage = {
  getToken: () => null,
  setToken: () => {},
};

export interface ApiClientOptions {
  baseUrl: string;
  tokenStorage: TokenStorage;
  /** Web SPA auth uses cookies and needs a CSRF cookie fetch + credentials. Mobile uses bearer tokens. */
  mode: 'cookie' | 'bearer';
}

function readCookie(name: string): string | null {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
  return match ? match[1] : null;
}

export class ApiError extends Error {
  constructor(public status: number, public body: unknown) {
    super(`API error ${status}`);
  }
}

export class ApiClient {
  constructor(private readonly options: ApiClientOptions) {}

  private async request<T>(path: string, init: RequestInit = {}): Promise<T> {
    const headers = new Headers(init.headers);
    headers.set('Accept', 'application/json');
    if (!(init.body instanceof FormData)) {
      headers.set('Content-Type', 'application/json');
    }

    if (this.options.mode === 'bearer') {
      const token = await this.options.tokenStorage.getToken();
      if (token) headers.set('Authorization', `Bearer ${token}`);
    }

    if (this.options.mode === 'cookie') {
      // Sanctum's SPA auth validates state-changing requests against the
      // XSRF-TOKEN cookie set by /sanctum/csrf-cookie — the browser sends
      // the cookie automatically, but the matching header has to be read
      // back out of document.cookie and attached by hand.
      const xsrfToken = readCookie('XSRF-TOKEN');
      if (xsrfToken) headers.set('X-XSRF-TOKEN', decodeURIComponent(xsrfToken));
    }

    const response = await fetch(`${this.options.baseUrl}${path}`, {
      ...init,
      headers,
      credentials: this.options.mode === 'cookie' ? 'include' : 'omit',
    });

    if (!response.ok) {
      const body = await response.json().catch(() => null);
      throw new ApiError(response.status, body);
    }

    if (response.status === 204) return undefined as T;

    return response.json() as Promise<T>;
  }

  private get<T>(path: string) {
    return this.request<T>(path, { method: 'GET' });
  }

  private post<T>(path: string, body?: unknown) {
    return this.request<T>(path, {
      method: 'POST',
      body: body instanceof FormData ? body : JSON.stringify(body ?? {}),
    });
  }

  private patch<T>(path: string, body?: unknown) {
    return this.request<T>(path, { method: 'PATCH', body: JSON.stringify(body ?? {}) });
  }

  // --- Auth ---
  async ensureCsrfCookie(): Promise<void> {
    if (this.options.mode !== 'cookie') return;
    await fetch(`${this.options.baseUrl.replace(/\/api$/, '')}/sanctum/csrf-cookie`, {
      credentials: 'include',
    });
  }

  async login(email: string, password: string): Promise<AuthUser> {
    await this.ensureCsrfCookie();
    const { data } = await this.post<{ data: AuthUser }>('/v1/auth/login', { email, password });
    return data;
  }

  async mobileLogin(email: string, password: string, deviceName: string): Promise<AuthUser> {
    const { data, token } = await this.post<{ data: AuthUser; token: string }>(
      '/v1/auth/mobile-login',
      { email, password, device_name: deviceName },
    );
    await this.options.tokenStorage.setToken(token);
    return data;
  }

  async logout(): Promise<void> {
    await this.post('/v1/auth/logout');
    await this.options.tokenStorage.setToken(null);
  }

  me() {
    return this.get<{ data: AuthUser }>('/v1/auth/me').then((r) => r.data);
  }

  async forgotPassword(email: string): Promise<void> {
    await this.ensureCsrfCookie();
    await this.post('/v1/auth/forgot-password', { email });
  }

  async resetPassword(payload: { token: string; email: string; password: string; password_confirmation: string }): Promise<void> {
    await this.ensureCsrfCookie();
    await this.post('/v1/auth/reset-password', payload);
  }

  // --- Employees ---
  listEmployees(params: Record<string, string> = {}) {
    const qs = new URLSearchParams(params).toString();
    return this.get<PaginatedResponse<Employment>>(`/v1/employees${qs ? `?${qs}` : ''}`);
  }

  getEmployee(id: number) {
    return this.get<{ data: Employment }>(`/v1/employees/${id}`).then((r) => r.data);
  }

  hireEmployee(payload: Record<string, unknown>) {
    return this.post<{ data: Employment }>('/v1/employees', payload).then((r) => r.data);
  }

  transferEmployee(employmentId: number, payload: Record<string, unknown>) {
    return this.post(`/v1/employees/${employmentId}/transfer`, payload);
  }

  rehirePerson(personId: number, payload: Record<string, unknown>) {
    return this.post<{ data: Employment }>(`/v1/people/${personId}/rehire`, payload).then((r) => r.data);
  }

  terminateEmployee(employmentId: number, payload: { termination_date: string; reason: string }) {
    return this.post<{ data: Employment }>(`/v1/employees/${employmentId}/terminate`, payload).then((r) => r.data);
  }

  bulkTransferEmployees(employmentIds: number[], payload: Record<string, unknown>) {
    return this.post<{ data: { succeeded: number[]; failed: Array<{ employment_id: number; error: string }> } }>(
      '/v1/employees/bulk-transfer',
      { employment_ids: employmentIds, ...payload },
    ).then((r) => r.data);
  }

  // --- Timeline ---
  getTimeline(personId: number) {
    return this.get<PaginatedResponse<EmployeeEvent>>(`/v1/people/${personId}/timeline`);
  }

  addNote(personId: number, summary: string, visibility?: string) {
    return this.post<{ data: EmployeeEvent }>(`/v1/people/${personId}/timeline`, { summary, visibility });
  }

  // --- Compensation ---
  getCompensationHistory(employmentId: number) {
    return this.get<{ data: unknown[] }>(`/v1/employees/${employmentId}/compensation`);
  }

  addCompensationChange(employmentId: number, payload: Record<string, unknown>) {
    return this.post(`/v1/employees/${employmentId}/compensation`, payload);
  }

  getBankingInfo(employmentId: number) {
    return this.get<{ data: BankingInfo | Record<string, never> }>(`/v1/employees/${employmentId}/banking-info`).then((r) => r.data);
  }

  submitBankingInfo(employmentId: number, payload: { routing_number: string; account_number: string; account_type: 'checking' | 'savings' }) {
    return this.post<{ data: BankingInfo }>(`/v1/employees/${employmentId}/banking-info`, payload).then((r) => r.data);
  }

  // --- Documents ---
  listDocumentCategories() {
    return this.get<{ data: DocumentCategory[] }>('/v1/document-categories').then((r) => r.data);
  }

  listDocuments(employmentId: number) {
    return this.get<{ data: DocumentRecord[] }>(`/v1/employees/${employmentId}/documents`);
  }

  uploadDocument(employmentId: number, form: FormData) {
    return this.post<{ data: DocumentRecord }>(`/v1/employees/${employmentId}/documents`, form);
  }

  acknowledgeDocument(employmentId: number, documentId: number, payload: Record<string, unknown>) {
    return this.post(`/v1/employees/${employmentId}/documents/${documentId}/acknowledge`, payload);
  }

  // --- Onboarding ---
  listOnboardingTemplates() {
    return this.get<{ data: unknown[] }>('/v1/onboarding/templates').then((r) => r.data);
  }

  getOnboardingWorkflow(employmentId: number) {
    return this.get<{ data: OnboardingWorkflow | null }>(`/v1/employees/${employmentId}/onboarding`).then((r) => r.data);
  }

  startOnboarding(employmentId: number, templateId: number) {
    return this.post<{ data: OnboardingWorkflow }>(`/v1/employees/${employmentId}/onboarding`, { template_id: templateId });
  }

  completeOnboardingTask(taskId: number) {
    return this.post(`/v1/onboarding/tasks/${taskId}/complete`);
  }

  getBackgroundChecks(employmentId: number) {
    return this.get<{ data: BackgroundCheckStatus[] }>(`/v1/employees/${employmentId}/background-checks`).then((r) => r.data);
  }

  // --- Offboarding ---
  getOffboardingWorkflow(employmentId: number) {
    return this.get<{ data: OffboardingWorkflow | null }>(`/v1/employees/${employmentId}/offboarding`).then((r) => r.data);
  }

  completeOffboardingTask(taskId: number) {
    return this.post(`/v1/offboarding/tasks/${taskId}/complete`);
  }

  // --- Time off ---
  listTimeOffPolicies() {
    return this.get<{ data: TimeOffPolicy[] }>('/v1/time-off/policies').then((r) => r.data);
  }

  listTimeOffRequests(params: Record<string, string> = {}) {
    const qs = new URLSearchParams(params).toString();
    return this.get<PaginatedResponse<TimeOffRequest>>(`/v1/time-off/requests${qs ? `?${qs}` : ''}`);
  }

  submitTimeOffRequest(payload: Record<string, unknown>) {
    return this.post<{ data: TimeOffRequest }>('/v1/time-off/requests', payload).then((r) => r.data);
  }

  decideTimeOffRequest(requestId: number, decision: 'approve' | 'deny', notes?: string) {
    return this.post<{ data: TimeOffRequest }>(`/v1/time-off/requests/${requestId}/${decision}`, { notes }).then((r) => r.data);
  }

  getTimeOffBalances(employmentId: number) {
    return this.get<{ data: TimeOffBalance[] }>(`/v1/employees/${employmentId}/time-off-balances`).then((r) => r.data);
  }

  // --- ATS ---
  listPipelineStages() {
    return this.get<{ data: unknown[] }>('/v1/ats/pipeline-stages').then((r) => r.data);
  }

  listRequisitions(params: Record<string, string> = {}) {
    const qs = new URLSearchParams(params).toString();
    return this.get<PaginatedResponse<JobRequisition>>(`/v1/ats/requisitions${qs ? `?${qs}` : ''}`);
  }

  createRequisition(payload: Record<string, unknown>) {
    return this.post<{ data: JobRequisition }>('/v1/ats/requisitions', payload).then((r) => r.data);
  }

  createCandidateApplication(payload: Record<string, unknown>) {
    return this.post<{ data: Application }>('/v1/ats/candidates', payload).then((r) => r.data);
  }

  listApplications(params: Record<string, string> = {}) {
    const qs = new URLSearchParams(params).toString();
    return this.get<PaginatedResponse<Application>>(`/v1/ats/applications${qs ? `?${qs}` : ''}`);
  }

  moveApplicationStage(applicationId: number, stageId: number) {
    return this.post<{ data: Application }>(`/v1/ats/applications/${applicationId}/move-stage`, { stage_id: stageId });
  }

  hireApplication(applicationId: number, payload: Record<string, unknown>) {
    return this.post(`/v1/ats/applications/${applicationId}/hire`, payload);
  }

  confirmFormerEmployee(candidateId: number, confirmed: boolean) {
    return this.post(`/v1/ats/candidates/${candidateId}/confirm-former-employee`, { confirmed });
  }

  // --- Notifications ---
  listNotifications() {
    return this.get<PaginatedResponse<AppNotification>>('/v1/notifications');
  }

  markNotificationRead(notificationId: number) {
    return this.post<{ data: AppNotification }>(`/v1/notifications/${notificationId}/read`);
  }

  // --- Dashboard / reporting ---
  getDashboard() {
    return this.get<{ data: DashboardSummary }>('/v1/dashboard').then((r) => r.data);
  }

  getTurnoverReport(params: { from?: string; to?: string } = {}) {
    const qs = new URLSearchParams(params as Record<string, string>).toString();
    return this.get<{ data: TurnoverReport }>(`/v1/reports/turnover${qs ? `?${qs}` : ''}`).then((r) => r.data);
  }

  // --- Admin config ---
  listAdminLocations() {
    return this.get<{ data: AdminLocation[] }>('/v1/admin/locations').then((r) => r.data);
  }

  createAdminLocation(payload: Record<string, unknown>) {
    return this.post<{ data: AdminLocation }>('/v1/admin/locations', payload).then((r) => r.data);
  }

  updateAdminLocation(id: number, payload: Record<string, unknown>) {
    return this.patch<{ data: AdminLocation }>(`/v1/admin/locations/${id}`, payload).then((r) => r.data);
  }

  listAdminDepartments() {
    return this.get<{ data: AdminDepartment[] }>('/v1/admin/departments').then((r) => r.data);
  }

  createAdminDepartment(payload: Record<string, unknown>) {
    return this.post<{ data: AdminDepartment }>('/v1/admin/departments', payload).then((r) => r.data);
  }

  listAdminPositions() {
    return this.get<{ data: AdminPosition[] }>('/v1/admin/positions').then((r) => r.data);
  }

  createAdminPosition(payload: Record<string, unknown>) {
    return this.post<{ data: AdminPosition }>('/v1/admin/positions', payload).then((r) => r.data);
  }

  listAdminTimeOffPolicies() {
    return this.get<{ data: TimeOffPolicy[] }>('/v1/admin/time-off-policies').then((r) => r.data);
  }

  createAdminTimeOffPolicy(payload: Record<string, unknown>) {
    return this.post<{ data: TimeOffPolicy }>('/v1/admin/time-off-policies', payload).then((r) => r.data);
  }

  listAdminDocumentCategories() {
    return this.get<{ data: DocumentCategory[] }>('/v1/admin/document-categories').then((r) => r.data);
  }

  createAdminDocumentCategory(payload: Record<string, unknown>) {
    return this.post<{ data: DocumentCategory }>('/v1/admin/document-categories', payload).then((r) => r.data);
  }

  listAdminOnboardingTemplates() {
    return this.get<{ data: unknown[] }>('/v1/admin/onboarding-templates').then((r) => r.data);
  }

  createAdminOnboardingTemplate(payload: Record<string, unknown>) {
    return this.post<{ data: unknown }>('/v1/admin/onboarding-templates', payload).then((r) => r.data);
  }

  addAdminOnboardingTemplateTask(templateId: number, payload: Record<string, unknown>) {
    return this.post<{ data: unknown }>(`/v1/admin/onboarding-templates/${templateId}/tasks`, payload).then((r) => r.data);
  }

  // --- Data import ---
  previewImport(form: FormData) {
    return this.post<ImportPreviewResult>('/v1/admin/import/preview', form);
  }

  commitImport(rows: Array<Record<string, string | null>>) {
    return this.post<ImportCommitResult>('/v1/admin/import/commit', { rows });
  }
}

export * from '@hris/shared-types';
