# Vue.js Codebase Cleanup Plan

## Executive Summary

This document outlines a comprehensive plan to clean up the Vue.js codebase without changing existing functionality. The current codebase has 42 Vue files with inconsistent organization patterns, several files exceeding the 150-line limit, and 34 ESLint errors.

## Current State Analysis

### File Size Issues
- **Home.vue**: 1,308 lines (requires major refactoring)
- **BatchUpdate.vue**: 624 lines (4x over limit)
- **Template.vue**: 427 lines (3x over limit)
- **Circular.vue**: 310 lines (2x over limit)
- **History.vue**: 263 lines (2x over limit)
- **AccountModal.vue**: 208 lines (exceeds limit)
- **App.vue**: 204 lines (exceeds limit)

### Folder Structure Analysis
Current folders violating the "3+ files" rule:
- `dev-intro/` (1 Vue file)
- `budget/users/` (1 Vue file)
- `core/switches/` (1 Vue file)
- `core/users/` (1 Vue file)

Folders meeting the standard:
- `core/buttons/` (5 files)
- `core/fields/` (5 files)
- `core/tables/` (5 files)
- `core/snackbars/` (3 files)
- `core/users/auth/` (3 files)
- `batchUpdates/` (3 files)
- `home/` (3 files)

### Code Quality Issues
- 34 ESLint errors across multiple files
- Inconsistent code organization within Vue files
- Missing TypeScript type safety in several areas
- Inconsistent import/export patterns

## Part 1: Vue File Architecture Standards

### 1.1 File Naming Convention
```
PascalCase.vue (already implemented correctly)
```

### 1.2 File Size Limits
- **Maximum 150 lines per Vue file**
- Split larger components into:
  - Smaller sub-components
  - Composable functions
  - Separate utility files

### 1.3 File Structure Template
```vue
<template>
  <!-- Template should be as minimal as possible -->
  <!-- Use sub-components to break down complex templates -->
</template>

<script setup lang="ts">
// 1. Imports (grouped and sorted)
//    - Vue imports first
//    - Third-party imports
//    - Local imports (@ prefixed)
//    - Relative imports

// 2. Props definition
// 3. Emits definition
// 4. Composables
// 5. Reactive data
// 6. Computed properties
// 7. Watch functions
// 8. Methods
// 9. Lifecycle hooks
</script>

<style scoped lang="scss">
// Component-specific styles only
// Global styles should be in separate files
</style>
```

## Part 2: Within-File Organization Standards

### 2.1 Script Block Organization (Composition API)

```typescript
<script setup lang="ts">
// ==========================================
// IMPORTS
// ==========================================
// Vue core imports
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// Third-party library imports
import axios from 'axios'
import { DateTime } from 'luxon'

// Local imports - stores
import { useAuth } from '@/core/users/auth'
import { useModals } from '@/store/modals'

// Local imports - components
import Button from '@/core/buttons/Button.vue'
import Modal from '@/core/modals/Modal.vue'

// Local imports - utilities
import { dollars } from '@/core/utilities/currency'

// Relative imports
import './styles.scss'

// ==========================================
// COMPONENT DEFINITION
// ==========================================
// Props
interface Props {
  modelValue: string
  disabled?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  disabled: false
})

// Emits
interface Emits {
  'update:modelValue': [value: string]
  'submit': [event: Event]
}
const emit = defineEmits<Emits>()

// ==========================================
// COMPOSABLES
// ==========================================
const route = useRoute()
const router = useRouter()
const auth = useAuth()
const modals = useModals()

// ==========================================
// REACTIVE DATA
// ==========================================
const loading = ref(false)
const form = ref({
  name: '',
  email: ''
})

// ==========================================
// COMPUTED PROPERTIES
// ==========================================
const isValid = computed(() => {
  return form.value.name && form.value.email
})

const displayName = computed(() => {
  return `${form.value.name} (${form.value.email})`
})

// ==========================================
// WATCHERS
// ==========================================
watch(() => props.modelValue, (newValue) => {
  // Handle prop changes
})

// ==========================================
// METHODS
// ==========================================
function handleSubmit() {
  // Method implementation
}

function resetForm() {
  // Method implementation
}

// ==========================================
// LIFECYCLE HOOKS
// ==========================================
onMounted(() => {
  // Initialization logic
})
</script>
```

### 2.2 Template Organization

