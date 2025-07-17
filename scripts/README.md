# Vue.js Cleanup Scripts

This directory contains automation scripts to help maintain Vue.js code organization standards.

## Scripts Overview

### 🔍 find-large-components.sh
Identifies Vue components exceeding the 150-line limit.

```bash
# Run directly
./scripts/find-large-components.sh

# Run via npm
npm run cleanup:large
```

**Output:**
- Lists files over 150 lines
- Shows how many lines over the limit
- Returns exit code 1 if violations found (useful for CI)

### 📁 check-folder-structure.sh
Validates folder structure against the "3+ files" rule.

```bash
# Run directly
./scripts/check-folder-structure.sh

# Run via npm
npm run cleanup:folders
```

**Output:**
- Lists folders with fewer than 3 Vue files
- Suggests consolidation strategies
- Shows compliant folders for reference

### 🏗️ generate-component.sh
Creates new Vue components following the standard template.

```bash
# Usage
./scripts/generate-component.sh <ComponentName> [path]

# Examples
./scripts/generate-component.sh UserProfile src/features/users/components
./scripts/generate-component.sh DataTable src/core/components/tables

# Via npm (requires manual path)
npm run cleanup:generate UserProfile src/components
```

**Creates:**
- Vue component with standard organization
- Properly structured script, template, and style blocks
- TypeScript interfaces for props and emits
- Basic test file (if in components directory)

## NPM Script Integration

Add these to your development workflow:

```bash
# Check all cleanup requirements
npm run cleanup:check

# Individual checks
npm run cleanup:large
npm run cleanup:folders

# Generate new components
npm run cleanup:generate ComponentName path/to/location
```

## CI Integration

Add to your GitHub Actions or other CI pipeline:

```yaml
- name: Check Vue.js code organization
  run: |
    npm run cleanup:check
    npm run lint
    npm run type-check
```

## Pre-commit Hook Integration

With husky and lint-staged:

```json
{
  "lint-staged": {
    "src/**/*.vue": [
      "scripts/find-large-components.sh",
      "eslint --fix"
    ]
  }
}
```

## Exit Codes

- **0**: All checks pass
- **1**: Violations found (for CI integration)

## Dependencies

Scripts require:
- bash
- find
- wc
- awk
- Basic UNIX utilities

All scripts are compatible with Linux, macOS, and Windows (via WSL/Git Bash).