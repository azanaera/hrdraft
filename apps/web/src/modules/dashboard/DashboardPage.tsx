import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import type { DashboardSummary } from '@hris/shared-types';
import { api } from '../../lib/apiClient';

const WIDGETS = [
  { key: 'headcount' as const, label: 'Active employees', icon: 'users', bg: 'primary' },
  { key: 'open_requisitions' as const, label: 'Open requisitions', icon: 'briefcase', bg: 'secondary' },
  { key: 'pending_time_off_requests' as const, label: 'Pending time off requests', icon: 'calendar', bg: 'warning' },
];

export function DashboardPage() {
  const [summary, setSummary] = useState<DashboardSummary | null>(null);

  useEffect(() => {
    api.getDashboard().then(setSummary);
  }, []);

  // The widget icons only exist in the DOM once `summary` loads (see the
  // early return below) — Layout's route-change feather.replace() fires
  // before this async fetch resolves, so it never sees them. Re-run it here
  // once they're actually rendered.
  useEffect(() => {
    if (summary) window.feather?.replace();
  }, [summary]);

  if (!summary) return <p>Loading…</p>;

  return (
    <div>
      <div className="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
        <div className="flex-grow-1">
          <h4 className="fs-18 fw-semibold m-0">Dashboard</h4>
        </div>
      </div>

      <div className="row">
        {WIDGETS.map((w) => (
          <div className="col-md-6 col-lg-4" key={w.key}>
            <div className="card">
              <div className="card-body">
                <div className="d-flex align-items-center mb-2">
                  <div className={`p-2 border border-${w.bg} border-opacity-10 bg-${w.bg}-subtle rounded-2 me-2`}>
                    <div className={`bg-${w.bg} rounded-circle widget-size text-center`}>
                      <i data-feather={w.icon} className="text-white" style={{ width: 20, height: 20, padding: 8 }}></i>
                    </div>
                  </div>
                  <p className="mb-0 text-dark fs-15">{w.label}</p>
                </div>
                <h3 className="mb-0 fs-22 text-dark">{summary[w.key]}</h3>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card">
        <div className="card-body">
          <div className="d-flex align-items-center justify-content-between mb-3">
            <h4 className="card-title mb-0 fs-16">Recent hires</h4>
          </div>
          <table className="table table-centered mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              {summary.recent_hires.map((hire) => (
                <tr key={hire.employment_id}>
                  <td>
                    <Link to={`/employees/${hire.employment_id}`}>{hire.name}</Link>
                  </td>
                  <td>{hire.date}</td>
                </tr>
              ))}
              {summary.recent_hires.length === 0 && (
                <tr>
                  <td colSpan={2} className="text-muted">
                    No recent hires.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
