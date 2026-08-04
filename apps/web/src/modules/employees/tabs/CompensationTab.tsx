import { FormEvent, useEffect, useState } from 'react';
import type { CompensationRecord } from '@hris/shared-types';
import { ApiError } from '@hris/api-client';
import { api } from '../../../lib/apiClient';
import { RequireRole } from '../../../lib/RequireRole';
import { BankingInfoSection } from './BankingInfoSection';

export function CompensationTab({ employmentId, onChanged }: { employmentId: number; onChanged: () => void }) {
  const [records, setRecords] = useState<CompensationRecord[]>([]);
  const [form, setForm] = useState({
    pay_type: 'hourly',
    rate_amount: '',
    pay_frequency: 'biweekly',
    effective_date: new Date().toISOString().slice(0, 10),
    reason: 'raise',
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function reload() {
    api.getCompensationHistory(employmentId).then((res) => setRecords(res.data as CompensationRecord[]));
  }

  useEffect(reload, [employmentId]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await api.addCompensationChange(employmentId, { ...form, rate_amount: Number(form.rate_amount) });
      reload();
      onChanged();
    } catch (err) {
      // Wage-compliance violations come back as a 422 with a clear message
      // from CompensationService::assertMeetsMinimumWage.
      setError(err instanceof ApiError ? String((err.body as { message?: string })?.message ?? 'Could not apply change.') : 'Could not apply change.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <RequireRole roles={['admin', 'hr_manager']}>
        <form className="card form-grid" onSubmit={handleSubmit}>
          <label>
            Pay type
            <select value={form.pay_type} onChange={(e) => setForm((f) => ({ ...f, pay_type: e.target.value }))}>
              <option value="hourly">Hourly</option>
              <option value="salary">Salary</option>
            </select>
          </label>
          <label>
            Rate amount
            <input
              required
              type="number"
              step="0.01"
              value={form.rate_amount}
              onChange={(e) => setForm((f) => ({ ...f, rate_amount: e.target.value }))}
            />
          </label>
          <label>
            Pay frequency
            <select value={form.pay_frequency} onChange={(e) => setForm((f) => ({ ...f, pay_frequency: e.target.value }))}>
              <option value="weekly">Weekly</option>
              <option value="biweekly">Biweekly</option>
              <option value="semimonthly">Semimonthly</option>
              <option value="monthly">Monthly</option>
              <option value="annual">Annual</option>
            </select>
          </label>
          <label>
            Effective date
            <input
              required
              type="date"
              value={form.effective_date}
              onChange={(e) => setForm((f) => ({ ...f, effective_date: e.target.value }))}
            />
          </label>
          <label>
            Reason
            <select value={form.reason} onChange={(e) => setForm((f) => ({ ...f, reason: e.target.value }))}>
              <option value="raise">Raise</option>
              <option value="promotion">Promotion</option>
              <option value="transfer">Transfer</option>
              <option value="adjustment">Adjustment</option>
              <option value="correction">Correction</option>
            </select>
          </label>
          {error && <div className="error-text form-span">{error}</div>}
          <button className="form-span" type="submit" disabled={submitting}>
            {submitting ? 'Saving…' : 'Apply compensation change'}
          </button>
        </form>
      </RequireRole>

      <table className="data-table">
        <thead>
          <tr>
            <th>Effective</th>
            <th>End</th>
            <th>Type</th>
            <th>Rate</th>
            <th>Frequency</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          {records.map((r) => (
            <tr key={r.id}>
              <td>{r.effective_date}</td>
              <td>{r.end_date ?? 'current'}</td>
              <td>{r.pay_type}</td>
              <td>{r.rate_amount}</td>
              <td>{r.pay_frequency}</td>
              <td>{r.reason}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <BankingInfoSection employmentId={employmentId} />
    </div>
  );
}
