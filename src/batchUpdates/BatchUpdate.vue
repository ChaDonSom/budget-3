<template>
    <div>
        <div class="text-center m-3">
            <div v-if="initiallyLoaded" class="mb-5">
                <h1 class="text-xl mt-5">
                    {{ $route.params.id != "new" ? "Edit" : "New" }} changes
                    {{
                        toDateTime(batchForm.date) <= DateTime.now()
                            ? "made"
                            : "to be made"
                    }}
                    {{
                        toDateTime(batchForm.date).toLocaleString(
                            DateTime.DATE_MED_WITH_WEEKDAY
                        )
                    }}
                </h1>
                <p v-if="!sortedAccounts.length" class="m-5">
                    ✨ No accounts ✨
                </p>
                <DataTable
                    @sort="updateSort"
                    v-if="sortedAccounts.length"
                    style="max-height: 83vh"
                >
                    <template #header>
                        <DataTableHeaderCell
                            sortable
                            column-id="name"
                            :sort="sort.name"
                        >
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
                        <DataTableRow
                            v-for="account of sortedAccounts"
                            :key="account.id"
                        >
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
                                @click="
                                    batchDifferences[account.id]
                                        ? edit(account)
                                        : null
                                "
                            >
                                <div
                                    v-if="
                                        currentlyEditingDifference !=
                                            account.id &&
                                        !batchDifferences[account.id]
                                    "
                                >
                                    <IconButton
                                        :density="-3"
                                        @click.stop="startWithdrawing(account)"
                                        >remove</IconButton
                                    >
                                    <IconButton
                                        :density="-3"
                                        @click.stop="startDepositing(account)"
                                        >add</IconButton
                                    >
                                </div>
                                <div
                                    v-else-if="batchDifferences[account.id]"
                                    @click.stop="edit(account)"
                                    class="w-full h-full flex items-center gap-2"
                                >
                                    <IconButton
                                        :density="-5"
                                        class="mr-2"
                                        @click.stop="
                                            clearBatchDifferenceFor(account)
                                        "
                                        >close</IconButton
                                    >
                                    {{
                                        batchDifferences[account.id].modifier ==
                                        1
                                            ? "+ "
                                            : ""
                                    }}
                                    {{
                                        dollars(
                                            batchDifferences[account.id]
                                                .amount *
                                                batchDifferences[account.id]
                                                    .modifier
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

                <CircularScrim :loading="batchForm.processing" />
                <Teleport to="body">
                    <div v-if="initiallyLoaded">
                        <Fab
                            @click="saveBatchUpdate()"
                            icon="save"
                            class="fixed right-4 bottom-6"
                            style="z-index: 2"
                        />
                        <OutlinedTextfield
                            type="date"
                            v-model="batchForm.date"
                            class="fixed bottom-0 right-20 opaque"
                            style="z-index: 2"
                        >
                            Date
                        </OutlinedTextfield>
                        <IconButton
                            v-if="areAnyBatchDifferences"
                            @click="clearBatchDifferences"
                            class="bottom-8 left-4"
                            style="position: fixed"
                        >
                            close
                        </IconButton>
                    </div>
                </Teleport>

                <transition name="opacity-0-scale-097-150ms" appear>
                    <DeleteButton
                        v-if="batchForm.id && initiallyLoaded"
                        @click="deleteBatchUpdate"
                        :loading="batchForm.processingDelete"
                        :disabled="batchForm.processing"
                    />
                </transition>
                <transition name="opacity-0-scale-097-150ms" appear>
                    <Button
                        v-if="batchForm.isDirty"
                        @click="loadBatchUpdate"
                        secondary
                    >
                        <template #leading-icon>replay</template>
                        Reset
                    </Button>
                </transition>

                <transition name="opacity-0-scale-097-150ms">
                    <div v-if="areAnyBatchDifferences" class="my-7">
                        <MdcSwitch v-model="batchForm.notify_me" id="notify_me">
                            Notify me when this change is made
                        </MdcSwitch>
                    </div>
                </transition>

                <transition name="opacity-0-scale-097-150ms" mode="out-in">
                    <div class="my-7" v-if="areAnyBatchDifferences">
                        <transition
                            name="opacity-0-scale-097-150ms"
                            mode="out-in"
                        >
                            <OutlinedTextfield
                                v-model="batchForm.weeks"
                                type="number"
                                step="1"
                                autoselect
                                :autofocus="
                                    !batchUpdates.data[String($route.params.id)]
                                        ?.weeks
                                "
                                v-if="batchForm.weeks != null"
                            >
                                Preferred # of weeks to pay by
                            </OutlinedTextfield>
                        </transition>
                        <Button
                            @click="
                                batchForm.weeks =
                                    batchForm.weeks == null ? 4 : null
                            "
                        >
                            {{
                                batchForm.weeks == null
                                    ? "Set preferred payment schedule"
                                    : "Remove payment schedule"
                            }}
                        </Button>
                    </div>
                </transition>

                <transition name="opacity-0-scale-097-150ms" mode="out-in">
                    <div
                        class="my-7 flex justify-center"
                        v-if="areAnyBatchDifferences"
                    >
                        <OutlinedTextarea
                            v-if="areAnyBatchDifferences"
                            v-model="batchForm.note"
                        >
                            Note
                        </OutlinedTextarea>
                    </div>
                </transition>

                <transition name="opacity-0-scale-097-150ms" mode="out-in">
                    <div class="my-7 mb-14" v-if="areAnyBatchDifferences">
                        <Button
                            v-if="batchForm.isDirty && route.params.id != 'new'"
                            @click="saveBatchUpdate(true)"
                        >
                            <template #leading-icon>save</template>
                            Save as new
                        </Button>
                    </div>
                </transition>

                <transition name="error-message">
                    <div class="flex">
                        <p
                            v-if="batchForm.errors.message"
                            class="bg-red-200 rounded-3xl py-3 px-4 mb-2 break-word max-w-fit mx-auto"
                        >
                            {{ batchForm.errors.message }}
                        </p>
                    </div>
                </transition>

                <!-- Spacer block to allow scroll to get to buttons/messages behind the save button & date field -->
                <div class="my-32" v-if="areAnyBatchDifferences"></div>
            </div>

            <div class="my-7">
                <p v-for="message of messages" :key="message">{{ message }}</p>
            </div>
            <!--
			<div class="my-7" v-if="auth.authenticated">
				<Button @click="sendPushNotification">Send myself a push notification</Button>
			</div>
			-->
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    ref,
    defineComponent,
    reactive,
    onMounted,
    computed,
    toRefs,
    watch,
    type Ref,
    markRaw,
} from "vue";
import Button from "@/core/buttons/Button.vue";
import { useAuth } from "../core/users/auth";
import { useEcho } from "../store/echo";
import axios from "axios";
import { useAccounts, type Account } from "@/store/accounts";
import { dollars } from "@/core/utilities/currency";
import DataTable from "@/core/tables/DataTable.vue";
import DataTableHeaderCell from "@/core/tables/DataTableHeaderCell.vue";
import DataTableRow from "@/core/tables/DataTableRow.vue";
import DataTableCell from "@/core/tables/DataTableCell.vue";
import { useLocalStorage } from "@vueuse/core";
import IconButton from "@/core/buttons/IconButton.vue";
import Fab from "@/core/buttons/Fab.vue";
import { useModals } from "@/store/modals";
import DollarsField from "@/core/fields/DollarsField.vue";
import FloatingDifferenceInputModalVue from "@/home/FloatingDifferenceInputModal.vue";
import { useForm } from "@/store/forms";
import { DateTime } from "luxon";
import OutlinedTextfield from "@/core/fields/OutlinedTextfield.vue";
import { onBeforeRouteLeave, useRoute, useRouter } from "vue-router";
import CircularScrim from "@/core/loaders/CircularScrim.vue";
import {
    type BatchUpdateWithAccounts,
    useBatchUpdates,
} from "@/store/batchUpdates";
import DeleteButton from "@/core/buttons/DeleteButton.vue";
import { toDateTime } from "@/core/utilities/datetime";
import { BatchDifference } from "@/home";
import MdcSwitch from "../core/switches/MdcSwitch.vue";
import OutlinedTextarea from "../core/fields/OutlinedTextarea.vue";

const auth = useAuth();
const route = useRoute();
const router = useRouter();
const messages = ref<any[]>([]);
const echo = useEcho();
onMounted(() => {
    // The '.' in '.my-event' means we'll listen on 'my-channel' instead of 'App\Events.my-channel'
    // That way, we can mess around with this from the Pusher event creator
    echo.echo.channel("my-channel").listen(".my-event", (data: any) => {
        console.log("data: ", data);
        messages.value.push(data);
    });
});

function sendPushNotification() {
    axios.post("/api/beams/self-notification", {
        title: "Hello World!",
        message: "Hi there, a notification from Somero Budget 3!",
    });
}

const showAllAccounts = ref(false);

/**
	---------------------------------------------------
	| Indexing and the table
	---------------------------------------------------
 */
const initiallySorted = ref(false);
const initiallyLoadedAccounts = ref(false);
const initiallyLoaded = computed(() => {
    return initiallyLoadedAccounts.value && initiallySorted.value;
});

const accounts = useAccounts();
accounts.fetchData().then(() => (initiallyLoadedAccounts.value = true));
const accountsTotal = computed(() =>
    accounts.values.map((i) => i.amount / 100).reduce((a, c) => a + c, 0)
);

const sortedAccounts: Ref<Account[]> = ref([]);
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
});
const hideProgress = ref<Function | null>(null);
function updateSort(event: {
    columnId: keyof typeof sort.value;
    sortValue: "ascending" | "descending";
    hideProgress: Function;
}) {
    hideProgress.value = event.hideProgress;
    if (sort.value[event.columnId].value == "descending")
        sort.value[event.columnId].value = "none";
    else sort.value[event.columnId].value = event.sortValue;
    sort.value[event.columnId].at = new Date().valueOf();
}

