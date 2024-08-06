<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import {onMounted, Ref, ref} from "vue";
import axios from "axios";
import {Bill, Bill_Plain_Obj, bill_to_plain, bills, plain_to_bill} from "@/Data/Bill";
import {utility_companies, UtilityCompany} from "@/Data/UtilityCompany";
import {useToast} from "primevue/usetoast";
import FloatLabel from 'primevue/floatlabel';

import Toast from 'primevue/toast';
import dayjs from "dayjs";
import {FileUploadBeforeSendEvent} from "primevue/fileupload";
import {getCookie} from "typescript-cookie";

import ConfirmPopup from 'primevue/confirmpopup';
import ConfirmDialog from 'primevue/confirmdialog';
import {useConfirm} from "primevue/useconfirm";
import {FilterMatchMode, FilterService} from "primevue/api";
import {DataTableFilterMetaData, DataTableOperatorFilterMetaData} from "primevue/datatable";
import {useI18n} from 'vue-i18n'
import {HouseHold} from "@/Data/HouseHold";

const {t} = useI18n();
const confirm = useConfirm();
const toast = useToast();

onMounted(async () => {
    await load_bills();
});

function formatCurrency(value: number): string {
    return value.toLocaleString('en-US', {style: 'currency', currency: 'EUR'});
}

// region DataTable

const waiting = ref(false);
// const totalRecords = ref(1);

// endregion


// region Utility Companies
const selected_company: Ref<UtilityCompany | string | null> = ref(null);
const dlg_add_company_visible = ref(false);
const adding_company_name = ref('');

async function add_company(): Promise<void> {
    const {data: {success, message, companies}} = await axios.post('/api/companies/store', {name: adding_company_name.value});

    if (success) {
        toast.add({severity: 'success', summary: t('success'), detail: t('company_successfully_added'), life: 3000});
        dlg_add_company_visible.value = false;
        if (companies) {
            utility_companies.value = companies;
            selected_company.value = companies.find((c: any) => c.name == adding_company_name.value);
        }
    } else
        toast.add({severity: 'error', summary: t('error'), detail: message, life: 10000});

}

// endregion

// region Households
const current_household: Ref<HouseHold | null> = ref(null);
const households: Ref<HouseHold[]> = ref([]);
const household_editor_active = ref(false);
const editing_hh_id = ref(0);
const editing_hh_name = ref('');
const hh_active_tab_index = ref(0);

async function hh_tab_changed(index: number) {
    current_household.value = households.value[index];
    household_editor_active.value = false;
    bills.value = [];
    if (current_household.value?.name) {
        waiting.value = true;
        await load_bills();
        waiting.value = false;
    }
}

async function add_household() {
    if (!editing_hh_name.value) return;

    const {data: {success, message, new_id}} = await axios.post('/api/households/store', {name: editing_hh_name.value});

    if (success) {
        toast.add({severity: 'success', summary: t('success'), detail: t('household_successfully_added'), life: 3000});
        let new_hh = {id: new_id, name: editing_hh_name.value};
        households.value.push(new_hh);
        editing_hh_name.value = '';
        current_household.value = new_hh;
    } else
        toast.add({severity: 'error', summary: t('error'), detail: message, life: 10000});
}

async function rename_household(h: HouseHold) {
    if (editing_hh_name.value.trim() == h.name.trim()) return; //nothing edited

    await axios.post('/api/households/update', {id: editing_hh_id.value, name: h.name})
    toast.add({severity: 'success', summary: t('success'), detail: t('household_successfully_renamed'), life: 3000});
}

async function remove_household(id: number) {
    const {data: {success, message}} = await axios.post('/api/households/destroy', {id});
    if (success) {
        toast.add({severity: 'success', summary: t('success'), detail: t('household_successfully_deleted'), life: 3000});
        households.value.splice(households.value.findIndex(h => h.id === id), 1);
        current_household.value = households.value[0];
        hh_active_tab_index.value = 0;
        await hh_tab_changed(0);
    } else
        toast.add({severity: 'error', summary: t('error'), detail: message, life: 10000});
}

// endregion

// region Bills

