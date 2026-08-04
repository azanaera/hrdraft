import { FormEvent, useEffect, useState } from 'react';
import type { AdminLocation } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function LocationsSection() {
  const [locations, setLocations] = useState<AdminLocation[]>([]);
  const [form, setForm] = useState({ name: '', code: '', city: '', state: '', minimum_wage: '' });
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listAdminLocations().then(setLocations);
  }

  useEffect(reload, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createAdminLocation({ ...form, minimum_wage: form.minimum_wage ? Number(form.minimum_wage) : null });
      setForm({ name: '', code: '', city: '', state: '', minimum_wage: '' });
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
        <label>
          City
          <input value={form.city} onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))} />
        </label>
        <label>
          State
          <input value={form.state} onChange={(e) => setForm((f) => ({ ...f, state: e.target.value }))} />
        </label>
        <label>
          Minimum wage
          <input type="number" step="0.01" value={form.minimum_wage} onChange={(e) => setForm((f) => ({ ...f, minimum_wage: e.target.value }))} />
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add location'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Code</th>
            <th>State</th>
            <th>Min wage</th>
          </tr>
        </thead>
        <tbody>
          {locations.map((l) => (
            <tr key={l.id}>
              <td>{l.id}</td>
              <td>{l.name}</td>
              <td>{l.code}</td>
              <td>{l.state}</td>
              <td>{l.minimum_wage ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
