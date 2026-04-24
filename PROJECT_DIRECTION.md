# AI Customer Support SaaS - Enhanced Product Direction

## Why This Idea Can Win

The base PRD is solid, but the strongest business edge comes from positioning this as a **revenue-protecting support platform**, not just a chatbot. Most businesses already have a FAQ bot; fewer have a system that:

- deflects repetitive tickets with high accuracy,
- escalates high-value conversations quickly,
- improves over time from resolved conversations,
- and proves ROI with measurable cost savings.

This direction increases retention, pricing power, and differentiation.

---

## Product Positioning (Sharper)

### Core Promise

"Resolve more support tickets in less time without losing customer trust."

### Primary ICP (Ideal Customer Profile)

- Shopify/eCommerce brands (high repetitive support volume)
- B2B SaaS startups (small support team, growing user base)
- Agencies managing support ops for multiple clients

### Wedge Strategy

Start with "easy embed + fast AI deflection + human takeover" and expand to "AI support copilot + insights + automation workflows."

---

## Enhanced Scope by Business Impact

## 1) Must-Have in MVP (Keep)

- Multi-tenant auth and tenant-isolated data
- Embeddable widget with API key auth
- Knowledge ingestion (FAQ + PDF/text)
- Basic RAG response flow
- Human takeover dashboard
- Escalation + basic notifications

## 2) Add These 5 High-Leverage Features Early

These features provide outsized business value with moderate engineering effort.

1. **Source Citation in AI Replies**
   - Show "Based on: FAQ X / Document Y"
   - Increases trust and reduces hallucination risk

2. **Confidence + Intent Tags**
   - Store confidence score and predicted intent per message
   - Enables quality analytics and escalation tuning

3. **Escalation SLA Timer**
   - Track "time since human required"
   - Critical for support team operations

4. **Conversation Outcome Labels**
   - AI_RESOLVED, HUMAN_RESOLVED, UNRESOLVED
   - Needed for ROI reporting and model improvement loops

5. **Admin Feedback Loop**
   - Agent can mark AI answer as Correct/Incorrect
   - Feeds prompt and retrieval improvements

---

## Recommended Product Architecture Enhancements

## Multi-Tenancy Model

- Keep Agency as tenant root.
- Add scoped roles: `owner`, `admin`, `agent`, `viewer`.
- Every query must be tenant-scoped by default (global scope or explicit tenant filter in repositories/services).

## Chat + Session Design

- Separate `visitor_sessions` from `conversations`.
- Allow one visitor session to have multiple conversations over time.
- Support widget metadata:
  - page URL
  - referrer
  - locale
  - browser/device

This improves routing and analytics quality later.

## Knowledge Ingestion

- Add chunking strategy config:
  - chunk size
  - overlap
  - max tokens per chunk
- Save document version and processing status:
  - PENDING, PROCESSING, READY, FAILED
- Keep raw extract + normalized text for reprocessing.

## AI Guardrails

- Strict context-grounded system prompt
- Fallback response when context is insufficient
- PII redaction option before model calls (future enterprise tier)

---

## Better API Design (Practical Laravel + SaaS)

The PRD lists generic CRUD endpoints. For production support systems, task-based endpoints are better.

