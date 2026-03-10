# Old CRM Call Flow – Audit & Target Behaviour

## 1. Link to old CRM

**No URL or repository link to the old CRM was found in this codebase.** The integration plan only references “Task Book Main / Task Book” as the older project name.

If you can share the old CRM URL (or repo path), we can do a **screen-by-screen audit** and match the UI and flow exactly. Until then, this document is based on your description and the existing integration plan.

---

## 2. Desired behaviour (from your description)

You want the **one-by-one call flow** from the old lead management app:

1. **One number at a time**  
   The app shows a single student/lead (name, number, context). No grid of cards; focus on one contact.

2. **Call action**  
   User clicks to call (e.g. `tel:` link). Optionally the app can just show the number prominently so they dial manually.

3. **After the call – capture details**  
   As soon as the call is (or will be) made, the app asks for:
   - Call outcome (connected, no answer, busy, etc.)
   - Notes
   - Lead status update (optional)
   - Next follow-up (optional)

4. **Submit → show next number**  
   When the user saves the call details, the app:
   - Saves the call and updates the student.
   - Automatically shows the **next** lead to call (same one-by-one view).

So the flow is: **Current lead → Call → Fill details → Save → Next lead**. No need to go back to a list and pick the next one.

---

## 3. Current behaviour vs target

| Aspect | Current | Target (old CRM style) |
|--------|--------|-------------------------|
| **My Leads list** | Grid of cards; many leads visible at once | **One lead at a time** in a dedicated “call” / “dialer” view |
| **Call + log** | “Call & log” opens a **modal** (and modal exists only on phone-campaigns show; My Leads has no modal) | After “call”, show **full-page or prominent form** for call details, then **auto-advance to next lead** |
| **After saving call** | Redirect `back()` (same list or same phone page) | **Redirect to “next lead”** in the same one-by-one view (or reload same route with next student) |
| **Entry point** | My Leads = list; user picks a card then “Call & log” or “Open history” | **My Leads** can still be the list, but primary workflow = **“Start calling”** → one-by-one queue (e.g. by next follow-up or “not called” first) |

---

## 4. Inferred old CRM UX (to confirm when you share the link)

- **Queue / dialer screen**  
  - Single lead in focus: name, father, school/class, phone(s).  
  - One primary action: “Call” (and/or “I’ve called – log it”).  
  - After “Call” or “Log call”, a **form** is shown (same page or next step): call status, notes, lead status, next follow-up.  
  - On submit: save → **replace current lead with the next one** (same screen or same URL with next ID).

- **Order of leads**  
  - Likely: overdue follow-ups first, then “not called”, then by next_followup_at.  
  - One lead per “page” or one lead per “step” in a wizard.

- **No grid on the “calling” flow**  
  - List view (My Leads) may still exist for browsing/filtering, but the **calling workflow** is a separate, linear flow: lead 1 → log → lead 2 → log → …

---

## 5. Proposed implementation (high level)

Once we align with the old CRM (or your confirmation), we can:

1. **Add a “Call queue” / “Next lead” route**  
   - e.g. `GET students/next-to-call` or `GET my-leads/call`  
   - Returns the **next** student for the current user (by same ordering as follow-ups: overdue, then not called, then by `next_followup_at`).  
   - Renders a **single-lead view**: big name, phone, school/class, last call summary, and a **“Call & log”** section.

2. **One-by-one UI**  
   - One card/section per screen for “current lead”.  
   - Prominent “Call” (`tel:`) and “I’ve called – log it” (expand or show form).  
   - Form fields: call status, duration, notes, lead status, next follow-up (same as current modal).

3. **Post-save behaviour**  
   - On successful `POST students/{student}/calls`:  
     - Redirect to **same “next to call” route** (e.g. `redirect()->route('students.next-to-call')`).  
     - That route always loads the **next** lead in queue; so after saving, the user immediately sees the next number.

4. **My Leads list**  
   - Keep as the list/filter view.  
   - Add a clear CTA: **“Start calling”** or **“Next lead”** that goes to the one-by-one queue.  
   - Optional: “Call next” on each card that jumps into the queue starting from that lead.

5. **Call-log form on My Leads**  
   - Currently “Call & log” on My Leads does not have the modal (it’s only on phone-campaigns show).  
   - Either: include the same modal on My Leads, or **prefer** the new flow: “Start calling” → one-by-one page with inline form (no modal).

6. **Optional: “Skip” / “Next”**  
   - On the one-by-one screen, a “Skip” or “Next” button that loads the next lead without logging a call (for “I’ll call later” or wrong number).

---

## 6. What we need from you

1. **Old CRM link** (if available)  
   So we can audit the exact screens and copy layout/order/wording.

2. **Confirmation of flow**  
   - Is the above “one lead → call → details → save → next lead” the desired flow?  
   - Any difference in ordering (e.g. “not called” first vs overdue first)?

3. **Where the queue should live**  
   - Under “Leads” as “Start calling” / “Next to call”?  
   - Or as the **default** when a telecaller opens “Leads” (first screen = next lead, with a link to “See all my leads” list)?

Once you confirm (and share the link if you have it), we can implement the one-by-one UI and redirect-after-save behaviour step by step.
