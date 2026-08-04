import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import type { Employment } from '@hris/shared-types';
import { api } from '../../lib/apiClient';
import { RequireRole } from '../../lib/RequireRole';
import { TimelineTab } from './tabs/TimelineTab';
import { CompensationTab } from './tabs/CompensationTab';
import { DocumentsTab } from './tabs/DocumentsTab';
import { TransferForm } from './tabs/TransferForm';
import { TerminateForm } from './tabs/TerminateForm';
import { RehireForm } from './tabs/RehireForm';
import { OnboardingTab } from './tabs/OnboardingTab';
import { OffboardingTab } from './tabs/OffboardingTab';

type Tab = 'overview' | 'timeline' | 'compensation' | 'documents' | 'onboarding' | 'offboarding';

export function EmployeeDetailPage() {
  const { id } = useParams();
  const employmentId = Number(id);
  const [employment, setEmployment] = useState<Employment | null>(null);
  const [tab, setTab] = useState<Tab>('overview');
  // Guards against an older in-flight GET resolving after a newer one and
  // clobbering it with stale data (e.g. mount-load vs. post-transfer reload).
  const requestSeq = useRef(0);

  function reload() {
    const requestId = ++requestSeq.current;
    api.getEmployee(employmentId).then((data) => {
      if (requestId === requestSeq.current) setEmployment(data);
    });
  }

  useEffect(() => {
    reload();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [employmentId]);

  if (!employment) return <p>Loading…</p>;

  const isTerminated = employment.employment_status === 'terminated';
  const tabs: Tab[] = isTerminated
    ? ['overview', 'timeline', 'compensation', 'documents', 'offboarding']
    : ['overview', 'timeline', 'compensation', 'documents', 'onboarding'];

  return (
    <div>
      <div className="page-header">
        <h1>
          {employment.person.first_name} {employment.person.last_name}
        </h1>
        <span className={`badge badge-${employment.employment_status}`}>{employment.employment_status}</span>
      </div>

      <div className="tabs">
        {tabs.map((t) => (
          <button key={t} className={tab === t ? 'tab active' : 'tab'} onClick={() => setTab(t)}>
            {t[0].toUpperCase() + t.slice(1)}
          </button>
        ))}
      </div>

      {tab === 'overview' && (
        <div className="card">
          <dl className="detail-list">
            <dt>Employee #</dt>
            <dd>{employment.employee_number}</dd>
            <dt>Hire date</dt>
            <dd>{employment.hire_date}</dd>
            <dt>Employment type</dt>
            <dd>{employment.employment_type}</dd>
            <dt>Department</dt>
            <dd>{employment.current_assignment?.department ?? '—'}</dd>
            <dt>Location</dt>
            <dd>{employment.current_assignment?.location ?? '—'}</dd>
            <dt>Position</dt>
            <dd>{employment.current_assignment?.position ?? '—'}</dd>
            <dt>Current pay</dt>
            <dd>
              {employment.current_compensation
                ? `${employment.current_compensation.rate_amount} (${employment.current_compensation.pay_type}, ${employment.current_compensation.pay_frequency})`
                : '—'}
            </dd>
            {employment.termination_date && (
              <>
                <dt>Termination date</dt>
                <dd>{employment.termination_date}</dd>
              </>
            )}
          </dl>

          <RequireRole roles={['admin', 'hr_manager']}>
            {!isTerminated ? (
              <div className="button-row">
                <TransferForm employmentId={employment.id} onDone={reload} />
                <TerminateForm employmentId={employment.id} onDone={reload} />
              </div>
            ) : (
              <RehireForm personId={employment.person.id} defaultEmploymentType={employment.employment_type} />
            )}
          </RequireRole>
        </div>
      )}

      {tab === 'timeline' && <TimelineTab personId={employment.person.id} />}
      {tab === 'compensation' && <CompensationTab employmentId={employment.id} onChanged={reload} />}
      {tab === 'documents' && <DocumentsTab employmentId={employment.id} />}
      {tab === 'onboarding' && <OnboardingTab employmentId={employment.id} />}
      {tab === 'offboarding' && <OffboardingTab employmentId={employment.id} />}
    </div>
  );
}
