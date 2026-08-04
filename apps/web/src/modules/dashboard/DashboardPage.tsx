import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import type { DashboardSummary } from '@hris/shared-types';
import { api } from '../../lib/apiClient';

export function DashboardPage() {
  const [summary, setSummary] = useState<DashboardSummary | null>(null);

  useEffect(() => {
    api.getDashboard().then(setSummary);
  }, []);

  if (!summary) return <p>Loading…</p>;

  return (
    <div>
      <h1>Dashboard</h1>

      <div className="stat-grid">
        <div className="stat-card">
          <div className="stat-value">{summary.headcount}</div>
          <div className="stat-label">Active employees</div>
        </div>
        <div className="stat-card">
          <div className="stat-value">{summary.open_requisitions}</div>
          <div className="stat-label">Open requisitions</div>
        </div>
        <div className="stat-card">
          <div className="stat-value">{summary.pending_time_off_requests}</div>
          <div className="stat-label">Pending time off requests</div>
        </div>
      </div>

      <h2>Recent hires</h2>
      <table className="data-table">
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
              <td colSpan={2} className="muted">
                No recent hires.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
