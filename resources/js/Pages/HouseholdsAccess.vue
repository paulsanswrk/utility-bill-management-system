<script setup lang="ts">
import axios from "axios";


import {onMounted, Ref, ref} from "vue";
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Toast from "primevue/toast";
import {useToast} from "primevue/usetoast";
import dayjs from "dayjs";
import {useI18n} from "vue-i18n";
import {types} from "sass";
import Number = types.Number;

const {t} = useI18n();

const toast = useToast();

let _households: { id: number; name: string }[] = [];
let _households_dict: { [id: number]: string } = {};
const waiting = ref(false);

interface AccessMappings {
    user_id: number;
    name: string;
    email: string;
    hh_ids: string;
    created_at: string | null; // ISO timestamp format
}


interface Invitation {
    id: number | null;
    invitee_id: number | null;
    invitee: { name: string } | null;
    invitee_email: string;
    household_ids: string;
    invitation_status: string | null;
    created_at: string | null; // ISO timestamp format
    type: string | null;
}

const _mappings_and_invitations: Ref<(AccessMappings | Invitation)[]> = ref([]);

function new_mapping(): AccessMappings {
    return {
        user_id: 0,
        name: '',
        email: '',
        hh_ids: '',
        created_at: null,
    };
}

let editing_mapping: AccessMappings = new_mapping();

onMounted(async () => {
    waiting.value = true;

    await load_households();
    await load_mappings();

    waiting.value = false;
});

async function load_households() {
    const {data: {households}} = await axios.post("/api/households/get_user_households");
    _households = households;
    _households_dict = Object.fromEntries(_households.map(h => [h.id, h.name]));
}

function format_invitation_data(invitations: Invitation[]): (AccessMappings)[] {
    return invitations.map((i: Invitation) => ({
        id: i.id,
        email: i.invitee_email,
        hh_ids: i.household_ids,
        name: i.invitee?.name ?? '',
        user_id: i.invitee_id ?? 0,
        type: 'invitation',
        invitation_status: i.invitation_status,
        created_at: i.created_at ? dayjs(i.created_at).format('YYYY-MM-DD') : null,
    }));
}

async function load_mappings() {
    const {data: {mappings, invitations}} = await axios.post("/api/households/get_households_mappings");
    _mappings_and_invitations.value = mappings.concat(format_invitation_data(invitations));
}

const is_add_user_dlg_visible = ref(false);
const editing_user_email = ref("");
const editing_hh_ids = ref<number[]>([]);


async function edit_mapping(mapping?: AccessMappings) {
    mapping ??= new_mapping();
    editing_mapping = mapping;
    editing_user_email.value = mapping.email;
    editing_hh_ids.value = mapping.hh_ids.split(',').filter(s => !!s).map(x => parseInt(x));

    is_add_user_dlg_visible.value = true;
}

async function delete_mapping(mapping: AccessMappings) {
    const index = _mappings_and_invitations.value.indexOf(mapping);
    if (index !== -1) {
        _mappings_and_invitations.value.splice(index, 1);
    }

    await save_mappings();
}

async function delete_invitation(invitation: Invitation) {

    const {
        data: {
            success,
            mappings,
            invitations,
        }
    } = await axios.post(`/api/households/delete_invitation/${invitation.id}`);

    if (success) {
        _mappings_and_invitations.value = mappings.concat(format_invitation_data(invitations));
        toast.add({
            severity: 'info',
            summary: t('hh_access.success'),
            detail: t('hh_access.changes_saved_successfully'),
            life: 3000
        });
    }
}

async function mapping_edit_finished() {
    if (_households.length == 1)
        editing_hh_ids.value = [_households[0].id];

    editing_mapping.email = editing_user_email.value;
    editing_mapping.hh_ids = editing_hh_ids.value.join(',');

    if (!editing_mapping.user_id) //it is new
        _mappings_and_invitations.value.push(editing_mapping)

    // Reset dialog inputs
    editing_user_email.value = "";
    editing_hh_ids.value = [];
    is_add_user_dlg_visible.value = false;

    await save_mappings();
}

async function save_mappings() {
    const {
        data: {
            success,
            mappings,
            invitations,
        }
    } = await axios.post("/api/households/update_households_mappings", {mappings: _mappings_and_invitations.value});
    if (success) {
        _mappings_and_invitations.value = mappings.concat(format_invitation_data(invitations));
        toast.add({
            severity: 'info',
            summary: t('hh_access.success'),
            detail: t('hh_access.changes_saved_successfully'),
            life: 3000
        });
    } else {
        toast.add({
            severity: 'error',
            summary: t('hh_access.an_error_occurred'),
            detail: t('hh_access.failed_to_save_changes'),
            life: 3000
        });
    }
}

