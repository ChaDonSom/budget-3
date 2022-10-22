<template>
  <div>
    <!--
      Templates index
    -->
    <div v-if="auth.authenticated" class="text-center m-3">
      <h1 class="text-xl mt-5">Templates</h1>
      <div v-if="initiallyLoaded" class="mb-5">
        <div v-if="!sortedTemplates.length" class="m-5">
          <p>✨ No templates ✨</p>
					<p class="m-5">(Hint: make one for payday to quickly budget your money the same amount every check)</p>
        </div>
        <DataTable @sort="updateSort" v-if="sortedTemplates.length" style="max-height: 85vh;">
          <template #header>
            <DataTableHeaderCell sortable column-id="name" :sort="sort.name">
              Name
            </DataTableHeaderCell>
            <DataTableHeaderCell sortable numeric column-id="amount" :sort="sort.amount">
              Amount
            </DataTableHeaderCell>
						<DataTableHeaderCell>
							<!-- Controls -->
						</DataTableHeaderCell>
          </template>
          <template #body>
            <DataTableRow v-for="template of sortedTemplates" @click="editTemplate(template.id)">
              <DataTableCell >{{ template.name }}</DataTableCell>
              <DataTableCell numeric>
								{{ dollars(template.accounts.reduce((a, c) => a + (c.pivot.amount / 100), 0)) }}
							</DataTableCell>
							<DataTableCell>
								<IconButton @click.stop="applyTemplate(template)">open_in_new</IconButton>
							</DataTableCell>
            </DataTableRow>
          </template>
        </DataTable>
      </div>

			<Teleport to="body">
				<div v-if="initiallyLoaded" teleport="body">
					<Fab
							@click="newTemplate"
							:icon="'add'"
							class="fixed right-4 bottom-6"
							style="z-index: 2;"
					/>
				</div>
			</Teleport>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/core/users/auth';
import { Template, TemplateWithAccounts, useTemplates } from '@/store/templates';
import { useLocalStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { dollars } from '@/core/utilities/currency';
import { useRouter } from 'vue-router';
import DataTable from '@/core/tables/DataTable.vue';
import DataTableHeaderCell from '@/core/tables/DataTableHeaderCell.vue';
import DataTableRow from '@/core/tables/DataTableRow.vue';
import DataTableCell from '@/core/tables/DataTableCell.vue';
import Fab from '@/core/buttons/Fab.vue';
import IconButton from '@/core/buttons/IconButton.vue';

const router = useRouter()
const auth = useAuth()

const initiallySorted = ref(false)
const initiallyLoadedTemplates = ref(false)
const initiallyLoaded = computed(() => {
	return (
		initiallyLoadedTemplates.value && initiallySorted.value
	)
})

const templates = useTemplates()
templates.fetchData().then(() => initiallyLoadedTemplates.value = true)
const sortedTemplates = ref<TemplateWithAccounts[]>([])
const sort = useLocalStorage('budget-templates-index-sort-v1', {
	name: {
		value: 'none',
		at: null as number|null,
	},
	amount: {
		value: 'none',
		at: null as number|null,
	},
})
const hideProgress = ref<Function|null>(null)
function updateSort(event: { columnId: keyof typeof sort.value, sortValue: "ascending"|"descending", hideProgress: Function }) {
	hideProgress.value = event.hideProgress
	if (sort.value[event.columnId].value == 'descending') sort.value[event.columnId].value = 'none'
	else sort.value[event.columnId].value = event.sortValue
	sort.value[event.columnId].at = (new Date()).valueOf()
}
watch(
	() => [templates.values, sort.value],
	() => {
		const worker = new Worker('worker.js')
		worker.postMessage({
			type: 'SORT_TEMPLATES',
			templates: JSON.stringify(templates.values.map(template => ({
				...template,
				amount: (template as Template & { accounts: { pivot: { amount: number } }[] }).accounts.reduce((a, c) => {
					a += c.pivot.amount
					return a
				}, 0)
			}))),
			sort: JSON.stringify(sort.value)
		})
		worker.addEventListener('message', event => {
			if (event.data?.type == 'SORT_TEMPLATES') {
				sortedTemplates.value = JSON.parse(event.data?.templates).map((a: Template & { [key: string]: any }) => {
					let result = a
					delete result.amount
					return result
				}) as TemplateWithAccounts[]
				if (hideProgress.value) hideProgress.value()
				initiallySorted.value = true
				worker.terminate()
			}
		})
	},
	{ deep: true, immediate: true }
)

function newTemplate() {
  router.push({ name: 'templates-show', params: { id: 'new' } })
}
function editTemplate(id: number) {
  router.push({ name: 'templates-show', params: { id } })
}
function applyTemplate(template: TemplateWithAccounts) {
	router.push({ name: 'index', params: { template: JSON.stringify(template) } })
}
</script>

<style scoped lang="scss">
@use "@/css/transitions";
</style>