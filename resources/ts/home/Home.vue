<template>
	<div>
		<div class="text-center m-3">
      <!--
        Welcome sign :)
      -->
			<div class="flex justify-center" v-if="!auth.authenticated">
				<img :src="vite_asset('android-chrome-512x512.png')" class="m-5 w-3/12">
			</div>
			<h1 class="text-3xl sm:text-5xl md:text-7xl font-thin" v-if="!auth.authenticated">Welcome to Somero Budget</h1>
			<h2 class="text-xl italic mt-3" v-if="$route.params.securityLoggedOut">
				({{ $route.params.securityLoggedOut }})
			</h2>
      <!--
        Dashboard
      -->
      <div v-if="auth.authenticated">
        <h1 class="text-xl mt-4 mb-2">Budget</h1>
				<div v-if="initiallyLoaded" class="mb-5">
					<p v-if="!sortedAccounts.length" class="m-5">
						✨ No accounts ✨
					</p>
					<DataTable @sort="updateSort" v-if="sortedAccounts.length" style="max-height: 83vh;">
						<template #header>
							<DataTableHeaderCell sortable column-id="name" :sort="sort.name">
								Name
							</DataTableHeaderCell>
							<DataTableHeaderCell sortable column-id="nextDate" :sort="sort.nextDate" class="hidden md:table-cell">
								Next date
							</DataTableHeaderCell>
							<DataTableHeaderCell
									sortable
									numeric
									column-id="nextAmount"
									:sort="sort.nextAmount"
									class="hidden lg:table-cell"
							>
								Next amount
							</DataTableHeaderCell>
							<DataTableHeaderCell
									class="hidden lg:table-cell"
									v-tooltip="'Minimum current balance needed to make the payment on time with ideal weekly saving'"
							>
								Ideal min.
							</DataTableHeaderCell>
							<DataTableHeaderCell class="hidden lg:table-cell">
								Over min.
							</DataTableHeaderCell>
							<DataTableHeaderCell class="hidden sm:table-cell">
								% covered
							</DataTableHeaderCell>
							<DataTableHeaderCell sortable numeric column-id="amount" :sort="sort.amount">
								Amount
							</DataTableHeaderCell>
							<DataTableHeaderCell numeric>
								<!-- Controls -->&nbsp;
							</DataTableHeaderCell>
						</template>
						<template #body>
							<DataTableRow
									v-for="account of sortedAccounts"
									:key="account.id"
									:style="{ 'background-color': (sort.nextDate.value != 'none' && ((isAccountWithBatchUpdates(account) ? fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)) : 1) % 2 == 0)) ? 'rgba(0,0,0,0.09)' : 'unset' }"
							>
								<DataTableCell @click="editAccount(account.id)">{{ account.name }}</DataTableCell>
								<DataTableCell class="hidden md:table-cell">
									<div
											v-if="isAccountWithBatchUpdates(account) && account.batch_updates?.[0]?.date"
											@click="$router.push({ name: 'batch-updates-show', params: { id: account.batch_updates?.[0]?.id } })"
									>
										{{ account.batch_updates?.[0]?.date }}
									</div>
								</DataTableCell>
								<DataTableCell class="hidden lg:table-cell" numeric>
									<div v-if="account.batch_updates?.[0]?.pivot?.amount">
										{{ dollars(account.batch_updates?.[0]?.pivot?.amount / 100) }}
									</div>
								</DataTableCell>
								<DataTableCell class="hidden lg:table-cell">
									<div v-if="isAccountWithBatchUpdates(account) && account.batch_updates?.[0]?.date" class="text-gray-400"
											v-tooltip="{
												content: `Ideally ${dollars(idealPayment(account.batch_updates?.[0]))} / week over ${idealWeeks(account.batch_updates?.[0])} weeks<br>Emergency ${emergencySaving(account)} / week over ${fridaysUntil(toDateTime(account.batch_updates?.[0]?.date))} weeks`,
												html: true
											}"
									>
										{{
											dollars(
												Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
												-
												(idealPayment(account.batch_updates?.[0]) * fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)))
											)
										}}
									</div>
								</DataTableCell>
								<DataTableCell class="hidden lg:table-cell">
									<div v-if="account.batch_updates?.[0]?.date" class="text-gray-400">
										{{
											(account.amount / 100) - (
												Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
												-
												(idealPayment(account.batch_updates?.[0]) * fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)))
											) > 0
											?
											dollars((account.amount / 100) - (
												Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
												-
												(idealPayment(account.batch_updates?.[0]) * fridaysUntil(toDateTime(account.batch_updates?.[0]?.date))))
											)
											: ''
										}}
									</div>
								</DataTableCell>
								<DataTableCell class="hidden sm:table-cell" numeric>
									<div
											v-if="account.batch_updates?.[0]?.pivot?.amount"
											v-tooltip="`Required: ${
													Math.round((
															(
																idealWeeks(account.batch_updates?.[0])
																- fridaysUntil(toDateTime(account.batch_updates?.[0]?.date))
															)
															/ idealWeeks(account.batch_updates?.[0])
													) * 100)
												}% (${
												dollars(
													Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
													-
													(idealPayment(account.batch_updates?.[0]) * fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)))
												)})
											`"
											class="select-none"
											:class="{
												'text-blue-600': Math.round(((account.amount / 100) / (Math.abs(account.batch_updates?.[0]?.pivot?.amount) / 100)) * 100) == Math.round(( ( idealWeeks(account.batch_updates?.[0]) - fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)) ) / idealWeeks(account.batch_updates?.[0]) ) * 100),
												'text-green-600': Math.round(((account.amount / 100) / (Math.abs(account.batch_updates?.[0]?.pivot?.amount) / 100)) * 100) > Math.round(( ( idealWeeks(account.batch_updates?.[0]) - fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)) ) / idealWeeks(account.batch_updates?.[0]) ) * 100),
											}"
									>
										{{ Math.round(((account.amount / 100) / (Math.abs(account.batch_updates?.[0]?.pivot?.amount) / 100)) * 100) }} %
									</div>
								</DataTableCell>
								<DataTableCell numeric>
									{{ dollars(account.amount / 100) }}
									<br v-if="batchDifferences[account.id]">
									<span v-if="batchDifferences[account.id]" class="text-gray-400"
											:class="{'text-red-400': (account.amount / 100) + (
												batchDifferences[account.id].amount * batchDifferences[account.id].modifier
											) < 0}"
									>
										{{ dollars(
											(account.amount / 100) + (
												batchDifferences[account.id].amount * batchDifferences[account.id].modifier
											)
										) }}
									</span>
								</DataTableCell>
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
								<DataTableCell class="hidden md:table-cell" />
								<DataTableCell class="hidden lg:table-cell" />
								<DataTableCell class="hidden lg:table-cell" />
								<DataTableCell class="hidden lg:table-cell">
									Total:
									{{
										dollars(accounts.values.reduce((total, account) => {
											if (account && account.batch_updates?.[0]?.pivot?.amount) {
												let overMinimum = (account.amount / 100) - (
													Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
													-
													(
														((Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)) / idealWeeks(account.batch_updates?.[0]))
														*
														// How many weeks left till then
														fridaysUntil(toDateTime(account.batch_updates?.[0]?.date))
													))
												total += overMinimum <= account.amount ? overMinimum : account.amount
											}
											return total
										}, 0))
									}}
								</DataTableCell>
								<DataTableCell class="hidden sm:table-cell" />
								<DataTableCell numeric>
									Total:
									{{ dollars(accountsTotal) }}
									<br v-if="areAnyBatchDifferences">
									<span v-if="areAnyBatchDifferences">&nbsp;</span>
								</DataTableCell>
								<DataTableCell numeric>
									<div v-if="areAnyBatchDifferences">
										{{ dollars(batchTotal) }}
										<br>
										<span :class="{ 'text-red-700': accountsTotal + batchTotal < 0 }">
											{{ dollars(accountsTotal + batchTotal) }}
										</span>
									</div>
								</DataTableCell>
							</DataTableRow>
						</template>
					</DataTable>
				</div>

				<CircularScrim :loading="batchForm.processing" />
				<Teleport to="body">
					<div v-if="initiallyLoaded">
						<Fab
								v-if="!areAnyBatchDifferences"
								@click="newAccount"
								:icon="'add'"
								class="fixed right-4 bottom-6"
								style="z-index: 2;"
						/>
						<Fab
								v-else
								@click="saveBatch"
								icon="save"
								class="fixed right-4 bottom-6"
								style="z-index: 2;"
						/>
						<OutlinedTextfield
								type="date"
								v-if="areAnyBatchDifferences"
								v-model="batchForm.date"
								class="fixed bottom-0 right-20 opaque"
								style="z-index: 2;"
						>
							Date
						</OutlinedTextfield>
						<IconButton v-if="areAnyBatchDifferences" @click="clearBatchDifferences"
								class="bottom-8 left-4"
								style="position: fixed;"
						>
							close
						</IconButton>
					</div>
				</Teleport>

				<transition name="error-message">
					<p v-if="batchForm.errors.message" class="bg-red-200 rounded-3xl py-3 px-4 mb-10 break-word max-w-fit">
						{{ batchForm.errors.message }}
					</p>
				</transition>

				<transition name="opacity-0-scale-097-150ms">
					<div v-if="areAnyBatchDifferences" class="my-7">
						<input type="checkbox" v-model="batchForm.notify_me" id="notify_me">
						<label for="notify_me"> Notify me when this change is made</label>
					</div>
				</transition>

				<transition name="opacity-0-scale-097-150ms" mode="out-in">
					<div class="my-7" v-if="areAnyBatchDifferences">
						<OutlinedTextfield
								v-model="batchForm.weeks"
								type="number"
								step="1"
								autoselect
								autofocus
								v-if="batchForm.weeks != null"
						>
							Preferred # of weeks to pay by
						</OutlinedTextfield>
						<Button @click="batchForm.weeks = batchForm.weeks == null ? 4 : null">
							{{ batchForm.weeks == null ? 'Set preferred payment schedule' : 'Remove payment schedule' }}
						</Button>
					</div>
				</transition>

				<!-- Spacer block to allow scroll to get to buttons/messages behind the save button & date field -->
				<div class="my-32" v-if="areAnyBatchDifferences"></div>
      </div>


			<div class="my-7">
				<p v-for="message of messages" :key="message">{{ message }}</p>
			</div>
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
import { useAccounts, Account, AccountWithBatchUpdates } from '@/ts/store/accounts';
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
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import { TemplateWithAccounts } from '@/ts/store/templates';
import CircularScrim from '@/ts/core/loaders/CircularScrim.vue';
// @ts-ignore
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { BatchUpdate, BatchUpdateWithAccounts } from '@/ts/store/batchUpdates';

const auth = useAuth()
const route = useRoute()
const router = useRouter()
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

function preventFlatPickrChange(date: string, selectedDates: [], dateStr: string, instance: { setDate: Function }) {
	instance.setDate(date)
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
const accountsTotal = computed(() => accounts.values.map(i => i.amount / 100).reduce((a, c) => a + c, 0))

const sortedAccounts: Ref<(Account|AccountWithBatchUpdates)[]> = ref([])
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
				}) as (Account|AccountWithBatchUpdates)[]
				if (hideProgress.value) hideProgress.value()
				initiallySorted.value = true
				worker.terminate()
			}
		})
	},
	{ deep: true, immediate: true }
)

// Table helpers
function fridaysUntil(date: DateTime, now: DateTime = DateTime.now()): number {
	return (( // That date's previous friday
		date.startOf('day').weekday >= 5
			? date.startOf('day').set({ weekday: 5 })
			: date.startOf('day').minus({ weeks: 1 }).set({ weekday: 5 })
	).diff(
		// Now's previous friday
		now.startOf('day').weekday >= 5
			? now.startOf('day').set({ weekday: 5 })
			: now.startOf('day').minus({ weeks: 1 }).set({ weekday: 5 })
	).as('weeks'))
}
function toDateTime(date: string) {
	return DateTime.fromFormat(date, 'yyyy-MM-dd')
}
function idealWeeks(batchUpdate: BatchUpdate) {
	return batchUpdate?.weeks ?? 4
}
function idealPayment(batchUpdate: BatchUpdate & { pivot: { amount: number }}) {
	return (Math.abs(batchUpdate?.pivot?.amount / 100)) / idealWeeks(batchUpdate)
}
function emergencySaving(account: AccountWithBatchUpdates) {
	let batchUpdate = account.batch_updates[0]
	let currentBalance = account.amount / 100
	let paymentToCover = Math.abs(batchUpdate.pivot.amount / 100)
	let weeks = fridaysUntil(toDateTime(batchUpdate.date)) || 1
	let perWeek = (paymentToCover - currentBalance) / weeks
	return dollars(perWeek)
}
function isAccountWithBatchUpdates(account: Account|AccountWithBatchUpdates): account is AccountWithBatchUpdates
{
	return !!account.batch_updates?.[0]
}

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
	currentlyEditingDifference.value = null
}
function edit(account: Account) {
	currentlyEditingDifference.value = account.id
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
const batchForm = useForm('/api/accounts/batch', {
	accounts: batchDifferences.value,
	date: batchDate.value.toFormat('yyyy-MM-dd'),
	notify_me: false,
	weeks: null as number|null
})
onMounted(() => {
	if (route.params.template) {
		let template: TemplateWithAccounts = JSON.parse(route.params.template as string)
    batchForm.reset({
      ...batchForm,
      ...template,
      accounts: template.accounts.reduce((a, c) => {
        a[c.id] = {
          amount: Math.abs(c.pivot.amount / 100),
          modifier: c.pivot.amount >= 0 ? 1 : -1
        }
        return a
      }, {} as { [key: number]: { amount: number, modifier: 1|-1 } })
    })
    batchDifferences.value = batchForm.accounts
		router.replace({ params: {} })
	}
})
async function saveBatch() {
	batchForm.reset({ accounts: batchDifferences.value, date: batchForm.date })
	// @ts-ignore (forms assume the response looks like their data, this one's doesn't)
	let data = await batchForm.post() as { accounts: [] } // Will be an AccountBatchUpdate, either done, or to be done later
	// has accounts, which have pivots describing the change
	// has audits, but are not returned here (I imagine they won't be useful here)
	for (let account of data.accounts) accounts.receive(account)
	clearBatchDifferences()
	currentlyEditingDifference.value = null
	batchDate.value	= DateTime.now()
	batchForm.date = batchDate.value.toFormat('yyyy-MM-dd')
}
onBeforeRouteLeave(async () => {
	try {
		if (areAnyBatchDifferences.value) await modals.confirm("Do you really want to leave unsaved changes?")
	} catch (e) {
		return false
	}
})

/**
	---------------------------------------------------
	| Directly editing accounts
	---------------------------------------------------
 */
const modals = useModals()
function newAccount() {
	modals.open({
		modal: markRaw(AccountModalVue), props: {}
	})
}
function editAccount(id: number) {
	modals.open({
		modal: markRaw(AccountModalVue),
		props: { accountId: id }
	})
}
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

:deep(.mdc-data-table) {
	.mdc-data-table__cell {
		padding-inline: 8px;
		&:first-child { padding-left: 16px; }
		&:last-child { padding-right: 16px; }
	}
}
</style>