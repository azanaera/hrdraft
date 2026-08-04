import { FormEvent, useEffect, useState } from 'react';
import type { BankingInfo } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';
import { ApiError } from '@hris/api-client';

export function BankingInfoSection({ employmentId }: { employmentId: number }) {
  const [info, setInfo] = useState<BankingInfo | null>(null);
  const [form, setForm] = useState({ routing_number: '', account_number: '', account_type: 'checking' });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.getBankingInfo(employmentId).then((data) => setInfo((data as BankingInfo).provider ? (data as BankingInfo) : null));
  }, [employmentId]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const saved = await api.submitBankingInfo(employmentId, { ...form, account_type: form.account_type as 'checking' | 'savings' });
      setInfo(saved);
      setForm({ routing_number: '', account_number: '', account_type: 'checking' });
    } catch (err) {
      setError(err instanceof ApiError ? String((err.body as { message?: string })?.message ?? 'Could not save banking info.') : 'Could not save banking info.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="card">
      <h3>Direct deposit</h3>
      <p className="muted small">
        Routing/account numbers are exchanged for a provider token immediately and never stored — this uses a local fake
        tokenization provider standing in for Plaid/Stripe.
      </p>

      {info ? (
        <p>
          {info.account_type} account ending in <strong>{info.account_last_four}</strong> ({info.provider}
          {info.verified ? ', verified' : ''})
        </p>
      ) : (
        <p className="muted">No direct deposit account on file.</p>
      )}

      <form className="form-grid" onSubmit={handleSubmit}>
        <label>
          Routing number
          <input required maxLength={9} value={form.routing_number} onChange={(e) => setForm((f) => ({ ...f, routing_number: e.target.value }))} />
        </label>
        <label>
          Account number
          <input required value={form.account_number} onChange={(e) => setForm((f) => ({ ...f, account_number: e.target.value }))} />
        </label>
        <label>
          Account type
          <select value={form.account_type} onChange={(e) => setForm((f) => ({ ...f, account_type: e.target.value }))}>
            <option value="checking">Checking</option>
            <option value="savings">Savings</option>
          </select>
        </label>
        {error && <div className="error-text form-span">{error}</div>}
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : info ? 'Update account' : 'Add account'}
        </button>
      </form>
    </div>
  );
}
