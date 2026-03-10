## Lead Management + Bulk Messaging Integration Plan

This document describes how we will blend the **TaskBook Lead Management features** from the older project into the current **TaskBook Bulk Messaging CRM** (students + campaigns), with a clear, step‑by‑step rollout plan.

The goal is:

- One **unified contact record** per student/phone (no duplicate people).
- A **proper lead pipeline** (statuses, follow‑ups, call history, owner/assignee).
- Tight integration with **bulk WhatsApp campaigns** and **single‑person messages** from approved templates, with full history in one place.

All changes below are **design only**; nothing is implemented until you approve this plan.

---

## 1. High‑level architecture

We will **not** copy the old `leads` + `lead_calls` system as‑is. Instead, we will:

1. Keep `students` as the **single source of truth** for contacts (unique by phone).
2. Add a new `student_calls` (name TBD) table modelled on `LeadCall` for call history and follow‑ups.
3. Extend `students` with extra fields (totals and follow‑up metadata) inspired by `Lead`.
4. Reuse selected logic from the old Lead Management:
   - Status pipeline concept and labels.
   - Auto follow‑up and auto status mapping rules.
   - “My Leads” / follow‑up screens and UX pattern (rewritten in Tailwind).
5. Integrate deeply with existing bulk‑messaging:
   - Ability to send **single‑student WhatsApp messages** from approved templates immediately after a call.
   - Campaign and message history visible per phone/student.

Result: Telecalling + bulk messaging + status tracking all live around `students` and `student_calls`.

---

## 2. Current state summary (our CRM)

**Already present in this project:**

- `students` table / model:
  - Unique by phone (primary/secondary; we normalise to 10‑digit Indian numbers).
  - Linked to `class_sections` and `schools`.
  - New `lead_status` field with values:
    - `lead`, `interested`, `not_interested`, `walkin_done`, `admission_done`, `follow_up_later`.
- Tagging:
  - Many‑to‑many `tags` / `student_tag` so a student can be in multiple entities/lists (DPS School, Interested Candidates, Walk‑in Campaign, etc).
  - Imports attach a tag per import instead of creating duplicates.
- Campaigns:
  - `campaigns`, `campaign_recipients` with per‑recipient status and stored message content.
  - Per‑phone campaigns view: `phone/{phone}/campaigns` shows all campaigns for that number.
  - Phones are already **clickable** from the Students list.
- Lead status UI:
  - Lead status field on student create/edit.
  - Lead status visible on Students index.
  - Lead status updatable from the per‑phone campaigns screen via a small form.

This means we **already** treat each 10‑digit phone as the unique key for a student and have the beginnings of a lead lifecycle.

---

## 3. What we like from the old Lead Management system

From `Task Book Main/Task Book` we want to **borrow ideas and selected code**, not the whole stack:

- `Lead` model:
  - Rich status pipeline with constants, labels, colors, and icons.
  - Fields for assignment (`assigned_to`, `assigned_by`, `assigned_at`).
  - Denormalised call summary on the lead (`total_calls`, `last_call_at`, `last_call_status`, `last_call_notes`, `next_followup_at`).
  - Helpful scopes: `active()`, `assignedTo()`, `unassigned()`, `needingFollowup()`.
- `LeadCall` model:
  - Detailed call information: `call_status`, `who_answered`, `interest_level`, `tags`, duration, notes.
  - Auto‑rules:
    - `getAutoFollowupHours($callStatus, $interestLevel)` – when to call again.
    - `getAutoLeadStatus($callStatus, $interestLevel)` – how a call result should update lead status.
  - Quick tags array (structured reasons for the call) for fast capture.
- UI/UX:
  - `my-leads.blade.php` and related views:
    - Card‑based “My Leads” dashboard for telecallers.
    - Badges for status (color + label).
    - Visible last call and next follow‑up dates.
    - Clear quick actions: Call, WhatsApp, View details.
- Import tracking (`lead_import_batches`) and duplicate handling.

We will **port** the core logic (status pipeline, call/follow‑up logic, UX ideas) and **map it on top of students**, rather than creating a parallel lead subsystem.

---

## 4. Proposed domain model after integration

### 4.1 Students remain central

`students` will continue to be the main table. We keep:

- One record per unique 10‑digit phone (primary/secondary).
- Tags for campaign lists and entities.
- `lead_status` to indicate lead journey.

We will **extend `students`** with optional fields (inspired by `Lead`):

- `assigned_to` (`users.id`) – which staff/telecaller currently “owns” this student.
- `assigned_by` (`users.id`, nullable) – who assigned it.
- `assigned_at` (nullable timestamp).
- Call summary:
  - `total_calls` (int, default 0).
  - `last_call_at` (nullable timestamp).
  - `last_call_status` (string, nullable).
  - `last_call_notes` (text, nullable).
  - `next_followup_at` (nullable timestamp).