const edit_bill_dlg_visible = ref(false);
const edit_bill_dlg_title = ref('');


const bill: Ref<Bill> = ref(default_bill());

function default_bill(): Bill {
    return {
        id: 0,
        amount: 0,
        bill_date: dayjs().startOf('month').subtract(1, 'month').toDate(),
        payment_date: dayjs().toDate(),
        utility_company_id: 0,
        utility_company_name: '',
        paid: false,
        has_bill_pdf: false,
        has_payment_pdf: false
    }
}

async function load_bills() {
    waiting.value = true;

    const {
        data: {
            user_bills,
            user_companies,
            user_households
        }
    } = await axios.get('/api/bills', {params: {household_id: current_household.value?.id}});
    bills.value = user_bills ?? [];
    utility_companies.value = user_companies ?? [];
    households.value = user_households ?? [];
    current_household.value = households.value[hh_active_tab_index.value] ?? households.value[0];

    waiting.value = false;
}

async function open_add_bill() {
    edit_bill_dlg_title.value = t('add_bill');
    save_pressed.value = false;

    bill.value = default_bill();
    selected_company.value = null;

    const {data: {new_id}} = await axios.post('/api/bills/store', {household_id: current_household.value?.id});

    bill.value.id = new_id;

    edit_bill_dlg_visible.value = true;
}

async function open_edit_bill(bill_plain: Bill_Plain_Obj) {
    edit_bill_dlg_title.value = t('edit_bill');
    save_pressed.value = false;
    bill.value = plain_to_bill(bill_plain);
    selected_company.value = utility_companies.value.find(c => c.id === bill_plain.utility_company_id) || null;

    edit_bill_dlg_visible.value = true;
}

async function save_bill(bill: Bill): Promise<void> {
    save_pressed.value = true;

    if (!(selected_company.value as any)?.id || !bill.bill_date || !bill.payment_date || !bill.has_bill_pdf) return;

    edit_bill_dlg_visible.value = false;

    bill.utility_company_id = (selected_company.value as any)!.id;
    const o = bill_to_plain(bill);

    const {
        data: {
            success,
            message,
            user_bills,
            user_companies
        }
    } = await axios.post('/api/bills/save', {household_id: current_household.value?.id, ...o});

    if (success) {
        toast.add({severity: 'success', summary: t('success'), detail: t('bill_successfully_saved'), life: 3000});
        if (user_bills)
            bills.value = user_bills;
        if (user_companies)
            utility_companies.value = user_companies;
    } else
        toast.add({severity: 'error', summary: t('error'), detail: message, life: 10000});

}

async function delete_bill(bill_plain: Bill_Plain_Obj) {
    const {data: {user_bills, user_companies}} = await axios.post('/api/bills/destroy', {
        id: bill_plain.id,
        household_id: current_household.value?.id
    });
    toast.add({severity: 'success', summary: t('success'), detail: t('bill_successfully_deleted'), life: 3000});
    if (user_bills)
        bills.value = user_bills;
    if (user_companies)
        utility_companies.value = user_companies;
}

async function delete_doc(bill: Bill, doctype: string) {
    const {data: {success, message, user_bills}} = await axios.post('/api/bills/delete_pdf', {id: bill.id, doctype, household_id: current_household.value?.id});
    if (success) {
        toast.add({severity: 'success', summary: t('success'), detail: t('document_deleted'), life: 3000});
        bill[doctype === 'bill' ? 'has_bill_pdf' : 'has_payment_pdf'] = false;
        if (user_bills)
            bills.value = user_bills;
    } else
        toast.add({severity: 'error', summary: t('error'), detail: message, life: 10000});
}

async function get_zip() {
    location.assign('/zip');
}

// endregion

// region FileUploader

function onFileUpload(e: Event) {
    // toast.add({severity: 'info', summary: 'Success', detail: 'File Uploaded', life: 3000});
}

function beforeSend(e: FileUploadBeforeSendEvent) {
    e.xhr.setRequestHeader('X-XSRF-TOKEN', getCookie('XSRF-TOKEN')!);
}

// endregion

// region Confirmations
const save_pressed = ref(false);

