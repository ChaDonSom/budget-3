# Implementation Guide: Vue.js Cleanup Plan

This guide provides step-by-step instructions for implementing the Vue.js codebase cleanup plan.

## Quick Start

### 1. Run Analysis Scripts
```bash
# Check for large components
npm run cleanup:large

# Check folder structure
npm run cleanup:folders

# Run both checks
npm run cleanup:check
```

### 2. Generate New Components
```bash
# Generate a new component
npm run cleanup:generate ComponentName src/path/to/component

# Example: Create a user profile component
npm run cleanup:generate UserProfile src/features/users/components
```

## Step-by-Step Implementation

### Phase 1: Fix ESLint Errors (Day 1-2)

1. **Run ESLint and fix automatic issues:**
   ```bash
   npm run lint
   ```

2. **Fix remaining manual issues:**
   - Add missing `v-bind:key` in v-for loops
   - Replace `Function` types with proper function signatures
   - Remove `@ts-ignore` comments and fix type issues
   - Fix prop mutation warnings

3. **Verify no errors remain:**
   ```bash
   npm run lint
   npm run type-check
   ```

### Phase 2: Folder Structure Cleanup (Day 3-4)

1. **Move single-file folders:**
   ```bash
   # Move dev-intro content
   mkdir -p src/components/dev
   mv src/dev-intro/HelloWorld.vue src/components/dev/
   rm -rf src/dev-intro

   # Move switches to fields
   mv src/core/switches/MdcSwitch.vue src/core/fields/
   rm -rf src/core/switches

   # Move budget/users content
   mv src/budget/users/SearchForUserTextfield.vue src/core/fields/
   rm -rf src/budget

   # Move Profile to auth
   mv src/core/users/Profile.vue src/core/users/auth/
   ```

2. **Update imports across codebase:**
   - Search and replace old import paths
   - Update TypeScript path mappings if needed

3. **Verify build still works:**
   ```bash
   npm run build
   ```

### Phase 3: Component Splitting (Day 5-10)

#### Priority Order (Largest first):

1. **Home.vue (1,308 lines)**
   ```bash
   # Create new structure
   mkdir -p src/home/components
   mkdir -p src/home/composables
   mkdir -p src/home/types
   
   # Generate components
   npm run cleanup:generate BudgetTable src/home/components
   npm run cleanup:generate BudgetTableRow src/home/components
   npm run cleanup:generate BudgetTableHeader src/home/components
   npm run cleanup:generate WelcomeSection src/home/components
   npm run cleanup:generate ActionButtons src/home/components
   ```

2. **BatchUpdate.vue (624 lines)**
   ```bash
   mkdir -p src/batchUpdates/components
   npm run cleanup:generate BatchUpdateForm src/batchUpdates/components
   npm run cleanup:generate BatchUpdatePreview src/batchUpdates/components
   ```

3. **Continue with other large files...**

### Phase 4: Code Organization (Day 11-12)

1. **Standardize script blocks:**
   - Follow the organization template from docs/examples/after/
   - Group imports properly
   - Add section comments
   - Extract composables where appropriate

2. **Update templates:**
   - Add semantic HTML structure
   - Use proper component hierarchy
   - Add CSS classes following naming conventions

### Phase 5: Validation (Day 13-14)

1. **Run all checks:**
   ```bash
   npm run cleanup:check
   npm run lint
   npm run type-check
   npm run build
   npm run test:unit
   ```

2. **Manual testing:**
   - Test key user flows
   - Verify no functionality is broken
   - Check responsive design
   - Test form submissions

## Best Practices During Implementation

### 1. Work in Small Batches
- Never refactor more than 2-3 files at once
- Test after each change
- Commit frequently with descriptive messages

### 2. Keep Tests Passing
- Run tests after each major change
- If tests break, fix them immediately
- Don't skip broken tests

### 3. Update Documentation
- Update component README files
- Document new composables
- Update type definitions

### 4. Use Type Safety
- Always use TypeScript interfaces
- Avoid `any` types
- Add proper prop and emit typing

## Common Pitfalls to Avoid

### 1. Don't Delete Working Code
- Always copy/move code, don't rewrite from scratch
- Keep the same component interfaces
- Preserve existing CSS classes users might depend on

### 2. Watch for Circular Dependencies
- Be careful when extracting composables
- Test import paths thoroughly
- Use proper module boundaries

### 3. Maintain Backwards Compatibility
- Don't change public component APIs
- Keep the same prop names and types
- Preserve existing events

## Troubleshooting

### Build Errors
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Check TypeScript config
npm run type-check

# Check for syntax errors
npm run lint
```

### Import Errors
```bash
# Check if files exist
ls -la src/path/to/component

# Update path mappings in tsconfig.json
# Check for typos in import statements
```

### Runtime Errors
```bash
# Check browser console for errors
# Test in development mode
npm run dev

# Check network tab for failed requests
```

## Success Criteria

### Automated Checks ✅
- [ ] All files under 150 lines (`npm run cleanup:large`)
- [ ] No ESLint errors (`npm run lint`)
- [ ] No TypeScript errors (`npm run type-check`)
- [ ] Build succeeds (`npm run build`)
- [ ] All tests pass (`npm run test:unit`)

### Manual Verification ✅
- [ ] All major user flows work
- [ ] No console errors in browser
- [ ] Responsive design intact
- [ ] Forms submit correctly
- [ ] Navigation works properly

### Code Quality ✅
- [ ] Consistent code organization
- [ ] Proper TypeScript typing
- [ ] Logical folder structure
- [ ] Clear separation of concerns
- [ ] Reusable composables extracted

## Maintenance

After implementation, use these practices to maintain code quality:

1. **Pre-commit hooks:**
   ```bash
   npm install --save-dev husky lint-staged
   npx husky add .husky/pre-commit "npx lint-staged"
   ```

2. **Regular checks:**
   - Run `npm run cleanup:check` weekly
   - Review new components for size limits
   - Enforce standards in code reviews

3. **Documentation updates:**
   - Keep the style guide current
   - Update examples when patterns change
   - Document new composables and utilities

This implementation guide ensures a smooth transition to the new codebase organization while maintaining functionality and code quality.