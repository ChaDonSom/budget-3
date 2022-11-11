<template>
  <Modal @close="attemptClose">
    <template #title>{{ Boolean(form.name) ? form.name : 'New account' }}</template>
    <template v-if="initiallyLoaded">
      <OutlinedTextfield autoselect v-model="form.name" :autofocus="!accountId" :error="form.errors.name">
        Name
      </OutlinedTextfield>
      <DollarsField autoselect v-model="amount" :error="form.errors.amount" @keydown-enter="save">Amount</DollarsField>
      <OutlinedTextarea v-model="form.note">Note</OutlinedTextarea>
    </template>

    <div
        v-if="isAccountWithBatchUpdates(form)
          && form.batch_updates?.[0]?.date
          && form.batch_updates?.[0]?.pivot?.amount"
        class="flex items-center"
    >
      Paying
      {{ dollars(Math.abs(form.batch_updates?.[0]?.pivot?.amount) / 100) }}
      on
      {{ toDateTime(form.batch_updates?.[0]?.date).toLocaleString({ weekday: "long", month: 'long', day: 'numeric' }) }}
      <IconButton 
        @click="openBatchUpdate(form.batch_updates?.[0])"
      >open_in_new</IconButton>
    </div>

    <!--

    <div
        v-if="isAccountWithBatchUpdates(form) && form.batch_updates?.[0]?.date"
        v-tooltip="{
          content: `Ideally ${dollars(idealPayment(form.batch_updates?.[0]))} / week over ${idealWeeks(form.batch_updates?.[0])} weeks<br>Emergency ${emergencySaving(form)} / week over ${fridaysUntil(toDateTime(form.batch_updates?.[0]?.date))} weeks`,
          html: true
        }"
    >
      Minimum now:
      {{
        dollars(
          Math.abs(form.batch_updates?.[0]?.pivot?.amount / 100)
          -
          (idealPayment(form.batch_updates?.[0]) * fridaysUntil(toDateTime(form.batch_updates?.[0]?.date)))
        )
      }}
    </div>
    <div
        v-if="isAccountWithBatchUpdates(form) && form.batch_updates?.[0]?.date"
    >
      Difference:
      {{
        (form.amount / 100) - minimumToMakeNextPayment(form)
        ?
        dollars((form.amount / 100) - minimumToMakeNextPayment(form))
        : ''
      }}
    </div>
    -->

    <div class="ml-4 my-4">
      <MdcSwitch 
          v-model="isFavorite"
          :id="`favorite-account-${form.id}`"
      >Favorite</MdcSwitch>
    </div>
    <Button v-if="accountId" @click="goToAccountHistory"><template #leading-icon>history</template>History</Button>
    <Button v-if="accountId && auth.user?.beta_opt_in" @click="goToAccountPaymentPlanning">
      <template #leading-icon>query_stats</template>
      Saving planning
    </Button>
    <transition name="error-message">
      <p v-if="form.errors.message" class="bg-red-200 rounded-3xl py-3 px-4 mb-2 break-word max-w-fit text-red-800">
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
import Modal from '@/core/modals/Modal.vue';
import Button from '@/core/buttons/Button.vue';
import SaveButton from '@/core/buttons/SaveButton.vue';
import { useModals } from '@/store/modals';
import { type Account, useAccounts } from '@/store/accounts';
import { computed, onMounted, ref } from 'vue';
import OutlinedTextfield from '@/core/fields/OutlinedTextfield.vue';
import DeleteButton from '@/core/buttons/DeleteButton.vue';
import { useAuth } from '@/core/users/auth';
import DollarsField from '@/core/fields/DollarsField.vue';
import { useForm } from '@/store/forms';
import { useRouter } from 'vue-router';
import { dollars } from '@/core/utilities/currency';
import {
  idealPayment,
  idealWeeks,
  fridaysUntil,
  isAccountWithBatchUpdates,
  emergencySaving,
  minimumToMakeNextPayment,
} from '@/home';
import { toDateTime } from '@/core/utilities/datetime';
import { columnsToShow } from '@/home';
import IconButton from '@/core/buttons/IconButton.vue';
import type { BatchUpdate } from '@/store/batchUpdates';
import { DateTime } from 'luxon';
import MdcSwitch from '../core/switches/MdcSwitch.vue';
import OutlinedTextarea from '../core/fields/OutlinedTextarea.vue';

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
  note: '',
  user_id: auth.user?.id ?? 0,
  favorited_users: [],
  batch_updates: []
})

const isFavorite = computed({
  get: () => Boolean(form.favorited_users?.find(i => i.id == auth.user?.id)),
  set: v  => {
    if (v) {
      if (auth.user && !form.favorited_users?.find(i => i.id == auth.user?.id)) {
        form.favorited_users?.push(auth.user)
      }
    } else {
      form.favorited_users = form.favorited_users?.filter(i => i.id != auth.user?.id)
    }
  }
})

const initiallyLoaded = ref(false);
(async () => {
  if (props.accountId) {
    form.reset<Account>(accounts.data[props.accountId])
  }
  initiallyLoaded.value = true
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
async function goToAccountPaymentPlanning() {
  await attemptClose()
  router.push({ name: 'saving-planning', params: { accountId: form.id } })
}

function openBatchUpdate(batchUpdate?: BatchUpdate) {
  modals.close(props.id)
  router.push({ name: 'batch-updates-show', params: { id: batchUpdate?.id } })
}
</script>

<style scoped lang="scss">
@use "@/css/transitions";
</style>