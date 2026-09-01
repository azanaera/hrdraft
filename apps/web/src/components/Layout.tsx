import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import type { Role } from '@hris/shared-types';
import { useAuth } from '../lib/AuthContext';
import { RequireRole } from '../lib/RequireRole';
import { roleLabels } from '@hris/ui-tokens';

interface NavItem {
  to: string;
  end: boolean;
  icon: string;
  label: string;
  roles: Role[] | null;
}

const NAV_ITEMS: NavItem[] = [
  { to: '/', end: true, icon: 'home', label: 'Dashboard', roles: null },
  { to: '/employees', end: false, icon: 'users', label: 'Employees', roles: null },
  { to: '/time-off', end: false, icon: 'calendar', label: 'Time Off', roles: null },
  { to: '/onboarding', end: false, icon: 'check-square', label: 'Onboarding', roles: ['admin', 'hr_manager'] },
  { to: '/ats', end: false, icon: 'briefcase', label: 'Hiring', roles: ['admin', 'hr_manager'] },
  { to: '/reports/turnover', end: false, icon: 'bar-chart-2', label: 'Reports', roles: ['admin', 'hr_manager'] },
  { to: '/admin', end: false, icon: 'settings', label: 'Admin', roles: ['admin', 'hr_manager'] },
];

function greeting(): string {
  const hour = new Date().getHours();
  if (hour < 12) return 'Good morning';
  if (hour < 18) return 'Good afternoon';
  return 'Good evening';
}

export function Layout() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const [sidebarHidden, setSidebarHidden] = useState(false);

  // Hando's own app.js is intentionally not loaded (see index.html) — its
  // init() runs before React mounts this shell, so feather.replace() has to
  // be called here instead, once on mount and again after every navigation
  // in case a page renders its own data-feather icons.
  useEffect(() => {
    window.feather?.replace();
  }, [location.pathname]);

  useEffect(() => {
    document.body.setAttribute('data-sidebar', sidebarHidden ? 'hidden' : 'default');
  }, [sidebarHidden]);

  const firstName = user?.name?.split(' ')[0] ?? '';

  return (
    <div id="app-layout">
      <div className="topbar-custom">
        <div className="container-fluid">
          <div className="d-flex justify-content-between">
            <ul className="list-unstyled topnav-menu mb-0 d-flex align-items-center">
              <li>
                <button className="button-toggle-menu nav-link" onClick={() => setSidebarHidden((v) => !v)}>
                  <i data-feather="menu" className="noti-icon"></i>
                </button>
              </li>
              <li className="d-none d-lg-block">
                <h5 className="mb-0">
                  {greeting()}
                  {firstName ? `, ${firstName}` : ''}
                </h5>
              </li>
            </ul>

            <ul className="list-unstyled topnav-menu mb-0 d-flex align-items-center">
              <li className="d-none d-sm-flex">
                <button type="button" className="btn nav-link" id="light-dark-mode">
                  <i data-feather="moon" className="align-middle dark-mode"></i>
                  <i data-feather="sun" className="align-middle light-mode"></i>
                </button>
              </li>

              <li className="dropdown notification-list topbar-dropdown">
                <a
                  className="nav-link dropdown-toggle nav-user me-0"
                  data-bs-toggle="dropdown"
                  href="#"
                  role="button"
                  aria-haspopup="false"
                  aria-expanded="false"
                  aria-label="Account menu"
                >
                  <span className="pro-user-name ms-1">
                    {user?.name} <i className="mdi mdi-chevron-down"></i>
                  </span>
                </a>
                <div className="dropdown-menu dropdown-menu-end profile-dropdown">
                  <div className="dropdown-header noti-title">
                    <h6 className="text-overflow m-0">{user ? roleLabels[user.role] : ''}</h6>
                  </div>
                  <div className="dropdown-divider"></div>
                  <button className="dropdown-item notify-item border-0 bg-transparent w-100 text-start" onClick={() => logout()}>
                    <i className="mdi mdi-location-exit fs-16 align-middle"></i>
                    <span>Log out</span>
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div className="app-sidebar-menu">
        <div className="h-100" data-simplebar>
          <div id="sidebar-menu">
            <div className="logo-box">
              <span className="logo logo-light">
                <span className="logo-lg fw-semibold fs-18 text-white">HRIS</span>
              </span>
              <span className="logo logo-dark">
                <span className="logo-lg fw-semibold fs-18">HRIS</span>
              </span>
            </div>

            <ul id="side-menu">
              <li className="menu-title">Menu</li>
              {NAV_ITEMS.map((item) => {
                const link = (
                  <li key={item.to}>
                    <NavLink to={item.to} end={item.end} className="tp-link">
                      <i data-feather={item.icon}></i>
                      <span> {item.label} </span>
                    </NavLink>
                  </li>
                );
                return item.roles ? (
                  <RequireRole key={item.to} roles={item.roles}>
                    {link}
                  </RequireRole>
                ) : (
                  link
                );
              })}
            </ul>
          </div>
          <div className="clearfix"></div>
        </div>
      </div>

      <div className="content-page">
        <div className="content">
          <div className="container-fluid">
            <Outlet />
          </div>
        </div>

        <footer className="footer">
          <div className="container-fluid">
            <div className="row">
              <div className="col fs-13 text-muted text-center">&copy; {new Date().getFullYear()} HRIS</div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  );
}
