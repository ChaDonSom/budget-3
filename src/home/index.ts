import { dollars } from "@/core/utilities/currency";
import { toDateTime } from "@/core/utilities/datetime";
import type { Account, AccountWithBatchUpdates } from "@/store/accounts";
import type { BatchUpdate } from "@/store/batchUpdates";
import { useLocalStorage } from "@vueuse/core";
import { DateTime } from "luxon";
import { ref } from "vue";

export function idealWeeks(batchUpdate: BatchUpdate) {
    return batchUpdate?.weeks ?? 4;
}

export function idealPayment(
    batchUpdate: BatchUpdate & { pivot: { amount: number } }
) {
    return Math.abs(batchUpdate?.pivot?.amount / 100) / idealWeeks(batchUpdate);
}

export function paydaysUntil(date: DateTime, now: DateTime = DateTime.now()): number {
    const PAYDAY = 4; // TODO: Make this configurable
    return Math.round(
        // That date's previous PAYDAY
        (
            date.startOf("day").weekday >= PAYDAY
                ? date.startOf("day").set({ weekday: PAYDAY })
                : date.startOf("day").minus({ weeks: 1 }).set({ weekday: PAYDAY })
        )
            .diff(
                // Now's previous PAYDAY
                now.startOf("day").weekday >= PAYDAY
                    ? now.startOf("day").set({ weekday: PAYDAY })
                    : now.startOf("day").minus({ weeks: 1 }).set({ weekday: PAYDAY })
            )
            .as("weeks")
    );
}

export const weeksUntil = paydaysUntil

export function isAccountWithBatchUpdates(
    account: Account | AccountWithBatchUpdates
): account is AccountWithBatchUpdates {
    return !!account.batch_updates?.[0];
}

export function emergencySaving(account: AccountWithBatchUpdates) {
    let batchUpdate = account.batch_updates[0];
    let currentBalance = account.amount / 100;
    let paymentToCover = Math.abs(batchUpdate.pivot.amount / 100);
    let weeks = paydaysUntil(toDateTime(batchUpdate.date)) || 1;
    let perWeek = (paymentToCover - currentBalance) / weeks;
    return dollars(perWeek);
}

export function minimumToMakeNextPayment(account: AccountWithBatchUpdates) {
    return Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
        - (idealPayment(account.batch_updates?.[0]) * paydaysUntil(toDateTime(account.batch_updates?.[0]?.date)))
}

export function minimumToMakeAllExistingScheduledPayments(account: AccountWithBatchUpdates) {
    let batchUpdates = account.batch_updates.filter(update => {
        return toDateTime(update.date) > DateTime.now() && !update.done_at
    })
    let result = batchUpdates.reduce((total, update) => {
        total += (
            Math.abs(update.pivot?.amount / 100)
                - (idealPayment(update) * paydaysUntil(toDateTime(update?.date)))
        )
        return total
    }, 0)
    return result
}

export class BatchDifference {
	public amount: number = 0
	public modifier: 1|-1 = 1
	constructor({ amount, modifier }: { amount: number, modifier: 1|-1 }) {
		this.amount = amount
		this.modifier = modifier
	}
	get resolved() {
		return this.amount * this.modifier
	}
}
export const latestBatchDifference = ref<BatchDifference|null>(null)

export const columnsToShow = useLocalStorage("budget-home-table-columns-to-show", {
    name: true,
    nextDate: false,
    nextAmount: false,
    minimum: false,
    overMinimum: false,
    percentCovered: false,
    amount: true,
});

export const homeSettings = useLocalStorage('budget-home-settings', {
    historyButtons: false,
})

export function accountIsOffMinimum(overMinimum: number) {
    return Math.floor(Math.abs(overMinimum * 100)) != 0
}