# Gauge Freedom Journal - WordPress Plugin

[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![Production Ready](https://img.shields.io/badge/Status-Production%20Ready-brightgreen.svg)](https://github.com/gaugefreedom/gauge-freedom-journal-wp)

**A complete, production-ready WordPress plugin for managing academic peer review.**

Built by [Gauge Freedom, Inc.](https://gaugefreedom.com) for [Gauge Freedom Journal](https://gaugefreedom.org) - advancing human+AI symbiosis in scientific research through rigorous, transparent peer review.

---

## 🎉 Current Status: **v1.0.0 - Production Ready**

✅ **Complete submission-to-publication workflow**
✅ **Multi-round peer review with re-reviews**
✅ **Double-blind anonymity enforcement**
✅ **Comprehensive security audit passed**
✅ **Successfully tested end-to-end**


---

## 🚀 Quick Start

```bash
# 1. Install
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/gaugefreedom/gauge-freedom-journal-wp.git
cd gauge-freedom-journal-wp

# 2. Activate in WordPress Admin → Plugins

# 3. Create required pages with shortcodes:
# - /dashboard/ → [gfj_dashboard]
# - /submit-manuscript/ → [gfj_submit_form]

# 4. Start accepting submissions!
```

**That's it!** The plugin handles everything else automatically.

---

## ✨ Features

### **Implemented & Working (v1.0.0)**

#### For Authors
- ✅ **Manuscript Submission** - Upload blinded + full PDFs, LaTeX sources, code/data links
- ✅ **Real-Time Tracking** - Dashboard shows current stage and editor feedback
- ✅ **Revision Uploads** - Respond to reviewer comments with revised manuscripts
- ✅ **Email Notifications** - Get notified at every workflow stage
- ✅ **Decision Letters** - View detailed editor feedback and reviewer comments

#### For Reviewers
- ✅ **Review Invitations** - Accept/decline requests via dashboard
- ✅ **Double-Blind Access** - See blinded manuscripts only (author info hidden)
- ✅ **Structured Reviews** - Score on 6 criteria + written feedback
- ✅ **Re-Reviews** - Review revised manuscripts with access to previous reviews
- ✅ **Previous Review Context** - See your prior comments and author's responses

#### For Editors
- ✅ **Triage Queue** - Review new submissions anonymously (author info hidden)
- ✅ **Reviewer Management** - Invite, track, and re-invite reviewers
- ✅ **Multi-Round Reviews** - Handle major/minor revisions with same or different reviewers
- ✅ **Editorial Decisions** - Accept, request revisions, or reject with feedback
- ✅ **Revision Workflow** - Distinguish triage revisions (anonymous) from post-review revisions
- ✅ **Complete Audit Trail** - Track all decisions, reviews, and revisions

### Security & Reliability
- ✅ **62+ Security Checks** - Nonce verification on all actions
- ✅ **SQL Injection Protection** - All queries parameterized
- ✅ **Permission Enforcement** - Role-based access control throughout
- ✅ **Error Handling** - Graceful failures with clear error messages
- ✅ **Email Validation** - Safe handling of deleted users/posts

### Open Science Support
- ✅ **Required Code Repositories** - Link to GitHub, Zenodo, etc.
- ✅ **Required Data Access** - Enforce data availability statements
- ✅ **LaTeX Source Upload** - Transparency in document preparation
- ✅ **AI Disclosure** - Required AI contribution statements
- ✅ **Conflict of Interest** - Mandatory COI declarations

### Email Notifications (8 Types)
1. **Author:** Submission confirmation
2. **Editor:** New submission alert
3. **Author:** Triage decision (approve/revise/reject)
4. **Reviewer:** Review invitation (initial or re-review)
5. **Editor:** Review completed alert
6. **Editor:** Reviewer accepted/declined
7. **Author:** Final decision (accept/revise/reject)
8. **Editor:** Revision uploaded

---

## 📋 Complete Workflow

### The Journey of a Manuscript

```
┌─────────────────────────────────────────────────────────────┐
│ 1. SUBMISSION                                               │
│    Author uploads: blinded PDF, full PDF, LaTeX, links      │
│    Status: TRIAGE                                           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. TRIAGE (Anonymous)                                       │
│    Editor sees: title, abstract, keywords (NO author info)  │
│    Decision: Send to Review / Request Changes / Desk Reject│
└─────────────────────────────────────────────────────────────┘
       │                      │                      │
       ▼                      ▼                      ▼
   REVIEW              REVISION (Triage)        REJECTED
       │                      │
       │         Author uploads revision
       │                      │
       │         Returns to TRIAGE (still anonymous)
       │                      │
       ├──────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. PEER REVIEW                                              │
│    Editor assigns 2-3 reviewers                             │
│    Reviewers see blinded manuscript only                    │
│    Editor sees full manuscript + author info                │
│    Status: REVIEW                                           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. EDITOR DECISION                                          │
│    Reviews complete → Editor makes decision                 │
│    Options: Accept / Minor Revision / Major Revision / Reject│
└─────────────────────────────────────────────────────────────┘
       │           │                    │              │
       ▼           ▼                    ▼              ▼
   ACCEPTED    REVISION (Minor)    REVISION (Major)  REJECTED
                   │                    │
                   │   Author uploads revision with response
                   │                    │
                   ▼                    ▼
               Returns to REVIEW (NOT anonymous)
                   │
                   │   Can re-invite same reviewers!
                   │   They see their previous review + author response
                   │
                   └────────> Repeat until ACCEPTED or REJECTED
```

### Key Workflow Features

**Triage Revisions (Anonymous):**
- Author info remains hidden
- Returns to triage queue
- Editor makes fresh decision

**Post-Review Revisions (Not Anonymous):**
- Author info visible to editor
- Returns to review stage
- Can re-invite same reviewers for re-review
- Reviewers see their previous comments + author's response

---

## 🛠️ Installation & Setup

### Requirements
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
- Pretty permalinks enabled

### Step-by-Step Setup

**1. Install Plugin**
```bash
cd wp-content/plugins/
git clone https://github.com/gaugefreedom/gauge-freedom-journal-wp.git
```

**2. Activate**
- Go to WordPress Admin → Plugins
- Activate "Gauge Freedom Journal"
- Plugin automatically creates database tables and user roles

**3. Create Pages**

| Page Slug | Shortcode | Purpose |
|-----------|-----------|---------|
| `/dashboard/` | `[gfj_dashboard]` | User dashboard (role-based) |
| `/submit-manuscript/` | `[gfj_submit_form]` | Submission form |

**4. Configure WordPress Registration**

The plugin uses WordPress's built-in registration with custom fields:
- Go to Settings → General → Enable "Anyone can register"
- Registration URL: `yourdomain.com/wp-login.php?action=register`
- New users choose role: Author or Reviewer
- Email verification required (password reset link)

**5. Optional: Customize Login Logo**

Option A - Via WordPress Customizer:
- Appearance → Customize → Site Identity → Upload Logo

Option B - Set URL directly:
```php
update_option('gfj_login_logo_url', 'https://yoursite.com/logo.png');
```

**6. Create Editorial Team**

Create user accounts with these roles:
- **Journal Editor** (`gfj_editor`) - Triage and assign reviewers
- **Editor in Chief** (`gfj_eic`) - Full editorial control
- **Managing Editor** (`gfj_managing_editor`) - Operational oversight

**Done!** Authors and reviewers can now self-register.

---

## 📖 Usage Guide

### For Authors

**Submit Manuscript:**
1. Register at `/wp-login.php?action=register` (choose "Author")
2. Verify email and set password
3. Login and go to "Submit Manuscript"
4. Fill form and upload files:
   - Blinded PDF (remove ALL author info)
   - Full PDF (complete manuscript)
   - LaTeX sources (ZIP)
   - Code/data repository links
5. Submit and track in dashboard

**Respond to Revisions:**
1. Receive email notification
2. Login to dashboard
3. Click "Upload Revision"
4. Upload revised files + response to reviewers
5. Submit

### For Reviewers

**Accept Invitation:**
1. Receive email with manuscript title/abstract
2. Login to dashboard
3. Click "Accept" or "Decline"
4. If accepted, click "Submit Review"

**Submit Review:**
1. Download and read blinded manuscript
2. Score on 6 criteria (1-5)
3. Write comments for author (shared) and editor (confidential)
4. Choose recommendation: Accept / Minor Revision / Major Revision / Reject
5. Submit before deadline (21 days)

**Re-Reviews (Revised Manuscripts):**
- Receive email: "Re-Review Invitation (Revised Manuscript)"
- See author's response to your previous review
- Review form shows your previous comments
- Assess if author adequately addressed concerns

### For Editors

**Triage Queue:**
1. View new submissions in dashboard
2. Click manuscript title
3. Review: title, abstract, keywords, cover letter
4. **Note:** Author info is hidden during triage
5. Make decision:
   - **Send to Review** - Approve for peer review
   - **Request Changes** - Ask author to revise before review
   - **Desk Reject** - Reject without review

**Assign Reviewers:**
1. After triage approval, full manuscript unlocked
2. Scroll to "Reviewer Assignment" section
3. Select reviewer from dropdown → Click "Invite"
4. Repeat for 2-3 reviewers
5. Track status in "Assigned Reviewers" table

**View Reviews:**
1. When reviewer submits, you receive email
2. Open manuscript edit page
3. Click "View Review" button
4. Modal shows:
   - Reviewer name (hidden from author)
   - Scores and recommendation
   - Comments to editor (confidential)
   - Comments for author (shared)
   - **Decision buttons** (see below)

**Make Editorial Decision:**
1. After viewing review, use decision buttons in modal:
   - ✅ Accept Manuscript
   - 📝 Request Minor Revisions
   - 📝 Request Major Revisions
   - ❌ Reject Manuscript
2. Write comments for author
3. Confirm decision
4. Author receives email notification

**Handle Revisions:**
1. Author uploads revision → You receive email
2. Open manuscript edit page
3. See revision notes at top (blue box)
4. **Triage revisions:** Make new triage decision (still anonymous)
5. **Review revisions:** Re-invite same reviewers or make decision directly

---

## 🔐 Security Features

- **SQL Injection:** All queries use `$wpdb->prepare()`
- **XSS Protection:** All outputs escaped (`esc_html`, `esc_url`)
- **CSRF Protection:** Nonce verification on all AJAX actions
- **Authorization:** Role-based permission checks everywhere
- **File Upload Security:** Type validation, size limits, malware scanning ready
- **Email Safety:** Null checks prevent crashes from deleted users


---

## 🔧 Customization

### Add Custom Workflow Stages

Edit `includes/post-types/class-manuscript.php`:

```php
'stages' => [
    'triage' => 'Initial Review',
    'review' => 'Peer Review',
    'revision' => 'Author Revision',
    'accepted' => 'Accepted',
    'copyediting' => 'Copyediting',  // ADD NEW STAGE
    'published' => 'Published',
]
```

### Modify Email Templates

Use WordPress filters:

```php
add_filter('wp_new_user_notification_email', function($email, $user) {
    // Customize new user email
    return $email;
}, 10, 2);
```

### Change Review Deadline

Default is 21 days. Modify in `includes/handlers/class-ajax-handler.php`:

```php
$due_date = date('Y-m-d H:i:s', strtotime('+30 days')); // Change to 30 days
```

---

## 📊 Roadmap

### v1.1.0 - Quality of Life (Q1 2025)
**Focus: Improve editor/reviewer experience**

- [ ] **Activity Logging** - Audit trail for all editorial actions
- [ ] **Email Queue System** - Retry failed emails, track delivery
- [ ] **Bulk Operations** - Assign multiple reviewers at once
- [ ] **Dashboard Widgets** - "Awaiting Triage" / "Pending Reviews" counts
- [ ] **Manuscript Search** - Filter by stage, author, keyword
- [ ] **Reviewer Database** - Track expertise, availability, performance

### v1.2.0 - Advanced Features (Q2 2025)
**Focus: Automation and integrations**

- [ ] **AI Co-Editor** - Optional AI review summaries (Claude API)
- [ ] **CAR Verification** - Validate computational reproducibility receipts
- [ ] **Advanced Analytics** - Review time metrics, acceptance rates
- [ ] **JATS XML Export** - Standard format for indexing services
- [ ] **Automated Reminders** - Email reviewers before deadlines
- [ ] **Reviewer Matching** - AI-suggested reviewers by expertise

### v2.0.0 - Publication Platform (Q3 2025)
**Focus: Full journal management**

- [ ] **Public Article Archive** - Browse published papers
- [ ] **DOI Integration** - Automatic DOI registration via Crossref
- [ ] **Article Versioning** - Track preprints, revisions, final versions
- [ ] **Citation Tracking** - Link to Crossref, Semantic Scholar
- [ ] **Multi-Journal Support** - Run multiple journals from one install
- [ ] **REST API** - Integrate with external systems

### v3.0.0 - Enterprise (Q4 2025)
**Focus: Scalability and internationalization**

- [ ] **Multi-Language Support** - i18n for global journals
- [ ] **Mobile Admin App** - iOS/Android editor interface
- [ ] **Advanced Permissions** - Fine-grained role customization
- [ ] **Performance Optimization** - Handle 10k+ manuscripts
- [ ] **White Label** - Rebrand for other journals
- [ ] **SaaS Mode** - Multi-tenant hosting support

[View Detailed Roadmap](ROADMAP.md)

---

## 🤝 Contributing

We welcome contributions! This is open source software built for the academic community.

### How to Contribute

1. **Report Bugs:** [Open an issue](https://github.com/gaugefreedom/gauge-freedom-journal-wp/issues)
2. **Suggest Features:** Describe your use case
3. **Submit Code:**
   ```bash
   git checkout -b feature/your-feature
   git commit -am "Add amazing feature"
   git push origin feature/your-feature
   # Open pull request
   ```

### Development Guidelines

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Add comments for complex logic
- Test on WordPress 5.8+ and PHP 7.4+
- Update documentation for new features
- Security: Never trust user input, always validate/sanitize

### Code Structure

```
gauge-freedom-journal-wp/
├── includes/
│   ├── class-gfj.php                 # Main plugin class
│   ├── class-gfj-activator.php       # Database setup
│   ├── handlers/
│   │   ├── class-ajax-handler.php    # All AJAX endpoints
│   │   └── class-metabox-handler.php # Save manuscript data
│   ├── post-types/
│   │   └── class-manuscript.php      # Manuscript CPT
│   └── roles/
│       └── class-gfj-roles.php       # User roles & capabilities
├── public/
│   ├── partials/                     # Frontend templates
│   └── class-gfj-public.php          # Public-facing functionality
├── assets/
│   ├── css/                          # Stylesheets
│   └── js/                           # JavaScript
└── README.md                         # This file
```

### Public Article Type Labels

Published `gfj_article` posts use `_gfj_article_type` for the public article-type label. Missing or invalid values fall back to `Research Article`; `gfj_topic` remains a subject/topic taxonomy.

---

## 📝 License

**GNU General Public License v3.0**

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

[Full License Text](LICENSE)

---

## 🙏 Acknowledgments

Built with ❤️ for the academic community by [Gauge Freedom Journal](https://gaugefreedom.org).

**Special Thanks:**
- WordPress community for excellent documentation
- Claude AI for development assistance and code review
- Academic editors and reviewers who tested the system
- Open science advocates pushing for transparency

---

## 📞 Support

- **Documentation:** [Wiki](https://github.com/gaugefreedom/gauge-freedom-journal-wp/wiki)
- **Bug Reports:** [Issues](https://github.com/gaugefreedom/gauge-freedom-journal-wp/issues)
- **Discussions:** [GitHub Discussions](https://github.com/gaugefreedom/gauge-freedom-journal-wp/discussions)
- **Email:** support@gaugefreedom.org

---

## 📈 Stats

- **Production Status:** ✅ Ready
- **Security Audit:** ✅ Passed
- **Test Coverage:** End-to-end workflow validated
- **WordPress Compatibility:** 5.8+
- **PHP Compatibility:** 7.4+
- **Lines of Code:** ~5,000+
- **Active Installations:** 1 (Gauge Freedom Journal)
- **Open Source:** GPL v3.0

---

**Ready to revolutionize academic publishing?** 🚀

[Install Now](#installation--setup) | [View Demo](https://gaugefreedom.org) | [Contribute](#contributing)
