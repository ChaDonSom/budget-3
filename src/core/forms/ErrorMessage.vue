<template>
  <transition name="error-message">
    <div class="flex">
      <p
          v-if="form.errors.message"
          class="bg-red-200 rounded-3xl py-3 px-4 mb-2 break-word max-w-fit mx-auto"
      >
        {{ form.errors.message }}
      </p>
    </div>
  </transition>
</template>

<script lang="ts" setup>
import type { useForm } from '@/store/forms'
import type { PropType } from 'vue'

defineProps({
  form: {
    type: Object as PropType<ReturnType<typeof useForm> & { weeks: number | null }>,
    required: true,
  }
})
</script>

<style lang="scss" scoped></style>