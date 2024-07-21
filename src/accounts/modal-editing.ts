import AccountModalVue from "@/accounts/AccountModal.vue"
import { useAccounts } from "@/store/accounts"
import { useModals } from "@/store/modals"
import { markRaw, ref } from "vue"

/**
	---------------------------------------------------
	| Directly editing accounts
	---------------------------------------------------
 */
export function useModalEditing() {
    const modals = useModals()
    const accounts = useAccounts()
    function newAccount() {
        modals.open({
            modal: markRaw(AccountModalVue),
            props: {},
        })
    }
    const loading = ref(false)
    async function editAccount(id: number | string) {
        // batch_update_x_x
        if (typeof id === "string")
            id = Number(id.split("_")[id.split("_").length - 1])
        loading.value = true
        await accounts.fetchAccount(id)
        modals.open({
            modal: markRaw(AccountModalVue),
            props: { accountId: id },
        })
        loading.value = false
    }

    return {
        newAccount,
        loading,
        editAccount,
    }
}
