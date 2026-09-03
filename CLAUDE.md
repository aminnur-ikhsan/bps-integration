# Branch strategy

This repo uses three branch tiers:

- **`main`** — production. Only ever updated by merging `development` in, right before a server deploy.
- **`development`** — integration branch, "before deploy". Feature branches merge here first; this is what gets tested together before it's promoted to `main`.
- **`feat_xxx`** — one branch per change or feature (e.g. `feat_bps-schema-migration`). Branches off `development`, merges back into `development` when done. Never branch directly off `main` and never commit directly to `main` or `development`.

Flow: `feat_xxx` → PR/merge → `development` → (when ready to ship) → merge/PR → `main` → deploy.
