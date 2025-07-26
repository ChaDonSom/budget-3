<script lang="ts" setup>
import DataTableRow from "@/core/tables/DataTableRow.vue"
import { sort, weeksUntil } from "."
import { computed } from "vue"
import {
    Account,
    isAccountWithBatchUpdatesAndDisplayFields,
} from "@/store/accounts"
import { toDateTime } from "@/core/utilities/datetime"
const nextDate = computed(() => sort.value.nextDate)

defineProps<{
    account: Account
}>()

function weeksUntilNextBatchUpdate(account: Account): number {
    return isAccountWithBatchUpdatesAndDisplayFields(account)
        ? weeksUntil(toDateTime(account.batch_updates?.[0]?.date))
        : 1
}
</script>

<template>
    <DataTableRow
        style="height: 3rem"
        :style="{
            'background-color':
                nextDate.value != 'none' &&
                weeksUntilNextBatchUpdate(account) % 2 == 0
                    ? 'rgba(0,0,0,0.09)' // Light gray
                    : 'rgba(0,0,0,0.045)', // Darker gray
        }"
    >
        <slot />
    </DataTableRow>
</template>
