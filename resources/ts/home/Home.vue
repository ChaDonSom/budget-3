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
				<DataTable>
					<template #header>
						<DataTableHeaderCell>Name</DataTableHeaderCell>
						<DataTableHeaderCell numeric>Amount</DataTableHeaderCell>
					</template>
					<template #body>
						<DataTableRow v-for="account of values">
							<DataTableCell>{{ account.name }}</DataTableCell>
							<DataTableCell numeric>{{ dollars(account.amount / 100) }}</DataTableCell>
						</DataTableRow>
					</template>
				</DataTable>
      </div>


			<div class="my-7">
				<p v-for="message of messages" :key="message">{{ message }}</p>
			</div>
			<div class="my-7" v-if="auth.authenticated">
				<Button @click="sendPushNotification">Send myself a push notification</Button>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, defineComponent, reactive, onMounted, computed, toRefs } from 'vue';
import Button from '@/ts/core/buttons/Button.vue'
import { vite_asset } from '@/ts/core/utilities/build'
import { useAuth } from '../core/users/auth';
import { useEcho } from '../store/echo';
import axios from 'axios';
import { useAccounts } from '@/ts/store/accounts';
import { dollars } from '@/ts/core/utilities/currency'
import DataTable from '@/ts/core/tables/DataTable.vue';
import DataTableHeaderCell from '@/ts/core/tables/DataTableHeaderCell.vue';
import DataTableRow from '@/ts/core/tables/DataTableRow.vue';
import DataTableCell from '@/ts/core/tables/DataTableCell.vue';

const prod = import.meta.env.PROD
const baseUrl = import.meta.env.VITE_DEV_SERVER_URL

const auth = useAuth()

const messages = ref([])
const echo = useEcho()
onMounted(() => {
	// The '.' in '.my-event' means we'll listen on 'my-channel' instead of 'App\Events.my-channel'
	// That way, we can mess around with this from the Pusher event creator
	echo.echo.channel('my-channel').listen('.my-event', data => {
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

const {
	getData,
	values,
} = useAccounts()
getData()
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
