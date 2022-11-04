<template>
  <div>
    <!--
      BatchUpdates index
    -->
    <div v-if="auth.authenticated" class="text-center m-3">
      <h1 class="text-xl mt-5">
        History
        <span v-if="$route.query.account_id">
          for account {{ accounts.data[Number($route.query.account_id)]?.name }}
        </span>
      </h1>
      <div v-if="initiallyLoaded">
        <Button @click="enableDateRange = !enableDateRange; includeFuture = enableDateRange == false ? false : includeFuture">
          <template #leading-icon>{{ enableDateRange ? 'check_box' : 'check_box_outline_blank' }}</template>
          By date range
        </Button>
        <FlatPickr
            v-if="enableDateRange != false"
            :modelValue="dateRange"
            :config="{
              mode: 'range',
              onReady: (selectedDates: any, dateStr: any, instance: { open: Function }) => {
                if (!loadedWithDateRange || hasDisabledDateRange) instance.open()
              },
              onClose: (selectedDates: any, dateStr: any, instance: { }) => {
                dateRange = dateStr
                dateRangeArray = selectedDates
              }
            }"
        />
        <Button @click="includeFuture = !includeFuture; enableDateRange = includeFuture ? false : enableDateRange">
          <template #leading-icon>{{ includeFuture ? 'check_box' : 'check_box_outline_blank' }}</template>Include future transactions
        </Button>
      </div>
      <CircularScrim :loading="loading" />
      <div v-if="initiallyLoaded" class="mb-5">
        <div v-if="!batchUpdatesValues.length" class="m-5">
          <p>✨ No transactions ✨</p>
					<p class="m-5">(Once you move some money, or schedule it, it will show up here)</p>
        </div>
        <DataTable v-if="batchUpdatesValues.length" style="max-height: 85vh;">
          <template #header>
            <DataTableHeaderCell>
              Accounts
            </DataTableHeaderCell>
            <DataTableHeaderCell>
              Date
            </DataTableHeaderCell>
            <DataTableHeaderCell numeric>
              Amount
            </DataTableHeaderCell>
          </template>
          <template #body>
            <DataTableRow
                v-for="batchUpdate of batchUpdatesValues"
                @click="editBatchUpdate(batchUpdate.id)"
                :key="batchUpdate.id"
                :class="{ 'not-done': !batchUpdate.done_at }"
            >
              <DataTableCell style="white-space: normal;">
                <div class="flex items-center">
                  <IconButton
                      v-if="notFilteredForAccounts(batchUpdate.accounts)"
                      :density="-4"
                      secondary
                      style="font-style: normal;"
                      @click.stop="$router.push({ name: 'history', query: 
                        batchUpdate.accounts.length > 1
                          ? { 
                            account_ids: JSON.stringify(batchUpdate.accounts.map(i => i.id))
                          }
                          : {
                            account_id: batchUpdate.accounts[0].id
                          } 
                      })"
                  >
                    filter_alt
                  </IconButton>
                  <span v-if="batchUpdate.accounts.length < 5">
                    {{ batchUpdate.accounts.map(i => i.name).join(', ') }}
                  </span>
                  <span v-else>
                    {{ batchUpdate.accounts.map(i => i.name).join(', ').substring(0, 35) }}...
                  </span>
                </div>
              </DataTableCell>
              <DataTableCell>{{ toDateTime(batchUpdate.date).toFormat('M/dd') }}</DataTableCell>
              <DataTableCell numeric>
								{{ dollars(batchUpdate.accounts.reduce((a, c) => a + (c.pivot.amount / 100), 0)) }}
							</DataTableCell>
            </DataTableRow>
          </template>
          <template #paginator>
            <DataTablePaginator
                :paginator="batchUpdates.paginator"
                @first-page="batchUpdates.fetchData({ page: 1 })"
                @prev-page="batchUpdates.fetchData({ page: (batchUpdates.paginator?.current_page ?? 0) - 1 })"
                @next-page="batchUpdates.fetchData({ page: (batchUpdates.paginator?.current_page ?? 0) + 1 })"
                @last-page="batchUpdates.fetchData({ page: (batchUpdates.paginator?.last_page ?? 0) })"
            />
          </template>
        </DataTable>
				<Teleport to="body">
          <Fab
              v-if="$route.query.account_id"
              @click="newBatchUpdate"
              :icon="'add'"
              class="fixed right-4 bottom-6"
              style="z-index: 2;"
          />
        </Teleport>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/core/users/auth';
