import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../lib/AuthContext';
import { RequireRole } from '../lib/RequireRole';
import { roleLabels } from '@hris/ui-tokens';

export function Layout() {
  const { user, logout } = useAuth();

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="brand">HRIS</div>
        <nav>
          <NavLink to="/" end>
            Dashboard
          </NavLink>
          <NavLink to="/employees">Employees</NavLink>
          <NavLink to="/time-off">Time Off</NavLink>
          <RequireRole roles={['admin', 'hr_manager']}>
            <NavLink to="/onboarding">Onboarding</NavLink>
            <NavLink to="/ats">Hiring (ATS)</NavLink>
            <NavLink to="/reports/turnover">Reports</NavLink>
            <NavLink to="/admin">Admin</NavLink>
          </RequireRole>
        </nav>
        <div className="user-card">
          <div className="user-name">{user?.name}</div>
          <div className="user-role">{user ? roleLabels[user.role] : ''}</div>
          <button onClick={() => logout()}>Log out</button>
        </div>
      </aside>
      <main className="content">
        <Outlet />
      </main>
    </div>
  );
}
