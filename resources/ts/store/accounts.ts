import { toReactive } from "@vueuse/core";
import axios, { AxiosResponse } from "axios";
import {
    ComponentPropsOptions,
    computed,
    ComputedRef,
    reactive,
    ref,
    SetupContext,
    toRefs,
    watch,
} from "vue";

export type Account = {
    id: number;
    name: string;
    amount: number;
    user_id: number;
};
export type AccountsData = { [key: string | number]: Account };

const data = ref<AccountsData>({});

const keys = computed(() => {
    let v = data.value
    return Object.keys(v)
});
const values: ComputedRef<Account[]> = computed(() => {
    let v = data.value
    return Object.values(v)
});

async function fetchData() {
    let response: AxiosResponse<AccountsData> = await axios.get("/api/accounts");
    data.value = response.data
}

async function fetchAccount(id: number) {
    let response: AxiosResponse<Account> = await axios.get(`/api/accounts/${id}`)
    data.value = {
        ...data.value,
        [id]: response.data
    }
    return data.value[id]
}

async function createAccount(account: Omit<Account, 'id'>) {
    let response: AxiosResponse<Account> = await axios.post(`/api/accounts`, account)
    data.value = {
        ...data.value,
        [response.data.id]: response.data
    }
    return data.value[response.data.id]
}

async function updateAccount(account: Account) {
    let response: AxiosResponse<Account> = await axios.patch(`/api/accounts/${account.id}`, account)
    data.value = {
        ...data.value,
        [account.id]: response.data
    }
    return data.value[account.id]
}

async function deleteAccount(account: Account) {
    let response: AxiosResponse<string> = await axios.delete(`/api/accounts/${account.id}`)
    delete data.value[account.id]
}

export function useAccounts(props?: ComponentPropsOptions, context?: SetupContext) {
    return {
        ...toRefs(reactive({
            data,
            keys,
            values,
        })),
        fetchData,
        fetchAccount,
        createAccount,
        updateAccount,
        deleteAccount,
    };
}
