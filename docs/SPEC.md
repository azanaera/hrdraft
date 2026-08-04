# HRIS — Product & Technical Spec

Derived from a structured stakeholder interview conducted after the initial draft MVP was built and verified running. This captures decisions, tradeoffs, and open questions that go beyond what's visible in the code — the "why" behind where this system needs to go next, not a restatement of [README.md](../README.md).

Status: the build sequence recommended in [§10](#10-suggested-sequencing-for-whats-next) has been implemented — see ✅ markers throughout for what's done vs. still open. Payroll gross-to-net (§2.1), SSO (§6), and the items in [§8](#8-explicit-open-questions-not-resolved-by-this-interview)/[§9](#9-explicitly-out-of-scope--deprioritized) remain as described: not built, by design. See [docs/TEST_PLAN.md](TEST_PLAN.md) for how everything below is tested.

---

## 1. Where this is actually headed

The single most consequential finding of this interview: **this system is meant to become a real, from-scratch payroll system** — not just a rate/hours tracker feeding an external payroll provider. That's a materially bigger build than what "compensation management" originally implied, and it reframes several other answers (banking data handling, backup/DR expectations, wage compliance).

**Immediate milestone**: a working demo/proof-of-concept for stakeholders, no fixed date. This means near-term priorities should favor a compelling, coherent walkthrough over raw feature breadth — see [§7](#7-near-term-demo-priorities).

---

## 2. Confirmed scope decisions

### 2.1 Payroll (major scope expansion)
- **Decision**: the system should eventually calculate actual paychecks — gross-to-net, tax withholding, direct deposit — not just hold pay rates as a reference for an external payroll provider.
- **Why it matters**: this is a full payroll engine (tax tables by jurisdiction, withholding calculations, pay stub generation, tax form generation like W-2/1099), which is an order of magnitude more work than the `compensation_records` effective-dating already built. Treat it as its own major phase, not an extension of the existing Compensation module.
- **Status: not built.** This remains the single largest deferred item — deliberately out of scope for this phase, per direct confirmation.
- **Banking data**: direct deposit account/routing numbers must **not** be stored raw, even encrypted. Use a tokenizing banking/payments API (Plaid, Stripe Treasury, or an embedded payroll provider like Check/Gusto Embedded) — the app should only ever hold a token, never the underlying account number. ✅ **Built** — `App\Domain\Compensation\Services\BankingProviderInterface` + `FakeBankingProvider`, `employment_banking_info` table has no raw account/routing columns even in the fake path. Swapping in Plaid/Stripe is a single binding change in `AppServiceProvider`.
- **Wage compliance**: multi-state minimum wage and overtime-eligibility rules matter now or soon. Rules should key off **location** (the `locations` table already has a `state` column — extend it with a minimum-wage/overtime-rule lookup) rather than position or a global setting. ✅ **Built** — `locations.minimum_wage`, validated in `CompensationService::applyChange()`. Overtime-eligibility rules are not yet modeled (only minimum wage).

### 2.2 Termination & offboarding — ✅ Built
All four of the following are in scope, roughly in priority order:
1. **Immediate access revocation** ✅ — `EnsureEmploymentActive` middleware + `TerminationService` deletes Sanctum tokens/invalidates the session the moment `employment_status` flips to `terminated`.
2. **Offboarding checklist/workflow** ✅ — `App\Domain\Offboarding` mirrors the `OnboardingWorkflow`/`OnboardingTask` pattern.
3. **Final paycheck / payout calculation** ✅ — `FinalPayoutService::calculate()` sums unused time-off balance at the current rate, recorded on the timeline. Note: this is a calculated *figure* for HR to act on, not an actual payment — real disbursement depends on payroll (§2.1, still deferred).
4. **Downstream notifications** ✅ — `App\Domain\Notifications` (in-app + log-driver email) fires on termination.

### 2.3 Hiring & compliance integrations
- **Background checks and E-Verify (I-9)** both matter and should eventually integrate into the onboarding/hire flow (e.g., Checkr for background checks, the federal E-Verify API). ✅ **Built** — `BackgroundCheckProviderInterface` + `FakeBackgroundCheckProvider`, both check types auto-run when onboarding starts. Swapping in Checkr/the real E-Verify API is a single binding change.
- **E-signatures must be legally binding** (ESIGN Act-compliant) for offer letters, I-9s, and handbook acknowledgments. The current `document_acknowledgments` table (typed name / checkbox + IP + timestamp) is **not sufficient** for anything that matters legally — needs a real e-signature provider (DocuSign, HelloSign/Dropbox Sign) integrated into the Documents module, replacing or supplementing the current self-rolled acknowledgment flow. ✅ **Workflow built** — `SignatureProviderInterface` + `FakeSignatureProvider` now route every signature-required acknowledgment through a provider. **Still not legally binding**: the fake provider is for local testing only — swapping in a real DocuSign/Dropbox Sign binding is required before any of this can be relied on legally.
- **ATS stays in-house.** No near-term third-party ATS swap is planned, despite the deliberately decoupled `HireCandidateService` architecture. Keep investing in the in-house requisition/pipeline/candidate UI — the decoupling is insurance, not an active migration plan. Unchanged.

### 2.4 Operational / workflow patterns
- **Approvals stay single-level.** No dollar-threshold or multi-step escalation chains needed for time-off or compensation changes — the current "any manager or HR/admin can approve" model matches how this business actually operates. Don't over-build approval chains. Unchanged — still single-level.
- **Bulk transfer/update is a real need**, not built at all today (current UI is one-employee-at-a-time). Given frequent field transfers, HR/ops needs to move groups of employees between locations at once (e.g., a shift consolidation or location closure). ✅ **Built** — `BulkTransferService` + bulk-select UI on the employee list.
- **Notifications**: email + in-app only. No SMS or mobile push needed for now, despite a mobile app already existing — don't build notification infrastructure beyond what email + in-app requires. ✅ **Built**, matching this scope exactly (no SMS/push).
- **English only.** No i18n/multi-language requirement identified for the hourly/field workforce at this time. Unchanged — no i18n built.
- **Mobile offline support is not needed.** Requiring a live connection is an acceptable constraint — don't build offline caching or request queuing for the mobile app. Unchanged.
- **Never multi-tenant.** This is being built for exactly one company, permanently. Don't design for tenant isolation, per-company data partitioning, or a "which company" concept anywhere. Unchanged.

### 2.5 Administration & roles
- **A non-technical HR/ops person will administer this day-to-day** — creating locations/departments, managing onboarding templates, configuring time-off policies. All of that currently only exists via seeders/code. **An admin UI for these is a real near-term need**, not a nice-to-have, once anyone other than a developer needs to configure the system. ✅ **Built** — admin CRUD for locations/departments/positions/time-off policies/onboarding templates/document categories, plus a CSV data-import UI (§5).
- **The 4-role model (Admin, HR Manager, People Manager, Employee) is sufficient for now**, but will need to evolve toward granular, module-level permissions as the HR/ops team grows (e.g., a recruiter who should see ATS but not compensation; a payroll specialist who should see pay data but not employee notes). Don't build fine-grained permissions yet, but don't design anything that makes that evolution hard later — the existing Policy-per-domain structure (`app/Policies/EmployeePolicy.php` etc.) already gives a reasonable seam for this. Still 4 roles — granular permissions remain future work (§10).

### 2.6 Reporting & search
- **Turnover/retention reporting is a real near-term need**, directly tied to the business's stated high-turnover pain point. The data already captured (`employment_status`, `termination_reason`, hire/rehire history via multiple `employments` rows per `person`) supports this — what's missing is a reporting UI/dashboard, not new data model work. ✅ **Built** — `TurnoverReportService` + `TurnoverReportPage`, derived entirely from existing data as predicted.
- **Employee search/filtering is a real near-term gap.** At the expected scale (see §3), the current bare paginated table won't hold up. People need to find employees by: name/employee number, department/location, employment status, and manager/reporting line — all four matter, not just name search. ✅ **Built** — all four filters on the employee list.

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
| Banking/direct deposit data | Must be tokenized via a third-party banking API, never stored raw | See §2.1. ✅ Tokenization pattern built (fake provider) — real bank API integration still pending, since it's only needed once payroll (still deferred) exists. |
| EEO-1 reporting | **Unknown** whether the company is at/near the 100-employee threshold that legally requires this | Needs a headcount check, not a design decision — flag as a to-verify item before assuming it's out of scope. |
| Accessibility (WCAG) | **Unknown** — no requirement identified, but not confirmed absent either | Worth checking with legal/compliance rather than assuming either way, especially for an employee-facing system with ADA exposure. |
| Backup / disaster recovery | **Near-zero data loss tolerance** required | Once real payroll/banking-adjacent data exists, nightly backups are not sufficient — plan for continuous WAL archiving / point-in-time recovery (e.g., Postgres PITR, or a managed service like RDS with PITR enabled) *before* that data becomes real, not as a retrofit. |
| Timeline (`employee_events`) retention | **Open question** — not yet decided whether to keep forever or archive after N years | Flagged, not resolved. Revisit once real usage data exists. |

---

## 5. Data migration — ✅ Built

There **is** existing data to migrate — currently living in **spreadsheets** (Excel/CSV/Google Sheets), not a legacy HRIS or ATS.

Implications:
- This is a one-time import project, not an ongoing sync — but spreadsheet data is typically messy/inconsistent, so budget for a proper mapping + validation import tool, not a naive CSV loader. ✅ `SpreadsheetImportService` does preview (row-level validation, nothing written) then commit (per-row, so one bad row doesn't abort the batch) — plus a `hris:import` Artisan command for the one-time real migration outside the UI.
- Needs careful mapping onto the `people` → `employments` → `assignments` model, especially for anyone with employment gaps or multiple stints (rehires) that a flat spreadsheet likely doesn't represent cleanly. Every imported row gets a timeline event, same as any other hire.
- Should happen *before* go-live, not be treated as an afterthought once the system is otherwise "done." Still the recommendation — the tooling exists now, but the actual real-data migration hasn't happened yet.

---

## 6. Identity & authentication

- **SSO is undecided.** No corporate identity provider (Okta, Azure AD, Google Workspace) is confirmed in use, and whether one gets adopted is genuinely open. **Design implication**: keep the auth layer pluggable enough to add a SAML/OIDC guard alongside the existing Sanctum session/token auth later, without a rewrite. Don't build SSO now, but don't paint the auth layer into a corner either.

---

## 7. Near-term (demo) priorities

Given the immediate goal is a stakeholder demo/POC with no fixed date:

1. **Build a real dashboard/landing view.** Currently, login drops straight into a raw employee table — confirmed as insufficient for a stakeholder-facing first impression. The dashboard should surface headcount, open requisitions, pending time-off approvals, and recent hires — all data that already exists, just not aggregated/visualized anywhere yet. ✅ **Built** — `DashboardPage`, now the default route after login.
2. **Lightweight placeholder branding pass.** Not waiting on real brand assets — apply a plausible placeholder company identity (name, logo, color palette beyond the current generic blue) so the demo doesn't read as an obviously unbranded prototype. Swap in real brand assets later. **Not done** — still generic styling, no placeholder branding pass yet.
3. **No fixed deadline** — sequence remaining work by priority (this document) rather than against a date. Unchanged.

The dashboard (#1) and employee search/filtering (§2.6) are both built. The full ATS hire flow (create requisition → add candidate → move through pipeline → hire → confirm the resulting employee's timeline/onboarding/compensation records) has now been walked end-to-end in the browser and is covered by an automated Playwright scenario (`ats-full-pipeline.spec.ts`) — see [README.md](../README.md)'s Verification section. The placeholder-branding pass (#2) remains the one demo-priority item not yet done.

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

Items 1–8 below (everything except payroll and role-permission granularity) have been implemented — see the ✅ markers in §2–§7 for specifics, and [README.md](../README.md)'s Verification section for how it's all tested.

1. ✅ **Access revocation on termination** (§2.2.1).
2. ✅ **Dashboard/landing view + employee search/filtering** (§7, §2.6).
3. ✅ **Offboarding workflow** (checklist, final pay calc, notifications) (§2.2).
4. ✅ **Admin UI for locations/departments/onboarding templates/time-off policies** (§2.5).
5. ✅ **Data migration tooling** (§5) — built; the actual one-time real-data migration hasn't happened yet.
6. ✅ **Turnover/retention reporting** (§2.6).
7. ✅ **E-signature provider integration** (§2.3) — workflow built against a fake provider; **not yet legally binding** until a real DocuSign/Dropbox Sign binding replaces it.
8. ✅ **Bulk transfer/update UI** (§2.4).
9. **Payroll (gross-to-net, tax withholding, tokenized direct deposit)** — still not started. The largest single item by far (§2.1); its own phase with dedicated scoping, not an incremental extension of Compensation. This is the next major milestone.
10. ✅ **Background check / E-Verify integration**, ✅ **wage-compliance validation by location** (§2.1, §2.3) — both built. **Role-permission granularity** (§2.5) is still not built — the 4-role model remains unchanged.

What's genuinely next, in order: (1) the placeholder-branding demo pass (§7 #2, small and quick), (2) a real bank/e-signature/background-check provider swap-in when those integrations need to go live for real, (3) payroll as its own dedicated phase, (4) role-permission granularity once the HR/ops team grows past what 4 roles can express.
