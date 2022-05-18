<template>
	<div>
		<div class="text-center m-3">
      <div v-if="initiallyLoaded" class="mb-5">
        <h1 class="text-xl mt-5">{{ Boolean(batchForm.name) ? batchForm.name : 'New template' }}</h1>
        <OutlinedTextfield autoselect v-model="batchForm.name" autofocus :error="batchForm.errors.name">
          Name
        </OutlinedTextfield>
        <p v-if="!sortedAccounts.length" class="m-5">
          ✨ No accounts ✨
        </p>
        <DataTable @sort="updateSort" v-if="sortedAccounts.length" style="max-height: 70vh;">
          <template #header>
            <DataTableHeaderCell sortable column-id="name" :sort="sort.name">
              Name
            </DataTableHeaderCell>
            <DataTableHeaderCell numeric>
              <!-- Controls -->&nbsp;
            </DataTableHeaderCell>
          </template>
          <template #body>
            <DataTableRow v-for="account of sortedAccounts">
              <DataTableCell>{{ account.name }}</DataTableCell>
              <DataTableCell numeric style="cursor: pointer;" @click="batchDifferences[account.id] ? edit(account) : null">
                <div v-if="currentlyEditingDifference != account.id && !batchDifferences[account.id]">
                  <IconButton :density="-3" @click.stop="startWithdrawing(account)">remove</IconButton>
                  <IconButton :density="-3" @click.stop="startDepositing(account)">add</IconButton>
                </div>
                <div
                    v-else-if="batchDifferences[account.id]"
                    @click.stop="edit(account)"
                    class="w-full h-full flex items-center gap-2"
                >
                  <IconButton
                      :density="-5"
                      class="mr-2"
                      @click.stop="clearBatchDifferenceFor(account)"
                  >close</IconButton>
                  {{ batchDifferences[account.id].modifier == 1 ? '+ ' : '' }}
                  {{ dollars(batchDifferences[account.id].amount * batchDifferences[account.id].modifier) }}
                </div>
              </DataTableCell>
            </DataTableRow>
            <DataTableRow class="sticky-bottom-row">
              <DataTableCell />
              <DataTableCell numeric>
                <div v-if="areAnyBatchDifferences">{{ dollars(batchTotal) }}</div>
              </DataTableCell>
            </DataTableRow>
          </template>
        </DataTable>
      </div>

      <Teleport to="body">
        <div v-if="initiallyLoaded">
          <Fab
              @click="saveTemplate"
              icon="save"
              class="fixed right-4 bottom-6"
              style="z-index: 2;"
          />
          <IconButton v-if="initiallyLoaded && areAnyBatchDifferences" @click="clearBatchDifferences"
              class="bottom-8 left-4"
              style="position: fixed;"
          >
            close
          </IconButton>
        </div>
      </Teleport>

      <DeleteButton
          v-if="batchForm.id && initiallyLoaded"
          @click="deleteTemplate"
          :loading="batchForm.processingDelete"
          :disabled="batchForm.processing"
      />
      <Button v-if="batchForm.isDirty" @click="loadTemplate" secondary>
        <template #leading-icon>replay</template>
        Reset
      </Button>

      <transition name="error-message">
        <p v-if="batchForm.errors.message" class="bg-red-200 rounded-3xl py-3 px-4 mb-2 break-word max-w-fit">
          {{ batchForm.errors.message }}
        </p>
      </transition>


			<div class="my-7">
				<p v-for="message of messages" :key="message">{{ message }}</p>
			</div>
			<!--
			<div class="my-7" v-if="auth.authenticated">
				<Button @click="sendPushNotification">Send myself a push notification</Button>
			</div>
			-->
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, defineComponent, reactive, onMounted, computed, toRefs, watch, Ref, markRaw } from 'vue';
import Button from '@/ts/core/buttons/Button.vue'
import { vite_asset } from '@/ts/core/utilities/build'
import { useAuth } from '../core/users/auth';
import { useEcho } from '../store/echo';
import axios from 'axios';
import { useAccounts, Account } from '@/ts/store/accounts';
import { dollars } from '@/ts/core/utilities/currency'
import DataTable from '@/ts/core/tables/DataTable.vue';
import DataTableHeaderCell from '@/ts/core/tables/DataTableHeaderCell.vue';
import DataTableRow from '@/ts/core/tables/DataTableRow.vue';
import DataTableCell from '@/ts/core/tables/DataTableCell.vue';
import { useLocalStorage } from '@vueuse/core';
import IconButton from '@/ts/core/buttons/IconButton.vue';
import Fab from '@/ts/core/buttons/Fab.vue';
import { useModals } from '@/ts/store/modals';
import AccountModalVue from '@/ts/accounts/AccountModal.vue';
import DollarsField from '@/ts/core/fields/DollarsField.vue';
import FloatingDifferenceInputModalVue from '@/ts/home/FloatingDifferenceInputModal.vue';
import { useForm } from '@/ts/store/forms';
import { DateTime } from 'luxon'
import OutlinedTextfield from '@/ts/core/fields/OutlinedTextfield.vue';
import { Template, TemplateWithAccounts, useTemplates } from '@/ts/store/templates';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import DeleteButton from '@/ts/core/buttons/DeleteButton.vue';

