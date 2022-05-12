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

      <!--
        Dashboard
      -->
      <div v-if="auth.authenticated">
        <h1 class="text-xl mt-5">Accounts</h1>
				<div v-if="initiallyLoaded">
					<p v-if="!sortedAccounts.length" class="m-5">
						✨ No accounts ✨
					</p>
					<DataTable @sort="updateSort" v-if="sortedAccounts.length" style="max-height: 75vh;">
						<template #header>
							<DataTableHeaderCell sortable column-id="name" :sort="sort.name">
								Name
							</DataTableHeaderCell>
							<DataTableHeaderCell>
								Next
							</DataTableHeaderCell>
							<DataTableHeaderCell sortable numeric column-id="amount" :sort="sort.amount">
								Amount
							</DataTableHeaderCell>
							<DataTableHeaderCell>
								<!-- Controls -->&nbsp;
							</DataTableHeaderCell>
						</template>
						<template #body>
							<DataTableRow v-for="account of sortedAccounts">
								<DataTableCell @click="editAccount(account.id)">{{ account.name }}</DataTableCell>
								<DataTableCell />
								<DataTableCell numeric>{{ dollars(account.amount / 100) }}</DataTableCell>
								<DataTableCell>
									<IconButton>remove</IconButton>
									<IconButton>add</IconButton>
								</DataTableCell>
							</DataTableRow>
							<DataTableRow>
								<DataTableCell />
								<DataTableCell />
								<DataTableCell numeric>{{ dollars(accounts.values.map(i => i.amount / 100).reduce((a, c) => a + c, 0)) }}</DataTableCell>
								<DataTableCell />
							</DataTableRow>
						</template>
					</DataTable>
				</div>

				<div v-if="initiallyLoaded">
					<Button @click="newAccount">New account</Button>
					<Button @click="accounts.fetchData">Fetch accounts</Button>
				</div>
      </div>


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

const prod = import.meta.env.PROD
const baseUrl = import.meta.env.VITE_DEV_SERVER_URL

const auth = useAuth()

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
const sort = useLocalStorage('budget-accounts-index-sort', {
	name: {
		value: 'none',
		at: null as number|null,
	},
	amount: {
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
			accounts: JSON.stringify(accounts.values),
			sort: JSON.stringify(sort.value)
		})
		worker.addEventListener('message', event => {
			if (event.data?.type == 'SORT_ACCOUNTS') {
				sortedAccounts.value = JSON.parse(event.data?.accounts) as Account[]
				if (hideProgress.value) hideProgress.value()
				if (initiallyLoadedAccounts.value) initiallySorted.value = true
				worker.terminate()
			}
		})
	},
	{ deep: true, immediate: true }
)

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
@use "@material/typography";
@include typography.core-styles;

code {
	background-color: #eee;
	padding: 2px 4px;
	border-radius: 4px;
	color: #304455;
}
</style>
