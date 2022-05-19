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

export type Template = {
    id: number;
    name: string;
    user_id: number;
    // batch_updates?: { batch: number, date: string, id: number, pivot: { amount: number } }[];
};
export type TemplateWithAccounts = Template & {
    accounts: (Account & { pivot: { amount: number } })[]
}
export type TemplatesData = { [key: string | number]: Template };

export const data = ref<TemplatesData>({});

export const keys = computed(() => {
    let v = data.value
    return Object.keys(v)
});
export const values: ComputedRef<Template[]> = computed(() => {
    let v = data.value
    return Object.values(v)
});

export async function fetchData() {
    let response: AxiosResponse<TemplatesData> = await axios.get("/api/templates");
    data.value = response.data
}

export async function fetchTemplate(id: number) {
    let response: AxiosResponse<Template> = await axios.get(`/api/templates/${id}`)
    data.value = {
        ...data.value,
        [id]: response.data
    }
    return data.value[id]
}

export async function receive(account: Template) {
    data.value = {
        ...data.value,
        [account.id]: account
    }
    return data.value[account.id]
}

export async function remove(id: number) {
    delete data.value[id]
}

export function useTemplates(props?: ComponentPropsOptions, context?: SetupContext) {
    return reactive({
        data,
        keys,
        values,
        fetchData,
        fetchTemplate,
        receive,
        remove,
    })
}
