<template>
  <transition
      name="opacity-0-scale-097-150ms"
      mode="out-in"
  >
    <div class="my-7">
      <transition
          name="opacity-0-scale-097-150ms"
          mode="out-in"
      >
        <OutlinedTextfield
            v-model="form.weeks"
            type="number"
            step="1"
            autoselect
            :autofocus="!batchUpdates.data[String($route.params.id)]?.weeks"
            v-if="form.weeks != null"
        >
          Preferred # of weeks to pay by
        </OutlinedTextfield>
      </transition>
      <Button @click="form.weeks = form.weeks == null ? 4 : null">
        {{
          form.weeks == null
          ? "Set preferred payment schedule"
          : "Remove payment schedule"
        }}
      </Button>
    </div>
  </transition>
</template>

<script lang="ts" setup>
import { useBatchUpdates } from '@/store/batchUpdates'
import type { useForm } from '@/store/forms'
import type { PropType } from 'vue'

defineProps({
  form: {
    type: Object as PropType<ReturnType<typeof useForm> & { weeks: number|null }>,
    required: true,
  }
})

const batchUpdates = useBatchUpdates()
</script>