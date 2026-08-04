import { useEffect, useState } from 'react';
import type { OffboardingWorkflow } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function OffboardingTab({ employmentId }: { employmentId: number }) {
  const [workflow, setWorkflow] = useState<OffboardingWorkflow | null>(null);
  const [loading, setLoading] = useState(true);

  function reload() {
    api
      .getOffboardingWorkflow(employmentId)
      .then(setWorkflow)
      .finally(() => setLoading(false));
  }

  useEffect(reload, [employmentId]);

  async function completeTask(taskId: number) {
    await api.completeOffboardingTask(taskId);
    reload();
  }

  if (loading) return <p>Loading…</p>;
  if (!workflow) return <p className="muted">No offboarding workflow — this employee hasn't been terminated.</p>;

  return (
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
      <p className="muted small">
        The final payout figure (unused time off at current rate) is calculated automatically when offboarding starts — see the
        employee's Timeline tab for the amount.
      </p>
    </div>
  );
}
