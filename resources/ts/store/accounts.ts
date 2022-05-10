import axios from "axios"
import { ComponentPropsOptions, reactive, ref, SetupContext } from "vue"

export function useAccounts(props: ComponentPropsOptions, context: SetupContext) {
    const data = ref({})

    async function getData() {
        let response = await axios.get('/api/accounts')
        data.value = response.data
    }
    return {
        data,
        getData,
    }
}