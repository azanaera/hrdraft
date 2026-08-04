import { FormEvent, useEffect, useState } from 'react';
import { api } from '../../../lib/apiClient';

interface TemplateTask {
  id: number;
  title: string;
  task_type: string;
}

interface Template {
  id: number;
  name: string;
  tasks: TemplateTask[];
}

export function OnboardingTemplatesSection() {
  const [templates, setTemplates] = useState<Template[]>([]);
  const [form, setForm] = useState({ name: '', applicable_employment_type: '' });
  const [taskForms, setTaskForms] = useState<Record<number, { title: string; task_type: string }>>({});
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listAdminOnboardingTemplates().then((data) => setTemplates(data as Template[]));
  }

  useEffect(reload, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.createAdminOnboardingTemplate({
        name: form.name,
        applicable_employment_type: form.applicable_employment_type || null,
      });
      setForm({ name: '', applicable_employment_type: '' });
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  async function addTask(templateId: number) {
    const task = taskForms[templateId];
    if (!task?.title) return;
    await api.addAdminOnboardingTemplateTask(templateId, { ...task, task_type: task.task_type || 'generic' });
    setTaskForms((f) => ({ ...f, [templateId]: { title: '', task_type: 'generic' } }));
    reload();
  }

  return (
    <div>
      <form className="card form-grid" onSubmit={handleSubmit}>
        <label>
          Name
          <input required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
        </label>
        <label>
          Applicable employment type
          <select
            value={form.applicable_employment_type}
            onChange={(e) => setForm((f) => ({ ...f, applicable_employment_type: e.target.value }))}
          >
            <option value="">Any</option>
            <option value="hourly">Hourly</option>
            <option value="salaried">Salaried</option>
          </select>
        </label>
        <button className="form-span" type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add template'}
        </button>
      </form>

      {templates.map((t) => (
        <div className="card" key={t.id}>
          <h3>{t.name}</h3>
          <ol>
            {t.tasks.map((task) => (
              <li key={task.id}>
                {task.title} <span className="muted small">— {task.task_type}</span>
              </li>
            ))}
          </ol>
          <div className="form-grid">
            <input
              placeholder="New task title"
              value={taskForms[t.id]?.title ?? ''}
              onChange={(e) => setTaskForms((f) => ({ ...f, [t.id]: { title: e.target.value, task_type: f[t.id]?.task_type ?? 'generic' } }))}
            />
            <select
              value={taskForms[t.id]?.task_type ?? 'generic'}
              onChange={(e) => setTaskForms((f) => ({ ...f, [t.id]: { title: f[t.id]?.title ?? '', task_type: e.target.value } }))}
            >
              <option value="form">Form</option>
              <option value="document_upload">Document upload</option>
              <option value="document_ack">Document acknowledgment</option>
              <option value="provisioning">Provisioning</option>
              <option value="generic">Generic</option>
            </select>
            <button type="button" onClick={() => addTask(t.id)}>
              Add task
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