```vue
<template>
  <!-- 
    Templates should be organized hierarchically:
    1. Main container
    2. Header/title section
    3. Content sections (logical grouping)
    4. Actions/footer
    5. Modals/overlays
  -->
  <div class="component-container">
    <!-- Header -->
    <header class="component-header">
      <h1>{{ title }}</h1>
    </header>

    <!-- Main content -->
    <main class="component-content">
      <!-- Content sections organized logically -->
      <section class="form-section">
        <!-- Form elements -->
      </section>
      
      <section class="data-section">
        <!-- Data display -->
      </section>
    </main>

    <!-- Actions -->
    <footer class="component-actions">
      <Button @click="save">Save</Button>
      <Button @click="cancel">Cancel</Button>
    </footer>
  </div>

  <!-- Teleported elements (modals, overlays) -->
  <Teleport to="body">
    <Modal v-if="showModal" @close="closeModal">
      <!-- Modal content -->
    </Modal>
  </Teleport>
</template>
```

## Part 3: Component Splitting Strategy

### 3.1 Home.vue Refactoring Plan (1,308 lines → ~150 lines)

**Current issues:**
- Massive template with complex table logic
- Large script block mixing concerns
- Complex sorting logic embedded in component

**Proposed split:**
```
src/home/
├── Home.vue (main component, ~150 lines)
├── components/
│   ├── BudgetTable.vue (~150 lines)
│   ├── BudgetTableRow.vue (~100 lines)
│   ├── BudgetTableHeader.vue (~80 lines)
│   ├── BudgetTableFooter.vue (~100 lines)
│   ├── WelcomeSection.vue (~50 lines)
│   └── ActionButtons.vue (~60 lines)
├── composables/
│   ├── useBudgetSorting.ts
│   ├── useBudgetCalculations.ts
│   └── useBudgetActions.ts
└── types/
    └── BudgetTypes.ts
```

### 3.2 BatchUpdate.vue Refactoring Plan (624 lines → ~150 lines)

**Proposed split:**
```
src/batchUpdates/
├── BatchUpdate.vue (main component)
├── components/
│   ├── BatchUpdateForm.vue
│   ├── BatchUpdatePreview.vue
│   └── BatchUpdateHistory.vue
└── composables/
    ├── useBatchUpdateForm.ts
    └── useBatchUpdateValidation.ts
```

### 3.3 General Splitting Rules

1. **When to split a component:**
   - Exceeds 150 lines
   - Has multiple distinct responsibilities
   - Template has deeply nested sections
   - Script has multiple concerns

2. **How to identify split boundaries:**
   - Logical sections in template
   - Separate data concerns
   - Reusable functionality
   - Different interaction patterns

## Part 4: Folder Structure Optimization

### 4.1 Current Structure Issues

**Folders to consolidate (< 3 files):**
- `dev-intro/HelloWorld.vue` → Move to `src/components/dev/`
- `budget/users/SearchForUserTextfield.vue` → Move to `src/core/fields/`
- `core/switches/MdcSwitch.vue` → Move to `src/core/fields/`
- `core/users/Profile.vue` → Move to `src/core/users/auth/`

### 4.2 Proposed New Structure

```
src/
├── components/              # Shared/general components
│   ├── dev/                # Development-only components
│   └── layout/             # Layout components
├── features/               # Feature-based organization
│   ├── accounts/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── types/
│   │   └── index.ts
│   ├── batchUpdates/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── types/
│   │   └── index.ts
│   └── home/
│       ├── components/
│       ├── composables/
│       ├── types/
│       └── index.ts
├── core/                   # Core UI components and utilities
│   ├── components/         # Basic UI components
│   │   ├── buttons/
│   │   ├── fields/
│   │   ├── modals/
│   │   ├── tables/
│   │   └── navigation/
│   ├── composables/        # Shared composables
│   ├── utilities/          # Utility functions
│   └── types/              # Shared types
└── shared/                 # Shared resources
    ├── styles/
    ├── assets/
    └── constants/
```

### 4.3 Migration Rules

1. **3+ files minimum** for new folders
2. **Feature-based organization** for business logic
3. **Core components** remain in `src/core/`
4. **Shared resources** in `src/shared/`

## Part 5: Automated Tooling and Scripts

### 5.1 ESLint Configuration Enhancement

```javascript
// .eslintrc.cjs additions
module.exports = {
  // ... existing config
  rules: {
    // ... existing rules
    
    // Vue-specific rules for organization
    'vue/component-definition-name-casing': ['error', 'PascalCase'],
    'vue/component-name-in-template-casing': ['error', 'PascalCase'],
    'vue/order-in-components': ['error', {
      order: [
        'el',
        'name',
        'key',
        'parent',
        'functional',
        ['delimiters', 'comments'],
        ['components', 'directives', 'filters'],
        'extends',
        'mixins',
        ['provide', 'inject'],
        'ROUTER_GUARDS',
        'layout',
        'middleware',
        'validate',
        'scrollToTop',
        'transition',
        'loading',
        'inheritAttrs',
        'model',
        ['props', 'propsData'],
        'emits',
        'setup',
        'data',
        'computed',
        'watch',
        'LIFECYCLE_HOOKS',
        'methods',
        ['template', 'render'],
        'renderError'
      ]
    }],
    'vue/max-lines-per-file': ['error', {
      max: 150,
      skipBlankLines: true,
      skipComments: true
    }]
  }
}
```

