<template>
  <div>
    <!--
      BatchUpdates index
    -->
    <div v-if="auth.authenticated" class="text-center m-3">
      <h1 class="text-xl mt-5">History</h1>
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
                <span v-if="batchUpdate.accounts.length < 5">
                  {{ batchUpdate.accounts.map(i => i.name).join(', ') }}
                </span>
                <span v-else>
                  {{ batchUpdate.accounts.map(i => i.name).join(', ').substring(0, 35) }}...
                </span>
              </DataTableCell>
              <DataTableCell>{{ batchUpdate.date }}</DataTableCell>
              <DataTableCell numeric>
								{{ dollars(batchUpdate.accounts.reduce((a, c) => a + (c.pivot.amount / 100), 0)) }}
							</DataTableCell>
            </DataTableRow>
          </template>
          <template #paginator>
            <DataTablePaginator
                :paginator="batchUpdates.paginator"
                @first-page="batchUpdates.fetchData(1)"
                @prev-page="batchUpdates.fetchData(batchUpdates.paginator?.current_page - 1)"
                @next-page="batchUpdates.fetchData(batchUpdates.paginator?.current_page + 1)"
                @last-page="batchUpdates.fetchData(batchUpdates.paginator?.last_page)"
            />
          </template>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/ts/core/users/auth';
import { BatchUpdate, BatchUpdateWithAccounts, useBatchUpdates } from '@/ts/store/batchUpdates';
import { useLocalStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { dollars } from '@/ts/core/utilities/currency';
import { useRouter } from 'vue-router';
import DataTable from '@/ts/core/tables/DataTable.vue';
import DataTableHeaderCell from '@/ts/core/tables/DataTableHeaderCell.vue';
import DataTableRow from '@/ts/core/tables/DataTableRow.vue';
import DataTableCell from '@/ts/core/tables/DataTableCell.vue';
import Fab from '@/ts/core/buttons/Fab.vue';
import IconButton from '@/ts/core/buttons/IconButton.vue';
import DataTablePaginator from '@/ts/core/tables/DataTablePaginator.vue';

const router = useRouter()
const auth = useAuth()

const initiallyLoadedBatchUpdates = ref(false)
const initiallyLoaded = computed(() => {
	return (initiallyLoadedBatchUpdates.value)
})

const batchUpdates = useBatchUpdates()
const batchUpdatesValues = computed(() => batchUpdates.ordered as BatchUpdateWithAccounts[])
batchUpdates.fetchData().then(() => initiallyLoadedBatchUpdates.value = true)

function editBatchUpdate(id: number) {
  router.push({ name: 'batch-updates-show', params: { id } })
}
</script>

<style scoped lang="scss">
@use "@/css/transitions";

:deep(.not-done) {
  * { color: #737373; }
}
</style>