import { inject, type InjectionKey } from 'vue';

// What fields need from the page's Inertia form: their own keys plus errors
export interface AdminForm {
    [key: string]: any;
    errors: Record<string, string>;
}

// Provided by pages/Admin/Resources/Form.vue so fields can bind their own keys
export const adminFormKey: InjectionKey<AdminForm> = Symbol('adminForm');

export function useAdminForm(): AdminForm {
    const form = inject(adminFormKey);
    if (!form) throw new Error('FieldInput must be used inside the admin form');
    return form;
}
