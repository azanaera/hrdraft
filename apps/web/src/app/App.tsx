import { Route, Routes } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import { ProtectedRoute } from './ProtectedRoute';
import { Layout } from '../components/Layout';
import { LoginPage } from '../modules/auth/LoginPage';
import { ForgotPasswordPage } from '../modules/auth/ForgotPasswordPage';
import { ResetPasswordPage } from '../modules/auth/ResetPasswordPage';
import { DashboardPage } from '../modules/dashboard/DashboardPage';
import { EmployeeListPage } from '../modules/employees/EmployeeListPage';
import { EmployeeDetailPage } from '../modules/employees/EmployeeDetailPage';
import { NewEmployeePage } from '../modules/employees/NewEmployeePage';
import { TimeOffPage } from '../modules/time-off/TimeOffPage';
import { OnboardingTemplatesPage } from '../modules/onboarding/OnboardingTemplatesPage';
import { RequisitionsPage } from '../modules/ats/RequisitionsPage';
import { RequisitionDetailPage } from '../modules/ats/RequisitionDetailPage';
import { AdminPage } from '../modules/admin/AdminPage';
import { ImportPage } from '../modules/admin/ImportPage';
import { TurnoverReportPage } from '../modules/reports/TurnoverReportPage';

export function App() {
  const { loading } = useAuth();

  if (loading) {
    return <div className="center-screen">Loading…</div>;
  }

  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<DashboardPage />} />
        <Route path="employees" element={<EmployeeListPage />} />
        <Route path="employees/new" element={<NewEmployeePage />} />
        <Route path="employees/:id" element={<EmployeeDetailPage />} />
        <Route path="time-off" element={<TimeOffPage />} />
        <Route path="onboarding" element={<OnboardingTemplatesPage />} />
        <Route path="ats" element={<RequisitionsPage />} />
        <Route path="ats/requisitions/:id" element={<RequisitionDetailPage />} />
        <Route path="admin" element={<AdminPage />} />
        <Route path="admin/import" element={<ImportPage />} />
        <Route path="reports/turnover" element={<TurnoverReportPage />} />
      </Route>
    </Routes>
  );
}