These are for **fast listing and filters**, while detailed history lives in `student_calls`.

### 4.2 New table: `student_calls`

New Eloquent model `StudentCall` modelled on `LeadCall`:

Fields (first iteration; we can expand later):

- Foreign keys:
  - `student_id` (FK -> `students.id`).
  - `user_id` (FK -> `users.id` – caller).
- Call basics:
  - `call_status` (enum): `connected`, `no_answer`, `busy`, `switched_off`, `not_reachable`, `wrong_number`, `callback`.
  - `duration_minutes` (int, default 0).
  - `called_at` (timestamp, default now).
- Outcome:
  - `call_notes` (text, nullable).
  - `status_changed_to` (string, nullable) – if lead_status was changed because of this call.
- Next step:
  - `next_followup_at` (nullable timestamp).
  - `followup_notes` (string, nullable).
- Later (optional, if we want parity with `LeadCall`):
  - `who_answered` (enum student/father/mother/guardian/other).
  - `interest_level` (string).
  - `tags` (JSON array) for quick tags like “fee query”, “will discuss with family”, etc.

Indexes:

- `student_id`, `user_id`, `called_at`, `next_followup_at`.

Relationships:

- `StudentCall` belongsTo `Student`.
- `StudentCall` belongsTo `User` (caller).
- `Student` hasMany `StudentCall`.

### 4.3 Status pipeline mapping

We don’t want to explode the number of statuses, but we want a **clear journey**.

Current `students.lead_status` values:

- `lead`, `interested`, `not_interested`, `walkin_done`, `admission_done`, `follow_up_later`.

Old `Lead` statuses:

- `new`, `attempting`, `interested`, `visit_scheduled`, `visited`, `follow_up`, `future_prospect`, `not_interested`, `lost`, `converted`.

**Proposed unified set for students (final names can be adjusted):**

- `lead` (equivalent of `new` / fresh lead).
- `attempting` (we are trying to reach).
- `interested` (wants more info / positive).
- `visit_scheduled` (campus visit booked).
- `visited` (walk‑in done).
- `follow_up_later` (needs follow‑up; generic).
- `future_prospect` (next session later).
- `not_interested`.
- `lost` (wrong number / joined elsewhere).
- `admission_done` (equivalent of `converted`).

We will:

- Store these in `students.lead_status` as string values.
- Maintain an array (like `Lead::$statuses`) in Student or a dedicated helper for:
  - Labels (for display).
  - Optional Tailwind color classes (for badges).

### 4.4 Auto follow‑up rules

We reuse simplified logic from `Lead::$autoFollowupDays` and `LeadCall::getAutoFollowupHours()`:

- For each call, based on `call_status` and (optionally) `interest_level`, we compute:
  - `next_followup_at` (timestamp).
  - A recommended `status_changed_to` (optional).

Examples:

- `call_status = no_answer` → `next_followup_at = now() + 1 day`, `status_changed_to = attempting`.
- `call_status = busy` → `next_followup_at = now() + 2 hours`, `status_changed_to = attempting`.
- `connected + very_interested` → `next_followup_at = 1–2 days`, `status_changed_to = interested`.
- `connected + not_interested` → `next_followup_at = null`, `status_changed_to = not_interested`.
- `connected + ready_to_enroll` → `status_changed_to = admission_done`, `next_followup_at = null` or a short courtesy follow‑up.

We implement this as **pure PHP methods** (service or static methods on `StudentCall`) so it’s easy to adjust later.

Whenever a `StudentCall` is saved:

- We update `students`:
  - Increment `total_calls`.
  - Set `last_call_at`, `last_call_status`, `last_call_notes`.
  - Set `next_followup_at` from the computed rule.
  - If a status change is selected in the UI, or the rule suggests one, update `students.lead_status`.

---

## 5. Screens and user flows

### 5.1 Students index (existing)

Enhancements:

- Ensure we show:
  - Lead status badge (with color), as already started.
  - Optional summary: total calls, next follow‑up (maybe hidden in a tooltip or additional column on desktop).
- Filters:
  - Add `lead_status` filter dropdown (All / each value).
  - Later: filter for “Next follow‑up due today/overdue”.

No big structural change here; this remains the general master list.

### 5.2 Per‑number view (existing: `phone/{phone}/campaigns`)

Current behaviour:

- Shows campaigns sent to that phone.
- Shows top panel with student name, class/school, tags, and **Lead status** (now updatable via small dropdown).

Planned enhancements:

- Add a **Call history tab/section**:
  - Table of `student_calls` for this student, newest first:
    - Date/time, caller, call status, status_changed_to, next_followup_at, short notes.
