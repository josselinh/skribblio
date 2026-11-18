import { isAxiosError } from 'axios';
import { reactive, ref, type UnwrapRef } from 'vue';
import type { ValidationErrorResponse } from '@/types/api';

/**
 * Wraps a form's data with submission state and Laravel validation errors.
 * Validation errors (HTTP 422) are exposed per field; other errors are rethrown.
 */
export function useForm<TData extends object>(initialData: TData) {
    const data = reactive(structuredClone(initialData));
    const errors = ref<Partial<Record<keyof TData | string, string>>>({});
    const isProcessing = ref(false);

    async function submit(handler: (formData: UnwrapRef<TData>) => Promise<void>): Promise<boolean> {
        isProcessing.value = true;
        errors.value = {};

        try {
            await handler(data as UnwrapRef<TData>);

            return true;
        } catch (error) {
            if (isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422) {
                errors.value = Object.fromEntries(
                    Object.entries(error.response.data.errors).map(([field, messages]) => [field, messages[0] ?? '']),
                );

                return false;
            }
            throw error;
        } finally {
            isProcessing.value = false;
        }
    }

    function reset(...fields: (keyof TData)[]): void {
        const keys = fields.length > 0 ? fields : (Object.keys(initialData) as (keyof TData)[]);
        for (const key of keys) {
            (data as TData)[key] = structuredClone(initialData[key]);
        }
    }

    return { data, errors, isProcessing, submit, reset };
}
