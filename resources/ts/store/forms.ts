import axios, { AxiosResponse } from "axios";
import { computed, ComputedRef, reactive, ref, toRefs, UnwrapNestedRefs, watch } from "vue";

export function useForm<T>(url: string, form: T) {
    const internalForm = reactive<object & T & { id?: number }>(JSON.parse(JSON.stringify(form)))
    watch(
        () => internalForm,
        () => isDirty.value = true,
        { deep: true }
    )
    const isDirty = ref(false)
    function reset<T>(resetValues: T) {
        Object.assign(internalForm, resetValues)
        setTimeout(() => isDirty.value = false)
    }
    const errors = ref(
        Object.keys(form).reduce((a, c) => {
            a[c] = undefined;
            return a;
        }, {} as { [key: string]: string | undefined })
    )
    function clearErrors() {
        errors.value = Object.keys(form).reduce((a, c) => {
            a[c] = undefined;
            return a;
        }, {} as { [key: string]: string | undefined });
    }
    const hasErrors = computed(() => Object.keys(errors.value).some(key => errors.value[key]))
    const processing = ref(false)
    const processingDelete = ref(false)
    const processingNotDelete = computed(() => processing.value && !processingDelete.value)
    const recentlySuccessful = ref(false)
    let recentlySuccessfulTimeoutId: number | undefined = undefined

    async function submit(method: string, internalUrl?: string) {
        processing.value = true
        if (method == 'delete') processingDelete.value = true
        recentlySuccessful.value = false
        clearTimeout(recentlySuccessfulTimeoutId)

        try {
            let response: AxiosResponse<T> | AxiosResponse<string> | null = null;
            if (method === "delete") {
                response = await axios.delete(internalUrl ?? url).catch((e: any) => onError(e)) as AxiosResponse<string>;
            } else {
                response = await (axios as any)[method]((internalUrl ?? url), internalForm).catch((e: any) => onError(e)) as AxiosResponse<T>;
            }
            return onSuccess(response ?? undefined);
        } catch (e: any) {
            return onError(e);
        }

        function onSuccess(response?: AxiosResponse<T> | AxiosResponse<string>) {
            processing.value = false
            processingDelete.value = false
            clearErrors()
            recentlySuccessful.value = true
            recentlySuccessfulTimeoutId = setTimeout(() => recentlySuccessful.value = false, 2000)
            isDirty.value = false
            return response?.data
        }
        function onError(e: any) {
            processing.value = false;
            processingDelete.value = false;
            let data = e.response?.data
            let status = e.response?.status
            let errs: { [key: string]: any } = {}
            if (status == 422) {
                if (data?.message == 'The given data was invalid.') errs.message = `Whoops! Looks like you may have typed something wrong. Care to retry?`
                Object.keys(data?.errors ?? {})
                    .forEach(key => {
                        if (data.errors[key]) errs[key] = data.errors[key].join('; ')
                    })
            } else {
                console.log('data: ', data)
                if (data?.message) errs.message = data?.message
                else {
                    if (e.message) {
                        Object.keys(e).forEach(key => errs[key] = e[key])
                    } else  errs.message = e.toString()
                }
            }
            clearErrors()
            errors.value = {
                ...errors.value,
                ...errs
            }
            throw errs
        }
    }
    const get = async () => submit('get', url)
    const post = async () => submit('post', url) as Promise<T & { id: number }>
    const put = async () => {
        if (internalForm.id) return submit('put', `${url}/${internalForm.id}`) as Promise<T & { id: number }>
        else throw Error('Form has no id')
    }
    const patch = async () => {
        if (internalForm.id) return submit('patch', `${url}/${internalForm.id}`) as Promise<T & { id: number }>
        else throw Error('Form has no id')
    }
    const internalDelete = async () => {
        if (internalForm.id) submit('delete', `${url}/${internalForm.id}`) as Promise<string>
        else throw Error('Form has no id');
    }
    const createOrUpdate = async () => {
        if (internalForm.id) return patch()
        return post()
    }

    return reactive({
        ...toRefs(internalForm),
        isDirty,
        reset,
        errors,
        clearErrors,
        hasErrors,
        processing,
        processingDelete,
        processingNotDelete,
        recentlySuccessful,
        submit,
        get,
        post,
        put,
        patch,
        delete: internalDelete,
        createOrUpdate,
    })
}