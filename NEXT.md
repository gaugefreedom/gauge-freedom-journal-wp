# NEXT

- Verify the article-type dropdown on a local WordPress install by editing a `gfj_article` and confirming the public header/card label changes.
- After deploying the metabox fix ZIP, open an existing Article edit screen and confirm the Article Metadata box shows DOI, author display, file URLs, publication fields, overrides, key findings, AI disclosure, and Article Type.
- If Article metadata is blank after deleting an old plugin copy, restore the database from a backup taken before that deletion; do not attempt manual database reconstruction unless no backup exists.
- Check whether `wp_dwjiwi_gfj_reviews`, `wp_dwjiwi_gfj_decisions`, and `wp_dwjiwi_gfj_ai_reviews` exist before relying on reviewer/editor dashboards; restore from backup if review history is needed.
- Test publishing a manuscript with a LaTeX ZIP containing `\articlecategory{TECHNICAL NOTE}` and confirm `_gfj_article_type` stores `TECHNICAL NOTE`.
- For live Article 005, update the Article Type field in WP admin after deploying the plugin patch; do not edit the database directly.
