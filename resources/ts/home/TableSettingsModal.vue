<template>
  <Modal @close="modals.close(id)">
    <template #title><h1 class="text-xl">Table settings</h1></template>
    <h2>Columns to show</h2>
    <div class="flex flex-col gap-3 my-2">
      <div>
        <input v-model="columnsToShow.name" type="checkbox" class="ml-3" id="name">
        <label for="name" class="ml-1">Name</label>
      </div>
      <div>
        <input v-model="columnsToShow.nextDate" type="checkbox" class="ml-3" id="nextDate">
        <label for="nextDate" class="ml-1">Next date</label>
      </div>
      <div>
        <input v-model="columnsToShow.nextAmount" type="checkbox" class="ml-3" id="nextAmount">
        <label for="nextAmount" class="ml-1">Next amount</label>
      </div>
      <div>
        <input v-model="columnsToShow.minimum" type="checkbox" class="ml-3" id="minimum">
        <label for="minimum" class="ml-1">Minimum</label>
      </div>
      <div>
        <input v-model="columnsToShow.overMinimum" type="checkbox" class="ml-3" id="overMinimum">
        <label for="overMinimum" class="ml-1">Over/under min.</label>
      </div>
      <div>
        <input v-model="columnsToShow.percentCovered" type="checkbox" class="ml-3" id="percentCovered">
        <label for="percentCovered" class="ml-1">% covered</label>
      </div>
      <div>
        <input v-model="columnsToShow.amount" type="checkbox" class="ml-3" id="amount">
        <label for="amount" class="ml-1">Amount</label>
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
import Modal from '@/ts/core/modals/Modal.vue';
import Button from '@/ts/core/buttons/Button.vue';
import SaveButton from '@/ts/core/buttons/SaveButton.vue';
import { useModals } from '@/ts/store/modals';
import { Account, useAccounts } from '@/ts/store/accounts';
import { computed, onMounted, ref } from 'vue';
import OutlinedTextfield from '@/ts/core/fields/OutlinedTextfield.vue';
import DeleteButton from '@/ts/core/buttons/DeleteButton.vue';
import { useAuth } from '@/ts/core/users/auth';
import DollarsField from '@/ts/core/fields/DollarsField.vue';
import { useForm } from '@/ts/store/forms';
import { useRouter } from 'vue-router';
import { dollars } from '@/ts/core/utilities/currency';
import { columnsToShow } from '@/ts/home/store';

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