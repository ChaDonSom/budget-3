<template>
  <transition
      name="opacity-0-scale-097-150ms"
      appear
  >
    <DeleteButton
        v-if="form.id"
        @click="$emit('delete')"
        :loading="form.processingDelete"
        :disabled="form.processing"
    />
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

defineEmits(['delete'])
</script>