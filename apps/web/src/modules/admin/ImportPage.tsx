import { ChangeEvent, useState } from 'react';
import type { ImportCommitResult, ImportPreviewResult } from '@hris/shared-types';
import { api } from '../../lib/apiClient';

export function ImportPage() {
  const [preview, setPreview] = useState<ImportPreviewResult | null>(null);
  const [result, setResult] = useState<ImportCommitResult | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleFileChange(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;

    setLoading(true);
    setResult(null);
    try {
      const form = new FormData();
      form.append('file', file);
      const res = await api.previewImport(form);
      setPreview(res);
    } finally {
      setLoading(false);
      e.target.value = '';
    }
  }

  async function handleCommit() {
    if (!preview) return;
    setLoading(true);
    try {
      const validRows = preview.rows.filter((r) => r.valid).map((r) => r.data);
      const res = await api.commitImport(validRows);
      setResult(res);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <h1>Data import</h1>
      <p className="muted">
        Upload a CSV of existing employee data (from a spreadsheet). Expected columns: first_name, last_name,
        personal_email, employee_number, hire_date, employment_type, department_code, location_code, position_title,
        pay_type, rate_amount, pay_frequency.
      </p>

      <div className="card">
        <input type="file" accept=".csv" disabled={loading} onChange={handleFileChange} />
      </div>

      {preview && (
        <div className="card">
          <p>
            {preview.valid_count} valid, {preview.error_count} with errors.
          </p>
          <table className="data-table">
            <thead>
              <tr>
                <th>Row</th>
                <th>Name</th>
                <th>Employee #</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {preview.rows.map((row, i) => (
                <tr key={i}>
                  <td>{i + 1}</td>
                  <td>
                    {row.data.first_name} {row.data.last_name}
                  </td>
                  <td>{row.data.employee_number}</td>
                  <td>
                    {row.valid ? (
                      <span className="badge badge-approved">valid</span>
                    ) : (
                      <span className="error-text">{row.errors.join('; ')}</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <button disabled={loading || preview.valid_count === 0} onClick={handleCommit}>
            {loading ? 'Importing…' : `Import ${preview.valid_count} valid row(s)`}
          </button>
        </div>
      )}

      {result && (
        <div className="card">
          <p>
            {result.created.length} employee(s) created. {result.failed.length} failed.
          </p>
        </div>
      )}
    </div>
  );
}