### 5.2 File Splitting Script

```bash
#!/bin/bash
# scripts/split-large-components.sh

echo "Finding Vue files exceeding 150 lines..."
find src -name "*.vue" -exec wc -l {} + | awk '$1 > 150 { print $2 " (" $1 " lines)" }' | sort -nr

echo "Run this script to identify files that need splitting"
```

### 5.3 Component Template Generator

```bash
#!/bin/bash
# scripts/generate-component.sh

COMPONENT_NAME=$1
COMPONENT_PATH=$2

mkdir -p "$COMPONENT_PATH"

cat > "$COMPONENT_PATH/$COMPONENT_NAME.vue" << EOF
<template>
  <div class="${COMPONENT_NAME,,}-container">
    <!-- TODO: Implement template -->
  </div>
</template>

<script setup lang="ts">
// ==========================================
// IMPORTS
// ==========================================
import { ref } from 'vue'

// ==========================================
// COMPONENT DEFINITION
// ==========================================
interface Props {
  // TODO: Define props
}
const props = defineProps<Props>()

interface Emits {
  // TODO: Define emits
}
const emit = defineEmits<Emits>()

// ==========================================
// REACTIVE DATA
// ==========================================

// ==========================================
// COMPUTED PROPERTIES
// ==========================================

// ==========================================
// METHODS
// ==========================================

// ==========================================
// LIFECYCLE HOOKS
// ==========================================
</script>

<style scoped lang="scss">
.${COMPONENT_NAME,,}-container {
  // TODO: Component styles
}
</style>
EOF

echo "Created $COMPONENT_PATH/$COMPONENT_NAME.vue"
```

## Part 6: Implementation Roadmap

### Phase 1: Foundation (Week 1)
- [ ] Fix all existing ESLint errors (34 issues)
- [ ] Update ESLint configuration with new rules
- [ ] Create component template generator script
- [ ] Document coding standards

### Phase 2: Folder Structure (Week 2)
- [ ] Consolidate single-file folders
- [ ] Reorganize into feature-based structure
- [ ] Update import paths across codebase
- [ ] Update TypeScript path mappings

### Phase 3: Large Component Splitting (Weeks 3-4)
- [ ] Split Home.vue into sub-components
- [ ] Split BatchUpdate.vue
- [ ] Split Template.vue
- [ ] Split other components exceeding 150 lines

### Phase 4: Code Organization (Week 5)
- [ ] Standardize script block organization
- [ ] Extract composables from large components
- [ ] Improve TypeScript typing
- [ ] Optimize imports

### Phase 5: Validation and Documentation (Week 6)
- [ ] Run comprehensive testing
- [ ] Update documentation
- [ ] Create style guide examples
- [ ] Set up automated checks

## Part 7: Success Metrics

### Quantitative Goals
- All Vue files under 150 lines
- Zero ESLint errors
- Folder structure follows 3+ file rule
- All components follow organizational template

### Qualitative Goals
- Improved developer experience
- Better code maintainability
- Consistent patterns across codebase
- Clear separation of concerns

## Part 8: Risk Mitigation

### Potential Risks
1. **Breaking functionality during refactoring**
   - Mitigation: Comprehensive testing at each step
   - Keep existing tests passing

2. **Import path conflicts during reorganization**
   - Mitigation: Use TypeScript path mapping
   - Update in small batches

3. **Developer adoption of new standards**
   - Mitigation: Clear documentation and examples
   - Automated linting enforcement

### Testing Strategy
- Run existing test suite after each change
- Manual testing of key user flows
- TypeScript compilation verification
- ESLint validation

## Part 9: Tools and Automation

### Recommended Tools
1. **ESLint plugins:**
   - `eslint-plugin-vue`
   - `@vue/eslint-config-typescript`
   - `eslint-plugin-import` (for import organization)

2. **VS Code extensions:**
   - Vue Language Features (Volar)
   - ESLint
   - Prettier
   - TypeScript Hero (import organization)

3. **Build tools:**
   - Vite (already configured)
   - TypeScript compiler
   - Vue SFC compiler

### Pre-commit Hooks
```json
{
  "husky": {
    "hooks": {
      "pre-commit": "lint-staged"
    }
  },
  "lint-staged": {
    "*.{vue,ts,js}": [
      "eslint --fix",
      "prettier --write"
    ]
  }
}
```

This comprehensive plan provides a structured approach to cleaning up the Vue.js codebase while maintaining functionality and improving developer experience.