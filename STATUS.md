# STATUS

- Public `gfj_article` type labels are now intended to come from `_gfj_article_type`, with invalid or missing values defaulting to `Research Article`.
- `gfj_topic`/Topics is a subject taxonomy for articles and should not be used as the article-type control.
- Publish flow should prefer `\articlecategory{...}` from LaTeX sources when available, then fall back to the manuscript type taxonomy.
- Audited against `/tmp/gauge-freedom-journal-wp`; relative to that online copy, local deploy-relevant differences are the article-type patch plus memory/docs files.
- Emergency metabox audit found `gfj_article` slug and meta keys intact; Article Metadata registration is now hardened through `add_meta_boxes_gfj_article`.
