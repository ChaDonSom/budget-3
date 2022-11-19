import { UpdateInTable } from "@/batchUpdates";
import { Dollars } from "@/core/utilities/currency";
import type { AccountWithBatchUpdates } from "@/store/accounts";
import type { MaybeRef } from "@vueuse/core";
import { computedEager } from "@vueuse/shared";
import { computed, unref } from "vue";

export function usePlanning(account: MaybeRef<AccountWithBatchUpdates>) {
    const updatesSorted = computed(() => {
        if (!unref(account)) return []
        return unref(account)?.batch_updates?.map(b => new UpdateInTable(b))
            .sort((a, b) => a.date.valueOf() - b.date.valueOf())
    })

    const minTotal = computed(() => {
        return new Dollars(updatesSorted.value?.reduce((total, row) => total + Number(row.idealMin), 0) ?? 0)
    })
    function rateForWeek(week: number) {
        return new Dollars(
            updatesSorted.value
                ?.filter(r => r.appliesToWeeksFromNow(week))
                .reduce((total, r) => total + Number(r.idealRate), 0)
                ?? 0
        )
    }
    const currentRate = computedEager(() => rateForWeek(1))
    const maxWeeks = computed(() => {
        return updatesSorted.value?.reduce((w, row) => row.weeksLeft > w ? row.weeksLeft : w, 0)
    })
    const ratesEachWeek = computed(() => {
        return Array(maxWeeks.value).fill(1).map((i, index) => rateForWeek(index + 1))
    })

    return {
        updatesSorted,
        minTotal,
        rateForWeek,
        currentRate,
        maxWeeks,
        ratesEachWeek,
    }
}