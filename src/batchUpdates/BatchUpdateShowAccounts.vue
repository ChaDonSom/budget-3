<template>
    <p v-if="!sortedAccounts.length" class="m-5">✨ No accounts ✨</p>
    <DataTable
        @sort="updateSort"
        v-if="sortedAccounts.length"
        style="max-height: 83vh"
    >
        <template #header>
            <DataTableHeaderCell sortable column-id="name" :sort="sort.name">
                Name
            </DataTableHeaderCell>
            <DataTableHeaderCell numeric>
                <Button @click="showAllAccounts = !showAllAccounts">
                    <template #leading-icon>{{
                        showAllAccounts
                            ? "check_box"
                            : "check_box_outline_blank"
                    }}</template>
                    Show all
                </Button>
            </DataTableHeaderCell>
        </template>
        <template #body>
            <DataTableRow v-for="account of sortedAccounts" :key="account.id">
                <DataTableCell
                    @click="
                        $router.push({
                            name: 'history',
                            query: { account_id: account.id },
                        })
                    "
                    >{{ account.name }}</DataTableCell
                >
                <DataTableCell
                    numeric
                    style="cursor: pointer"
                    @click="batchDifferences[account.id] ? $emit('edit', account) : null"
                >
                    <div
                        v-if="
                            currentlyEditingDifference != account.id &&
                            !batchDifferences[account.id]
                        "
                    >
                        <IconButton
                            :density="-3"
                            @click.stop="$emit('start-withdrawing', account)"
                            >remove</IconButton
                        >
                        <IconButton
                            :density="-3"
                            @click.stop="$emit('start-depositing', account)"
                            >add</IconButton
                        >
                    </div>
                    <div
                        v-else-if="batchDifferences[account.id]"
                        @click.stop="$emit('edit', account)"
                        class="w-full h-full flex items-center gap-2"
                    >
                        <IconButton
                            :density="-5"
                            class="mr-2"
                            @click.stop="clearBatchDifferenceFor(account)"
                            >close</IconButton
                        >
                        {{
                            batchDifferences[account.id].modifier == 1
                                ? "+ "
                                : ""
                        }}
                        {{
                            dollars(
                                batchDifferences[account.id].amount *
                                    batchDifferences[account.id].modifier
                            )
                        }}
                    </div>
                </DataTableCell>
            </DataTableRow>
            <DataTableRow class="sticky-bottom-row">
                <DataTableCell />
                <DataTableCell numeric>
                    <div v-if="areAnyBatchDifferences">
                        {{ dollars(batchTotal) }}
                    </div>
                </DataTableCell>
            </DataTableRow>
        </template>
    </DataTable>
</template>

<script lang="ts" setup>
import type { BatchDifference } from "@/home"
import { useAccounts, type Account } from "@/store/accounts"
import { useLocalStorage } from "@vueuse/core"
import { onMounted, ref, watch, type Ref, type PropType, toRef } from "vue"
import { dollars } from "@/core/utilities/currency"
import { useAreAnyBatchDifferences, useBatchTotal } from "@/batchUpdates"
import type { useForm } from "@/store/forms"

const props = defineProps({
    batchDifferences: {
        type: Object as PropType<{ [key: number]: BatchDifference }>,
        required: true,
    },
    currentlyEditingDifference: {
        type: [Number, null] as PropType<number | null>,
        required: true,
    },
    batchForm: {
        type: Object as PropType<ReturnType<typeof useForm> & { accounts: {
                [key: number]: BatchDifference
            }
        }>,
        required: true,
    },
    initiallySorted: {
        type: Boolean,
        required: true,
    }
})

const emit = defineEmits([
    'update:currentlyEditingDifference',
    'update:initiallySorted',
    'start-withdrawing',
    'start-depositing',
    'edit',
])

const areAnyBatchDifferences = useAreAnyBatchDifferences(toRef(props, 'batchDifferences'))
const batchTotal = useBatchTotal(toRef(props, 'batchDifferences'))

const sortedAccounts: Ref<Account[]> = ref([])

const showAllAccounts = ref(false)

function clearBatchDifferenceFor(account: Account) {
    delete props.batchDifferences[account.id]
    if (props.currentlyEditingDifference == account.id) emit('update:currentlyEditingDifference', null)
}

const accounts = useAccounts()
onMounted(async () => {
    watch(
        () => [
            accounts.values,
            sort.value,
            showAllAccounts.value,
            props.batchForm.accounts,
        ],
        () => {
            const worker = new Worker("worker.js")
            worker.postMessage({
                type: "SORT_ACCOUNTS",
                accounts: JSON.stringify(
                    accounts.values.map((account) => ({
                        ...account,
                        nextDate: account.batch_updates?.[0]?.date ?? "",
                        nextAmount:
                            account.batch_updates?.[0]?.pivot?.amount ?? 0,
                    }))
                ),
                sort: JSON.stringify(sort.value),
                filter: JSON.stringify({
                    ids: !showAllAccounts.value ? props.batchForm.accounts : null,
                }),
            })
            worker.addEventListener("message", (event) => {
                if (event.data?.type == "SORT_ACCOUNTS") {
                    sortedAccounts.value = JSON.parse(event.data?.accounts).map(
                        (a: Account & { [key: string]: any }) => {
                            let result = a
                            delete result.nextDate
                            delete result.nextAmount
                            return result
                        }
                    ) as Account[]
                    if (hideProgress.value) hideProgress.value()
                    emit('update:initiallySorted', true)
                    worker.terminate()
                }
            })
        },
        { deep: true, immediate: true }
    )
})

const sort = useLocalStorage("budget-batch-update-accounts-sort-v1", {
    name: {
        value: "none",
        at: null as number | null,
    },
    amount: {
        value: "none",
        at: null as number | null,
    },
    nextDate: {
        value: "none",
        at: null as number | null,
    },
    nextAmount: {
        value: "none",
        at: null as number | null,
    },
})
const hideProgress = ref<(() => any) | null>(null)
function updateSort(event: {
    columnId: keyof typeof sort.value
    sortValue: "ascending" | "descending"
    hideProgress: () => any
}) {
    hideProgress.value = event.hideProgress
    if (sort.value[event.columnId].value == "descending")
        sort.value[event.columnId].value = "none"
    else sort.value[event.columnId].value = event.sortValue
    sort.value[event.columnId].at = new Date().valueOf()
}
</script>