/**
	---------------------------------------------------
	| Setting up withdraw/deposit batches
	---------------------------------------------------
 */
const currentlyEditingDifference = ref<number | null>(null);
const batchDifferences = ref({} as { [key: number]: BatchDifference });
const batchDate = ref<DateTime>(DateTime.now());
const areAnyBatchDifferences = computed(() =>
    Boolean(Object.keys(batchDifferences.value).length)
);
const batchTotal = computed(() =>
    Object.values(batchDifferences.value)
        .map((i) => i.amount * i.modifier)
        .reduce((a, c) => a + c, 0)
);
function startWithdrawing(account: Account) {
    currentlyEditingDifference.value = account.id;
    batchDifferences.value[account.id] = new BatchDifference({
        amount: 0,
        modifier: -1,
    });
    modals.open({
        modal: markRaw(FloatingDifferenceInputModalVue),
        props: {
            difference: batchDifferences.value[account.id],
        },
    });
}
function startDepositing(account: Account) {
    currentlyEditingDifference.value = account.id;
    batchDifferences.value[account.id] = new BatchDifference({
        amount: 0,
        modifier: 1,
    });
    modals.open({
        modal: markRaw(FloatingDifferenceInputModalVue),
        props: {
            difference: batchDifferences.value[account.id],
        },
    });
}
function clearBatchDifferenceFor(account: Account) {
    delete batchDifferences.value[account.id];
    if (currentlyEditingDifference.value == account.id)
        currentlyEditingDifference.value = null;
}
function clearBatchDifferences() {
    batchDifferences.value = {};
    batchForm.accounts = {};
    currentlyEditingDifference.value = null;
}
function edit(account: Account) {
    currentlyEditingDifference.value = account.id;
    modals.open({
        modal: markRaw(FloatingDifferenceInputModalVue),
        props: {
            difference: batchDifferences.value[account.id],
        },
    });
}
const batchForm = useForm("/api/batch-updates", {
    id: null as number | null,
    user_id: auth.user?.id,
    date: batchDate.value.toFormat("yyyy-MM-dd"),
    note: "",
    accounts: batchDifferences.value,
    notify_me: false,
    weeks: null as number | null,
});
const batchUpdates = useBatchUpdates();
async function loadBatchUpdate() {
    if (route.params.id && route.params.id != "new") {
        let result = (await batchUpdates.fetchBatchUpdate(
            Number(route.params.id)
        )) as BatchUpdateWithAccounts;
        batchForm.reset({
            ...batchForm.internalForm,
            ...result,
            notify_me: Boolean(result.notify_me),
            weeks: result.weeks,
            accounts: result.accounts.reduce((a, c) => {
                a[c.id] = {
                    amount: Math.abs(c.pivot.amount / 100),
                    modifier: c.pivot.amount >= 0 ? 1 : -1,
                };
                return a;
            }, {} as { [key: number]: { amount: number; modifier: 1 | -1 } }),
        });
        batchDifferences.value = batchForm.accounts;
    }
    if (route.params.id == "new" && route.query.account_id) {
        batchDifferences.value = {
            [Number(route.query.account_id)]: new BatchDifference({
                amount: 0,
                modifier: 1,
            }),
        };
        batchForm.accounts = batchDifferences.value;
    }
}
onMounted(async () => {
    await loadBatchUpdate();

    watch(
        () => [
            accounts.values,
            sort.value,
            showAllAccounts.value,
            batchForm.accounts,
        ],
        () => {
            const worker = new Worker("worker.js");
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
                    ids: !showAllAccounts.value ? batchForm.accounts : null,
                }),
            });
            worker.addEventListener("message", (event) => {
                if (event.data?.type == "SORT_ACCOUNTS") {
                    sortedAccounts.value = JSON.parse(event.data?.accounts).map(
                        (a: Account & { [key: string]: any }) => {
                            let result = a;
                            delete result.nextDate;
                            delete result.nextAmount;
                            return result;
                        }
                    ) as Account[];
                    if (hideProgress.value) hideProgress.value();
                    initiallySorted.value = true;
                    worker.terminate();
                }
            });
        },
        { deep: true, immediate: true }
    );
});
async function saveBatchUpdate(asNew = false) {
    if (asNew) {
        batchForm.reset({
            id: null,
            user_id: batchForm.user_id,
            accounts: batchDifferences.value,
        });
    } else {
        batchForm.reset({
            user_id: batchForm.user_id,
            accounts: batchDifferences.value,
        });
    }
    await batchForm.createOrUpdate();
    setTimeout(() => router.back());
}
const modals = useModals();
async function deleteBatchUpdate() {
    if (batchForm.id && route.params.id && route.params.id != "new") {
        try {
            await modals.confirm(`Do you really want to delete these changes?`);
        } catch (e) {
            return;
        }
        let id = batchForm.id;
        await batchForm.delete();
        batchUpdates.remove(id);
        setTimeout(() => router.back());
    } else {
        throw Error("Form has no id");
    }
}
onBeforeRouteLeave(async () => {
    try {
        if (batchForm.isDirty)
            await modals.confirm(
                "Do you really want to leave unsaved changes?"
            );
    } catch (e) {
        return false;
    }
});
</script>

<style scoped lang="scss">
@use "@/css/mdc-theme";
@use "@/css/transitions";
@use "@material/typography";
@include typography.core-styles;

code {
    background-color: #eee;
    padding: 2px 4px;
    border-radius: 4px;
    color: #304455;
}

:deep(.sticky-bottom-row td) {
    position: -webkit-sticky;
    position: sticky;
    bottom: 0;
    z-index: 1;
    background-color: white;
    &::before {
        content: "";
        display: block;
        position: absolute;
        width: calc(100% + 32px);
        height: 2px;
        top: 0;
        left: -16px;
        background-color: rgb(197, 197, 197);
    }
}
:deep(.mdc-data-table__row.sticky-bottom-row:not(.mdc-data-table__row--selected):hover
        .mdc-data-table__cell) {
    background-color: white;
}

// For the date field to not be see-through
:deep(.opaque) {
    .mdc-text-field {
        .mdc-notched-outline__leading,
        .mdc-notched-outline__trailing,
        .mdc-notched-outline__notch {
            background-color: var(--color-background);
        }
        input.mdc-text-field__input {
            z-index: 2;
        }
    }
}
</style>
