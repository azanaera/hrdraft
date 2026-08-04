import { FormEvent, useState } from 'react';
import { api } from '../../../lib/apiClient';

export function TerminateForm({ employmentId, onDone }: { employmentId: number; onDone: () => void }) {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ termination_date: new Date().toISOString().slice(0, 10), reason: '' });
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (!window.confirm('This immediately revokes the employee\'s system access and starts offboarding. Continue?')) return;
    setSubmitting(true);
    try {
      await api.terminateEmployee(employmentId, form);
      setOpen(false);
      onDone();
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) {
    return (
      <button className="secondary danger" onClick={() => setOpen(true)}>
        Terminate employee
      </button>
    );
  }

  return (
    <form className="form-grid" onSubmit={handleSubmit}>
      <label>
        Termination date
        <input required type="date" value={form.termination_date} onChange={(e) => setForm((f) => ({ ...f, termination_date: e.target.value }))} />
      </label>
      <label>
        Reason
        <input required value={form.reason} onChange={(e) => setForm((f) => ({ ...f, reason: e.target.value }))} placeholder="e.g. Voluntary resignation" />
      </label>
      <div className="form-span">
        <button type="submit" className="danger" disabled={submitting}>
          {submitting ? 'Terminating…' : 'Confirm termination'}
        </button>
        <button type="button" className="secondary" onClick={() => setOpen(false)}>
          Cancel
        </button>
      </div>
    </form>
  );
}
