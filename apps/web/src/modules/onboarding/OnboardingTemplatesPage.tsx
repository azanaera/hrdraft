import { useEffect, useState } from 'react';

interface TemplateTask {
  id: number;
  title: string;
  task_type: string;
  is_required: boolean;
}

interface Template {
  id: number;
  name: string;
  applicable_employment_type: string | null;
  tasks: TemplateTask[];
}

import { api } from '../../lib/apiClient';

export function OnboardingTemplatesPage() {
  const [templates, setTemplates] = useState<Template[]>([]);

  useEffect(() => {
    api.listOnboardingTemplates().then((data) => setTemplates(data as Template[]));
  }, []);

  return (
    <div>
      <h1>Onboarding templates</h1>
      <p className="muted">
        Start a workflow for a specific employee from their profile once onboarding is wired into the hire flow.
      </p>

      {templates.map((t) => (
        <div className="card" key={t.id}>
          <h3>
            {t.name} {t.applicable_employment_type && <span className="muted small">({t.applicable_employment_type})</span>}
          </h3>
          <ol>
            {t.tasks.map((task) => (
              <li key={task.id}>
                {task.title} <span className="muted small">— {task.task_type.replace(/_/g, ' ')}</span>
              </li>
            ))}
          </ol>
        </div>
      ))}
      {templates.length === 0 && <p className="muted">No onboarding templates yet.</p>}
    </div>
  );
}
