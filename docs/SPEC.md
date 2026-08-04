# HRIS — Product & Technical Spec

Derived from a structured stakeholder interview conducted after the initial draft MVP was built and verified running. This captures decisions, tradeoffs, and open questions that go beyond what's visible in the code — the "why" behind where this system needs to go next, not a restatement of [README.md](../README.md).

Status: draft interview notes → spec. Nothing here is implemented yet unless explicitly noted as "already built."

---

## 1. Where this is actually headed

The single most consequential finding of this interview: **this system is meant to become a real, from-scratch payroll system** — not just a rate/hours tracker feeding an external payroll provider. That's a materially bigger build than what "compensation management" originally implied, and it reframes several other answers (banking data handling, backup/DR expectations, wage compliance).

**Immediate milestone**: a working demo/proof-of-concept for stakeholders, no fixed date. This means near-term priorities should favor a compelling, coherent walkthrough over raw feature breadth — see [§7](#7-near-term-demo-priorities).

---

## 2. Confirmed scope decisions

### 2.1 Payroll (major scope expansion)
- **Decision**: the system should eventually calculate actual paychecks — gross-to-net, tax withholding, direct deposit — not just hold pay rates as a reference for an external payroll provider.
- **Why it matters**: this is a full payroll engine (tax tables by jurisdiction, withholding calculations, pay stub generation, tax form generation like W-2/1099), which is an order of magnitude more work than the `compensation_records` effective-dating already built. Treat it as its own major phase, not an extension of the existing Compensation module.
- **Banking data**: direct deposit account/routing numbers must **not** be stored raw, even encrypted. Use a tokenizing banking/payments API (Plaid, Stripe Treasury, or an embedded payroll provider like Check/Gusto Embedded) — the app should only ever hold a token, never the underlying account number.
- **Wage compliance**: multi-state minimum wage and overtime-eligibility rules matter now or soon. Rules should key off **location** (the `locations` table already has a `state` column — extend it with a minimum-wage/overtime-rule lookup) rather than position or a global setting.

### 2.2 Termination & offboarding (currently unbuilt beyond a status flag)
All four of the following are in scope, roughly in priority order:
1. **Immediate access revocation** — the single highest-priority gap. A terminated employee's `users` row must stop authenticating the moment `employment_status` flips to `terminated`. Today nothing enforces this — Sanctum tokens/sessions stay valid indefinitely.
2. **Offboarding checklist/workflow** — mirror the existing `OnboardingWorkflow`/`OnboardingTask` pattern in reverse: equipment return, badge/key collection, exit interview scheduling.
3. **Final paycheck / payout calculation** — trigger calculation of final pay including payout-eligible unused time-off balance, using the existing `time_off_ledger_entries` as the source of truth.
4. **Downstream notifications** — notify IT/payroll/manager when a termination is recorded. This is an events/listener concern (extend `TimelineRecorder`'s pattern) more than a new data model.

### 2.3 Hiring & compliance integrations
- **Background checks and E-Verify (I-9)** both matter and should eventually integrate into the onboarding/hire flow (e.g., Checkr for background checks, the federal E-Verify API). Not built yet — flag as a real gap given the stated high-volume hourly hiring pattern.
- **E-signatures must be legally binding** (ESIGN Act-compliant) for offer letters, I-9s, and handbook acknowledgments. The current `document_acknowledgments` table (typed name / checkbox + IP + timestamp) is **not sufficient** for anything that matters legally — needs a real e-signature provider (DocuSign, HelloSign/Dropbox Sign) integrated into the Documents module, replacing or supplementing the current self-rolled acknowledgment flow.
- **ATS stays in-house.** No near-term third-party ATS swap is planned, despite the deliberately decoupled `HireCandidateService` architecture. Keep investing in the in-house requisition/pipeline/candidate UI — the decoupling is insurance, not an active migration plan.

### 2.4 Operational / workflow patterns
- **Approvals stay single-level.** No dollar-threshold or multi-step escalation chains needed for time-off or compensation changes — the current "any manager or HR/admin can approve" model matches how this business actually operates. Don't over-build approval chains.
- **Bulk transfer/update is a real need**, not built at all today (current UI is one-employee-at-a-time). Given frequent field transfers, HR/ops needs to move groups of employees between locations at once (e.g., a shift consolidation or location closure).
- **Notifications**: email + in-app only. No SMS or mobile push needed for now, despite a mobile app already existing — don't build notification infrastructure beyond what email + in-app requires.
- **English only.** No i18n/multi-language requirement identified for the hourly/field workforce at this time.
- **Mobile offline support is not needed.** Requiring a live connection is an acceptable constraint — don't build offline caching or request queuing for the mobile app.
- **Never multi-tenant.** This is being built for exactly one company, permanently. Don't design for tenant isolation, per-company data partitioning, or a "which company" concept anywhere.

### 2.5 Administration & roles
- **A non-technical HR/ops person will administer this day-to-day** — creating locations/departments, managing onboarding templates, configuring time-off policies. All of that currently only exists via seeders/code. **An admin UI for these is a real near-term need**, not a nice-to-have, once anyone other than a developer needs to configure the system.
- **The 4-role model (Admin, HR Manager, People Manager, Employee) is sufficient for now**, but will need to evolve toward granular, module-level permissions as the HR/ops team grows (e.g., a recruiter who should see ATS but not compensation; a payroll specialist who should see pay data but not employee notes). Don't build fine-grained permissions yet, but don't design anything that makes that evolution hard later — the existing Policy-per-domain structure (`app/Policies/EmployeePolicy.php` etc.) already gives a reasonable seam for this.

### 2.6 Reporting & search
- **Turnover/retention reporting is a real near-term need**, directly tied to the business's stated high-turnover pain point. The data already captured (`employment_status`, `termination_reason`, hire/rehire history via multiple `employments` rows per `person`) supports this — what's missing is a reporting UI/dashboard, not new data model work.
- **Employee search/filtering is a real near-term gap.** At the expected scale (see §3), the current bare paginated table won't hold up. People need to find employees by: name/employee number, department/location, employment status, and manager/reporting line — all four matter, not just name search.

---

## 3. Scale planning

Expected size: **500–5,000 employees** (active + former — rehires mean historical `employments` rows accumulate) within 1–2 years.

Implications for what's already built:
- Current in-app pagination and unindexed-feeling query patterns are workable at this scale but should be watched, not ignored.
- The `employee_events` timeline table is unbounded and never pruned — **retention/archiving policy is an explicit open question** (see §5). At 500–5,000 employees generating events continuously from six modules, this table will grow fast even before hitting an archiving decision point.
- Search/filtering (§2.6) becomes necessary, not optional, well before the top of this range.

---

## 4. Compliance & data governance

| Topic | Decision | Notes |
|---|---|---|
| SSN storage | App-level encryption (Laravel's `encrypted` cast) is acceptable for now | Revisit KMS-backed field encryption if a compliance mandate (SOC2, an enterprise customer contract) forces the issue. Already implemented. |
| Banking/direct deposit data | Must be tokenized via a third-party banking API, never stored raw | See §2.1. Not implemented — payroll doesn't exist yet. |
| EEO-1 reporting | **Unknown** whether the company is at/near the 100-employee threshold that legally requires this | Needs a headcount check, not a design decision — flag as a to-verify item before assuming it's out of scope. |
| Accessibility (WCAG) | **Unknown** — no requirement identified, but not confirmed absent either | Worth checking with legal/compliance rather than assuming either way, especially for an employee-facing system with ADA exposure. |
| Backup / disaster recovery | **Near-zero data loss tolerance** required | Once real payroll/banking-adjacent data exists, nightly backups are not sufficient — plan for continuous WAL archiving / point-in-time recovery (e.g., Postgres PITR, or a managed service like RDS with PITR enabled) *before* that data becomes real, not as a retrofit. |
| Timeline (`employee_events`) retention | **Open question** — not yet decided whether to keep forever or archive after N years | Flagged, not resolved. Revisit once real usage data exists. |

---

## 5. Data migration

There **is** existing data to migrate — currently living in **spreadsheets** (Excel/CSV/Google Sheets), not a legacy HRIS or ATS.

Implications:
- This is a one-time import project, not an ongoing sync — but spreadsheet data is typically messy/inconsistent, so budget for a proper mapping + validation import tool, not a naive CSV loader.
- Needs careful mapping onto the `people` → `employments` → `assignments` model, especially for anyone with employment gaps or multiple stints (rehires) that a flat spreadsheet likely doesn't represent cleanly.
- Should happen *before* go-live, not be treated as an afterthought once the system is otherwise "done."

---

## 6. Identity & authentication

- **SSO is undecided.** No corporate identity provider (Okta, Azure AD, Google Workspace) is confirmed in use, and whether one gets adopted is genuinely open. **Design implication**: keep the auth layer pluggable enough to add a SAML/OIDC guard alongside the existing Sanctum session/token auth later, without a rewrite. Don't build SSO now, but don't paint the auth layer into a corner either.

---

## 7. Near-term (demo) priorities

Given the immediate goal is a stakeholder demo/POC with no fixed date:

1. **Build a real dashboard/landing view.** Currently, login drops straight into a raw employee table — confirmed as insufficient for a stakeholder-facing first impression. The dashboard should surface headcount, open requisitions, pending time-off approvals, and recent hires — all data that already exists, just not aggregated/visualized anywhere yet.
2. **Lightweight placeholder branding pass.** Not waiting on real brand assets — apply a plausible placeholder company identity (name, logo, color palette beyond the current generic blue) so the demo doesn't read as an obviously unbranded prototype. Swap in real brand assets later.
3. **No fixed deadline** — sequence remaining work by priority (this document) rather than against a date.

Given the demo framing, the highest-leverage additions for a walkthrough are probably (in rough order): the dashboard (#1 above), employee search/filtering (§2.6 — makes the employee list itself demo-worthy at any realistic headcount), and a clean walk-through of the full ATS hire flow end-to-end in the browser (noted as not yet manually verified in [README.md](../README.md)).

---

## 8. Explicit open questions (not resolved by this interview)

These were surfaced but deliberately left open — don't guess at them, revisit with the right person:

- **Timeline/event retention policy** — keep forever vs. archive after N years.
- **Company headcount relative to the EEO-1 100-employee threshold** — determines whether demographic-data capture needs to be designed for.
- **Accessibility/WCAG compliance requirement** — needs a check with legal/compliance, not an assumption.
- **SSO / identity provider adoption** — no current IdP, unknown whether one gets adopted.

---

## 9. Explicitly out of scope / deprioritized

Captured so these don't get accidentally re-litigated or built speculatively:

- Multi-tenancy (never needed — single company, permanently).
- Mobile offline support (requiring a live connection is acceptable).
- Multi-language / i18n (English-only workforce need identified).
- Multi-level/threshold-based approval chains (single-level matches actual operations).
- SMS and push notifications (email + in-app is sufficient).
- Third-party ATS integration/swap (staying in-house).

---

## 10. Suggested sequencing for what's next

Roughly in priority order, synthesizing the above (not a committed roadmap — a recommendation):

1. **Access revocation on termination** — highest-severity gap (§2.2.1), small in scope, real security exposure today.
2. **Dashboard/landing view + employee search/filtering** — directly serves the near-term demo goal (§7) and the scale reality (§3).
3. **Offboarding workflow** (checklist, final pay calc, notifications) — completes the employment lifecycle the system otherwise fully models.
4. **Admin UI for locations/departments/onboarding templates/time-off policies** — unblocks the non-technical administrator (§2.5) from depending on a developer for basic configuration.
5. **Data migration tooling** — needs to exist before any real go-live, independent of feature work (§5).
6. **Turnover/retention reporting** — high business value, low technical risk (data already captured).
7. **E-signature provider integration** — required before onboarding documents can be relied upon legally (§2.3).
8. **Bulk transfer/update UI** — real operational need, moderate scope.
9. **Payroll (gross-to-net, tax withholding, tokenized direct deposit)** — the largest single item by far (§2.1); treat as its own phase with dedicated scoping, not an incremental extension of Compensation.
10. **Background check / E-Verify integration**, **wage-compliance validation by location**, **role-permission granularity** — each real, each not urgent enough to block the above.