### Auth / Tenant

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /me`
- `GET /agency`
- `PATCH /agency`
- `POST /agency/api-keys` (rotate key)

### Widget / Public

- `POST /widget/sessions`
- `POST /widget/messages`
- `GET /widget/conversations/{id}`

### Agent Dashboard

- `GET /conversations?status=&assignee=&channel=&q=`
- `POST /conversations/{id}/assign`
- `POST /conversations/{id}/takeover`
- `POST /conversations/{id}/close`
- `POST /conversations/{id}/messages`

### Knowledge Base

- `POST /knowledge/documents` (upload)
- `GET /knowledge/documents`
- `POST /knowledge/documents/{id}/reprocess`
- `DELETE /knowledge/documents/{id}`
- `GET /knowledge/search/test?q=` (admin retrieval debug)

---

## Monetization Strategy (Important for Direction)

## Pricing Model

Use a hybrid model:

- Base subscription per agency
- Included AI messages/month
- Overage pricing for additional AI usage
- Optional add-ons:
  - additional agent seats
  - premium integrations
  - advanced analytics

## Suggested Early Tiers

- **Starter**: 1 brand, low message cap, basic dashboard
- **Growth**: multi-agent, higher cap, webhook notifications
- **Scale**: multi-brand, advanced analytics, SLA workflows

---

## KPI Framework (From Day 1)

Track these from initial release:

- AI Deflection Rate (% handled without human)
- Human Escalation Rate
- Median First Response Time (AI and human separately)
- Median Time to Resolution
- Reopen Rate
- CSAT (thumbs up/down minimum)
- Cost per Resolved Conversation

Without these, product learning and sales proof become weak.

---

## Competitive Differentiators to Build Toward

1. **Fast setup in under 30 minutes**
2. **Explainable answers with source references**
3. **Human-in-the-loop quality controls**
4. **Multi-tenant agency management**
5. **Actionable support intelligence (not just chat logs)**

---

## Risk Register + Mitigation

## Risk: Hallucinated answers
- Mitigation: strict context-only prompt, confidence threshold, citations, fallback to escalation

## Risk: Bad retrieval quality
- Mitigation: chunking strategy, reprocessing, embedding diagnostics, test endpoint

## Risk: Slow response during traffic spikes
- Mitigation: queue priorities, Redis tuning, async AI pipeline, response caching for repeated intents

## Risk: Tenant data leakage
- Mitigation: mandatory tenant scoping, policy checks, tenancy tests, audit logs

## Risk: Low agent adoption
- Mitigation: simple inbox UX, assignment workflow, SLA timers, saved replies/macros (phase 2)

---

## Execution Roadmap (Practical and Realistic)

## Phase 1 - Foundation (Week 1)
- tenancy + auth + roles
- widget session + messaging APIs
- conversation/message schema
- basic inbox UI

## Phase 2 - AI Core (Week 2)
- knowledge upload + parsing + embeddings
- retrieval pipeline
- AI response generation job
- confidence-based escalation

## Phase 3 - Reliability + Measurement (Week 3)
- notification channels (dashboard + email/webhook)
- outcome labels + KPI tracking
- citation rendering + feedback loop
- baseline analytics cards

## Phase 4 - Growth Features (Week 4+)
- multilingual support
- CRM/helpdesk integrations
- macros, auto-tags, routing rules
- billing and plan controls

---

## Suggested Data Model Additions

Beyond current PRD, include:

- `agency_api_keys` (rotation + last_used_at)
- `visitor_sessions`
- `conversation_assignments`
- `message_feedback` (agent feedback on AI answers)
- `knowledge_chunks`
- `document_processing_runs`
- `conversation_metrics` (denormalized aggregates)
- `audit_logs` (security/compliance readiness)

---

## Prompt and AI Policy (Production Grade)

AI assistant must:

- answer only from retrieved context,
- quote source titles when possible,
- state uncertainty clearly,
- avoid legal/financial guarantees,
- escalate on low confidence or sensitive intent.

Add rule-based escalation triggers:

- refund/cancellation/chargeback mentions
- legal threats
- abusive language
- repeated "not helpful" feedback

---

## What to Build Next (Immediate Team Direction)

Priority order:

1. Finalize schema for tenants, conversations, messages, knowledge chunks.
2. Build widget session + message ingestion endpoints.
3. Implement async AI pipeline (retrieve -> generate -> confidence -> escalate).
4. Build human inbox with takeover/assign/close actions.
5. Add KPI events + minimal analytics from day one.

This creates a usable product quickly while preserving a strong path to monetization and scale.

---

## Final Strategic Note

Treat this product as a **support operations platform with AI at the center**, not only a chatbot widget. The long-term moat is:

- better retrieval quality,
- better escalation orchestration,
- better measurable outcomes for support teams.

That combination is what customers will pay to keep.