function confirm_delete_hh(hh: HouseHold) {
    confirm.require({
        group: 'hh',
        message: t('do_you_want_to_delete_this_household') + ' ' + hh.name + '?',
        // header: 'Danger Zone',
        icon: 'pi pi-info-circle',
        rejectLabel: t('cancel'),
        acceptLabel: t('del'),
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await remove_household(hh.id);
        },
    });
}

async function confirm_delete_bill(event: Event, bill_plain: Bill_Plain_Obj) {
    confirm.require({
        target: event.currentTarget as any,
        message: t('do_you_want_to_delete_this_bill'),
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined p-button-sm',
        acceptClass: 'p-button-danger p-button-sm',
        rejectLabel: t('cancel'),
        acceptLabel: t('del'),
        accept: async () => {
            await delete_bill(bill_plain);
        },
    });
}

async function confirm_delete_pdf(event: Event, doctype: string) {
    confirm.require({
        target: event.currentTarget as any,
        message: t('do_you_want_to_delete_this_doc'),
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined p-button-sm',
        acceptClass: 'p-button-danger p-button-sm',
        rejectLabel: t('cancel'),
        acceptLabel: t('del'),
        accept: async () => {
            await delete_doc(bill.value, doctype);
        },
    });
}

async function confirm_change_paid_status(event: Event) {
    confirm.require({
        target: event.currentTarget as any,
        message: t('do_you_want_to_change_the_payment_status'),
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined p-button-sm',
        acceptClass: 'p-button-danger p-button-sm',
        rejectLabel: t('cancel'),
        acceptLabel: t('yes'),
        accept: async () => {
        },
        reject() {
            bill.value.paid = !bill.value.paid;
        },
    });
}

// endregion

// region Filtering
FilterService.register('filterByMonthRange', function (date: string, filter:Date) {
    // console.log('filterByDateRange', arguments, filter)
    // return true;
    return dayjs(filter).format('YYYY-MM') == date;
});

FilterService.register('filterByDateRange', function (date: string, filter:Date) {
    // console.log('filterByDateRange', arguments)
    // console.log('filterByDateRange', {from: dayjs(from).format('YYYY-MM-DD'), date, to: dayjs(to).format('YYYY-MM-DD')})
    return dayjs(filter).format('YYYY-MM-DD') == date;
});

const filters: Ref<{ [k: string]: DataTableFilterMetaData | DataTableOperatorFilterMetaData }> = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
    utility_company_name: {value: null, matchMode: FilterMatchMode.IN,},
    paid: {value: null, matchMode: FilterMatchMode.EQUALS,},
    bill_date: {value: null, matchMode: 'filterByMonthRange'},
    payment_date: {value: null, matchMode: 'filterByDateRange'},
});
// endregion


</script>

