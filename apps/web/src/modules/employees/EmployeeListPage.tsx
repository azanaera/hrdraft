import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import type { AdminDepartment, AdminLocation, Employment } from '@hris/shared-types';
import { api } from '../../lib/apiClient';
import { RequireRole } from '../../lib/RequireRole';
import { BulkTransferBar } from './BulkTransferBar';

export function EmployeeListPage() {
  const [employees, setEmployees] = useState<Employment[]>([]);
  const [loading, setLoading] = useState(true);
  const [departments, setDepartments] = useState<AdminDepartment[]>([]);
  const [locations, setLocations] = useState<AdminLocation[]>([]);
  const [filters, setFilters] = useState({ search: '', department_id: '', location_id: '', status: '' });
  const [selected, setSelected] = useState<number[]>([]);
  // Guards against out-of-order responses: if the user changes filters again
  // before an in-flight request resolves, an older/slower response must not
  // be allowed to overwrite the newer filtered result once it lands.
  const requestSeq = useRef(0);

  function reload() {
    const requestId = ++requestSeq.current;
    setLoading(true);
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''));
    api
      .listEmployees(params)
      .then((res) => {
        if (requestId === requestSeq.current) setEmployees(res.data);
      })
      .finally(() => {
        if (requestId === requestSeq.current) setLoading(false);
      });
  }

  useEffect(reload, [filters]);

  useEffect(() => {
    api.listAdminDepartments().then(setDepartments).catch(() => {});
    api.listAdminLocations().then(setLocations).catch(() => {});
  }, []);

  function toggleSelected(id: number) {
    setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  }

  return (
    <div>
      <div className="page-header">
        <h1>Employees</h1>
        <RequireRole roles={['admin', 'hr_manager']}>
          <Link className="button" to="/employees/new">
            + Hire employee
          </Link>
        </RequireRole>
      </div>

      <div className="filter-bar">
        <input
          placeholder="Search name or employee #"
          value={filters.search}
          onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
        />
        <select value={filters.department_id} onChange={(e) => setFilters((f) => ({ ...f, department_id: e.target.value }))}>
          <option value="">All departments</option>
          {departments.map((d) => (
            <option key={d.id} value={d.id}>
              {d.name}
            </option>
          ))}
        </select>
        <select value={filters.location_id} onChange={(e) => setFilters((f) => ({ ...f, location_id: e.target.value }))}>
          <option value="">All locations</option>
          {locations.map((l) => (
            <option key={l.id} value={l.id}>
              {l.name}
            </option>
          ))}
        </select>
        <select value={filters.status} onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))}>
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="on_leave">On leave</option>
          <option value="terminated">Terminated</option>
        </select>
      </div>

      <RequireRole roles={['admin', 'hr_manager']}>
        {selected.length > 0 && (
          <BulkTransferBar
            employmentIds={selected}
            onDone={() => {
              setSelected([]);
              reload();
            }}
            onCancel={() => setSelected([])}
          />
        )}
      </RequireRole>

      {loading ? (
        <p>Loading…</p>
      ) : (
        <table className="data-table">
          <thead>
            <tr>
              <RequireRole roles={['admin', 'hr_manager']}>
                <th className="checkbox-cell"></th>
              </RequireRole>
              <th>Name</th>
              <th>Employee #</th>
              <th>Department</th>
              <th>Location</th>
              <th>Type</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {employees.map((e) => (
              <tr key={e.id}>
                <RequireRole roles={['admin', 'hr_manager']}>
                  <td className="checkbox-cell">
                    <input type="checkbox" checked={selected.includes(e.id)} onChange={() => toggleSelected(e.id)} />
                  </td>
                </RequireRole>
                <td>
                  <Link to={`/employees/${e.id}`}>
                    {e.person.first_name} {e.person.last_name}
                  </Link>
                </td>
                <td>{e.employee_number}</td>
                <td>{e.current_assignment?.department ?? '—'}</td>
                <td>{e.current_assignment?.location ?? '—'}</td>
                <td>{e.employment_type}</td>
                <td>
                  <span className={`badge badge-${e.employment_status}`}>{e.employment_status}</span>
                </td>
              </tr>
            ))}
            {employees.length === 0 && (
              <tr>
                <td colSpan={7} className="muted">
                  No employees match these filters.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      )}
    </div>
  );
}
