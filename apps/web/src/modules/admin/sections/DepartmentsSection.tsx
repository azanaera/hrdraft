import { FormEvent, useEffect, useState } from 'react';
import type { AdminDepartment } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function DepartmentsSection() {
  const [departments, setDepartments] = useState<AdminDepartment[]>([]);
  const [form, setForm] = useState({ name: '', code: '' });
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listAdminDepartments().then(setDepartments);
  }

  useEffect(reload, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createAdminDepartment(form);
      setForm({ name: '', code: '' });
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <form className="card form-grid" onSubmit={handleSubmit}>
        <label>
          Name
          <input required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
        </label>
        <label>
          Code
          <input required value={form.code} onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} />
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add department'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Code</th>
          </tr>
        </thead>
        <tbody>
          {departments.map((d) => (
            <tr key={d.id}>
              <td>{d.id}</td>
              <td>{d.name}</td>
              <td>{d.code}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
