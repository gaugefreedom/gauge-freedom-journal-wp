# Gauge Freedom Journal Plugin - Development Roadmap

**Current Version:** v1.0.0 (Production Ready) ✅
**Last Updated:** 2025-10-02

This roadmap outlines planned features and improvements based on community feedback, academic publishing best practices.

---

## ✅ v1.0.0 - Core Platform (COMPLETED)

**Released:** 2025-10-02
**Status:** Production Ready

### Features Delivered

**Manuscript Management:**
- ✅ Complete submission workflow with file uploads
- ✅ Double-blind peer review enforcement
- ✅ Multi-round revisions with reviewer re-invitations
- ✅ Triage vs. review revision distinction
- ✅ Complete audit trail via metadata

**User Roles & Permissions:**
- ✅ 5 custom roles (Author, Reviewer, Editor, EIC, Managing Editor)
- ✅ Role-based dashboards
- ✅ Stage-dependent access control
- ✅ WordPress registration integration with email verification

**Email Notifications:**
- ✅ 8 notification types covering all workflow stages
- ✅ Safe handling of deleted users/posts
- ✅ Customizable via WordPress filters

**Security:**
- ✅ 62+ nonce verification checks
- ✅ SQL injection protection (parameterized queries)
- ✅ XSS prevention (output escaping)
- ✅ Permission enforcement on all actions

---

## 🔨 v1.1.0 - Quality of Life

**Target:** Q1 2025
**Focus:** Improve editor and reviewer experience

### Priority Features

#### 1. Activity Logging System
**Problem:** No audit trail for editorial decisions
**Solution:** Create `gfj_activity_log` table

```sql
CREATE TABLE wp_gfj_activity_log (
    id bigint AUTO_INCREMENT,
    actor_id bigint NOT NULL,
    action_type varchar(50) NOT NULL,
    manuscript_id bigint,
    target_id bigint,
    metadata longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY manuscript_id (manuscript_id),
    KEY actor_id (actor_id)
);
```

**Logged Actions:**
- Manuscript submitted/edited
- Triage decision made
- Reviewer invited/accepted/declined
- Review submitted
- Editorial decision made
- Revision uploaded

**UI:** Add "Activity Log" tab to manuscript edit page

---

#### 2. Email Queue & Retry System
**Problem:** Failed emails are lost
**Solution:** Queue emails for background delivery

**Implementation:**
- Use `wp_schedule_single_event()` for async sending
- Create `gfj_email_queue` table with delivery status
- Retry failed emails 3 times with exponential backoff
- Admin page to view/resend failed emails

**Benefits:**
- Reliable delivery even during server issues
- Track email open rates (optional)
- Prevent timeout on bulk operations

---

#### 3. Dashboard Widgets
**Problem:** Editors must drill into pages to see workload
**Solution:** At-a-glance metrics on WP dashboard

**Widgets:**
- **Triage Queue Count** - "5 manuscripts awaiting triage"
- **Pending Reviews** - "12 reviews in progress, 3 overdue"
- **Author Revisions** - "8 revised manuscripts ready for review"
- **Recent Activity** - Timeline of last 10 actions

---

#### 4. Bulk Reviewer Assignment
**Problem:** Inviting multiple reviewers is tedious
**Solution:** Checkbox selection + bulk action

**UI:**
```
Reviewer Pool:
☑️ Dr. Smith (Quantum Physics)
☑️ Dr. Jones (AI Theory)
☑️ Dr. Lee (Information Geometry)

[Invite Selected Reviewers]
```

**Features:**
- Select 2-3 reviewers at once
- Send all invitations simultaneously
- Set custom deadline per reviewer

---

#### 5. Advanced Manuscript Search
**Problem:** Finding specific manuscripts is difficult
**Solution:** Filters + full-text search

**Filters:**
- Stage (Triage, Review, Revision, Accepted, Rejected)
- Author name
- Submission date range
- Reviewer assigned
- Keyword in title/abstract

**UI:** Add to manuscript list page above table

---

#### 6. Reviewer Database
**Problem:** Hard to track reviewer expertise and performance
**Solution:** Structured reviewer profiles

