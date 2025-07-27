<script lang="ts" setup>
import DataTableRow from "@/core/tables/DataTableRow.vue"
import { columnsToShow } from "."
import { dollars } from "@/core/utilities/currency"
import { computed } from "vue"
import DataTableCell from "@/core/tables/DataTableCell.vue"

defineProps<{
  /**
   * The account object containing the totals. Shows 'nextAmount' meaning the total of the accounts' next amounts
   * it's for.
   */
  account: {
    nextAmount?: number
  }
}>()

const columnKeys = computed(() => Object.keys(columnsToShow.value) as (keyof (typeof columnsToShow)["value"])[])
</script>

<template>
  <DataTableRow style="height: unset" class="text-xl italic border-t-2 text-slate-600 border-slate-400">
    <DataTableCell
      v-for="column of columnKeys"
      :key="column"
      :numeric="column === 'nextAmount'"
      :class="{ hidden: !columnsToShow[column] }"
    >
      <span v-if="column === 'name'">Totals</span>
      <div v-else-if="column === 'nextAmount'" class="whitespace-nowrap">
        {{ dollars((account.nextAmount ?? 0) / 100) }}
      </div>
    </DataTableCell>
  </DataTableRow>
</template>
