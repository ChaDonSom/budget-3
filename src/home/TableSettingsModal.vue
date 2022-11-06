<template>
  <Modal @close="modals.close(id)">
    <template #title><h1 class="text-xl">Table settings</h1></template>
    <h2>Columns to show</h2>
    <div class="flex flex-col my-2">
      <div>
        <MdcSwitch v-model="columnsToShow.name" class="ml-3" id="name">
          Name
        </MdcSwitch>
      </div>
      <div>
        <MdcSwitch v-model="columnsToShow.nextDate" class="ml-3" id="nextDate">
          Next date
        </MdcSwitch>
      </div>
      <div>
        <MdcSwitch v-model="columnsToShow.nextAmount" class="ml-3" id="nextAmount">
          Next amount
        </MdcSwitch>
      </div>
      <div>
        <MdcSwitch v-model="columnsToShow.minimum" class="ml-3" id="minimum">
          Minimum
        </MdcSwitch>
      </div>
      <div>
        <MdcSwitch v-model="columnsToShow.overMinimum" class="ml-3" id="overMinimum">
          Over/under min.
        </MdcSwitch>
      </div>
      <div>
        <MdcSwitch v-model="columnsToShow.percentCovered" class="ml-3" id="percentCovered">
          % covered
        </MdcSwitch>
      </div>
      <div>
        <MdcSwitch v-model="columnsToShow.amount" class="ml-3" id="amount">
          Amount
        </MdcSwitch>
      </div>
    </div>
    <template #actions>
      <div class="flex justify-between">
        <Button @click="modals.close(id)" class="ml-auto">Done</Button>
      </div>
    </template>
  </Modal>
</template>

<script lang="ts" setup>
import Modal from '@/core/modals/Modal.vue';
import Button from '@/core/buttons/Button.vue';
import { useModals } from '@/store/modals';
import { useAuth } from '@/core/users/auth';
import { useRouter } from 'vue-router';
import { columnsToShow } from '@/home';
import MdcSwitch from '../core/switches/MdcSwitch.vue';

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
})

const auth = useAuth()
const modals = useModals()
const router = useRouter()
</script>

<style scoped lang="scss">
@use "@/css/transitions";
</style>