function fill_form_for_matching_user(editing_user_email: string) {
    const matching_mapping = _mappings_and_invitations.value.find((m: any) => m.type == null && m.email == editing_user_email) as AccessMappings;
    if (matching_mapping) {
        editing_hh_ids.value = matching_mapping.hh_ids.split(',').filter(s => !!s).map(x => parseInt(x));
    } else {
        editing_hh_ids.value = [];
    }
}

</script>

<template>
    <Head :title="$t('households_access')"/>

    <AuthenticatedLayout>

        <template #header>
            <a href="/" class="block mb-6">
                <Button :label="$t('back_to_my_bills')" icon="pi pi-chevron-left" size="small"/>
            </a>
            <h2 class="font-semibold text-3xl text-white leading-tight">{{ $t('households_access') }}</h2>
        </template>

        <div id="hh-access" class="py-3">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">

                    <ProgressSpinner v-if=waiting class="w-full"/>

                    <template v-else>

                        <Button @click="edit_mapping()">{{ $t('add_user_access.add_access_for_user') }}</Button>

                        <DataTable :value="_mappings_and_invitations" class="mt-4 align-items-start"
                                   :row-class="data => data.type == 'invitation' ? 'text-gray-500' : ''">
                            <Column field="name" :header="$t('hh_access.user')">
                                <template #body="{data}">
                                    <div>
                                        {{ data.name }} ({{ data.email }})
                                    </div>
                                </template>
                            </Column>

                            <Column field="hh_ids" :header="$t('hh_access.allow_access_to_households')"
                                    v-if="_households.length > 1">
                                <template #body="{data}">
                                    <div v-for="hh_id in data.hh_ids.split(',')">
                                        {{ _households_dict[hh_id] }}
                                    </div>
                                </template>
                            </Column>

                            <Column :header="$t('hh_access.actions')">
                                <template #body="{ data }">

                                    <template v-if="data.type == 'invitation'">

                                        <Button :label="$t('hh_access.delete')" icon="pi pi-trash"
                                                class="p-button-text p-button-sm p-button-danger"
                                                @click="delete_invitation(data)"/>

                                        <template v-if="false">
                                            <template v-if="data.invitation_status == 'sent'">
                                                {{ $t('hh_access.invitation_sent') }} {{ data.created_at }}
                                            </template>
                                            <template v-else-if="data.invitation_status == 'error'">
                                                {{ $t('hh_access.error_sending_invitation') }}
                                            </template>
                                            <template v-else-if="data.invitation_status == 'declined'">
                                                {{ $t('hh_access.invitation_declined') }}
                                            </template>
                                        </template>
                                    </template>

                                    <template v-else>
                                        <Button :label="$t('hh_access.delete')" icon="pi pi-trash"
                                                class="p-button-text p-button-sm p-button-danger"
                                                @click="delete_mapping(data)"/>

                                        <Button :label="$t('hh_access.change')" v-if="_households.length > 1"
                                                icon="pi pi-pencil" class="p-button-text p-button-sm"
                                                @click="edit_mapping(data)"/>
                                    </template>
                                </template>
                            </Column>

                            <template #empty>
                                <em>{{ $t('hh_access.no_household_access') }}</em>
                            </template>
                        </DataTable>


                        <div v-if="false" class="mt-5">
                            <Button @click="save_mappings()">{{ $t('add_user_access.save_changes') }}</Button>
                        </div>

                    </template>

                </div>
            </div>
        </div>


        <Dialog :header="$t('add_user_access.header')" v-model:visible="is_add_user_dlg_visible" :modal="true"
                :style="{ width: '500px' }">
            <form @submit.prevent="mapping_edit_finished" class="p-fluid">
                <div class="field">
                    <label for="email">{{ $t('add_user_access.user_email') }}</label>
                    <input id="email" v-model.trim="editing_user_email" type="email"
                           @input="fill_form_for_matching_user(editing_user_email)"
                           required autofocus autocomplete="email"
                           class="p-inputtext p-component p-filled text-white"/>
                </div>

                <div class="field" v-if="_households.length > 1">
                    <label>{{ $t('add_user_access.households') }}</label>
                    <div v-for="household in _households" :key="household.id" class="flex align-items-center mb-2">
                        <label class="ml-2">
                            <Checkbox v-model="editing_hh_ids" :value="household.id"/>
                            {{ household.name }}
                        </label>
                    </div>
                </div>

                <div class="p-dialog-footer">
                    <Button :label="$t('add_user_access.cancel')" icon="pi pi-times"
                            @click="is_add_user_dlg_visible = false"
                            class="p-button-text"/>
                    <Button :label="$t('add_user_access.save')" icon="pi pi-check" autofocus type="submit"/>
                </div>
            </form>
        </Dialog>


        <Toast/>


    </AuthenticatedLayout>
</template>

<style lang="scss">

#hh-access {
    .p-datatable-table {
        width: auto;

        td {
            vertical-align: top;
        }
    }
}
</style>
