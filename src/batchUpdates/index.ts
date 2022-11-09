import type { BatchDifference } from "@/home";
import { useAccountsStore } from "@/store/accounts";
import { useForm } from "@/store/forms";
import { DateTime } from "luxon";
import { computed, ref } from "vue";

export const batchDifferences = ref({} as { [key: number]: BatchDifference })
export const areAnyBatchDifferences = computed(() => Boolean(Object.keys(batchDifferences.value).length))
export const batchDate = ref<DateTime>(DateTime.now())
export const batchForm = useForm('/api/batch-updates', {
	accounts: batchDifferences.value,
	date: batchDate.value.toFormat('yyyy-MM-dd'),
	notify_me: false,
	weeks: null as number|null,
	note: '',
})
export const accountsTotal = computed(() => useAccountsStore().values.map(i => i.amount / 100).reduce((a, c) => a + c, 0))
export const batchTotal = computed(() => Object.values(batchDifferences.value).map(i => i.resolved).reduce((a, c) => a + c, 0))

export const currentlyEditingDifference = ref<number|null>(null)
export function clearBatchDifferences() {
	batchDifferences.value = {}
	currentlyEditingDifference.value = null
}
export async function saveBatch() {
	batchForm.reset({ accounts: batchDifferences.value, date: batchForm.date })
	// @ts-ignore (forms assume the response looks like their data, this one's doesn't)
	let data = await batchForm.post() as { accounts: [] } // Will be an AccountBatchUpdate, either done, or to be done later
	// has accounts, which have pivots describing the change
	// has audits, but are not returned here (I imagine they won't be useful here)
	for (let account of data.accounts) useAccountsStore().receive(account)
	clearBatchDifferences()
	currentlyEditingDifference.value = null
	batchDate.value	= DateTime.now()
	batchForm.date = batchDate.value.toFormat('yyyy-MM-dd')
}