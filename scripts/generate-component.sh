#!/bin/bash
# scripts/generate-component.sh
# Generate a new Vue component following the standard template

COMPONENT_NAME=$1
COMPONENT_PATH=${2:-"src/components"}

if [ -z "$COMPONENT_NAME" ]; then
    echo "Usage: $0 <ComponentName> [path]"
    echo "Example: $0 UserProfile src/features/users/components"
    exit 1
fi

# Create directory if it doesn't exist
mkdir -p "$COMPONENT_PATH"

# Convert component name to kebab-case for CSS classes
KEBAB_CASE=$(echo "$COMPONENT_NAME" | sed 's/\([A-Z]\)/-\1/g' | sed 's/^-//' | tr '[:upper:]' '[:lower:]')

# Create the Vue component file
cat > "$COMPONENT_PATH/$COMPONENT_NAME.vue" << EOF
<template>
  <div class="$KEBAB_CASE-container">
    <!-- TODO: Implement template -->
    <slot />
  </div>
</template>

<script setup lang="ts">
// ==========================================
// IMPORTS
// ==========================================
import { ref, computed } from 'vue'

// ==========================================
// COMPONENT DEFINITION
// ==========================================
interface Props {
  // TODO: Define props
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  id: undefined
})

interface Emits {
  // TODO: Define emits
  'update': [value: any]
}

const emit = defineEmits<Emits>()

// ==========================================
// COMPOSABLES
// ==========================================

// ==========================================
// REACTIVE DATA
// ==========================================

// ==========================================
// COMPUTED PROPERTIES
// ==========================================

// ==========================================
// WATCHERS
// ==========================================

// ==========================================
// METHODS
// ==========================================

// ==========================================
// LIFECYCLE HOOKS
// ==========================================
</script>

<style scoped lang="scss">
.$KEBAB_CASE-container {
  // TODO: Component styles
}
</style>
EOF

echo "✅ Created $COMPONENT_PATH/$COMPONENT_NAME.vue"

# Create a basic test file if in a components directory
if [[ "$COMPONENT_PATH" == *"components"* ]]; then
    TEST_PATH="${COMPONENT_PATH}/__tests__"
    mkdir -p "$TEST_PATH"
    
    cat > "$TEST_PATH/$COMPONENT_NAME.spec.ts" << EOF
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import $COMPONENT_NAME from '../$COMPONENT_NAME.vue'

describe('$COMPONENT_NAME', () => {
  it('renders properly', () => {
    const wrapper = mount($COMPONENT_NAME)
    expect(wrapper.exists()).toBe(true)
  })
})
EOF
    
    echo "✅ Created test file $TEST_PATH/$COMPONENT_NAME.spec.ts"
fi