<template>
  <transition
      name="opacity-0-scale-097-150ms"
      mode="out-in"
  >
    <div
        class="my-7 mb-14"
    >
      <Button
          v-if="form.isDirty && route.params.id != 'new'"
          @click="$emit('save-as-new', true)"
      > n
        <template #leading-icon>save</template>
        Save as new
      </Button>
    </div>
  </transition>
</template>

<script lang="ts" setup>
import type { useForm } from '@/store/forms'
import type { PropType } from 'vue'
import { useRoute } from 'vue-router'

defineProps({
  form: {
    type: Object as PropType<ReturnType<typeof useForm> & { weeks: number | null }>,
    required: true,
  }
})

defineEmits(['save-as-new'])

const route = useRoute()
</script>
