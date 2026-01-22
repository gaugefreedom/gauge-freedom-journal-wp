
**Role:** You are a Senior WordPress Plugin Developer specializing in academic publishing systems and custom post types.

**Context:**
We have an existing plugin (`gauge-freedom-journal-wp`) that handles the private submission and peer-review workflow. Currently, manuscripts are stored as a private Custom Post Type (CPT) called `gfj_manuscript`.
We need to implement the **"Public Publishing"** module. This involves creating a public-facing CPT (`gfj_article`) and a workflow to "promote" an accepted manuscript into a published article.

**Objective:**
Implement a new "Publication Module" that includes:

1. Registration of a public `gfj_article` CPT.
2. Automated injection of Google Scholar (Highwire Press) meta tags.
3. A custom single-template display for articles (Sidebar + Content).
4. An admin action to convert an "Accepted" manuscript into a "Published" article.

---

### **Technical Specification (The Spec)**

#### **1. Register Public CPT (`gfj_article`)**

* **File:** Create `includes/post-types/class-gfj-article.php`
* **Settings:**
* `public` => `true`
* `has_archive` => `articles`
* `rewrite` => `['slug' => 'article']`
* `supports` => `['title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields']`


* **Taxonomies:** Register a custom taxonomy `gfj_topic` (hierarchical) for organizing papers (e.g., "Quantum AI", "Gauge Theory").

#### **2. Meta Data Structure (Post Meta)**

The `gfj_article` needs to store specific academic data. Use standard WP Post Meta:

* `_gfj_doi` (The DOI string)
* `_gfj_pdf_url` (URL to the final PDF)
* `_gfj_artifacts_url` (URL to the ZIP bundle: CARs, Logs, etc.)
* `_gfj_significance` (The significance statement text)
* `_gfj_key_findings` (Serialized array or HTML list)
* `_gfj_ai_disclosure` (Text regarding AI use)
* `_gfj_publication_date` (Date string)
* `_gfj_source_manuscript_id` (ID of the original submission for reference)

#### **3. Frontend Template (Single Article)**

* **File:** `public/partials/single-gfj-article.php` (You will need to use the `single_template` filter to force this template for this CPT).
* **Layout Strategy:**
* **Header:** Title, Authors (linked to ORCID if avail), Dates (Received/Accepted/Published), DOI link.
* **Sidebar (Right or Left - Sticky):**
* Button: "Download Full PDF"
* Button: "Download Artifacts Bundle (CAR, IXIR, Logs, ...)" (Highlight color)
* Section: "How to Cite" (Toggle box with BibTeX/Text).


* **Main Content:**
* **Abstract** (Styled distinctly)
* **Significance Statement** (Boxed/Highlighted)
* **Key Findings** (Bulleted list)


* **Footer:** License (CC-BY 4.0), Conflict of Interest.



#### **4. SEO & Indexing (Highwire Press Tags)**

* **Hook:** `wp_head`
* **Logic:** If `is_singular('gfj_article')`, output the following `<meta>` tags dynamically based on post meta:
* `citation_title`
* `citation_author` (Loop through authors)
* `citation_publication_date`
* `citation_journal_title` ("Gauge Freedom Journal")
* `citation_pdf_url`
* `citation_doi`



#### **5. Workflow Automation (The "Publish" Button)**

* **Location:** Add a meta box or button in the `gfj_manuscript` edit screen (Admin) *only* visible when Status = "Accepted".
* **Action:** "Publish to Journal"
* **Logic:**
1. Create new `gfj_article` post.
2. Copy Title, Abstract, and Author data from `gfj_manuscript`.
3. Copy the `_gfj_final_pdf` attachment ID to the new article.
4. Update `gfj_manuscript` status to `published` (to prevent duplicates).
5. Redirect admin to the edit screen of the new `gfj_article`.

### 6. Upgrade the "Public Publishing" module to include Volume/Issue organization and a custom Archive page.

**1. Register Taxonomy (`gfj_issue`)**

* **File:** Update `includes/post-types/class-gfj-article.php`
* **Function:** `register_taxonomy`
* **Slug:** `gfj_issue`
* **Post Type:** `gfj_article`
* **Settings:** `hierarchical => true` (allows Parent Volumes > Child Issues), `public => true`, `show_admin_column => true`.
* **Labels:** "Volumes & Issues", "Volume", "Issue".

**2. Create the Archive Template**

* **File:** `public/partials/archive-gfj-article.php`
* **Logic:**
* Do NOT use the standard loop (which just lists posts by date).
* Instead, get all terms for `gfj_issue` (Volumes).
* Loop through each Volume (e.g., "Volume 1").
* Inside the Volume, run a `WP_Query` to get articles assigned to that Volume.
* **Fallback:** If an article has *no* volume assigned, list it at the top under "Preprints / Just Accepted".



**3. Styling (CSS)**

* **Container:** `max-width: 1200px; margin: 0 auto;`
* **Volume Header:** Distinct background color (e.g., `#f5f5f5`), bold text, border-bottom.
* **Article Card:**
* Display as a clean row.
* **Left:** Date & Article Type (e.g., "Review", "Letter").
* **Middle:** Title (H3, Blue Link), Authors (Italic), DOI (Small gray text).
* **Right:** "PDF" Icon/Button.



**4. Admin Column Update**

* In the Admin Dashboard list of Articles, add a column showing which "Volume/Issue" the paper belongs to, so the editor can see it at a glance.


---

### **Implementation Steps for Agent**

1. **Scaffold the CPT:** Create the class file for `gfj_article` and instantiate it in `class-gfj.php`.
2. **Create the Template:** Build the HTML/CSS structure for the single article view within `public/partials/`. Ensure it is responsive.
3. **Add Meta Tags:** Write the function to inject SEO tags in `public/class-gfj-public.php`.
4. **Build the Migrator:** Create a helper function in `includes/class-gfj-activator.php` (or a new helper class) that handles the data mapping from Manuscript -> Article.

**Constraints:**

* Use WordPress Coding Standards.
* Ensure all outputs are escaped (`esc_html`, `esc_url`).
* Do NOT use external dependencies (like bootstrap) if not already present; stick to the plugin's existing CSS structure.

**Start by generating the code for Step 1: Registering the CPT and Custom Fields.**

---