<template>
    <Head :title="$t('my_bills')"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-white ">
                {{ $t('my_bills') }}
            </h2>
        </template>

        <div class="py-3">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">

                    <ProgressSpinner v-if=waiting class="w-full"/>
                    <TabView v-else :scrollable="true" class="hh-tabs" v-model:active-index="hh_active_tab_index"
                             @update:active-index="hh_tab_changed($event)">

                        <TabPanel v-for="(household, n_hh) in households" :key="household.id">

                            <template #header>
                                <span v-if="n_hh !== hh_active_tab_index">{{ household.name }}</span>
                                <SplitButton v-else :label="household.name"
                                             :model="[{label:t('rename_household'), command: ()=>{household_editor_active=true; editing_hh_id=household.id; editing_hh_name=household.name}},
                                                {label:t('delete_household'), command: ()=>confirm_delete_hh(household)}]"
                                             text class="hh-tab p-0">

                                    <Inplace :closable="true" close-icon="pi pi-check"
                                             :active="household_editor_active && editing_hh_id==household.id"
                                             @update:active="(value: boolean) => household_editor_active=value"
                                             @close="rename_household(household)">
                                        <template #display>
                                            {{ household.name }}
                                        </template>
                                        <template #content>
                                            <InputText v-model="household.name" autofocus
                                                       @keydown.space="$event.stopPropagation()"/>
                                        </template>

                                    </Inplace>
                                </SplitButton>
                            </template>

                            <DataTable :value="bills" paginator paginator-position="top" :rows="10"
                                       :always-show-paginator="true"
                                       :rowsPerPageOptions="[10, 20, 50, 100]" sort-field="bill_date" :sort-order="-1"
                                       v-model:filters="filters" filterDisplay="row"
                                       class="bills-table" table-style="width: 100%" showGridlines stripedRows>

                                <Column v-if="false" field="id" header="ID" sortable/>

                                <Column field="bill_date" :header="$t('bill_date')" sortable :show-clear-button="true"
                                        :show-filter-menu="false">

                                    <template #filter="{ filterModel, filterCallback }">
                                        <div class="flex">
                                            <FloatLabel>
                                                <Calendar v-model="filterModel.value"
                                                          view="month"
                                                          dateFormat="yy-mm" @dateSelect="filterCallback()"
                                                          :input-style="{width:'180px'}" showIcon icon-display="input"/>
                                                <label>{{ $t('bill_date') }}</label>
                                            </FloatLabel>
                                            <Button v-if="false" icon="pi pi-filter-slash" text severity="secondary"
                                                    @click="filterModel.value = null"/>
                                        </div>

                                    </template>


                                </Column>

                                <Column field="utility_company_name" :header="$t('utility_company')" sortable>

                                    <template #filter="{ filterModel, filterCallback }">
                                        <MultiSelect v-model="filterModel.value" @change="filterCallback()"
                                                     :options="utility_companies.map(c=>c.name)"
                                                     :placeholder="$t('any')" class="p-column-filter"
                                                     style="min-width: 14rem"
                                                     :maxSelectedLabels="1"/>
                                    </template>

                                </Column>

                                <Column field="payment_date" :header="$t('payment_date')" sortable
                                        :show-clear-button="true"
                                        :show-filter-menu="false">

                                    <template #filter="{ filterModel, filterCallback }">
                                        <div class="flex">
                                            <FloatLabel>
                                                <Calendar v-model="filterModel.value" dateFormat="yy-mm-dd"
                                                          @dateSelect="filterCallback()" showButtonBar
                                                          :input-style="{width:'220px'}" showIcon icon-display="input"/>
                                                <label>{{ $t('payment_date') }}</label>
                                            </FloatLabel>
                                            <Button v-if="false" icon="pi pi-filter-slash" text severity="secondary"
                                                    @click="filterModel.value = null"/>
                                        </div>

                                    </template>

                                </Column>

                                <Column field="paid" :header="$t('paid')" sortable>

                                    <template #body="{data}">
                                        <Checkbox v-model="data.paid" binary disabled/>
                                    </template>

                                    <template #filter="{ filterModel, filterCallback }">
                                        <Dropdown v-model="filterModel.value" @change="filterCallback()"
                                                  :options="[true, false]"
                                                  :placeholder="$t('any')" class="p-column-filter" style="width: 120px">
                                            <template #option="{option}">
                                                {{ option ? $t('paid') : $t('unpaid') }}
                                            </template>
                                            <template #value="{value}">
                                                {{
                                                    value == true ? $t('paid') : (value === false ? $t('unpaid') : $t('any'))
                                                }}
                                            </template>
                                        </Dropdown>
                                    </template>

                                </Column>

                                <Column field="amount" :header="$t('amount')" sortable>
                                    <template #body="{data}">
                                        {{ data.amount ? formatCurrency(data.amount) : '' }}
                                    </template>
                                </Column>

                                <Column :header="`${$t('bill')} PDF`">
                                    <template #body="{data}">
                                        <a v-if="data.has_bill_pdf" :href="`/api/bills/pdf/${data.id}/bill/download`"
                                           class="pdf-link" target="_blank" :title="`${$t('download')} PDF`">
                                            <img src="/img/pdf-svgrepo-com.svg"/>
                                        </a>
                                    </template>
                                </Column>

                                <Column :header="`${$t('payment_confirmation')} PDF`">
                                    <template #body="{data}">
                                        <a v-if="data.has_payment_pdf"
                                           :href="`/api/bills/pdf/${data.id}/payment_confirmation/download`"
                                           class="pdf-link"
                                           target="_blank" :title="`${$t('download')} PDF`">
                                            <img src="/img/pdf-svgrepo-com.svg"/>
                                        </a>
                                    </template>
                                </Column>

                                <Column :header="$t('actions')">
                                    <template #body="{data}">
                                        <Button severity="info" :aria-label="$t('edit')" class="mr-2 mb-2" size="small"
                                                outlined
                                                :label="$t('edit')" @click="open_edit_bill(data)"/>
                                        <Button severity="danger" :aria-label="$t('del')" class=" mb-2" size="small"
                                                outlined
                                                :label="$t('del')" @click="confirm_delete_bill($event, data)"/>
                                    </template>
                                </Column>

                                <template #empty>{{ $t('no_bills_found') }}</template>

                                <template #loading>
                                    <div style="background-color: #333; padding: 30px; margin-top: 140px;">
                                        {{ $t('loading_data') }}
                                    </div>
                                </template>

                                <template #paginatorstart>
                                    <em class="hidden lg:block" style="margin: 0 25px;">{{ $t('total_records') }}:
                                        {{ bills.length }}</em>
                                </template>

                                <template #paginatorend>
                                    <div class="mt-2 md:mt-0">
                                        <Button outlined :aria-label="$t('add_bill')" :label="$t('add_bill')"
                                                :title="$t('add_bill')"
                                                @click="open_add_bill()"/>
                                        <Button outlined class="ml-2" :aria-label="$t('get_zip')" :label="$t('get_zip')"
                                                :title="$t('get_zip')" @click="get_zip()"/>
                                    </div>
                                </template>

                            </DataTable>

                        </TabPanel>

                        <TabPanel>
                            <template #header>

                                <Inplace :closable="true" @open="editing_hh_name=''" @close="add_household" unstyled
                                         close-icon="pi pi-check"
                                         class="tab-add">
                                    <template #display>
                                        <Button text icon="pi pi-plus" :title="$t('add_household')"/>
                                    </template>
                                    <template #content>
                                        <InputText v-model="editing_hh_name" :placeholder="$t('household_name')" autofocus class="max-w-24 sm:max-w-none"
                                                   @keydown.space="$event.stopPropagation()"/>
                                    </template>
                                </Inplace>
                            </template>
                        </TabPanel>

                    </TabView>

                    <Dialog v-model:visible="edit_bill_dlg_visible" modal :header="edit_bill_dlg_title"
                            content-class="dlg-edit-bill"
                            :style="{ width: '900px', maxWidth: '95%' }">

                        <div class="feedback-ctr sm:flex sm:align-items-center sm:gap-3 mt-4 mb-5">

                            <div>
                                <FloatLabel class="w-full md:w-24rem">
                                    <Dropdown id="utility_company" v-model="selected_company"
                                              filter reset-filter-on-clear show-clear :options="utility_companies"
                                              optionLabel="name"
                                              :placeholder="$t('select_a_company_or_add_a_new_company')"
                                              class="w-full"
                                    />
                                    <label for="utility_company">{{ $t('utility_company') }}</label>
                                </FloatLabel>
                                <span class="feedback text-danger"
                                      v-if="save_pressed && !(selected_company as any)?.id">{{
                                        $t('this_field_is_required')
                                    }}</span>
                            </div>

                            <Button type="button" :label="$t('add_company')" size="small" class=" sm:mt-0 mt-2 float-end sm:float-none"
                                    @click="()=>{adding_company_name=''; dlg_add_company_visible=true}"></Button>

                        </div>

                        <div class="md:flex align-items-start gap-3 mb-2">
                            <div class="feedback-ctr w-full md:w-24rem mb-2">
                                <FloatLabel class="">
                                    <Calendar v-model="bill.bill_date" view="month" dateFormat="yy-mm" showIcon
                                              icon-display="input"
                                              class="w-full"
                                              input-id="bill_date"/>
                                    <label for="bill_date">{{ $t('bill_date') }}</label>
                                </FloatLabel>
                                <span class="feedback text-danger"
                                      v-if="save_pressed && !bill.bill_date">{{ $t('this_field_is_required') }}</span>
                            </div>

                            <div class="feedback-ctr w-full md:w-24rem mb-2">
                                <FloatLabel class="">
                                    <Calendar v-model="bill.payment_date" view="date" class="w-full" showIcon
                                              icon-display="input" :max-date="new Date()"
                                              dateFormat="yy-mm-dd"
                                              input-id="payment_date"/>
                                    <label for="payment_date">{{ $t('payment_date') }}</label>
                                </FloatLabel>
                                <span class="feedback text-danger" v-if="save_pressed && !bill.payment_date">{{
                                        $t('this_field_is_required')
                                    }}</span>
                            </div>

                            <div class="w-full md:w-24rem md:mt-0 mb-2">
                                <FloatLabel>
                                    <InputNumber mode="currency" currency="EUR" locale="en-US" class="w-full"
                                                 v-model.number="bill.amount" input-id="amount"/>
                                    <label for="amount">{{ $t('amount') }}</label>
                                </FloatLabel>
                            </div>

                            <div class="w-full md:w-24rem md:mt-0 mt-5 mb-6">
                                <FloatLabel>
                                    <Toolbar style="padding: 0.6rem">
                                        <template #start>
                                            <Checkbox class="w-full" :binary="true" v-model="bill.paid" input-id="paid"
                                                      @click="confirm_change_paid_status"/>
                                        </template>
                                    </Toolbar>
                                    <label for="paid">{{ $t('paid') }}</label>
                                    <input type="hidden" class="p-filled"/>
                                </FloatLabel>
                            </div>

                        </div>

                        <div class="flex w-full align-items-start gap-3 mb-6 text-sm">

                            <div class="feedback-ctr p-float-label">
                                <div v-if="bill.has_bill_pdf" class="text-center">
                                    <a :href="`/api/bills/pdf/${bill.id}/bill`" class="pdf-link" target="_blank"
                                       :title="$t('view_pdf')">
                                        <img src="/img/pdf-svgrepo-com.svg"/>
                                    </a>

                                    <Button text icon="pi pi-trash" @click="confirm_delete_pdf($event, 'bill')"/>
                                </div>
                                <FileUpload v-else name="pdf" auto :url="`/api/upload/${bill.id ?? 0}/bill`"
                                            :multiple="false" accept=".pdf" :show-cancel-button="false"
                                            :choose-label="$t('upload_bill')"
                                            :show-upload-button="false"
                                            @before-send="beforeSend($event)"
                                            @upload="bill.has_bill_pdf = true"
                                            :maxFileSize="1000000" input-id="bill_pdf_path">
                                    <template #empty>
                                        <p>{{ $t('drag_and_drop_file_to_here_to_upload') }}</p>
                                    </template>
                                </FileUpload>
                                <input type="hidden" class="p-filled"/>
                                <label for="bill_pdf_path">{{ $t('bill') }}</label>
                                <span class="feedback text-danger" v-if="save_pressed && !bill.has_bill_pdf">{{
                                        $t('this_field_is_required')
                                    }}</span>
                            </div>


                            <div class="p-float-label" style="min-width: 135px; max-width: 54%;">
                                <div v-if="bill.has_payment_pdf" class="text-center">
                                    <a :href="`/api/bills/pdf/${bill.id}/payment_confirmation`" class="pdf-link"
                                       target="_blank" :title="$t('view_pdf')">
                                        <img src="/img/pdf-svgrepo-com.svg"/>
                                    </a>

                                    <Button text icon="pi pi-trash"
                                            @click="confirm_delete_pdf($event, 'payment_confirmation')"/>
                                </div>
                                <FileUpload v-else name="pdf" auto
                                            :url="`/api/upload/${bill.id ?? 0}/payment_confirmation`"
                                            :multiple="false" accept=".pdf" :show-cancel-button="false"
                                            :choose-label="$t('upload_payment_confirmation')"
                                            :show-upload-button="false"
                                            @before-send="beforeSend($event)"
                                            @upload="bill.has_payment_pdf = true; bill.paid = true"
                                            :maxFileSize="1000000" input-id="payment_confirmation_pdf_path">
                                    <template #empty>
                                        <p>{{ $t('drag_and_drop_file_to_here_to_upload') }}</p>
                                    </template>
                                </FileUpload>
                                <input type="hidden" class="p-filled"/>
                                <label for="payment_confirmation_pdf_path">{{ $t('payment_confirmation') }}</label>
                            </div>
                        </div>

                        <div class="flex justify-content-end gap-2">
                            <Button type="button" :label="$t('cancel')" severity="secondary"
                                    @click="edit_bill_dlg_visible = false"></Button>
                            <Button type="button" :label="$t('save')" @click="save_bill(bill)"></Button>
                        </div>

                    </Dialog>

                    <Dialog v-model:visible="dlg_add_company_visible" modal :header="$t('add_company')" :style="{ width: '25rem' }">
                        <div class="flex align-items-center gap-3 mt-4 mb-2">

                            <div class="w-full md:w-24rem md:mt-0 mb-2">
                                <FloatLabel>
                                    <InputText class="w-full" v-model="adding_company_name" input-id="company_name" autofocus/>
                                    <label for="company_name">{{ $t('enter_a_company_name') }}</label>
                                </FloatLabel>
                            </div>

                        </div>

                        <div class="flex justify-content-end gap-2">
                            <Button type="button" :label="$t('cancel')" size="small" severity="secondary" @click="dlg_add_company_visible = false"/>
                            <Button type="button" :label="$t('save')" size="small" @click="add_company"/>
                        </div>
                    </Dialog>

                </div>
            </div>
        </div>

        <Toast/>
        <ConfirmPopup/>
        <ConfirmDialog class="hh-confirm" group="hh"/>

    </AuthenticatedLayout>
