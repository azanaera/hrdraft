import { FormEvent, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import type { JobRequisition } from '@hris/shared-types';
import { api } from '../../lib/apiClient';

export function RequisitionsPage() {
  const [requisitions, setRequisitions] = useState<JobRequisition[]>([]);
  const [form, setForm] = useState({
    title: '',
    department_id: '',
    location_id: '',
    position_id: '',
    employment_type: 'hourly',
    status: 'open',
  });
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listRequisitions().then((res) => setRequisitions(res.data));
  }

  useEffect(reload, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createRequisition({
        ...form,
        department_id: Number(form.department_id),
        location_id: Number(form.location_id),
        position_id: Number(form.position_id),
      });
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <h1>Hiring (ATS)</h1>

      <form className="card form-grid" onSubmit={handleSubmit}>
        <label>
          Title
          <input required value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
        </label>
        <label>
          Department ID
          <input required value={form.department_id} onChange={(e) => setForm((f) => ({ ...f, department_id: e.target.value }))} />
        </label>
        <label>
          Location ID
          <input required value={form.location_id} onChange={(e) => setForm((f) => ({ ...f, location_id: e.target.value }))} />
        </label>
        <label>
          Position ID
          <input required value={form.position_id} onChange={(e) => setForm((f) => ({ ...f, position_id: e.target.value }))} />
        </label>
        <label>
          Employment type
          <select value={form.employment_type} onChange={(e) => setForm((f) => ({ ...f, employment_type: e.target.value }))}>
            <option value="hourly">Hourly</option>
            <option value="salaried">Salaried</option>
          </select>
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Creating…' : 'Create requisition'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Department</th>
            <th>Location</th>
            <th>Status</th>
            <th>Applications</th>
          </tr>
        </thead>
        <tbody>
          {requisitions.map((r) => (
            <tr key={r.id}>
              <td>
                <Link to={`/ats/requisitions/${r.id}`}>{r.title}</Link>
              </td>
              <td>{r.department}</td>
              <td>{r.location}</td>
              <td>
                <span className={`badge badge-${r.status}`}>{r.status}</span>
              </td>
              <td>{r.applications_count ?? 0}</td>
            </tr>
          ))}
          {requisitions.length === 0 && (
            <tr>
              <td colSpan={5} className="muted">
                No open requisitions yet.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