import { BatchUpdate, BatchUpdateWithAccounts, useBatchUpdates } from '@/store/batchUpdates';
import { useLocalStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { dollars } from '@/core/utilities/currency';
import { useRoute, useRouter } from 'vue-router';
import DataTable from '@/core/tables/DataTable.vue';
import DataTableHeaderCell from '@/core/tables/DataTableHeaderCell.vue';
import DataTableRow from '@/core/tables/DataTableRow.vue';
import DataTableCell from '@/core/tables/DataTableCell.vue';
import Fab from '@/core/buttons/Fab.vue';
import IconButton from '@/core/buttons/IconButton.vue';
import DataTablePaginator from '@/core/tables/DataTablePaginator.vue';
import { Account, useAccounts } from '@/store/accounts';
import Button from '@/core/buttons/Button.vue';
import { toDateTime } from '@/core/utilities/datetime';
// @ts-ignore
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { DateTime } from 'luxon';
import CircularScrim from '@/core/loaders/CircularScrim.vue';

const router = useRouter()
const route = useRoute()
const auth = useAuth()
const accounts = useAccounts()

const loading = ref(true)
const initiallyLoadedBatchUpdates = ref(false)
const initiallyLoaded = computed(() => {
	return (initiallyLoadedBatchUpdates.value)
})

const enableDateRange = useLocalStorage('budget-history-date-range-enabled', false)
const loadedWithDateRange = enableDateRange.value
const hasDisabledDateRange = ref(false)
const dateRange = useLocalStorage('budget-history-date-range', '')
const dateRangeArray = useLocalStorage<Date[]>('budget-history-date-range-array', [], {
  serializer: {
    read: (raw: string) => JSON.parse(raw).map((i: string) => new Date(i)),
    write: (value: Date[]) => JSON.stringify(value)
  }
})
watch(
  () => enableDateRange.value,
  (to, from) => {
    if (!to) dateRangeArray.value = [] as Date[]
    if (from && !to) hasDisabledDateRange.value = true
  }
)
const includeFuture = useLocalStorage('budget-history-include-future', false)
const batchUpdates = useBatchUpdates()
const batchUpdatesValues = computed(() => batchUpdates.ordered as BatchUpdateWithAccounts[])
watch(
  () => ([ route.query, includeFuture.value, dateRangeArray.value ]),
  async () => {
    if (enableDateRange.value && dateRangeArray.value.length != 2) return
    loading.value = true
    await batchUpdates.fetchData({
      ...route.query,
      include_future: includeFuture.value,
      date_range: enableDateRange.value
        ? JSON.stringify(dateRangeArray.value.map(i => DateTime.fromJSDate(i).toFormat('yyyy-MM-dd')))
        : '[]',
    })
    if (route.query.account_id && !accounts.data[Number(route.query.account_id)]) {
      accounts.fetchAccount(Number(route.query.account_id))
    }
    initiallyLoadedBatchUpdates.value = true
    loading.value = false
  },
  { deep: true, immediate: true }
)

function notFilteredForAccounts(accounts: Account[]) {
  let ids = route.query.account_id
    ? [Number(route.query.account_id)]
    : (JSON.parse(String(route.query.account_ids ?? "[]")) as string[])?.map(i => Number(i))
  let aIds = accounts.map(a => a.id)
  return !ids.every(i => aIds.includes(i)) || !aIds.every(i => ids.includes(i))
}

function editBatchUpdate(id: number) {
  router.push({ name: 'batch-updates-show', params: { id } })
}
function newBatchUpdate() {
  router.push({ name: 'batch-updates-show', params: { id: 'new', account_id: String(route.query.account_id) } })
}
</script>

<style scoped lang="scss">
@use "@/css/transitions";

:deep(.not-done) {
  * {
    color: #737373;
    font-style: italic;
  }
}

:deep(.mdc-data-table) {
	.mdc-data-table__cell, .mdc-data-table__header-cell {
		white-space: normal;
		padding-inline: 8px;
		&:first-child { padding-left: 16px; }
		&:last-child { padding-right: 16px; }
	}
}
</style>