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
    <div class="flex justify-between px-4 mb-5" v-if="!modifying">
      <IconButton :density="-3" @click.stop="modifying = difference.modifier == -1 ? 1 : -1">remove</IconButton>
      <IconButton :density="-3" @click.stop="modifying = difference.modifier">add</IconButton>
    </div>
    <div class="flex justify-between px-4" v-if="!modifying">
      <IconButton :density="-3" @click.stop="modifying = '/'">
        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13H5v-2h14v2m-7-8a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0 10a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2Z"/></svg>
      </IconButton>
      <IconButton :density="-3" @click.stop="modifying = 'x'">close</IconButton>
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
import DollarsField from '@/core/fields/DollarsField.vue';
import IconButton from '@/core/buttons/IconButton.vue';
import { onMounted, onUnmounted, ref } from 'vue';
import Modal from '@/core/modals/Modal.vue';
import { useModals } from '@/store/modals';

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

const modifying = ref<1|-1|'x'|'/'|null>(null)
const modifyAmount = ref(0)
function modify() {
  if (modifying.value) {
    let trueAmount = props.difference.amount * props.difference.modifier
    if (modifying.value == 1 || modifying.value == -1) {
      trueAmount += (modifyAmount.value * modifying.value)
    } else if (modifying.value == 'x') {
      trueAmount = trueAmount * modifyAmount.value
    } else if (modifying.value == '/') {
      trueAmount = Math.ceil((trueAmount / modifyAmount.value) * 100) / 100
    }
    props.difference.amount = Math.abs(Math.round(trueAmount * 100) / 100)
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