- Add a **“Log call” or “Quick call” button**:
  - Opens a small modal form (Tailwind) to log a call:
    - Call status (select).
    - Short notes.
    - Optional override for lead status.
    - Optional override for next follow‑up (defaulted from auto rules).
  - On save:
    - Creates a `StudentCall`.
    - Updates `students` summary fields and `lead_status`.
    - Optionally redirects back to same page with a success message.
- **Single‑student WhatsApp messages right after a call**:
  - In the call logging modal or below the call history, add:
    - Dropdown to pick an approved template (from `AisensyTemplate`).
    - Preview of the message with parameters.
    - “Send now” button which:
      - Creates a one‑off `Campaign` (type `single` or `adhoc`) or a light‑weight `DirectMessage` record.
      - Creates a `CampaignRecipient` (or similar) for this one student and immediately **queues** the message via the existing job/queue system.
      - Logs this in the same history you already have (campaigns per phone).

This gives the telecaller a **one‑screen workspace**: call, set status, schedule follow‑up, and send a WhatsApp from approved templates.

### 5.3 “My Leads / My Students” dashboard (new)

Purpose: Telecaller’s daily view, similar to `leads.my-leads.blade.php`:

- Route: e.g. `students/my-leads` or `leads/my-students`.
- Data:
  - Students where `assigned_to = current_user.id`.
  - Active statuses only (exclude final statuses like `admission_done`, `lost`, `not_interested` if desired).
- Layout (mobile‑first, Tailwind):
  - Card per student:
    - Student name, father name, school + class.
    - Phone (clickable, as now).
    - Lead status badge.
    - Total calls + last call age.
    - Next follow‑up date/time, with red highlight if overdue.
    - Last call notes (short).
    - Actions:
      - “Open student” (to main Student edit).
      - “Open phone view” (campaigns + call history).
      - “Quick call” (opens same call modal as per‑phone view).
- Filters:
  - Search (by name/phone/school).
  - Lead status filter.
  - Optional “Show only overdue follow‑ups”.

This is almost a **copy of the old my‑leads UX**, but implemented with:

- `Student` + `StudentCall`.
- Tailwind classes.
- Your existing `auth()` / staff permission system.

### 5.4 Follow‑ups dashboard (new)

Purpose: quickly see students that need follow up **today or overdue**.

- Route: e.g. `students/followups`.
- Data: students where:
  - `next_followup_at` is not null and `<= now()` and `lead_status` is not final (unless you want otherwise).
  - For non‑admin users: `assigned_to = current_user.id`.
- UI:
  - Similar card/table list with emphasis on `next_followup_at`.
  - Quick link to call/log and to open per‑phone view.

This mirrors `LeadController::followups()` logic.

---

## 6. Bulk messaging integration details

### 6.1 Bulk campaigns (existing behaviour)

Bulk campaigns remain almost unchanged:

- Admin selects school/class/tags to build recipient list.
- We create `campaign_recipients` and queue messages.
- Per‑campaign stats and per‑recipient message body are already present.

No structural change needed here; we just **reuse the same data** in the per‑phone view and student view.

### 6.2 Single‑student messages from call screen

Implementation options:

1. **Mini‑campaign per call (simple, consistent)**
   - When telecaller selects a template + sends:
     - Create a `Campaign` record with:
       - `type = 'single'` or `name = 'Direct: [template_name] to [student_name]'`.
       - `total_recipients = 1`.
     - Create a single `CampaignRecipient` pointing to the student/phone.
     - Dispatch the same job used for normal campaigns.
   - Pros:
     - Reuses all existing campaign logic, jobs, and tracking.
     - Appears in normal campaign reports.
   - Cons:
     - Many small campaigns may exist; we can group them by type for reporting.

2. **Separate `direct_messages` table (more work)**
   - New table with `student_id`, `template_id`, `message`, `status`, etc.
   - Separate job and UI.
   - More complex; not necessary initially.

**Plan:** Start with **Option 1 (mini‑campaigns)** for simplicity and full reuse.

On the per‑phone view:

- After a call is logged (or in same modal), user can:
  - Choose a template.
  - Fill dynamic fields (if any; we can reuse your existing template handling).
  - Click “Send WhatsApp”.
- We:
  - Create a small campaign + recipient.
  - Immediately queue sending.
  - Show the resulting send in:
    - The campaign’s page.
    - The per‑phone campaigns view (already exists).

### 6.3 History view

- **Per‑phone**: already shows campaign sends, and we will add call history.
- **Per‑student** (edit screen):
  - Optional small section (tab) summarising:
    - Lead status & history.
    - Last 3 calls.
    - Last 3 campaigns/messages.

This gives staff a complete picture when editing a student.

---

## 7. Permissions and roles

We will reuse your existing `users` + staff permission system:

- Admins:
  - Can see all students, all calls, all follow‑ups.
  - Can assign/unassign students to staff (`assigned_to`).
