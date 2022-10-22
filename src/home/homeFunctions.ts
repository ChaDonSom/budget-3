import { dollars } from "@/core/utilities/currency";
import { toDateTime } from "@/core/utilities/datetime";
import type { Account, AccountWithBatchUpdates } from "@/store/accounts";
import type { BatchUpdate } from "@/store/batchUpdates";
import { DateTime } from "luxon";

export function idealWeeks(batchUpdate: BatchUpdate) {
    return batchUpdate?.weeks ?? 4;
}

export function idealPayment(
    batchUpdate: BatchUpdate & { pivot: { amount: number } }
) {
    return Math.abs(batchUpdate?.pivot?.amount / 100) / idealWeeks(batchUpdate);
}

export function fridaysUntil(date: DateTime, now: DateTime = DateTime.now()): number {
    return (
        // That date's previous friday
        (
            date.startOf("day").weekday >= 5
                ? date.startOf("day").set({ weekday: 5 })
                : date.startOf("day").minus({ weeks: 1 }).set({ weekday: 5 })
        )
            .diff(
                // Now's previous friday
                now.startOf("day").weekday >= 5
                    ? now.startOf("day").set({ weekday: 5 })
                    : now.startOf("day").minus({ weeks: 1 }).set({ weekday: 5 })
            )
            .as("weeks")
    );
}

export function isAccountWithBatchUpdates(
    account: Account | AccountWithBatchUpdates
): account is AccountWithBatchUpdates {
    return !!account.batch_updates?.[0];
}

export function emergencySaving(account: AccountWithBatchUpdates) {
    let batchUpdate = account.batch_updates[0];
    let currentBalance = account.amount / 100;
    let paymentToCover = Math.abs(batchUpdate.pivot.amount / 100);
    let weeks = fridaysUntil(toDateTime(batchUpdate.date)) || 1;
    let perWeek = (paymentToCover - currentBalance) / weeks;
    return dollars(perWeek);
}

export function minimumToMakeNextPayment(account: AccountWithBatchUpdates) {
    return Math.abs(account.batch_updates?.[0]?.pivot?.amount / 100)
        - (idealPayment(account.batch_updates?.[0]) * fridaysUntil(toDateTime(account.batch_updates?.[0]?.date)))
}