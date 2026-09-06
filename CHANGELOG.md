# Changelog

All notable changes to `softrequired-for-filament` will be documented in this file.

## v0.1.0 - 2026-09-06

Initial release.

- `->softRequired()` / `->softRequired(warn: false)` field macro with closure support — the third field state between required and optional
- Live warning hint on empty soft-required fields (warning color, dark-mode safe)
- Incomplete saves: warning notification (default), confirm modal, or silent — configurable globally, per plugin, or per page
- `Completable` model trait: `incomplete()` / `complete()` scopes, `isComplete()`, `getIncompleteAttributes()`; field list introspected from the resource form, `protected array $completable` as override
- Automatic **Incomplete** table filter on resources with soft-required fields (config/macro opt-outs)
- Completion widget on the dashboard: every incomplete record with its missing fields as badges, inline **Complete** modal reusing the actual resource form fields, link to the full form; hidden when everything is complete. Tracks resource-less models (relation-manager territory) via `widget.models` + `$completable`
- The completion modal pulls in fields that the missing fields' validation rules depend on (`required_with`, `after`, `gt`, `same`, …) — recursively, in both directions — greyed out as context, so the modal validates exactly like the full form
- Tabs per model (with count badges) when several models have incomplete records; lists truncate at `widget.records_limit` with an "N more…" link to the pre-filtered table
- Per-resource incomplete stat above list tables (render hook, config opt-out)
- Zero migrations — completeness is always derived
- English + German translations
- `softrequired:uninstall` removes every published artifact and walks you through deregistering the plugin
