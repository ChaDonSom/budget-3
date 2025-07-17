# Vue Naming Conventions and Folder Rules

## File and Component Naming
- Use PascalCase for all component filenames and component names (e.g., `AccountList.vue`, `BaseButton.vue`).
- All component names must be multi-word to avoid conflicts with HTML elements.
- Prefix base UI components with `Base` (e.g., `BaseInput.vue`).
- Prefix single-instance components with `The` (e.g., `TheHeader.vue`).
- Prefix tightly coupled child components with their parent (e.g., `AccountListItem.vue`).
- For composables, use `useXyz` (e.g., `useAccounts.ts`).
- For utility files, use camelCase or kebab-case (e.g., `dateFormatter.ts`, `validators.ts`).

## Folder Structure Rules
- Place most components in a flat `components/` directory.
- Only create a new folder if 3 or more files share a prefix or are tightly related.
- For features/domains, group all related files (components, composables, store, tests) under a `features/` or `modules/` directory.
- Place composables in `composables/`, utilities in `utils/`, and route components in `views/`.
- Remove or merge folders with only 1–2 files unless they are feature roots.

## Test File Placement
- Place test files next to the code they test (e.g., `AccountList.vue` and `AccountList.spec.ts` in the same folder), or in a parallel `tests/` directory. Be consistent across the codebase.

---

These conventions should be referenced in CONTRIBUTING.md and followed for all new and refactored Vue code.
