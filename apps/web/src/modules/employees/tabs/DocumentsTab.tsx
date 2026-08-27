import { ChangeEvent, useEffect, useState } from 'react';
import type { DocumentCategory, DocumentRecord } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function DocumentsTab({ employmentId }: { employmentId: number }) {
  const [documents, setDocuments] = useState<DocumentRecord[]>([]);
  const [categories, setCategories] = useState<DocumentCategory[]>([]);
  const [categoryId, setCategoryId] = useState('');
  const [title, setTitle] = useState('');
  const [uploading, setUploading] = useState(false);
  const [signedIds, setSignedIds] = useState<number[]>([]);
  const [signingId, setSigningId] = useState<number | null>(null);

  function reload() {
    api.listDocuments(employmentId).then((res) => setDocuments(res.data));
  }

  useEffect(() => {
    reload();
    api.listDocumentCategories().then(setCategories);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [employmentId]);

  async function handleFileChange(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file || !categoryId || !title) return;

    setUploading(true);
    try {
      const form = new FormData();
      form.append('category_id', categoryId);
      form.append('title', title);
      form.append('file', file);
      await api.uploadDocument(employmentId, form);
      setTitle('');
      reload();
    } finally {
      setUploading(false);
      e.target.value = '';
    }
  }

  async function sign(doc: DocumentRecord) {
    setSigningId(doc.id);
    try {
      await api.acknowledgeDocument(employmentId, doc.id, {
        signature_type: 'typed',
        signature_data: 'Signed via HRIS',
      });
      setSignedIds((prev) => [...prev, doc.id]);
    } finally {
      setSigningId(null);
    }
  }

  return (
    <div>
      <p className="notice small">
        Signatures collected here use a placeholder e-signature flow and are <strong>not legally binding</strong>. For I-9 forms
        specifically, use the existing paper process — do not rely on the in-system "Sign" button for I-9.
      </p>

      <div className="card form-grid">
        <label>
          Category
          <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
            <option value="">Select…</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </label>
        <label>
          Title
          <input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Signed I-9" />
        </label>
        <label>
          File
          <input type="file" disabled={!categoryId || !title || uploading} onChange={handleFileChange} />
        </label>
      </div>

      <table className="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Version</th>
            <th>Uploaded</th>
            <th>Signature</th>
          </tr>
        </thead>
        <tbody>
          {documents.map((d) => (
            <tr key={d.id}>
              <td>{d.title}</td>
              <td>{d.category}</td>
              <td>v{d.current_version?.version_number ?? '—'}</td>
              <td>{d.current_version ? new Date(d.current_version.uploaded_at).toLocaleDateString() : '—'}</td>
              <td>
                {d.category === 'I-9' ? (
                  <span className="muted small">use paper process</span>
                ) : !d.requires_signature ? (
                  <span className="muted small">n/a</span>
                ) : signedIds.includes(d.id) ? (
                  <span className="badge badge-approved">signed</span>
                ) : (
                  <button className="small" disabled={signingId === d.id} onClick={() => sign(d)}>
                    {signingId === d.id ? 'Signing…' : 'Sign'}
                  </button>
                )}
              </td>
            </tr>
          ))}
          {documents.length === 0 && (
            <tr>
              <td colSpan={5} className="muted">
                No documents uploaded yet.
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
