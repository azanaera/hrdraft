import { FormEvent, useEffect, useState } from 'react';
import type { DocumentCategory } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function DocumentCategoriesSection() {
  const [categories, setCategories] = useState<DocumentCategory[]>([]);
  const [form, setForm] = useState({ name: '', requires_signature: false, applicable_to: 'all' });
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listAdminDocumentCategories().then(setCategories);
  }

  useEffect(reload, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createAdminDocumentCategory(form);
      setForm({ name: '', requires_signature: false, applicable_to: 'all' });
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
          Applicable to
          <select value={form.applicable_to} onChange={(e) => setForm((f) => ({ ...f, applicable_to: e.target.value }))}>
            <option value="all">All</option>
            <option value="employee">Employee</option>
            <option value="candidate">Candidate</option>
          </select>
        </label>
        <label>
          <input
            type="checkbox"
            checked={form.requires_signature}
            onChange={(e) => setForm((f) => ({ ...f, requires_signature: e.target.checked }))}
          />{' '}
          Requires e-signature
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add category'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Applicable to</th>
            <th>Requires signature</th>
          </tr>
        </thead>
        <tbody>
          {categories.map((c) => (
            <tr key={c.id}>
              <td>{c.name}</td>
              <td>{c.applicable_to}</td>
              <td>{c.requires_signature ? 'Yes' : 'No'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