- Staff:
  - Can access students only if allowed by `can_access_students` (already present).
  - For “My Leads” and “Follow‑ups” pages:
    - See only students where `assigned_to = their id`.
  - Can log calls and send single‑student messages for students they are allowed to view.

If you want an extra flag (like `can_access_leads`), we can add it later, but not required at first.

---

## 8. Database and code changes (step‑by‑step)

We will implement in **small, safe phases** so we don’t break existing behaviour.

### Phase 1 – Schema foundations (no UI change)

1. Migration:
   - Add to `students`:
     - `assigned_to` (nullable, FK to `users`).
     - `assigned_by` (nullable, FK to `users`).
     - `assigned_at` (nullable timestamp).
     - `total_calls` (unsigned int, default 0).
     - `last_call_at` (nullable timestamp).
     - `last_call_status` (nullable string).
     - `last_call_notes` (nullable text).
     - `next_followup_at` (nullable timestamp).
   - Create `student_calls` table as designed above.
2. Model:
   - New `StudentCall` model.
   - Add `calls()` relationship to `Student`.
3. No controllers/views changed yet.

### Phase 2 – Lead status mapping and helpers

1. Define status config in `Student` or a dedicated helper:
   - Allowed `lead_status` values with labels + Tailwind badge classes.
2. Adjust validation in `StudentController` to ensure we use the new unified set.
3. Ensure existing records either:
   - Are migrated to equivalent new statuses, or
   - Continue working if we keep backward‑compatible values (we can discuss exact mapping).

### Phase 3 – Call logging (backend)

1. Service/helper methods (pure PHP) for:
   - Computing `next_followup_at` from `call_status` / `interest_level`.
   - Computing suggested `status_changed_to`.
2. Controller actions:
   - New `StudentCallController` or methods on `StudentController`:
     - `storeCall(student)` – validate input, create `StudentCall`, update student summary + `lead_status`.
3. No UI yet; we can test via simple forms or Tinker.

### Phase 4 – Per‑phone view integration

1. Extend existing `PhoneCampaignsController@show`:
   - Already loads `Student` by phone.
   - Also load `student->calls` (paginated).
2. Update `crm/phone-campaigns/show.blade.php`:
   - Add call history table.
   - Add call logging modal or inline form.
3. Wire the call form to the new controller action.

### Phase 5 – Single‑student WhatsApp from call screen

1. UI on per‑phone view:
   - Template dropdown (list of approved templates).
   - Message preview.
   - “Send now” button.
2. Controller logic:
   - Create mini‑campaign and single `CampaignRecipient`.
   - Dispatch the existing job for that recipient.
3. Ensure:
   - This send appears both in campaign list and per‑phone history.

### Phase 6 – “My Leads” and “Follow‑ups” dashboards

1. Routes:
   - `students/my-leads` → `StudentLeadController@myLeads`.
   - `students/followups` → `StudentLeadController@followups`.
2. Controller logic:
   - Queries based on `assigned_to`, `next_followup_at`, `lead_status`.
3. Views:
   - Tailwind cards based on `leads.my-leads.blade.php` design concepts.
4. Navigation:
   - Add menu items under `Students` or a new `Leads` menu (admins only, or staff with permission).

### Phase 7 – Polishing and reports

1. Add small lead/call/high‑level stats to the dashboard:
   - e.g. number of active leads, overdue follow‑ups, conversions (`admission_done`).
2. Optional: export of leads with their statuses and call counts.
3. Optional: assignment tools (bulk assign students to staff).

---

## 9. Risks and mitigation

- **Risk:** Over‑complicating statuses.  
  **Mitigation:** Keep the status list we defined; don’t expose every possible state from the old LMS. We can hide rarely used ones from the UI initially.

- **Risk:** Data confusion between old and new systems.  
  **Mitigation:** We are **not** introducing a second leads table. Everything stays inside `students`. Old LMS remains a reference implementation only.

- **Risk:** Staff workflow disruption.  
  **Mitigation:** Implement in phases; no existing screen will be removed. New features appear in parallel (`My Leads`, call history, single‑send), so we can gradually adopt them.

---

## 10. What I need from you to proceed

Before implementing any code, please confirm:

1. **Status list** – Are you happy with the unified statuses:
   - `lead`, `attempting`, `interested`, `visit_scheduled`, `visited`, `follow_up_later`, `future_prospect`, `not_interested`, `lost`, `admission_done`?
2. **Single‑student WhatsApp flow** – Do you agree we use **mini‑campaigns** for these sends, so they appear in all existing reports?
3. **Dashboards naming & location** – Prefer:
   - `My Leads` + `Follow‑ups` under the `Students` menu, or
   - A separate `Leads` menu item in the main nav?

Once you approve this document (and optionally tweak the three points above), I will start with **Phase 1** and move forward step‑by‑step, keeping everything backward‑compatible and focused on stability.

