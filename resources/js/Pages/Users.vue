<template>

    <Head :title="$t('users')"/>

    <AuthenticatedLayout>

        <template #header>
            <h2 class="text-white text-2xl">
                {{ $t('users') }}
            </h2>
        </template>

        <div class="py-3">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">

                    <ProgressSpinner v-if=waiting class="w-full"/>

                    <Message v-if="!!_message" :severity="_message.severity" :sticky="false" :life="4000">
                        {{ _message.content }}
                    </Message>

                    <DataTable v-else :value="_users"
                               :paginator="true" :rows="10" :total-records="_totalUsers"
                               :rowsPerPageOptions="[10, 20, 50, 100, 200, 500, 1000]"
                               v-model:rows="rows"
                               :lazy="true" @page="onPage"
                               @sort="onSort($event)"
                               paginator-position="both"
                    >
                        <Column field="name" header="Name" sortable></Column>
                        <Column field="email" header="Email" sortable></Column>
                        <Column field="is_admin" header="Is Admin" sortable>
                            <template #body="{data}">
                                <input type="checkbox" :checked="data.is_admin" disabled/>
                            </template>
                        </Column>
                        <Column header="Actions">
                            <template #body="{data}">
                                <Button label="Edit" size="small" @click="edit_user!.open(data)"
                                        class="p-button-outlined p-button-primary"/>
                                <Button label="Change Password" size="small"
                                        @click="change_pwd!.open(data.id, data.name)"
                                        class="p-button-outlined p-button-warning ml-2"/>
                                <Button label="Reset Password" size="small" @click="resetPassword($event, data)"
                                        class="p-button-outlined p-button-danger ml-2"/>
                                <Button label="Impersonate" size="small" @click="impersonate(data.id)"
                                        class="p-button-outlined p-button-success ml-2"/>
                            </template>
                        </Column>
                    </DataTable>

                </div>
            </div>
        </div>

        <EditUser ref="edit_user" @update:user="load_users"/>
        <ChangePwd ref="change_pwd"/>
        <Toast/>
        <ConfirmPopup/>

    </AuthenticatedLayout>

</template>

<script setup lang="ts">
import {onMounted, Ref, ref, useTemplateRef} from 'vue';
import {Head} from "@inertiajs/vue3";
import {useI18n} from "vue-i18n";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import EditUser from "@/Components/EditUser.vue";
import Toast from "primevue/toast";
import ConfirmPopup from "primevue/confirmpopup";
import axios from "axios";
import ChangePwd from "@/Components/ChangePwd.vue";
import {useConfirm} from "primevue/useconfirm";
import {useToast} from "primevue/usetoast";

const toast = useToast();
const confirm = useConfirm();
const {t} = useI18n();
const waiting = ref(false);
const edit_user = useTemplateRef('edit_user');
const change_pwd = useTemplateRef('change_pwd');

//pagination
const _totalUsers = ref(0);
const rows = ref(20);
const page = ref(0);
let sortField = 'name';
let sortOrder = 'asc';

const _users = ref([]);
const _message: Ref<{ severity: string, content: string } | null> = ref(null);

onMounted(async () => {
    await load_users();
});

async function load_users() {
    waiting.value = true;

    const {
        data: {
            success,
            message,
            users: {
                data, total, current_page
            },
        }
    } = await axios.get('/api/users', {
        params: {
            page: page.value + 1,
            rows: rows.value,
            sortField,
            sortOrder,
        }
    });

    if (success) {
        _users.value = data ?? [];
        _totalUsers.value = total;
    } else {
        _users.value = [];
        _message.value = {severity: 'error', content: message,};
    }

    waiting.value = false;
}

async function onPage(ev: any) {
    page.value = ev.page;
    await load_users();
}

async function onSort(event: any) {
    sortField = event.sortField;
    sortOrder = event.sortOrder == 1 ? 'asc' : 'desc';
    page.value = 0;
    await load_users();
}

/*const resetPassword = (user: any) => {
    // Implement reset password functionality
    console.log('Reset password for', user);
};*/

async function resetPassword(event: any, user: any) {
    confirm.require({
        target: event.currentTarget,
        message: 'An email with  a password reset link will be sent to user',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined p-button-sm',
        acceptClass: 'p-button-sm',
        rejectLabel: 'Cancel',
        acceptLabel: 'Continue',
        accept: async () => {
            const {data: {success, message,}} = await axios.post('/api/users/send_pwd_reset_link', {email: user.email});
            if (success)
                toast.add({severity: 'info', summary: 'Confirmed', detail: 'Password reset email successfully sent', life: 3000});
            else
                toast.add({severity: 'error', summary: 'An error occurred', detail: message, life: 3000});
        },
        reject: () => {
        }
    });
}

async function impersonate(user_id: number) {
    const {data: {success, message,}} = await axios.post('/api/users/impersonate', {user_id});
    if (success)
        location.assign('/');
    else
        toast.add({severity: 'error', summary: 'An error occurred', detail: message, life: 3000});
}
</script>

<style lang="scss" scoped>
.ml-2 {
    margin-left: 0.5rem;
}
</style>
