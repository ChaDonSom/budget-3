import { toReactive } from "@vueuse/core";
import axios from "axios"
import { ComponentPropsOptions, computed, ComputedRef, reactive, ref, SetupContext, toRefs } from "vue"

export function useAccounts(props?: ComponentPropsOptions, context?: SetupContext) {
    type Account = { id: number; name: string; amount: number }
    type AccountsData = { [key: string|number]: Account }
    const data = ref<AccountsData>({})

    const keys   = computed(() => Object.keys(data.value))
    const values: ComputedRef<Account[]> = computed(() => Object.values(data.value))

    async function getData() {
        let response = await axios.get('/api/accounts')
        data.value = response.data as AccountsData
    }
    
    return {
        ...toRefs({
            data,
            keys,
            values,
        }),
        getData,
    }
}