const props = defineProps({
  id: {
    type: [Number, String],
    required: true,
  }
})

const auth = useAuth()
const route = useRoute()
const router = useRouter()
const modals = useModals()

const messages = ref<any[]>([])
const echo = useEcho()
onMounted(() => {
	// The '.' in '.my-event' means we'll listen on 'my-channel' instead of 'App\Events.my-channel'
	// That way, we can mess around with this from the Pusher event creator
	echo.echo.channel('my-channel').listen('.my-event', (data: any) => {
		console.log('data: ', data)
		messages.value.push(data)
	})
})

function sendPushNotification() {
	axios.post('/api/beams/self-notification', {
		title: 'Hello World!',
		message: 'Hi there, a notification from Somero Budget 3!'
	})
}

/**
	---------------------------------------------------
	| Indexing and the table
	---------------------------------------------------
 */
const initiallySorted = ref(false)
const initiallyLoadedAccounts = ref(false)
const initiallyLoaded = computed(() => {
	return (
		initiallyLoadedAccounts.value && initiallySorted.value
	)
})

const accounts = useAccounts()
accounts.fetchData().then(() => initiallyLoadedAccounts.value = true)

const sortedAccounts: Ref<Account[]> = ref([])
const sort = useLocalStorage('budget-accounts-index-sort-v2', {
	name: {
		value: 'none',
		at: null as number|null,
	},
	amount: {
		value: 'none',
		at: null as number|null,
	},
	nextDate: {
		value: 'none',
		at: null as number|null,
	},
	nextAmount: {
		value: 'none',
		at: null as number|null,
	}
})
const hideProgress = ref<Function|null>(null)
function updateSort(event: { columnId: keyof typeof sort.value, sortValue: "ascending"|"descending", hideProgress: Function }) {
	hideProgress.value = event.hideProgress
	if (sort.value[event.columnId].value == 'descending') sort.value[event.columnId].value = 'none'
	else sort.value[event.columnId].value = event.sortValue
	sort.value[event.columnId].at = (new Date()).valueOf()
}
watch(
	() => [accounts.values, sort.value],
	() => {
		const worker = new Worker('worker.js')
		worker.postMessage({
			type: 'SORT_ACCOUNTS',
			accounts: JSON.stringify(accounts.values.map(account => ({
				...account,
				nextDate: account.batch_updates?.[0]?.date ?? '',
				nextAmount: account.batch_updates?.[0]?.pivot?.amount ?? 0,
			}))),
			sort: JSON.stringify(sort.value)
		})
		worker.addEventListener('message', event => {
			if (event.data?.type == 'SORT_ACCOUNTS') {
				sortedAccounts.value = JSON.parse(event.data?.accounts).map((a: Account & { [key: string]: any }) => {
					let result = a
					delete result.nextDate
					delete result.nextAmount
					return result
				}) as Account[]
				if (hideProgress.value) hideProgress.value()
				if (initiallyLoadedAccounts.value) initiallySorted.value = true
				worker.terminate()
			}
		})
	},
	{ deep: true, immediate: true }
)

