import { FormEvent, useEffect, useRef, useState } from 'react';
import type { TimeOffPolicy, TimeOffRequest } from '@hris/shared-types';
import { api } from '../../lib/apiClient';
import { useAuth } from '../../lib/AuthContext';

export function TimeOffPage() {
  const { user } = useAuth();
  const [requests, setRequests] = useState<TimeOffRequest[]>([]);
  const [policies, setPolicies] = useState<TimeOffPolicy[]>([]);
  const [form, setForm] = useState({
    policy_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date().toISOString().slice(0, 10),
    hours_requested: '8',
  });
  const [submitting, setSubmitting] = useState(false);
  // Guards against an older in-flight GET (e.g. the initial mount load)
  // resolving after a newer one (e.g. post-submit reload) and clobbering it
  // with stale data — same class of race as EmployeeListPage's search filter.
  const requestSeq = useRef(0);

  function reload() {
    const requestId = ++requestSeq.current;
    api.listTimeOffRequests().then((res) => {
      if (requestId === requestSeq.current) setRequests(res.data);
    });
  }

  useEffect(() => {
    reload();
    api.listTimeOffPolicies().then(setPolicies);
  }, []);

  const canDecide = user && user.role !== 'employee';

  async function submitRequest(e: FormEvent) {
    e.preventDefault();
    if (!user?.employment_id) return;
    setSubmitting(true);
    try {
      await api.submitTimeOffRequest({
        employment_id: user.employment_id,
        policy_id: Number(form.policy_id),
        start_date: form.start_date,
        end_date: form.end_date,
        hours_requested: Number(form.hours_requested),
      });
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  async function decide(id: number, decision: 'approve' | 'deny') {
    await api.decideTimeOffRequest(id, decision);
    reload();
  }

  return (
    <div>
      <h1>Time Off</h1>

      <form className="card form-grid" onSubmit={submitRequest}>
        <label>
          Policy
          <select required value={form.policy_id} onChange={(e) => setForm((f) => ({ ...f, policy_id: e.target.value }))}>
            <option value="">Select…</option>
            {policies.map((p) => (
              <option key={p.id} value={p.id}>
                {p.name}
              </option>
            ))}
          </select>
        </label>
        <label>
          Start date
          <input required type="date" value={form.start_date} onChange={(e) => setForm((f) => ({ ...f, start_date: e.target.value }))} />
        </label>
        <label>
          End date
          <input required type="date" value={form.end_date} onChange={(e) => setForm((f) => ({ ...f, end_date: e.target.value }))} />
        </label>
        <label>
          Hours
          <input
            required
            type="number"
            step="0.5"
            value={form.hours_requested}
            onChange={(e) => setForm((f) => ({ ...f, hours_requested: e.target.value }))}
          />
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Submitting…' : 'Request time off'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Policy</th>
            <th>Dates</th>
            <th>Hours</th>
            <th>Status</th>
            {canDecide && <th>Actions</th>}
          </tr>
        </thead>
        <tbody>
          {requests.map((r) => (
            <tr key={r.id}>
              <td>{r.employee_name}</td>
              <td>{r.policy}</td>
              <td>
                {r.start_date} – {r.end_date}
              </td>
              <td>{r.hours_requested}</td>
              <td>
                <span className={`badge badge-${r.status}`}>{r.status}</span>
              </td>
              {canDecide && (
                <td>
                  {r.status === 'pending' && (
                    <>
                      <button className="small" onClick={() => decide(r.id, 'approve')}>
                        Approve
                      </button>
                      <button className="small secondary" onClick={() => decide(r.id, 'deny')}>
                        Deny
                      </button>
                    </>
                  )}
                </td>
              )}
            </tr>
          ))}
          {requests.length === 0 && (
            <tr>
              <td colSpan={canDecide ? 6 : 5} className="muted">
                No time off requests yet.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
