# Build spec — Event Logs

**Goal:** a lightweight system event log for troubleshooting.

## Route
- `GET /admin/logs` (filters) ; `POST /admin/logs` with `_action=clear` to empty (CSRF).

## Data — `logs`
id, level enum(info,warn,error), category, message, meta JSON, created_at.

## Build — logger
`log_event($category, $message, $level='info', $meta=[])` → insert a row (json-encode meta). Call it at
key points: enquiry received/saved, email sent/failed, order created/paid, stripe session/webhook,
contact submit, settings/product/hero saves, media edits, lazy migrations.

## Build — page
- Filters: level (info/warn/error) + category (dropdown of distinct categories).
- Table: Level badge, Category, Message (+meta line under it), Time. Newest first, LIMIT 300.
- "Clear logs" button (POST, CSRF) deletes all.

## Acceptance
- Actions across the app produce log rows; filters narrow by level/category; clear empties the table.

## Troubleshooting guidance (document for operators)
- Email issues → filter category=email / level=warn, then check SMTP settings.
- Payment issues → filter category=stripe/order to see webhook + signature activity.
