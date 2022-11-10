<template>
	<div>
		<div class="text-center mx-0 md:mx-3 max-h-screen relative">
      <!--
        Welcome sign :)
      -->
			<div class="flex justify-center" v-if="!auth.authenticated">
				<img src="/android-chrome-512x512.png" class="m-5 w-3/12">
			</div>
			<h1 class="text-3xl sm:text-5xl md:text-7xl font-thin" v-if="!auth.authenticated">Welcome to Somero Budget</h1>
			<h2 class="text-xl italic mt-3" v-if="$route.params.securityLoggedOut">
				({{ $route.params.securityLoggedOut }})
			</h2>
      <!--
        Dashboard
      -->
      <div v-if="auth.authenticated" class="max-h-screen">
        <h1 class="text-xl pb-2 pt-3">Budget</h1>
				<div v-if="initiallyLoaded">
					<p v-if="!sortedAccounts.length" class="m-5">
						✨ No accounts ✨
					</p>
					<DataTable
							v-if="sortedAccounts.length"
							style="max-height: calc(100vh - 3rem)"
							class="max-w-full md:max-w-[95vw]"
							@sort="updateSort"
					>
						<template #header>
							<DataTableHeaderCell sortable column-id="name" :sort="sort.name"
									:class="{ 'hidden': !columnsToShow.name }"
							>
								Name
							</DataTableHeaderCell>
							<DataTableHeaderCell sortable column-id="nextDate" :sort="sort.nextDate"
									:class="{ 'hidden': !columnsToShow.nextDate }"
							>
								Next date
							</DataTableHeaderCell>
							<DataTableHeaderCell
									sortable
									numeric
									column-id="nextAmount"
									:sort="sort.nextAmount"
									:class="{ 'hidden': !columnsToShow.nextAmount }"
							>
								Next amount
							</DataTableHeaderCell>
							<DataTableHeaderCell
									sortable
									numeric
									column-id="minimum"
									:sort="sort.minimum"
									:class="{ 'hidden': !columnsToShow.minimum }"
									v-tooltip="'Minimum current balance needed to make the payment on time with ideal weekly saving'"
							>
								Minimum
							</DataTableHeaderCell>
							<DataTableHeaderCell
									:class="{ 'hidden': !columnsToShow.overMinimum }"
									sortable
									numeric
									column-id="overMinimum"
									:sort="sort.overMinimum"
							>
								Over / under min.
							</DataTableHeaderCell>
							<DataTableHeaderCell
									:class="{ 'hidden': !columnsToShow.percentCovered }"
									sortable
									numeric
									column-id="percentCovered"
									:sort="sort.percentCovered"
							>
								% covered
							</DataTableHeaderCell>
							<DataTableHeaderCell sortable numeric column-id="amount" :sort="sort.amount"
									:class="{ 'hidden': !columnsToShow.amount }"
							>
								Amount
							</DataTableHeaderCell>
							<DataTableHeaderCell numeric>
								<IconButton @click="editTableSettings">more_vert</IconButton>
							</DataTableHeaderCell>
						</template>
						<template #body>
							<DataTableRow
									v-for="account of sortedAccounts"
									:key="account.id"
									:style="{ 'background-color': (sort.nextDate.value != 'none' && ((isAccountWithBatchUpdatesAndDisplayFields(account) ? weeksUntil(toDateTime(account.batch_updates?.[0]?.date)) : 1) % 2 == 0)) ? 'rgba(0,0,0,0.09)' : 'unset' }"
							>
								<!-- Name -->
								<DataTableCell @click="editAccount(account.id)" style="white-space: normal;"
										:class="{ 'hidden': !columnsToShow.name }"
								>
									<div class="flex items-center gap-1">
										<RouterLink :to="{ name: 'history', query: { account_id: account.id } }">
											<IconButton
													v-if="homeSettings.historyButtons"
													v-tooltip="`Transaction history`"
													:density="-5"
													@click.stop="() => {}"
											>
												history
											</IconButton>
										</RouterLink>
										<IconButton
												v-if="account.favorited_users?.some(i => i.id == auth.user?.id)"
												:density="-5"
												primary
												v-tooltip="`Favorite`"
										>
											push_pin
										</IconButton>
										{{ account.name }}
									</div>
								</DataTableCell>
								<!-- Next withdrawal date -->
								<DataTableCell :class="{ 'hidden': !columnsToShow.nextDate }" numeric>
									<div
											v-if="isAccountWithBatchUpdatesAndDisplayFields(account)"
											@click="$router.push({ name: 'batch-updates-show', params: { id: account.batch_updates?.[0]?.id } })"
									>
										{{ toDateTime(account.nextDate).toFormat('M/dd') }}
									</div>
								</DataTableCell>
								<!-- Next withdrawal amount -->
								<DataTableCell :class="{ 'hidden': !columnsToShow.nextAmount }" numeric>
									<div v-if="isAccountWithBatchUpdatesAndDisplayFields(account)">
										{{ dollars(account.nextAmount / 100) }}
									</div>
								</DataTableCell>
								<!-- Minimum preferred amount -->
								<DataTableCell numeric :class="{ 'hidden': !columnsToShow.minimum }">
									<div
											v-if="isAccountWithBatchUpdatesAndDisplayFields(account)"
											class="text-gray-400 select-none whitespace-nowrap"
											v-tooltip="{
												content: tooltipToCompareIdealVsEmergency(account),
												html: true
											}"
									>
										{{ dollars(account.minimum) }}
									</div>
								</DataTableCell>
								<!-- Over / under minimum -->
								<DataTableCell :class="{ 'hidden': !columnsToShow.overMinimum }" numeric>
									<div
											v-if="isAccountWithBatchUpdatesAndDisplayFields(account)"
											v-tooltip="(account.amount / 100) < account.overMinimum ? `True amount is only ${dollars((account.amount / 100))}` : ''"
											:class="{
												'text-gray-500': Math.floor(account.overMinimum * 100) >= 0,
												'text-red-500': Math.floor(account.overMinimum * 100) < 0,
												'italic text-orange-500': (account.amount / 100) < account.overMinimum,
											}"
											class="whitespace-nowrap"
									>
										{{ accountIsOffMinimum(account.overMinimum) ? dollars(account.overMinimum) : '' }}
										<!-- Over / under if difference will be saved -->
										<br v-if="batchDifferences[account.id]">
										<span v-if="batchDifferences[account.id]" class="text-gray-400"
												:class="{'text-red-400': account.overMinimum + batchDifferences[account.id].resolved < 0}"
										>
											{{ dollars(account.overMinimum + batchDifferences[account.id].resolved) }}
										</span>
									</div>
								</DataTableCell>
								<!-- Percent covered of next payment -->
								<DataTableCell :class="{ 'hidden': !columnsToShow.percentCovered }" numeric>
									<div
											v-if="isAccountWithBatchUpdatesAndDisplayFields(account)"
											v-tooltip="`Required: ${progressedTimeTowardNextBatchUpdatePercent(account)}% (${
													dollars(idealProgressTowardNextBatchUpdate(account))
												})
											`"
											class="select-none"
											:class="{
												'text-blue-600': account.percentCovered == progressedTimeTowardNextBatchUpdatePercent(account),
												'text-green-600': account.percentCovered > progressedTimeTowardNextBatchUpdatePercent(account),
											}"
									>
										{{ account.percentCovered }} %
									</div>
								</DataTableCell>
								<!-- Current amount -->
								<DataTableCell
										numeric
										:class="{
											'hidden': !columnsToShow.amount,
											'text-red-600': ((account.amount / 100) + batchDifferences[account.id]?.resolved) < 0
										}"
								>
									{{ dollars(account.amount / 100) }}
									<!-- New amount if difference will be saved -->
									<br v-if="batchDifferences[account.id]">
									<span v-if="batchDifferences[account.id]" class="text-gray-400"
											:class="{'text-red-400': (account.amount / 100) + batchDifferences[account.id].resolved < 0}"
									>
										{{ dollars((account.amount / 100) + batchDifferences[account.id].resolved) }}
									</span>
								</DataTableCell>
								<!-- Add / subtract actions -->
								<DataTableCell numeric style="cursor: pointer;" @click="batchDifferences[account.id] ? edit(account) : null">
									<div v-if="currentlyEditingDifference != account.id && !batchDifferences[account.id]"
											style="white-space: nowrap;"
									>
										<IconButton :density="-3" @click.stop="startWithdrawing(account)">remove</IconButton>
										<IconButton :density="-3" @click.stop="startDepositing(account)">add</IconButton>
									</div>
									<div
											v-else-if="batchDifferences[account.id]"
											@click.stop="edit(account)"
											class="w-full h-full flex flex-wrap items-center gap-2"
											style="white-space: nowrap;"
									>
										<IconButton
												:density="-5"
												class="sm:mr-2"
												@click.stop="clearBatchDifferenceFor(account)"
										>close</IconButton>
										{{ batchDifferences[account.id].modifier == 1 ? '+ ' : '' }}
										{{ dollars(batchDifferences[account.id].resolved) }}
									</div>
								</DataTableCell>
							</DataTableRow>
							<!-- Bottom sticky row (totals) -->
							<DataTableRow class="sticky-bottom-row">
								<DataTableCell :class="{ 'hidden': !columnsToShow.name }"/>
								<DataTableCell :class="{ 'hidden': !columnsToShow.nextDate }" />
								<DataTableCell :class="{ 'hidden': !columnsToShow.nextAmount }" />
								<DataTableCell :class="{ 'hidden': !columnsToShow.minimum }" />
								<!-- Total over / under minimum -->
								<DataTableCell :class="{ 'hidden': !columnsToShow.overMinimum }" numeric style="white-space: normal;">
									Total:
									{{ dollars(overMinimumTotal) }}
									<br v-if="areAnyBatchDifferences">
									<span v-if="areAnyBatchDifferences" class="text-gray-500" :class="{ 'text-red-500': overMinimumTotal + batchTotal < 0 }">
										{{ dollars(overMinimumTotal + batchTotalOfOffMinimumAccounts) }}
									</span>
								</DataTableCell>
								<DataTableCell :class="{ 'hidden': !columnsToShow.percentCovered }" />
								<!-- Total current amount -->
								<DataTableCell numeric style="white-space: normal;" :class="{ 'hidden': !columnsToShow.amount }">
									Total:
									{{ dollars(accountsTotal) }}
									<br v-if="areAnyBatchDifferences">
									<span v-if="areAnyBatchDifferences">&nbsp;</span>
								</DataTableCell>
								<!-- Total action changes -->
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

				<CircularScrim :loading="batchForm.processing || loading" />
				<Teleport to="body">
					<div v-if="initiallyLoaded">
						<Fab
								v-if="!areAnyBatchDifferences"
								@click="newAccount"
								:icon="'add'"
								small
								class="fixed right-4 bottom-4"
								style="z-index: 2;"
						/>
						<RouterLink v-if="areAnyBatchDifferences" :to="{ name: 'batch-updates-detail', params: { 'id': 'new' } }">
							<Fab
									icon="check"
									class="fixed left-3 bottom-3"
									style="z-index: 2;"
							/>
						</RouterLink>
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

				<div class="my-7" v-if="messages.length">
					<p v-for="message of messages" :key="message">{{ message }}</p>
				</div>
      </div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, defineComponent, reactive, onMounted, computed, toRefs, watch, type Ref, markRaw } from 'vue';
import Button from '@/core/buttons/Button.vue'
import { useAuth } from '../core/users/auth';
import { useEcho } from '../store/echo';
import axios from 'axios';
import { useAccounts, type Account, type AccountWithBatchUpdates, useAccountsStore } from '@/store/accounts';
import { dollars } from '@/core/utilities/currency'
import DataTable from '@/core/tables/DataTable.vue';
import DataTableHeaderCell from '@/core/tables/DataTableHeaderCell.vue';
import DataTableRow from '@/core/tables/DataTableRow.vue';
import DataTableCell from '@/core/tables/DataTableCell.vue';
import { useLocalStorage } from '@vueuse/core';
import IconButton from '@/core/buttons/IconButton.vue';
import Fab from '@/core/buttons/Fab.vue';
import { useModals } from '@/store/modals';
import AccountModalVue from '@/accounts/AccountModal.vue';
import DollarsField from '@/core/fields/DollarsField.vue';
import FloatingDifferenceInputModalVue from '@/home/FloatingDifferenceInputModal.vue';
import { useForm } from '@/store/forms';
import { DateTime } from 'luxon'
import OutlinedTextfield from '@/core/fields/OutlinedTextfield.vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import type { TemplateWithAccounts } from '@/store/templates';
import CircularScrim from '@/core/loaders/CircularScrim.vue';
// @ts-ignore
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { type BatchUpdate, type BatchUpdateWithAccounts } from '@/store/batchUpdates';
import { toDateTime } from '@/core/utilities/datetime';
import {
	idealPayment,
	idealWeeks,
	weeksUntil,
	isAccountWithBatchUpdates,
	emergencySaving,
	minimumToMakeNextPayment,
	BatchDifference,
	columnsToShow,
	homeSettings,
	accountIsOffMinimum,
} from '@/home';
import TableSettingsModal from '@/home/TableSettingsModal.vue'
import MdcSwitch from '../core/switches/MdcSwitch.vue';
import { accountsTotal, areAnyBatchDifferences, batchDate, batchDifferences, batchForm, batchTotal, clearBatchDifferences, currentlyEditingDifference } from '@/batchUpdates';

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

function editTableSettings() {
	modals.open({
		modal: markRaw(TableSettingsModal),
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

const accounts = useAccountsStore()
accounts.fetchData().then(() => initiallyLoadedAccounts.value = true)
const overMinimumTotal = computed(() => {
	return sortedAccounts.value.reduce((total, account) => {
		if (account && isAccountWithBatchUpdatesAndDisplayFields(account) && account.batch_updates?.[0]?.pivot?.amount) {
			total += account.overMinimum <= (account.amount / 100)
				? account.overMinimum
				: (account.amount / 100)
		}
		return total
	}, 0)
})


type AccountWithBatchUpdatesAndSortedFields = AccountWithBatchUpdates & {
	nextDate: string,
	nextAmount: number,
	minimum: number,
	overMinimum: number,
	percentCovered: number,
}
function isAccountWithBatchUpdatesAndDisplayFields(
	account: Account | AccountWithBatchUpdates | AccountWithBatchUpdatesAndSortedFields
): account is AccountWithBatchUpdatesAndSortedFields {
	return !!account.batch_updates?.[0];
}
const sortedAccounts: Ref<(Account|AccountWithBatchUpdatesAndSortedFields)[]> = ref([])
const sort = useLocalStorage('budget-accounts-index-sort-v5', {
	isFavorite: {
		value: 'descending',
		at: DateTime.now().valueOf()
	},
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
	},
	minimum: {
		value: 'none',
		at: null as number|null,
	},
	overMinimum: {
		value: 'none',
		at: null as number|null,
	},
	percentCovered: {
		value: 'none',
		at: null as number|null,
	},
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
				minimum: isAccountWithBatchUpdates(account) ? minimumToMakeNextPayment(account) : null,
				overMinimum: isAccountWithBatchUpdates(account)
					? (account.amount / 100) - minimumToMakeNextPayment(account)
					: null,
				percentCovered: isAccountWithBatchUpdates(account)
					? Math.round(((account.amount / 100) / (Math.abs(account.batch_updates?.[0]?.pivot?.amount) / 100)) * 100)
					: null,
				isFavorite: Boolean(account.favorited_users?.some(i => i.id == auth.user?.id))
			}))),
			sort: JSON.stringify(sort.value)
		})
		worker.addEventListener('message', event => {
			if (event.data?.type == 'SORT_ACCOUNTS') {
				sortedAccounts.value = JSON.parse(event.data?.accounts).map((a: Account & { [key: string]: any }) => {
					let result = a
					return result
				}) as (Account|(AccountWithBatchUpdates & {
					nextDate?: string,
					nextAmount?: number,
					minimum?: number,
					overMinimum?: number,
					percentCovered?: number,
				}))[]
				if (hideProgress.value) hideProgress.value()
				initiallySorted.value = true
				worker.terminate()
			}
		})
	},
	{ deep: true, immediate: true }
)
const batchTotalOfOffMinimumAccounts = computed(() => Object.keys(batchDifferences.value)
	.filter(i => {
		let account = sortedAccounts.value.find(j => j.id == Number(i))
		if (account && isAccountWithBatchUpdatesAndDisplayFields(account)) {
			return accountIsOffMinimum(account.overMinimum)
		}
	})
	.map(i => batchDifferences.value[Number(i)])
	.map(i => i.resolved)
	.reduce((a, c) => a + c, 0))

function tooltipToCompareIdealVsEmergency(account: AccountWithBatchUpdates) {
	let idealWeeksForAccount = idealWeeks(account.batch_updates?.[0])
	let weeksUntilForAccount = weeksUntil(toDateTime(account.batch_updates?.[0]?.date))
	return `
		Ideally ${dollars(idealPayment(account.batch_updates?.[0]))} / week for
		${idealWeeksForAccount} week${idealWeeksForAccount == 1 ? '' : 's'}<br>
		Emergency ${emergencySaving(account)} / week for
		${weeksUntilForAccount} week${weeksUntilForAccount == 1 ? '' : 's'}
	`
}

function progressedTimeTowardNextBatchUpdatePercent(account: AccountWithBatchUpdates) {
	return Math.round((
		(
			idealWeeks(account.batch_updates?.[0])
			- weeksUntil(toDateTime(account.batch_updates?.[0]?.date))
		)
		/ idealWeeks(account.batch_updates?.[0])
	) * 100)
}

function idealProgressTowardNextBatchUpdate(account: AccountWithBatchUpdates) {
	return Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
		- (idealPayment(account.batch_updates?.[0]) * weeksUntil(toDateTime(account.batch_updates?.[0]?.date)))
}

/**
	---------------------------------------------------
	| Setting up withdraw/deposit batches
	---------------------------------------------------
 */
function startWithdrawing(account: Account) {
	currentlyEditingDifference.value = account.id
	batchDifferences.value[account.id] = new BatchDifference({
		amount: 0,
		modifier: -1
	})
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
function startDepositing(account: Account) {
	currentlyEditingDifference.value = account.id
	batchDifferences.value[account.id] = new BatchDifference({
		amount: 0,
		modifier: 1
	})
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
function clearBatchDifferenceFor(account: Account) {
	delete batchDifferences.value[account.id]
	if (currentlyEditingDifference.value == account.id) currentlyEditingDifference.value = null
}
function edit(account: Account) {
	currentlyEditingDifference.value = account.id
	modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
		difference: batchDifferences.value[account.id],
	} })
}
onMounted(() => {
	if (route.params.template) {
		let template: TemplateWithAccounts = JSON.parse(route.params.template as string)
    batchForm.reset({
      ...batchForm.internalForm,
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
const loading = ref(false)
async function editAccount(id: number) {
	loading.value = true
	await accounts.fetchAccount(id)
	modals.open({
		modal: markRaw(AccountModalVue),
		props: { accountId: id }
	})
	loading.value = false
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
			background-color: var(--color-background);
		}
		input.mdc-text-field__input {
			z-index: 2;
		}
	}
}
</style>