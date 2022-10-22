import { useAuth, User } from "@/core/users/auth";
import { BatchUpdate, BatchUpdateWithAccounts, useBatchUpdates } from "@/store/batchUpdates";
import { useEcho } from "@/store/echo";
import axios, { AxiosResponse } from "axios";
import { defineStore } from "pinia";
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
    batch_updates?: (BatchUpdate & { pivot: { amount: number }})[];
    favorited_users?: User[];
};
export type AccountWithBatchUpdates = Account & {
    batch_updates: (BatchUpdate & { pivot: { amount: number } })[];
};
export type AccountsData = { [key: string | number]: Account };

export const data = ref<AccountsData>({});

export const keys = computed(() => {
    let v = data.value
    return Object.keys(v)
});
export const values: ComputedRef<Account[]> = computed(() => {
    let v = data.value
    return Object.values(v)
});

export async function fetchData() {
    let response: AxiosResponse<AccountsData> = await axios.get("/api/accounts");
    data.value = response.data
    const echo = useEcho()
    const auth = useAuth()
    const batchUpdates = useBatchUpdates()
    echo.echo.private(`AccountBatchUpdates.User.${auth.user?.id}`).listen(
        'AccountBatchUpdateSaved',
        (payload: { update: BatchUpdateWithAccounts }) => {
            console.log(`Got broadcast for batchUpdate ${payload.update.id}, updating in store...`)
            batchUpdates.receive(payload.update)
            for (let account of payload.update.accounts) {
                data.value[account.id].amount = account.amount
            }
        }
    )
}

export async function fetchAccount(id: number) {
    let response: AxiosResponse<Account> = await axios.get(`/api/accounts/${id}`)
    data.value = {
        ...data.value,
        [id]: response.data
    }
    return data.value[id]
}

export async function receive(account: Account) {
    data.value = {
        ...data.value,
        [account.id]: account
    }
    return data.value[account.id]
}

export async function remove(id: number) {
    delete data.value[id]
}

export function useAccounts(props?: ComponentPropsOptions, context?: SetupContext) {
    return reactive({
        data,
        keys,
        values,
        fetchData,
        fetchAccount,
        receive,
        remove,
    })
}

export const useAccountsStore = defineStore('accounts', () => {
    return {
        data,
        keys,
        values,
        fetchData,
        fetchAccount,
        receive,
        remove,
    };
})