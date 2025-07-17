# Vue Single-File Component (SFC) Organization Standard

## Section Order
1. `<script setup lang="ts">` (or `<script lang="ts">`)
2. `<template>`
3. `<style scoped>`

## Inside `<script>` (Composition API)
1. Imports
2. Define props/emits
4. For each direct sub-domain of the component:
    1. Refs/reactive state
    2. Computed properties
    3. Watchers
    4. Methods/functions
    5. Lifecycle hooks
    6. Exports/returns

## `<script>` (Options API) should be considered legacy and converted to Composition API

## Style
- Use `<style scoped>` or CSS Modules.
- Place global styles in `assets/styles/`.

## Additional Notes
- Keep templates simple; move logic to computed/methods.
- Split components >150 lines into smaller subcomponents or composables.
- Use PascalCase for file and component names.
- Document props and emits with JSDoc or TypeScript types.

---

This standard should be referenced in CONTRIBUTING.md and followed for all new and refactored Vue components.
