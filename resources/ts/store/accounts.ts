import { toReactive } from "@vueuse/core";
import axios from "axios"
import { ComponentPropsOptions, computed, reactive, ref, SetupContext } from "vue"

export function useAccounts(props?: ComponentPropsOptions, context?: SetupContext) {
    type AccountsData = {
        [key: string|number]: { id: number; name: string; amount: number }
    }
    const data = ref<AccountsData>({})

    const keys   = computed(() => Object.keys(data.value))
    const values = computed(() => Object.values(data.value))

    async function getData() {
        let response = await axios.get('/api/accounts')
        data.value = response.data as AccountsData
    }
    return toReactive({
        data,
        keys,
        values,
        getData,
    })
}