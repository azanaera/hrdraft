import { useState } from 'react';
import { Link } from 'react-router-dom';
import { LocationsSection } from './sections/LocationsSection';
import { DepartmentsSection } from './sections/DepartmentsSection';
import { PositionsSection } from './sections/PositionsSection';
import { TimeOffPoliciesSection } from './sections/TimeOffPoliciesSection';
import { DocumentCategoriesSection } from './sections/DocumentCategoriesSection';
import { OnboardingTemplatesSection } from './sections/OnboardingTemplatesSection';

type Section = 'locations' | 'departments' | 'positions' | 'time-off-policies' | 'document-categories' | 'onboarding-templates';

const SECTIONS: { key: Section; label: string }[] = [
  { key: 'locations', label: 'Locations' },
  { key: 'departments', label: 'Departments' },
  { key: 'positions', label: 'Positions' },
  { key: 'time-off-policies', label: 'Time Off Policies' },
  { key: 'document-categories', label: 'Document Categories' },
  { key: 'onboarding-templates', label: 'Onboarding Templates' },
];

export function AdminPage() {
  const [section, setSection] = useState<Section>('locations');

  return (
    <div>
      <div className="page-header">
        <h1>Admin</h1>
        <Link className="button" to="/admin/import">
          Data import
        </Link>
      </div>

      <div className="tabs">
        {SECTIONS.map((s) => (
          <button key={s.key} className={section === s.key ? 'tab active' : 'tab'} onClick={() => setSection(s.key)}>
            {s.label}
          </button>
        ))}
      </div>

      {section === 'locations' && <LocationsSection />}
      {section === 'departments' && <DepartmentsSection />}
      {section === 'positions' && <PositionsSection />}
      {section === 'time-off-policies' && <TimeOffPoliciesSection />}
      {section === 'document-categories' && <DocumentCategoriesSection />}
      {section === 'onboarding-templates' && <OnboardingTemplatesSection />}
    </div>
  );
}
