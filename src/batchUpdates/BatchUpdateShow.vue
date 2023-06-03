<template>
    <div>
        <div class="text-center m-3">
            <div v-if="initiallyLoaded" class="mb-5">
                <BatchUpdateShowHeader :batch-form="batchForm" />

                <BatchUpdateShowAccounts
                    v-model:initially-sorted="initiallySorted"
                    v-model:currently-editing-difference="currentlyEditingDifference"
                    :batch-differences="batchDifferences"
                    :batch-form="batchForm"
                    @start-withdrawing="startWithdrawing"
                    @start-depositing="startDepositing"
                    @edit="edit"
                />

                <CircularScrim :loading="batchForm.processing" />

                <BatchUpdateFixedInputs
                    :batch-form="batchForm"
                    :initially-loaded="initiallyLoaded"
                    :are-any-batch-differences="areAnyBatchDifferences"
                    @save-batch-update="saveBatchUpdate()"
                    @clear-batch-differences="clearBatchDifferences"
                />

                <FormTransitionedDeleteButton
                    v-if="initiallyLoaded"
                    :form="batchForm"
                    @delete="deleteBatchUpdate"
                />

                <FormTransitionedResetButton
                    v-if="initiallyLoaded"
                    :form="batchForm"
                    @reset="loadBatchUpdate"
                />

                <transition name="opacity-0-scale-097-150ms">
                    <div v-if="areAnyBatchDifferences" class="my-7">
                        <MdcSwitch v-model="batchForm.notify_me" id="notify_me">
                            Notify me when this change is made
                        </MdcSwitch>
                    </div>
                </transition>

                <BatchUpdatePaymentScheduleForm
                    v-if="areAnyBatchDifferences"
                    :form="batchForm"
                />

                <BatchUpdateNoteField
                    v-if="areAnyBatchDifferences"
                    v-model="batchForm.note"
                />

                <FormTransitionedSaveAsNewButton
                    v-if="areAnyBatchDifferences"
                    :form="batchForm"
                    @save-as-new="saveBatchUpdate"
                />

                <ErrorMessage :form="batchForm" />

                <!-- Spacer block to allow scroll to get to buttons/messages behind the save button & date field -->
                <div class="my-32" v-if="areAnyBatchDifferences"></div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, markRaw, } from "vue";
import { useAuth } from "../core/users/auth";
import { useEcho } from "../store/echo";
import { useAccounts, type Account } from "@/store/accounts";
import { useModals } from "@/store/modals";
import FloatingDifferenceInputModalVue from "@/home/FloatingDifferenceInputModal.vue";
import { useForm } from "@/store/forms";
import { DateTime } from "luxon";
import { onBeforeRouteLeave, useRoute, useRouter } from "vue-router";
import CircularScrim from "@/core/loaders/CircularScrim.vue";
import { type BatchUpdateWithAccounts, useBatchUpdates } from "@/store/batchUpdates";
import { BatchDifference } from "@/home";
import MdcSwitch from "../core/switches/MdcSwitch.vue";
import BatchUpdateShowHeader from "@/batchUpdates/BatchUpdateShowHeader.vue"
import { useAreAnyBatchDifferences } from "@/batchUpdates"
import BatchUpdateFixedInputs from "@/batchUpdates/BatchUpdateFixedInputs.vue"
import FormTransitionedDeleteButton from "@/core/buttons/FormTransitionedDeleteButton.vue"
import FormTransitionedResetButton from "@/core/buttons/FormTransitionedResetButton.vue"
import BatchUpdateNoteField from "@/batchUpdates/BatchUpdateNoteField.vue"
import FormTransitionedSaveAsNewButton from "@/core/buttons/FormTransitionedSaveAsNewButton.vue"
import ErrorMessage from "@/core/forms/ErrorMessage.vue"

const auth = useAuth();
const route = useRoute();
const router = useRouter();

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

/**
	---------------------------------------------------
	| Setting up withdraw/deposit batches
	---------------------------------------------------
 */
const currentlyEditingDifference = ref<number | null>(null);
const batchDifferences = ref({} as { [key: number]: BatchDifference });
const batchDate = ref<DateTime>(DateTime.now());
const areAnyBatchDifferences = useAreAnyBatchDifferences(batchDifferences)
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
onMounted(loadBatchUpdate)
async function saveBatchUpdate(asNew = false) {
    console.log('asNew :', asNew);
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
        const id = batchForm.id;
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
