<template>
  <Teleport to="body">
    <div v-if="initiallyLoaded">
      <Fab
          @click="$emit('save-batch-update')"
          icon="save"
          class="fixed right-4 bottom-6"
          style="z-index: 2"
      />
      <OutlinedTextfield
          type="date"
          v-model="batchForm.date"
          class="fixed bottom-0 right-20 opaque"
          style="z-index: 2"
      >
        Date
      </OutlinedTextfield>
      <IconButton
          v-if="areAnyBatchDifferences"
          @click="$emit('clear-batch-differences')"
          class="bottom-8 left-4"
          style="position: fixed"
      >
        close
      </IconButton>
    </div>
  </Teleport>
</template>

<script lang="ts" setup>
import type { BatchDifference } from '@/home'
import type { useForm } from '@/store/forms'
import type { PropType } from 'vue'

defineProps({
  initiallyLoaded: {
    type: Boolean,
    required: true,
  },
  batchForm: {
    type: Object as PropType<ReturnType<typeof useForm> & {
      accounts: {
        [key: number]: BatchDifference
      },
      date: string,
    }>,
    required: true,
  },
  areAnyBatchDifferences: {
    type: Boolean,
    required: true,
  }
})

defineEmits([
  'save-batch-update',
  'clear-batch-differences',
])
</script>