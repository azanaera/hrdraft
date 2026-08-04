import { FormEvent, useEffect, useState } from 'react';
import type { TurnoverReport } from '@hris/shared-types';
import { api } from '../../lib/apiClient';

export function TurnoverReportPage() {
  const [report, setReport] = useState<TurnoverReport | null>(null);
  const [range, setRange] = useState({
    from: new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().slice(0, 10),
    to: new Date().toISOString().slice(0, 10),
  });

  function load() {
    api.getTurnoverReport(range).then(setReport);
  }

  useEffect(load, []);

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    load();
  }

  return (
    <div>
      <h1>Turnover &amp; Retention</h1>

      <form className="filter-bar" onSubmit={handleSubmit}>
        <input type="date" value={range.from} onChange={(e) => setRange((r) => ({ ...r, from: e.target.value }))} />
        <input type="date" value={range.to} onChange={(e) => setRange((r) => ({ ...r, to: e.target.value }))} />
        <button type="submit">Update</button>
      </form>

      {report && (
        <>
          <div className="stat-grid">
            <div className="stat-card">
              <div className="stat-value">{report.termination_count}</div>
              <div className="stat-label">Terminations in period</div>
            </div>
            <div className="stat-card">
              <div className="stat-value">{report.turnover_rate}%</div>
              <div className="stat-label">Turnover rate</div>
            </div>
            <div className="stat-card">
              <div className="stat-value">{report.average_tenure_days}</div>
              <div className="stat-label">Avg. tenure (days)</div>
            </div>
            <div className="stat-card">
              <div className="stat-value">{report.rehire_rate}%</div>
              <div className="stat-label">Rehire rate</div>
            </div>
          </div>

          <div className="card">
            <h3>Terminations by department</h3>
            {report.terminations_by_department.map((row) => (
              <div className="row" key={row.label}>
                <span>{row.label}</span>
                <span>{row.count}</span>
              </div>
            ))}
            {report.terminations_by_department.length === 0 && <p className="muted">No terminations in this period.</p>}
          </div>

          <div className="card">
            <h3>Terminations by location</h3>
            {report.terminations_by_location.map((row) => (
              <div className="row" key={row.label}>
                <span>{row.label}</span>
                <span>{row.count}</span>
              </div>
            ))}
            {report.terminations_by_location.length === 0 && <p className="muted">No terminations in this period.</p>}
          </div>

          <div className="card">
            <h3>Terminations by reason</h3>
            {report.terminations_by_reason.map((row) => (
              <div className="row" key={row.reason}>
                <span>{row.reason}</span>
                <span>{row.count}</span>
              </div>
            ))}
            {report.terminations_by_reason.length === 0 && <p className="muted">No terminations in this period.</p>}
          </div>
        </>
      )}
    </div>
  );
}
