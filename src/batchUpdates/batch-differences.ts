import { batchDifferences, currentlyEditingDifference } from "@/batchUpdates"
import { BatchDifference } from "@/home"
import FloatingDifferenceInputModalVue from "@/home/FloatingDifferenceInputModal.vue"
import type { Account } from "@/store/accounts"
import { useModals } from "@/store/modals"
import { markRaw } from "vue"

/**
 ---------------------------------------------------
 | Setting up withdraw/deposit batches
 ---------------------------------------------------
 */
export function useBatchDifferences() {
    const modals = useModals()
    function startWithdrawing(account: Account) {
        currentlyEditingDifference.value = account.id
        batchDifferences.value[account.id] = new BatchDifference({
            amount: 0,
            modifier: -1
        })
        modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
            difference: batchDifferences.value[account.id],
        } })
    }
    function startDepositing(account: Account) {
        currentlyEditingDifference.value = account.id
        batchDifferences.value[account.id] = new BatchDifference({
            amount: 0,
            modifier: 1
        })
        modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
            difference: batchDifferences.value[account.id],
        } })
    }
    function clearBatchDifferenceFor(account: Account) {
        delete batchDifferences.value[account.id]
        if (currentlyEditingDifference.value == account.id) currentlyEditingDifference.value = null
    }
    function edit(account: Account) {
        currentlyEditingDifference.value = account.id
        modals.open({ modal: markRaw(FloatingDifferenceInputModalVue), props: {
            difference: batchDifferences.value[account.id],
        } })
    }

    return {
        startWithdrawing,
        startDepositing,
        clearBatchDifferenceFor,
        edit,
    }
}