<template>
  <Modal
      ref="mainRef"
      @close="modals.close(id)"
  >
    <DollarsField
        :density="-4"
        :icon="difference.modifier == 1 ? 'add' : 'remove'"
        @icon-click="difference.modifier = difference.modifier == 1 ? -1 : 1"
        autofocus
        autoselect
        v-model="difference.amount"
        @keydown-enter="modals.close(id)"
        class="difference-field"
    >Amount</DollarsField>
    <div class="flex justify-between px-4" v-if="!modifying">
      <IconButton :density="-3" @click.stop="modifying = difference.modifier == -1 ? 1 : -1">remove</IconButton>
      <IconButton :density="-3" @click.stop="modifying = difference.modifier">add</IconButton>
    </div>
    <DollarsField
        v-if="modifying"
        :density="-4"
        :icon="modifying == 1 ? 'add' : 'remove'"
        @icon-click.stop="modifying = modifying == 1 ? -1 : 1"
        autofocus
        autoselect
        v-model="modifyAmount"
        @blur="modify"
        @keydown-enter="modify"
        class="difference-field"
    >Amount</DollarsField>
  </Modal>
</template>

<script setup lang="ts">
import DollarsField from '@/ts/core/fields/DollarsField.vue';
import IconButton from '@/ts/core/buttons/IconButton.vue';
import { onMounted, onUnmounted, ref } from 'vue';
import Modal from '@/ts/core/modals/Modal.vue';
import { useModals } from '@/ts/store/modals';

const props = defineProps({
  id: {
    type: [Number, String],
    required: true,
  },
  difference: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['close'])

const modals = useModals()

const modifying = ref<1|-1|null>(null)
const modifyAmount = ref(0)
function modify() {
  if (modifying.value) {
    let trueAmount = props.difference.amount * props.difference.modifier
    trueAmount += (modifyAmount.value * modifying.value)
    props.difference.amount = Math.abs(trueAmount)
    props.difference.modifier = trueAmount >= 0 ? 1 : -1
  }
  modifying.value = null
}

const mainRef = ref<HTMLElement|null>(null)
</script>

<style scoped lang="scss">
.difference-field :deep(.mdc-text-field) {
	max-width: 150px;
}
</style>