/**
	---------------------------------------------------
	| Setting up withdraw/deposit batches
	---------------------------------------------------
 */
const currentlyEditingDifference = ref<number|null>(null)
const batchDifferences = ref({} as { [key: number]: { amount: number, modifier: 1|-1 } })
const batchDate = ref<DateTime>(DateTime.now())
const areAnyBatchDifferences = computed(() => Boolean(Object.keys(batchDifferences.value).length))
const batchTotal = computed(() => Object.values(batchDifferences.value).map(i => i.amount * i.modifier).reduce((a, c) => a + c, 0))
function startWithdrawing(account: Account) {
	currentlyEditingDifference.value = account.id
	batchDifferences.value[account.id] = {
		amount: 0,
		modifier: -1
	}
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
function startDepositing(account: Account) {
	currentlyEditingDifference.value = account.id
	batchDifferences.value[account.id] = {
		amount: 0,
		modifier: 1
	}
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
function clearBatchDifferenceFor(account: Account) {
	delete batchDifferences.value[account.id]
	if (currentlyEditingDifference.value == account.id) currentlyEditingDifference.value = null
}
function clearBatchDifferences() {
	batchDifferences.value = {}
  batchForm.accounts = {}
	currentlyEditingDifference.value = null
}
function edit(account: Account) {
	currentlyEditingDifference.value = account.id
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
const templates = useTemplates()
const batchForm = useForm('/api/templates', {
  name: '',
  id: null as number|null,
  user_id: auth.user?.id,
	accounts: batchDifferences.value,
})
async function loadTemplate() {
  if (route.params.id && route.params.id != 'new') {
    let result = await templates.fetchTemplate(Number(route.params.id)) as TemplateWithAccounts
    batchForm.reset({
      ...batchForm,
      ...result,
      accounts: result.accounts.reduce((a, c) => {
        a[c.id] = {
          amount: c.pivot.amount / 100,
          modifier: c.pivot.amount >= 0 ? 1 : -1
        }
        return a
      }, {} as { [key: number]: { amount: number, modifier: 1|-1 } })
    })
    batchDifferences.value = batchForm.accounts
  }
}
onMounted(loadTemplate)
async function saveTemplate() {
	batchForm.reset({ name: batchForm.name, user_id: batchForm.user_id, accounts: batchDifferences.value })
	await batchForm.createOrUpdate()
  setTimeout(() => router.back())
}
async function deleteTemplate() {
  if (batchForm.id && route.params.id && route.params.id != 'new') {
    try {
      await modals.confirm(`Do you really want to delete ${templates.data[Number(route.params.id)].name}?`)
    } catch(e) { return }
    let id = batchForm.id
    await batchForm.delete()
    templates.remove(id)
    setTimeout(() => router.back())
  } else {
    throw Error('Form has no id')
  }
}
onBeforeRouteLeave(async () => {
	try {
		if (batchForm.isDirty) await modals.confirm("Do you really want to leave unsaved changes?")
	} catch (e) {
		return false
	}
})
</script>

<style scoped lang="scss">
@use "@/css/mdc-theme";
@use "@/css/transitions";
@use "@material/typography";
@include typography.core-styles;

code {
	background-color: #eee;
	padding: 2px 4px;
	border-radius: 4px;
	color: #304455;
}

:deep(.sticky-bottom-row td) {
	position: -webkit-sticky;
	position: sticky;
	bottom: 0;
	z-index: 1;
	background-color: white;
	&::before {
		content: '';
		display: block;
		position: absolute;
		width: calc(100% + 32px);
		height: 2px;
		top: 0;
		left: -16px;
		background-color: rgb(197, 197, 197);
	}
}
:deep(.mdc-data-table__row.sticky-bottom-row:not(.mdc-data-table__row--selected):hover .mdc-data-table__cell) {
	background-color: white;
}

// For the date field to not be see-through
:deep(.opaque) {
	.mdc-text-field {
		.mdc-notched-outline__leading, .mdc-notched-outline__trailing, .mdc-notched-outline__notch {
			background-color: white;
		}
		input.mdc-text-field__input {
			z-index: 2;
		}
	}
}
</style>
