# Contributing to Somero Budget V3

## Vue Code Standards

- **File/Folder Structure:**
  - Follow the "3+ prefix" rule for folders (see `docs/vue-naming-conventions.md`).
  - Use a flat `components/` directory unless 3+ files share a prefix.
  - Group feature/domain files under `features/` or `modules/`.
- **Within-File Organization:**
  - Follow the SFC organization standard in `docs/vue-sfc-organization.md`.
- **Naming Conventions:**
  - Use PascalCase for components, multi-word names, and proper prefixes (see `docs/vue-naming-conventions.md`).
- **Component Size:**
  - Keep components ≤150 lines. Split into subcomponents or composables if needed.
- **Testing:**
  - Place test files next to the code they test or in a parallel `tests/` directory.

## Tooling & Automation

- **Linting:**
  - Run `npm run lint` before committing.
- **Formatting:**
  - Run `npm run format` to auto-format code.
- **Component Size Check:**
  - Run `npm run check-vue-lines` to ensure no Vue component exceeds 150 lines.
- **Pre-commit Hook:**
  - Husky will block commits if lint or size checks fail.

## References
- [docs/vue-sfc-organization.md](docs/vue-sfc-organization.md)
- [docs/vue-naming-conventions.md](docs/vue-naming-conventions.md)

---

Thank you for helping keep the codebase clean, consistent, and maintainable!
