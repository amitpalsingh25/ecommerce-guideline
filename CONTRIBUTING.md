# Maintaining these guides

These docs describe how the platform works. When the platform changes, **update the docs in the same
spirit** — extend, don't erase.

## Golden rules

1. **Amend, don't destroy.** When a feature changes, edit the relevant section in place and add new
   subsections for new behaviour. Don't delete history-worthy context just because a screen moved.
2. **One topic per file.** Each admin section has its own file under `docs/`. Add a **new file** for a
   genuinely new area rather than overloading an existing one. Then add it to the README index table.
3. **Keep it brand-neutral.** No business name, address, phone, email, or domain in these docs — describe
   the *platform*, not one deployment.
4. **Never commit secrets.** No credentials, API keys, tokens, DB details, or `credentials.txt` contents.
   If an example needs a key, use an obvious placeholder (`sk_…`, `whsec_…`).
5. **Match reality.** Document what the code actually does. If unsure, read the relevant admin function
   before writing.

## How to add or change a guide

1. Edit the file under `docs/` (or create a new `docs/<area>.md`).
2. If new, add a row to the **Contents** table in [`README.md`](README.md).
3. Keep the existing structure: a short intro, the path, then tables/sections for fields and behaviour.
4. Commit with a clear message, e.g. `docs: update media gallery — add bulk delete`.

## Suggested commit style

```
docs: <area> — <what changed>
```
Examples:
- `docs: products — document the new bulk import`
- `docs: settings — add the shipping section`
- `docs: add returns-and-refunds guide`

## Versioning note

If a change is large or breaking, add a short **"Changed in <date>"** note at the top of the affected
section instead of removing the old description outright. That keeps the guide useful for anyone on an
older build.

## File map

See [`README.md`](README.md) for the full, current index of guides. Keep that table in sync whenever you
add or rename a file.
