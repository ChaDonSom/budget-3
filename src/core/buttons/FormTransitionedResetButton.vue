<template>
  <transition
      name="opacity-0-scale-097-150ms"
      appear
  >
    <Button
        v-if="form.isDirty"
        @click="$emit('reset', $event)"
        secondary
    >
      <template #leading-icon>replay</template>
      Reset
    </Button>
  </transition>
</template>

<script lang="ts" setup>
import type { useForm } from '@/store/forms'
import type { PropType } from 'vue'

defineProps({
  form: {
    type: Object as PropType<ReturnType<typeof useForm>>,
    required: true,
  }
})

defineEmits(['reset'])
</script>