</template>

<style lang="scss">
h2 {
    font-family: "Audiowide", sans-serif;
    font-size: 24px;
}

.p-fileupload {
    .p-fileupload-file-thumbnail {
        display: none;
    }
}

.bills-table {

    @media screen and (max-width: 450px) {
        .p-paginator {
            padding: 0.5rem 0rem;
        }
    }

    .p-paginator-rpp-options {
        .p-dropdown-label.p-inputtext {
            padding-top: 4px;
        }
    }

    .pdf-link {
        display: block;
        width: 40px;
    }

    .p-column-filter-menu-button {
        display: none;
    }

    th.p-filter-column {
        padding-top: 2rem;
        vertical-align: top;
    }

    .p-sortable-column:not(.p-highlight):hover {
        background: #283950;
    }
}

.dlg-edit-bill {
    .feedback-ctr {
        position: relative;
        padding-bottom: 28px;
    }

    .feedback {
        $errorColor: #ef9a9a !default;

        color: $errorColor;
        position: absolute;
        bottom: 0;
    }

    .p-fileupload.p-component {
        &, .p-button {
            font-size: inherit;
        }
    }

    .pdf-link {
        display: block;
        width: 80px;
    }
}

.hh-tabs {
    .p-tabview-nav li .p-tabview-nav-link {
        border-top-right-radius: 10px;
        border-top-left-radius: 10px;

    }

    .p-tabview-nav-content {
        margin-right: 30px;
    }

    .p-highlight {
        .p-tabview-nav-link {
            padding-top: 7px;
            padding-bottom: 7px;
        }
    }

    .p-tabview-nav-link:has(.tab-add) {
        padding-top: 7px;
        padding-bottom: 7px;
    }

    .p-tabview-nav {
        align-items: flex-end;
    }
}

.hh-tab {
    .p-inplace-display {
        cursor: inherit;
        pointer-events: none;
    }

    & > .p-splitbutton-defaultbutton {
        padding: 0;
    }

    .p-inplace .p-inplace-display:focus {
        box-shadow: none;
    }
}

.hh-confirm {
    .p-dialog-header-icons {
        display: none;
    }
}

.tab-add {
    button {
        margin-left: 5px;
    }
}
</style>
