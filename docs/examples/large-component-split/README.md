# Component Splitting Example

This example shows how to split a large component (similar to Home.vue) into smaller, manageable pieces.

## Before: Large Component (300+ lines)

The original component had multiple responsibilities:
- User authentication logic
- Data table rendering
- Sorting and filtering logic
- Modal management
- Form handling

## After: Split into Multiple Components

### Main Component (UserDashboard.vue - ~100 lines)
- Orchestrates child components
- Manages high-level state
- Handles routing and navigation

### Child Components:
1. **WelcomeSection.vue** (~50 lines)
   - Welcome message
   - Basic user info

2. **UserDataTable.vue** (~120 lines)
   - Table rendering
   - Row interactions
   - Uses table composables

3. **UserTableActions.vue** (~80 lines)
   - Action buttons
   - Bulk operations

4. **UserModals.vue** (~100 lines)
   - Modal management
   - Form modals

### Composables:
1. **useUserTableSorting.ts**
   - Sorting logic
   - Filter logic

2. **useUserActions.ts**
   - CRUD operations
   - API calls

3. **useUserTableState.ts**
   - Table state management
   - Column visibility

## Benefits:
- Each component has a single responsibility
- Easier to test individual pieces
- Better code reusability
- Improved developer experience
- Easier maintenance and debugging