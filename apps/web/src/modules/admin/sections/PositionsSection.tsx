import { FormEvent, useEffect, useState } from 'react';
import type { AdminDepartment, AdminPosition } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function PositionsSection() {
  const [positions, setPositions] = useState<AdminPosition[]>([]);
  const [departments, setDepartments] = useState<AdminDepartment[]>([]);
  const [form, setForm] = useState({ title: '', department_id: '', default_employment_type: 'hourly' });
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listAdminPositions().then(setPositions);
  }

  useEffect(() => {
    reload();
    api.listAdminDepartments().then(setDepartments);
  }, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createAdminPosition({ ...form, department_id: Number(form.department_id) });
      setForm({ title: '', department_id: '', default_employment_type: 'hourly' });
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <form className="card form-grid" onSubmit={handleSubmit}>
        <label>
          Title
          <input required value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
        </label>
        <label>
          Department
          <select required value={form.department_id} onChange={(e) => setForm((f) => ({ ...f, department_id: e.target.value }))}>
            <option value="">Select…</option>
            {departments.map((d) => (
              <option key={d.id} value={d.id}>
                {d.name}
              </option>
            ))}
          </select>
        </label>
        <label>
          Default employment type
          <select value={form.default_employment_type} onChange={(e) => setForm((f) => ({ ...f, default_employment_type: e.target.value }))}>
            <option value="hourly">Hourly</option>
            <option value="salaried">Salaried</option>
          </select>
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add position'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
          {positions.map((p) => (
            <tr key={p.id}>
              <td>{p.id}</td>
              <td>{p.title}</td>
              <td>{p.default_employment_type}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
