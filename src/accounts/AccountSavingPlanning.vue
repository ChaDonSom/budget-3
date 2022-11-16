<template>
  <div>
    <div v-if="account" class="flex flex-col my-5">
      <h1 class="mx-2">
        Saving planning for {{ account.name }} ({{ DateTime.now().toFormat('M/dd') }}: {{ dollars(account.amount / 100) }})
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
          <DataTableRow v-for="row of rows">
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
              {{ new Dollars(rows?.reduce((total, row) => total + Number(row.idealMin), 0) ?? 0) }}
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
          <DataTableRow v-for="row of rows?.reduce((weeks, row) => row.weeksLeft > weeks ? row?.weeksLeft : weeks, 0)">
            <DataTableCell numeric>{{ row }}</DataTableCell>
            <DataTableCell numeric>
              {{
                new Dollars(
                  rows
                    ?.filter(r => r.weeksLeft >= row && (!r.weeksUntilIdealStart || r.weeksUntilIdealStart < row))
                    .reduce((total, r) => total + Number(r.idealRate), 0)
                  ?? 0
                )
              }}
            </DataTableCell>
          </DataTableRow>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { data, fetchAccount } from '@/store/accounts';
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import DataTable from '../core/tables/DataTable.vue'
import DataTableHeaderCell from '../core/tables/DataTableHeaderCell.vue'
import DataTableRow from '../core/tables/DataTableRow.vue'
import DataTableCell from '../core/tables/DataTableCell.vue'
import { toDateTime } from '@/core/utilities/datetime';
import { DateTime } from 'luxon';
import { emergencySaving, idealPayment, idealWeeks, weeksUntil } from '@/home';
import { Dollars, dollars } from '@/core/utilities/currency';
import type { BatchUpdate } from '@/store/batchUpdates';

const accountId = computed(() => Number(useRoute().params.accountId))
const account = computed(() => data.value[accountId.value])
onMounted(() => {
  if (!account.value) fetchAccount(accountId.value)
})

class UpdateInTable {
  b: BatchUpdate & { pivot: { amount: number } }
  constructor(b: BatchUpdate & { pivot: { amount: number } }) {
    this.b = b
  }

  get amount() { return (function(amount) {
    let me = {
      toString: () => dollars(amount / 100),
      valueOf: () => amount / 100 
    }
    return me
  })(this.b.pivot.amount) }
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
  get ideallyCoveredWeeks() {
    return this.weeks - this.weeksLeft
  }

  get idealRate() {
    return new Dollars(Math.abs(Number(this.amount)) / (this.b.weeks ?? 4))
  }
  get idealMin() {
    let n = Number(this.idealRate) * this.ideallyCoveredWeeks
    if (n < 0) n = 0
    return new Dollars(n)
  }
}

const rows = computed(() => {
  if (!account.value) return []
  return account.value?.batch_updates?.map(b => new UpdateInTable(b))
    .sort((a, b) => a.date.valueOf() - b.date.valueOf())
})

const blurb = computed(() => {
  let batchUpdates = account.value?.batch_updates?.filter(update => {
    return toDateTime(update.date) > DateTime.now() && !update.done_at
  })
  let stillToBeCovered = batchUpdates?.reduce((a, b) => {
    return a + (idealPayment(b) * weeksUntil(toDateTime(b.date)))
  }, 0)
  let sortedByLengthOfPayments = batchUpdates?.sort((a, b) => {
    return toDateTime(a.date).valueOf() - toDateTime(b.date).valueOf()
  })
  let idealPaymentsToBeCovered = sortedByLengthOfPayments?.reduce((a, b) => {
    return a + (idealPayment(b)) + ', '
  }, '')
  return sortedByLengthOfPayments?.map((b, index) => {
    if (!sortedByLengthOfPayments) return
    let weeksTill = idealWeeks(b)
    let weeks = weeksTill - (index ? weeksUntil(toDateTime(sortedByLengthOfPayments[index - 1]?.date)) : 0)
    let idealToThisWeek = sortedByLengthOfPayments.slice(index).map(b => idealPayment(b)).reduce((a, b) => a + b, 0)
    return { weeksTill, weeks, idealToThisWeek }
  }).reduce((a, b) => {
    if (!b) return a
    if (b.weeks == 0 || b.weeks == a[a.length - 1]?.weeks) {
      a[a.length - 1].idealToThisWeek += b.idealToThisWeek
    } else {
      a.push(b)
    }
    return a
  }, <{ weeksTill: number, weeks: number, idealToThisWeek: number }[]>[])
})
</script>