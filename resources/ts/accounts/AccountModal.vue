<template>
  <Modal @close="modals.close(id)">
    <template #title>{{ Boolean(internalAccount.name) ? internalAccount.name : 'New account' }}</template>
    <OutlinedTextfield autoselect v-model="internalAccount.name" autofocus>Name</OutlinedTextfield>
    <DollarsField autoselect v-model="amount">Amount</DollarsField>
    <template #actions>
      <div class="flex justify-between">
        <DeleteButton v-if="'id' in internalAccount" @click="deleteAccount" :loading="deleteLoading" :disabled="deleteLoading" />
        <SaveButton class="ml-auto" @click="save" :loading="loading" :disabled="loading" />
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

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
  accountId: {
    type: Number,
    required: false,
  }
})

const auth = useAuth()
const modals = useModals()

const accounts = useAccounts()

const internalAccount = ref<Omit<Account, 'id'>|Account>({
  name: '',
  amount: 0,
  user_id: auth.user.id
})
const amount = computed({
  get: () => internalAccount.value.amount / 100,
  set: v  => internalAccount.value.amount = v * 100
});

(async () => {
  if (props.accountId) {
    let result = await accounts.fetchAccount(props.accountId)
    internalAccount.value = JSON.parse(JSON.stringify(result))
  }
})()

const loading = ref(false)
async function save() {
  loading.value = true
  let result
  if ('id' in internalAccount.value) result = await accounts.updateAccount(internalAccount.value)
  else result = await accounts.createAccount(internalAccount.value)
  internalAccount.value = result
  loading.value = false
  setTimeout(() => modals.close(props.id))
}
const deleteLoading = ref(false)
async function deleteAccount() {
  deleteLoading.value = true
  // @ts-ignore (the delete button only shows if internalAccount.value has an `id` property)
  await accounts.deleteAccount(internalAccount.value)
  deleteLoading.value = false
  setTimeout(() => modals.close(props.id))
}
</script>

<style scoped lang="scss">
</style>