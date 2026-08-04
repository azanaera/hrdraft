import { FormEvent, useState } from 'react';
import { api } from '../../lib/apiClient';

export function BulkTransferBar({
  employmentIds,
  onDone,
  onCancel,
}: {
  employmentIds: number[];
  onDone: () => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState({
    department_id: '',
    location_id: '',
    position_id: '',
    effective_start_date: new Date().toISOString().slice(0, 10),
  });
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult] = useState<{ succeeded: number[]; failed: unknown[] } | null>(null);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await api.bulkTransferEmployees(employmentIds, {
        department_id: Number(form.department_id),
        location_id: Number(form.location_id),
        position_id: Number(form.position_id),
        effective_start_date: form.effective_start_date,
      });
      setResult(res);
      if (res.failed.length === 0) onDone();
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form className="bulk-action-bar" onSubmit={handleSubmit}>
      <strong>{employmentIds.length} selected</strong>
      <input
        placeholder="Department ID"
        required
        value={form.department_id}
        onChange={(e) => setForm((f) => ({ ...f, department_id: e.target.value }))}
      />
      <input
        placeholder="Location ID"
        required
        value={form.location_id}
        onChange={(e) => setForm((f) => ({ ...f, location_id: e.target.value }))}
      />
      <input
        placeholder="Position ID"
        required
        value={form.position_id}
        onChange={(e) => setForm((f) => ({ ...f, position_id: e.target.value }))}
      />
      <input
        type="date"
        required
        value={form.effective_start_date}
        onChange={(e) => setForm((f) => ({ ...f, effective_start_date: e.target.value }))}
      />
      <button type="submit" disabled={submitting}>
        {submitting ? 'Transferring…' : 'Bulk transfer'}
      </button>
      <button type="button" className="secondary" onClick={onCancel}>
        Cancel
      </button>
      {result && result.failed.length > 0 && (
        <span className="error-text">
          {result.succeeded.length} succeeded, {result.failed.length} failed.
        </span>
      )}
    </form>
  );
}
