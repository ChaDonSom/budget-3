<template>
  <Modal @close="attemptClose">
    <template #title>{{ Boolean(form.name) ? form.name : 'New account' }}</template>
    <OutlinedTextfield autoselect v-model="form.name" autofocus :error="form.errors.name">Name</OutlinedTextfield>
    <DollarsField autoselect v-model="amount" :error="form.errors.amount" @keydown-enter="save">Amount</DollarsField>
    <Button @click="goToAccountHistory"><template #leading-icon>history</template>History</Button>
    <transition name="error-message">
      <p v-if="form.errors.message" class="bg-red-200 rounded-3xl py-3 px-4 mb-2 break-word max-w-fit">
        {{ form.errors.message }}
      </p>
    </transition>
    <template #actions>
      <div class="flex justify-between">
        <DeleteButton v-if="form.id" @click="deleteAccount" :loading="form.processingDelete" :disabled="form.processing" />
        <SaveButton class="ml-auto" @click="save" :loading="form.processingNotDelete" :disabled="form.processing" />
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

const amount = computed({
  get: () => form.amount / 100,
  set: v  => form.amount = v * 100
});

const form = useForm<Omit<Account, 'id'> & { id: number|null }>('/api/accounts', {
  id: null,
  name: '',
  amount: 0,
  user_id: auth.user?.id ?? 0
});

(async () => {
  if (props.accountId) {
    let result = await accounts.fetchAccount(props.accountId)
    form.reset<Account>(result)
  }
})()

async function save() {
  accounts.receive(await form.createOrUpdate())
  setTimeout(() => modals.close(props.id))
}
async function deleteAccount() {
  if (form.id && props.accountId) {
    try {
      await modals.confirm(`Do you really want to delete ${accounts.data[props.accountId].name}?`)
    } catch(e) { return }
    let id = form.id
    await form.delete()
    accounts.remove(id)
    setTimeout(() => modals.close(props.id))
  } else {
    throw Error('Form has no id')
  }
}

async function attemptClose() {
  try {
    if (form.isDirty) await modals.confirm(`Do you really want to leave unsaved changes?`)
    modals.close(props.id)
  } catch (e) {}
}

const router = useRouter()
async function goToAccountHistory() {
  await attemptClose()
  router.push({ name: 'history', query: { account_id: form.id } })
}
</script>

<style scoped lang="scss">
@use "@/css/transitions";
</style>