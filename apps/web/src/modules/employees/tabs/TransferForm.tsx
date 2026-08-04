import { FormEvent, useState } from 'react';
import { api } from '../../../lib/apiClient';

export function TransferForm({ employmentId, onDone }: { employmentId: number; onDone: () => void }) {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({
    department_id: '',
    location_id: '',
    position_id: '',
    effective_start_date: new Date().toISOString().slice(0, 10),
  });
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.transferEmployee(employmentId, {
        department_id: Number(form.department_id),
        location_id: Number(form.location_id),
        position_id: Number(form.position_id),
        effective_start_date: form.effective_start_date,
      });
      setOpen(false);
      onDone();
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) {
    return (
      <button className="secondary" onClick={() => setOpen(true)}>
        Transfer employee
      </button>
    );
  }

  return (
    <form className="form-grid" onSubmit={handleSubmit}>
      <label>
        New department ID
        <input required value={form.department_id} onChange={(e) => setForm((f) => ({ ...f, department_id: e.target.value }))} />
      </label>
      <label>
        New location ID
        <input required value={form.location_id} onChange={(e) => setForm((f) => ({ ...f, location_id: e.target.value }))} />
      </label>
      <label>
        New position ID
        <input required value={form.position_id} onChange={(e) => setForm((f) => ({ ...f, position_id: e.target.value }))} />
      </label>
      <label>
        Effective date
        <input
          required
          type="date"
          value={form.effective_start_date}
          onChange={(e) => setForm((f) => ({ ...f, effective_start_date: e.target.value }))}
        />
      </label>
      <div className="form-span">
        <button type="submit" disabled={submitting}>
          {submitting ? 'Transferring…' : 'Confirm transfer'}
        </button>
        <button type="button" className="secondary" onClick={() => setOpen(false)}>
          Cancel
        </button>
      </div>
    </form>
  );
}