**Fields:**
- Expertise areas (taxonomy)
- Average review time (calculated)
- Reviews completed/declined counts
- Availability status (Active, Sabbatical, Unavailable)
- Internal notes (editor-only)

**UI:**
- "Reviewers" menu item under Manuscripts
- Filter reviewers by expertise when assigning

---

### Technical Debt

- [ ] Add PHPUnit tests for AJAX handlers
- [ ] Add JavaScript tests for frontend interactions
- [ ] Optimize database queries (add indexes)
- [ ] Implement object caching for reviewer lists
- [ ] Add WP-CLI commands for bulk operations

---

## 🤖 v1.2.0 - Automation & Integrations

**Target:** Q2 2025
**Focus:** AI assistance and external integrations

### 1. AI Co-Editor Integration
**Goal:** Optional AI assistance for editors

**Features:**
- Summarize manuscript abstract (Claude API)
- Check for common issues:
  - Missing citations
  - Unclear methodology
  - Overstated claims
- Generate suggested reviewers by matching expertise
- Flag potential plagiarism (via API)

**UI:**
- "AI Co-Editor Report" metabox on manuscript edit page
- Collapsible sections for each check
- Clearly labeled as "AI-generated, not a substitute for human judgment"

**Requirements:**
- API key for Claude/OpenAI
- Rate limiting (max 10 requests/day per manuscript)
- Disable AI features checkbox (for journals that don't want AI)

---

### 2. CAR Verification
**Goal:** Validate computational reproducibility receipts

**Integration:**
- Connect to `intelexta-verify` API
- Upload CAR file during submission
- Verify hash matches code repository
- Display verification status in manuscript details

**Workflow:**
1. Author uploads CAR file (optional but encouraged)
2. Plugin sends to verification API
3. API returns: Valid / Invalid / Cannot Verify
4. Status shown to editor as badge

---

### 3. Advanced Analytics Dashboard
**Goal:** Track journal health metrics

**Metrics:**
- Submissions per month (line chart)
- Average time to first decision
- Acceptance rate (overall and by article type)
- Reviewer performance (avg days to review, acceptance rate)
- Manuscripts by stage (pie chart)

**UI:** New "Analytics" page under Manuscripts menu

**Export:** Download as CSV for external analysis

---

### 4. JATS XML Export
**Goal:** Prepare manuscripts for indexing in PubMed, Crossref

**Features:**
- Convert accepted manuscripts to JATS XML format
- Extract metadata: title, authors, abstract, keywords
- Include references, figures, tables (from LaTeX)
- Validate against JATS DTD

**UI:** "Export as JATS XML" button on accepted manuscripts

**Use Case:** Submit to PubMed Central, DOAJ, indexing services

---

### 5. Automated Reminders
**Goal:** Keep reviewers on track

**Reminders:**
- Review invitation expires in 3 days (if pending)
- Review due in 7 days
- Review overdue by 3 days
- Review overdue by 7 days (escalate to editor)

**Configuration:**
- Enable/disable reminders globally
- Customize email templates
- Set reminder intervals (default: 3, 7, 14 days before due)

---

### 6. AI Reviewer Matching
**Goal:** Suggest best reviewers for each manuscript

**Algorithm:**
1. Extract keywords from manuscript abstract
2. Match against reviewer expertise areas
3. Check reviewer availability and workload
4. Rank by relevance score
5. Show top 5 suggestions to editor

**UI:**
- "Suggested Reviewers" section above manual dropdown
- Each suggestion shows: name, match score, recent activity

---

## 📚 v2.0.0 - Publication Platform

**Target:** Q3 2025
**Focus:** Transform from submission system to full journal platform

### 1. Public Article Archive
**Goal:** Display published papers on website

**Features:**
- Browse articles by date, topic, author
- Search full text with Elasticsearch
- Article detail page with PDF download
- Automatic sitemap generation for SEO
- RSS feed for new publications

**Permissions:**
- Embargoed articles (visible only to authors/editors)
- Public articles (visible to all)

---

### 2. DOI Integration
**Goal:** Assign permanent identifiers via Crossref

**Features:**
- Register DOI on manuscript acceptance
- Format: `10.XXXXX/gfj.YYYY.NNN` (e.g., 10.12345/gfj.2025.001)
- Automatic metadata deposit to Crossref
- Display DOI prominently on article page
- Update DOI if article URL changes

**Requirements:**
- Crossref membership ($275/year)
- DOI prefix allocation
- HTTPS required

---

### 3. Article Versioning
**Goal:** Track preprints, revisions, final versions

**Versions:**
- **Preprint** - Initial submission (optional public posting)
- **Accepted Manuscript** - Post-review, pre-copyedit
- **Version of Record** - Final published version
- **Corrections** - Post-publication errata

**UI:**
- Version timeline on article page
- "View Previous Version" dropdown
- Changelog between versions

---

### 4. Citation Tracking
**Goal:** Monitor impact of published work

**Integrations:**
- Crossref Event Data (citations from other papers)
- Semantic Scholar API (citation count, influential papers)
- Altmetric (social media mentions)
- Google Scholar (via web scraping)

**UI:**
- Citation count badge on article page
- "Cited By" section with links to citing papers
- Impact timeline graph

---

### 5. Multi-Journal Support
**Goal:** Run multiple journals from one WordPress install

**Features:**
- Select journal during submission
- Separate editorial teams per journal
- Shared reviewer pool (optional)
- Journal-specific settings (review period, email templates)
- Cross-journal admin dashboard

**Use Case:** Publisher running multiple niche journals

---

### 6. REST API
**Goal:** Integrate with external systems

**Endpoints:**
```
GET    /wp-json/gfj/v1/manuscripts
POST   /wp-json/gfj/v1/manuscripts
GET    /wp-json/gfj/v1/manuscripts/{id}
PUT    /wp-json/gfj/v1/manuscripts/{id}
GET    /wp-json/gfj/v1/reviews
POST   /wp-json/gfj/v1/reviews
GET    /wp-json/gfj/v1/reviewers
```

**Authentication:** OAuth 2.0 or API keys

**Use Cases:**
- Submit manuscripts from LaTeX editor plugin
- External dashboard for editors
- Data export for institution repositories
- Integration with ORCID

---

## 🌍 v3.0.0 - Enterprise & Internationalization

**Target:** Q4 2025
**Focus:** Scale to large journals and global audiences

### 1. Multi-Language Support (i18n)
**Goal:** Translate UI for non-English journals

**Languages:**
- Spanish (high priority for Latin American journals)
- Portuguese (Brazil)
- French
- German
- Chinese (Simplified)

**Implementation:**
- Use WordPress `__()` translation functions (already partially implemented)
- Create `.pot` template file
- Community-contributed translations via [translate.wordpress.org](https://translate.wordpress.org)

---

### 2. Mobile Admin App
**Goal:** iOS/Android app for editors on the go

**Features:**
- View triage queue
- Make triage decisions
- Assign reviewers
- View reviews
- Approve manuscripts

**Tech Stack:**
- React Native for cross-platform
- REST API backend (from v2.0.0)
- Push notifications for new submissions

---

### 3. Advanced Permissions
**Goal:** Fine-grained role customization beyond default 5 roles

**Examples:**
- "Senior Editor" - Can assign reviewers, cannot make final decisions
- "Production Editor" - Can edit accepted manuscripts, cannot view reviews
- "Statistical Reviewer" - Sees only methods/data sections

**UI:**
- Role editor with capability checkboxes
- Per-manuscript access grants (invite external expert for one paper)

---

### 4. Performance Optimization
**Goal:** Handle 10,000+ manuscripts without slowdown

**Techniques:**
- Database query optimization (add composite indexes)
- Object caching (Redis/Memcached)
- Lazy loading in dashboards
- Pagination improvements (virtual scrolling)
- CDN integration for PDF downloads
- Background processing for heavy operations

**Target:** Page load < 1 second with 10k manuscripts

---

### 5. White Label
**Goal:** Rebrand plugin for other journals

**Customizations:**
- Replace "Gauge Freedom Journal" with custom name
- Upload custom logo/colors
- Customize email footer
- Hide "Powered by GFJ" attribution (optional)

**Use Case:** Journal consortiums, university presses

---

### 6. SaaS Mode (Multi-Tenant)
**Goal:** One WordPress install, multiple isolated journals

**Features:**
- Each journal has separate:
  - Database tables (prefixed with journal ID)
  - User pools (or shared with permissions)
  - File uploads (separate directories)
  - Email settings
- Central billing/subscription management
- Journal creation wizard for new customers

**Use Case:** Offer peer review platform as a service

---

## 🔬 Research & Experimental

**Long-term ideas not scheduled yet:**

### Blockchain-Based Peer Review
- Store review hashes on blockchain for tamper-proof records
- Reputation system for reviewers
- Portable review credits across journals

### Decentralized Storage
- IPFS for manuscript PDFs (immutable, distributed)
- Content addressing ensures reproducibility
- Integrate with CAR system

### Real-Time Collaboration
- Google Docs-style collaborative editing
- Inline comments between author and editor
- Version control with git-like branching

### Automated Peer Review Matching
- Machine learning model trained on past assignments
- Predicts reviewer quality and accept probability
- Reduces bias in reviewer selection

### Open Peer Review Option
- Optional public reviews (non-anonymous)
- Reviews published alongside article
- Reviewer attribution and recognition

---

## 🎯 Contribution Priorities

**If you want to contribute, we most need help with:**

### High Priority
1. **Testing** - Manual and automated tests
2. **Documentation** - User guides, video tutorials, FAQ
3. **Translations** - Non-English UI strings
4. **Bug Reports** - Find edge cases we missed

### Medium Priority
5. **Code Review** - Review PRs, suggest improvements
6. **Feature Requests** - Share your journal's pain points
7. **Design** - UI/UX improvements, accessibility
8. **Performance** - Profile slow queries, suggest optimizations

### Low Priority
9. **New Features** - Implement roadmap items
10. **Integrations** - Connect to external services

**How to Contribute:** [See CONTRIBUTING.md](CONTRIBUTING.md)

---

## 📊 Success Metrics

**We'll track these metrics to measure impact:**

| Metric | Current | v1.1.0 Goal | v2.0.0 Goal | v3.0.0 Goal |
|--------|---------|-------------|-------------|-------------|
| Active Installations | 1 | 10 | 50 | 200 |
| Contributors | 2 | 5 | 15 | 30 |
| Issues Closed | 0 | 20 | 100 | 300 |
| Manuscripts Processed | 0 | 100 | 500 | 2,000 |
| Code Coverage | 0% | 30% | 60% | 80% |
| Community Stars ⭐ | 0 | 50 | 200 | 500 |

---

## 🗓️ Release Schedule

| Version | Feature Freeze | Beta Testing | Release Date |
|---------|---------------|--------------|--------------|
| v1.0.0 | 2025-09-15 | 2025-09-20 | **2025-10-02** ✅ |
| v1.1.0 | 2025-02-15 | 2025-02-20 | 2025-03-01 |
| v1.2.0 | 2025-05-15 | 2025-05-20 | 2025-06-01 |
| v2.0.0 | 2025-08-15 | 2025-08-20 | 2025-09-01 |
| v3.0.0 | 2025-11-15 | 2025-11-20 | 2025-12-01 |

**Release Cadence:** Every 3 months (quarterly)

---

## 💡 Community Input

**This roadmap is a living document!**

We actively seek feedback from:
- Academic editors using the system
- Peer reviewers
- Authors submitting manuscripts
- Developers and WordPress experts
- Journal publishers and societies

**How to suggest changes:**
1. Open a [GitHub Discussion](https://github.com/gaugefreedom/gauge-freedom-journal-wp/discussions)
2. Describe your use case
3. Propose a solution
4. Tag with `roadmap` label

**Popular requests will be prioritized!**

---

**Last Updated:** 2025-10-02
**Maintained By:** Gauge Freedom Journal Team
**Questions?** [Open a discussion](https://github.com/gaugefreedom/gauge-freedom-journal-wp/discussions)
