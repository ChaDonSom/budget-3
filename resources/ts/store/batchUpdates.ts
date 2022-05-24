import { Account } from "@/ts/store/accounts";
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

export type BatchUpdate = {
    id: number;
    user_id: number;
    batch: number;
    date: string;
    done_at: string|null;
    notify_me: boolean;
    weeks: number|null;
};
export type BatchUpdateWithAccounts = BatchUpdate & {
    accounts: (Account & { pivot: { amount: number } })[];
};
export type BatchUpdatesData = { [key: string | number]: BatchUpdateWithAccounts };
export type BatchUpdatesPaginator = {
    data: BatchUpdateWithAccounts[];
    current_page: number,
    last_page: number,
}

export const paginator = ref<BatchUpdatesPaginator|null>(null)
export const data = ref<BatchUpdatesData>({});
export const order = ref<{ [key: number]: number }>({})

export const keys = computed(() => {
    let v = data.value;
    return Object.keys(v);
});
export const values: ComputedRef<BatchUpdate[]> = computed(() => {
    let v = data.value;
    return Object.values(v);
});
export const ordered: ComputedRef<BatchUpdateWithAccounts[]> = computed(() => {
    let v = data.value
    return Object.keys(order.value).map(key => v[order.value[Number(key)]])
})

export async function fetchData(params?: { page?: number, [key: string]: any }) {
    let p = {
        page: 1,
        ...params,
    } as { [key: string]: string | number }
    let queryString = Object.keys(p).map(key => `${key}=${p[key]}`).join('&')
    let response: AxiosResponse<BatchUpdatesPaginator> = await axios.get(`/api/batch-updates?${queryString}`);

    paginator.value = response.data

    let result: { [key: number]: BatchUpdateWithAccounts } = {};
    let ord: { [key: number]: number } = {};
    for (let i = 0; i < response.data.data.length; i++) {
        let batchUpdate: BatchUpdateWithAccounts = response.data.data[i]
        result[batchUpdate.id] = batchUpdate
        ord[i] = batchUpdate.id
    }
    data.value = result
    order.value = ord
}

export async function fetchBatchUpdate(id: number) {
    let response: AxiosResponse<BatchUpdateWithAccounts> = await axios.get(
        `/api/batch-updates/${id}`
    );
    data.value = {
        ...data.value,
        [id]: response.data,
    };
    return data.value[id];
}

export async function receive(account: BatchUpdateWithAccounts) {
    data.value = {
        ...data.value,
        [account.id]: account,
    };
    return data.value[account.id];
}

export async function remove(id: number) {
    delete data.value[id];
}

export function useBatchUpdates(
    props?: ComponentPropsOptions,
    context?: SetupContext
) {
    return reactive({
        paginator,
        data,
        keys,
        values,
        ordered,
        fetchData,
        fetchBatchUpdate,
        receive,
        remove,
    });
}
