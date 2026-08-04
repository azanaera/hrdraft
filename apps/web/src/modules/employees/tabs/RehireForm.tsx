import { FormEvent, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../../lib/apiClient';

export function RehireForm({ personId, defaultEmploymentType }: { personId: number; defaultEmploymentType: string }) {
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({
    employee_number: '',
    hire_date: new Date().toISOString().slice(0, 10),
    employment_type: defaultEmploymentType,
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const employment = await api.rehirePerson(personId, form);
      navigate(`/employees/${employment.id}`);
    } catch {
      setError('Could not rehire — check the employee number is unique.');
    } finally {
      setSubmitting(false);
    }
  }

  if (!open) {
    return <button onClick={() => setOpen(true)}>Rehire this person</button>;
  }

  return (
    <form className="form-grid" onSubmit={handleSubmit}>
      <label>
        New employee #
        <input required value={form.employee_number} onChange={(e) => setForm((f) => ({ ...f, employee_number: e.target.value }))} />
      </label>
      <label>
        Hire date
        <input required type="date" value={form.hire_date} onChange={(e) => setForm((f) => ({ ...f, hire_date: e.target.value }))} />
      </label>
      <label>
        Employment type
        <select value={form.employment_type} onChange={(e) => setForm((f) => ({ ...f, employment_type: e.target.value }))}>
          <option value="hourly">Hourly</option>
          <option value="salaried">Salaried</option>
        </select>
      </label>
      {error && <div className="error-text form-span">{error}</div>}
      <div className="form-span">
        <button type="submit" disabled={submitting}>
          {submitting ? 'Rehiring…' : 'Confirm rehire'}
        </button>
        <button type="button" className="secondary" onClick={() => setOpen(false)}>
          Cancel
        </button>
      </div>
    </form>
  );
}
