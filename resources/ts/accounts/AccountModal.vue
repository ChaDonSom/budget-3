<template>
  <Modal @close="modals.close(id)">
    <template #title>Account modal</template>
    Account body
    <template #actions>
      <SaveButton />
    </template>
  </Modal>
</template>

<script lang="ts" setup>
import Modal from '@/ts/core/modals/Modal.vue';
import Button from '@/ts/core/buttons/Button.vue';
import SaveButton from '@/ts/core/buttons/SaveButton.vue';
import { useModals } from '@/ts/store/modals';

const props = defineProps({
  id: {
    type: Number,
    required: true,
  }
})

const modals = useModals()
</script>

<style scoped lang="scss">
</style>