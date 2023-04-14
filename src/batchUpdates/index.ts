import { Dollars } from "@/core/utilities/currency";
import { toDateTime } from "@/core/utilities/datetime";
import { weeksUntil, type BatchDifference } from "@/home";
import { useAccountsStore } from "@/store/accounts";
import type { BatchUpdate } from "@/store/batchUpdates";
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
	batchForm.note = ''
}

export class UpdateInTable {
  b: BatchUpdate & { pivot: { amount: number } }
  constructor(b: BatchUpdate & { pivot: { amount: number } }) {
    this.b = b
  }

  get amount() { return new Dollars(this.b.pivot.amount / 100) }
  get date() { return (function(date) {
    let me = toDateTime(date)
    me.toString = () => me.toFormat('M/dd')
    return me
  })(this.b.date) }

  get weeks() {
    return this.b.weeks ?? 4
  }
  get weeksLeft() {
    return weeksUntil(this.date)
  }
  get weeksUntilIdealStart() {
    return this.weeksLeft > this.weeks ? this.weeksLeft - this.weeks : 0
  }
  appliesToWeeksFromNow(week: number) {
    return this.weeksLeft >= week && this.weeksUntilIdealStart < week
  }
  get ideallyCoveredWeeks() {
    return this.weeks - this.weeksLeft
  }

  // Really, both of these reference another idea we could extract. Amount left to cover. Either the whole thing
  // (to figure out the ideal), or whatever's currently left, to figure out the real / practical.
  get idealRate() {
    return new Dollars(Math.abs(Number(this.amount)) / (this.b.weeks ?? 4))
  }
  // minRate() { (this.amount - this.accounts.reduce(() => amount (less any non-ideally-covered updates))) / this.weeksLeft }
  get idealMin() {
    let n = Number(this.idealRate) * this.ideallyCoveredWeeks
    if (n < 0) n = 0
    return new Dollars(n)
  }
  // idealMin doesn't have a 'min' analogue?
  // realMin() { minRate * weeksLeft }
}