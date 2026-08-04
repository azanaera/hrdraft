import { FormEvent, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../lib/apiClient';

export function NewEmployeePage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({
    first_name: '',
    last_name: '',
    personal_email: '',
    employee_number: '',
    hire_date: new Date().toISOString().slice(0, 10),
    employment_type: 'hourly',
    department_id: '',
    location_id: '',
    position_id: '',
    pay_type: 'hourly',
    rate_amount: '',
    pay_frequency: 'biweekly',
  });
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function set<K extends keyof typeof form>(key: K, value: string) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      const employment = await api.hireEmployee({
        ...form,
        department_id: Number(form.department_id),
        location_id: Number(form.location_id),
        position_id: Number(form.position_id),
        rate_amount: Number(form.rate_amount),
      });
      navigate(`/employees/${employment.id}`);
    } catch {
      setError('Could not hire employee — check required fields (department/location/position IDs must exist).');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <h1>Hire employee</h1>
      <form className="card form-grid" onSubmit={handleSubmit}>
        <label>
          First name
          <input required value={form.first_name} onChange={(e) => set('first_name', e.target.value)} />
        </label>
        <label>
          Last name
          <input required value={form.last_name} onChange={(e) => set('last_name', e.target.value)} />
        </label>
        <label>
          Personal email
          <input type="email" value={form.personal_email} onChange={(e) => set('personal_email', e.target.value)} />
        </label>
        <label>
          Employee #
          <input required value={form.employee_number} onChange={(e) => set('employee_number', e.target.value)} />
        </label>
        <label>
          Hire date
          <input required type="date" value={form.hire_date} onChange={(e) => set('hire_date', e.target.value)} />
        </label>
        <label>
          Employment type
          <select value={form.employment_type} onChange={(e) => set('employment_type', e.target.value)}>
            <option value="hourly">Hourly</option>
            <option value="salaried">Salaried</option>
          </select>
        </label>
        <label>
          Department ID
          <input required value={form.department_id} onChange={(e) => set('department_id', e.target.value)} />
        </label>
        <label>
          Location ID
          <input required value={form.location_id} onChange={(e) => set('location_id', e.target.value)} />
        </label>
        <label>
          Position ID
          <input required value={form.position_id} onChange={(e) => set('position_id', e.target.value)} />
        </label>
        <label>
          Pay type
          <select value={form.pay_type} onChange={(e) => set('pay_type', e.target.value)}>
            <option value="hourly">Hourly</option>
            <option value="salary">Salary</option>
          </select>
        </label>
        <label>
          Rate amount
          <input required type="number" step="0.01" value={form.rate_amount} onChange={(e) => set('rate_amount', e.target.value)} />
        </label>
        <label>
          Pay frequency
          <select value={form.pay_frequency} onChange={(e) => set('pay_frequency', e.target.value)}>
            <option value="weekly">Weekly</option>
            <option value="biweekly">Biweekly</option>
            <option value="semimonthly">Semimonthly</option>
            <option value="monthly">Monthly</option>
            <option value="annual">Annual</option>
          </select>
        </label>

        {error && <div className="error-text form-span">{error}</div>}
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Hiring…' : 'Hire employee'}
        </button>
      </form>
    </div>
  );
}
