<template>
  <div>
    <div v-if="account" class="flex flex-col my-5">
      <h1 class="mx-2">
        Saving planning for {{ account.name }} ({{ DateTime.now().toFormat('M/dd') }}: {{ dollars(account.amount / 100) }})<br>
        Ideal rate now: {{ currentRate }}
      </h1>
      <h2 class="text-xl mx-2 mt-4">Basic numbers</h2>
      <DataTable class="my-5">
        <template #header>
          <DataTableHeaderCell>Date</DataTableHeaderCell>
          <DataTableHeaderCell numeric>Amount</DataTableHeaderCell>
          <DataTableHeaderCell numeric>Ideal weeks</DataTableHeaderCell>
          <DataTableHeaderCell numeric>Ideal rate ($/w)</DataTableHeaderCell>
          <DataTableHeaderCell numeric>Weeks left</DataTableHeaderCell>
          <DataTableHeaderCell numeric>Min</DataTableHeaderCell>
        </template>
        <template #body>
          <DataTableRow v-for="row of updatesSorted">
            <DataTableCell>{{ row.date }}</DataTableCell>
            <DataTableCell numeric>{{ row.amount }}</DataTableCell>
            <DataTableCell numeric>{{ row.weeks }}</DataTableCell>
            <DataTableCell numeric>{{ row.idealRate }}</DataTableCell>
            <DataTableCell numeric>{{ row.weeksLeft }}</DataTableCell>
            <DataTableCell numeric>{{ row.idealMin }}</DataTableCell>
          </DataTableRow>
          <DataTableRow>
            <DataTableCell></DataTableCell>
            <DataTableCell></DataTableCell>
            <DataTableCell></DataTableCell>
            <DataTableCell></DataTableCell>
            <DataTableCell></DataTableCell>
            <DataTableCell numeric>
              {{ minTotal }}
            </DataTableCell>
          </DataTableRow>
        </template>
      </DataTable>
      <h2 class="text-xl mx-2 mt-4">Weekly payment plan</h2>
      <DataTable class="my-5">
        <template #header>
          <DataTableHeaderCell numeric>Week</DataTableHeaderCell>
          <DataTableHeaderCell numeric>Rate ($/w)</DataTableHeaderCell>
        </template>
        <template #body>
          <DataTableRow v-for="(row, index) of ratesEachWeek">
            <DataTableCell numeric>{{ index + 1 }}</DataTableCell>
            <DataTableCell numeric>{{ row }}</DataTableCell>
          </DataTableRow>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { data, fetchAccount, type AccountWithBatchUpdates } from '@/store/accounts';
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import DataTable from '../core/tables/DataTable.vue'
import DataTableHeaderCell from '../core/tables/DataTableHeaderCell.vue'
import DataTableRow from '../core/tables/DataTableRow.vue'
import DataTableCell from '../core/tables/DataTableCell.vue'
import { DateTime } from 'luxon';
import { dollars } from '@/core/utilities/currency';
import { usePlanning } from '@/accounts/planning';

const accountId = computed(() => Number(useRoute().params.accountId))
const account = computed(() => data.value[accountId.value] as AccountWithBatchUpdates)
onMounted(() => {
  if (!account.value) fetchAccount(accountId.value)
})

const {
  minTotal,
  rateForWeek,
  updatesSorted,
  currentRate,
  ratesEachWeek,
} = usePlanning(account)
</script>