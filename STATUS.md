# STATUS

- Public `gfj_article` type labels are now intended to come from `_gfj_article_type`, with invalid or missing values defaulting to `Research Article`.
- `gfj_topic`/Topics is a subject taxonomy for articles and should not be used as the article-type control.
- Publish flow should prefer `\articlecategory{...}` from LaTeX sources when available, then fall back to the manuscript type taxonomy.
- Audited against `/tmp/gauge-freedom-journal-wp`; relative to that online copy, local deploy-relevant differences are the article-type patch plus memory/docs files.
- Emergency metabox audit found `gfj_article` slug and meta keys intact; Article Metadata registration is now hardened through `add_meta_boxes_gfj_article`.
- `uninstall.php` previously deleted all `_gfj_%` postmeta on plugin deletion, which includes article publication metadata; destructive uninstall is now guarded by `GFJ_ALLOW_DESTRUCTIVE_UNINSTALL`.
- Live recovery restored `wp_postmeta`; GFJ article metrics are `_gfj_metric_*` postmeta, while peer-review history depends on `gfj_reviews`, `gfj_decisions`, and `gfj_ai_reviews` tables.
- Article admin now includes configurable license label, license URL, and review/access status fields; article sidebars and archive cards render from article metadata with backward-compatible defaults.
