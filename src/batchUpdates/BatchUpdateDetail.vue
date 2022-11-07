<template>
  <div class="max-h-screen mx-3">
    <h1 class="text-xl pb-2 pt-3">
      {{ route.params.id == 'new' ? 'Make changes' : 'Edit changes' }}
    </h1>
    <table v-if="areAnyBatchDifferences">
      <tbody>
        <tr v-for="account_id of Object.keys(batchDifferences)">
          <td>{{ accounts.data[account_id]?.name }}</td>
          <td class="pl-3 text-right">{{ dollars(batchDifferences[Number(account_id)].resolved) }}</td>
        </tr>
        <tr><td></td><td>&nbsp;</td></tr>
        <tr><td>Total</td><td class="pl-3 text-right">{{ dollars(batchTotal) }}</td></tr>
      </tbody>
    </table>



    <transition name="opacity-0-scale-097-150ms">
      <div v-if="areAnyBatchDifferences" class="my-3">
        <MdcSwitch v-model="batchForm.notify_me" id="notify_me">
          Notify me when this change is made
        </MdcSwitch>
      </div>
    </transition>

    <transition name="opacity-0-scale-097-150ms" mode="out-in">
      <div class="my-5" v-if="areAnyBatchDifferences">
        <OutlinedTextfield v-model="batchForm.weeks" type="number" step="1" autoselect autofocus
          v-if="batchForm.weeks != null">
          Preferred # of weeks to pay by
        </OutlinedTextfield>
        <Button @click="batchForm.weeks = batchForm.weeks == null ? 4 : null">
          {{ batchForm.weeks == null ? 'Set preferred payment schedule' : 'Remove payment schedule' }}
        </Button>
      </div>
    </transition>

    <transition name="opacity-0-scale-097-150ms" mode="out-in">
      <div class="my-7" v-if="areAnyBatchDifferences">
        <OutlinedTextfield type="date" v-if="areAnyBatchDifferences" v-model="batchForm.date" style="z-index: 2;">
          Date
        </OutlinedTextfield>
      </div>
    </transition>

    <Teleport to="body">
      <Fab
          @click="saveBatch"
          icon="save"
          class="fixed right-4 bottom-6"
          style="z-index: 2;"
      />
    </Teleport>
  </div>
</template>

<script lang="ts" setup>
import {
  accountsTotal,
  areAnyBatchDifferences,
  batchDate,
  batchDifferences,
  batchForm,
  batchTotal,
  saveBatch as saveBatchOriginal
} from '@/batchUpdates';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import MdcSwitch from '../core/switches/MdcSwitch.vue';
import OutlinedTextfield from '../core/fields/OutlinedTextfield.vue';
import Button from '../core/buttons/Button.vue';
import { useAccountsStore } from '@/store/accounts';
import { dollars } from '@/core/utilities/currency';
import { useModals } from '@/store/modals';
import Fab from '../core/buttons/Fab.vue';

const route = useRoute()
const router = useRouter()
const accounts = useAccountsStore()

async function saveBatch() {
  await saveBatchOriginal()
  router.go(-1)
}

onBeforeRouteLeave(async (to) => {
  try {
    if (areAnyBatchDifferences.value && to.name != 'home-experiment') {
      await useModals().confirm("Do you really want to leave unsaved changes?")
    }
  } catch (e) {
    return false
  }
})
</script>