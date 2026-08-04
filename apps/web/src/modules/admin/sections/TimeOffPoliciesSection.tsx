import { FormEvent, useEffect, useState } from 'react';
import type { TimeOffPolicy } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function TimeOffPoliciesSection() {
  const [policies, setPolicies] = useState<TimeOffPolicy[]>([]);
  const [form, setForm] = useState({ name: '', applies_to: 'all', accrual_method: 'per_pay_period', accrual_rate: '3', max_balance: '120' });
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listAdminTimeOffPolicies().then(setPolicies);
  }

  useEffect(reload, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createAdminTimeOffPolicy({
        ...form,
        accrual_rate: Number(form.accrual_rate),
        max_balance: form.max_balance ? Number(form.max_balance) : null,
      });
      setForm({ name: '', applies_to: 'all', accrual_method: 'per_pay_period', accrual_rate: '3', max_balance: '120' });
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
          Applies to
          <select value={form.applies_to} onChange={(e) => setForm((f) => ({ ...f, applies_to: e.target.value }))}>
            <option value="all">All</option>
            <option value="hourly">Hourly</option>
            <option value="salaried">Salaried</option>
          </select>
        </label>
        <label>
          Accrual rate (hrs/period)
          <input type="number" step="0.1" value={form.accrual_rate} onChange={(e) => setForm((f) => ({ ...f, accrual_rate: e.target.value }))} />
        </label>
        <label>
          Max balance
          <input type="number" step="0.1" value={form.max_balance} onChange={(e) => setForm((f) => ({ ...f, max_balance: e.target.value }))} />
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add policy'}
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Applies to</th>
            <th>Accrual rate</th>
            <th>Max balance</th>
          </tr>
        </thead>
        <tbody>
          {policies.map((p) => (
            <tr key={p.id}>
              <td>{p.name}</td>
              <td>{p.applies_to}</td>
              <td>{p.accrual_rate}</td>
              <td>{p.max_balance ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
