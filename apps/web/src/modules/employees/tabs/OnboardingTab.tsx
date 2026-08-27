import { useEffect, useState } from 'react';
import type { BackgroundCheckStatus, OnboardingWorkflow } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function OnboardingTab({ employmentId }: { employmentId: number }) {
  const [workflow, setWorkflow] = useState<OnboardingWorkflow | null>(null);
  const [checks, setChecks] = useState<BackgroundCheckStatus[]>([]);
  const [loading, setLoading] = useState(true);

  function reload() {
    Promise.all([api.getOnboardingWorkflow(employmentId), api.getBackgroundChecks(employmentId)])
      .then(([w, c]) => {
        setWorkflow(w);
        setChecks(c);
      })
      .finally(() => setLoading(false));
  }

  useEffect(reload, [employmentId]);

  async function completeTask(taskId: number) {
    await api.completeOnboardingTask(taskId);
    reload();
  }

  if (loading) return <p>Loading…</p>;
  if (!workflow) return <p className="muted">No onboarding workflow for this employee.</p>;

  return (
    <div>
      <div className="card">
        <div className="page-header">
          <h3>{workflow.template}</h3>
          <span className={`badge badge-${workflow.status}`}>{workflow.status.replace('_', ' ')}</span>
        </div>
        <ul>
          {workflow.tasks.map((task) => (
            <li key={task.id}>
              <label>
                <input type="checkbox" checked={task.status === 'completed'} disabled={task.status === 'completed'} onChange={() => completeTask(task.id)} />{' '}
                {task.title} <span className="muted small">({task.task_type.replace(/_/g, ' ')})</span>
              </label>
            </li>
          ))}
        </ul>
      </div>

      <div className="card">
        <h3>Background &amp; eligibility checks</h3>
        <p className="notice small">
          These statuses come from a placeholder provider and are <strong>not authoritative</strong>. HR must still run the real
          background check and E-Verify process outside this system until a real provider is connected.
        </p>
        {checks.map((c) => (
          <div key={c.check_type} className="row">
            <span>{c.check_type === 'e_verify' ? 'E-Verify (I-9)' : 'Background check'}</span>
            <span className={`badge badge-${c.status === 'clear' ? 'active' : c.status === 'flagged' ? 'terminated' : 'pending'}`}>
              {c.status}
            </span>
          </div>
        ))}
        {checks.length === 0 && <p className="muted">No checks run yet.</p>}
      </div>
    </div>
  );
}
