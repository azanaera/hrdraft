import { FormEvent, useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import type { Application } from '@hris/shared-types';
import { api } from '../../lib/apiClient';

export function RequisitionDetailPage() {
  const { id } = useParams();
  const requisitionId = Number(id);
  const [applications, setApplications] = useState<Application[]>([]);
  const [candidateForm, setCandidateForm] = useState({ first_name: '', last_name: '', email: '' });
  const [hiringId, setHiringId] = useState<number | null>(null);
  const [hireForm, setHireForm] = useState({
    employee_number: '',
    hire_date: new Date().toISOString().slice(0, 10),
    employment_type: 'hourly',
    pay_type: 'hourly',
    rate_amount: '',
    pay_frequency: 'biweekly',
  });
  // Same out-of-order-response guard as EmployeeListPage/TimeOffPage.
  const requestSeq = useRef(0);

  function reload() {
    const requestId = ++requestSeq.current;
    api.listApplications({ requisition_id: String(requisitionId) }).then((res) => {
      if (requestId === requestSeq.current) setApplications(res.data);
    });
  }

  useEffect(reload, [requisitionId]);

  async function addCandidate(e: FormEvent) {
    e.preventDefault();
    await api.createCandidateApplication({ ...candidateForm, requisition_id: requisitionId });
    setCandidateForm({ first_name: '', last_name: '', email: '' });
    reload();
  }

  async function hire(applicationId: number) {
    await api.hireApplication(applicationId, {
      ...hireForm,
      rate_amount: Number(hireForm.rate_amount),
    });
    setHiringId(null);
    reload();
  }

  return (
    <div>
      <h1>Requisition #{requisitionId}</h1>

      <form className="card form-grid" onSubmit={addCandidate}>
        <h3 className="form-span">Add candidate</h3>
        <label>
          First name
          <input required value={candidateForm.first_name} onChange={(e) => setCandidateForm((f) => ({ ...f, first_name: e.target.value }))} />
        </label>
        <label>
          Last name
          <input required value={candidateForm.last_name} onChange={(e) => setCandidateForm((f) => ({ ...f, last_name: e.target.value }))} />
        </label>
        <label>
          Email
          <input required type="email" value={candidateForm.email} onChange={(e) => setCandidateForm((f) => ({ ...f, email: e.target.value }))} />
        </label>
        <button className="form-span" type="submit">
          Add to pipeline
        </button>
      </form>

      <table className="data-table">
        <thead>
          <tr>
            <th>Candidate</th>
            <th>Stage</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {applications.map((a) => (
            <tr key={a.id}>
              <td>
                {a.candidate?.name}
                {a.candidate?.is_former_employee && <span className="badge badge-info small"> former employee</span>}
              </td>
              <td>{a.current_stage}</td>
              <td>
                <span className={`badge badge-${a.status}`}>{a.status}</span>
              </td>
              <td>
                {a.status === 'active' && hiringId !== a.id && (
                  <button className="small" onClick={() => setHiringId(a.id)}>
                    Hire
                  </button>
                )}
              </td>
            </tr>
          ))}
          {applications.length === 0 && (
            <tr>
              <td colSpan={4} className="muted">
                No candidates yet.
              </td>
            </tr>
          )}
        </tbody>
      </table>

      {hiringId && (
        <form
          className="card form-grid"
          onSubmit={(e) => {
            e.preventDefault();
            hire(hiringId);
          }}
        >
          <h3 className="form-span">Hire candidate</h3>
          <label>
            Employee #
            <input required value={hireForm.employee_number} onChange={(e) => setHireForm((f) => ({ ...f, employee_number: e.target.value }))} />
          </label>
          <label>
            Hire date
            <input required type="date" value={hireForm.hire_date} onChange={(e) => setHireForm((f) => ({ ...f, hire_date: e.target.value }))} />
          </label>
          <label>
            Employment type
            <select value={hireForm.employment_type} onChange={(e) => setHireForm((f) => ({ ...f, employment_type: e.target.value }))}>
              <option value="hourly">Hourly</option>
              <option value="salaried">Salaried</option>
            </select>
          </label>
          <label>
            Rate amount
            <input required type="number" step="0.01" value={hireForm.rate_amount} onChange={(e) => setHireForm((f) => ({ ...f, rate_amount: e.target.value }))} />
          </label>
          <div className="form-span">
            <button type="submit">Confirm hire</button>
            <button type="button" className="secondary" onClick={() => setHiringId(null)}>
              Cancel
            </button>
          </div>
        </form>
      )}
    </div>
  );
}
