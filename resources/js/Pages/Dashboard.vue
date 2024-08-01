<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import {onMounted, Ref, ref} from "vue";
import axios from "axios";
import {Bill, Bill_Plain_Obj, bill_to_plain, bills, plain_to_bill} from "@/Data/Bill";
import {utility_companies, UtilityCompany} from "@/Data/UtilityCompany";
import {useToast} from "primevue/usetoast";

import Toast from 'primevue/toast';
import dayjs from "dayjs";
import {FileUploadBeforeSendEvent} from "primevue/fileupload";
import {getCookie} from "typescript-cookie";

import ConfirmPopup from 'primevue/confirmpopup';
import {useConfirm} from "primevue/useconfirm";
import {FilterMatchMode, FilterService} from "primevue/api";
import {DataTableFilterMetaData, DataTableOperatorFilterMetaData} from "primevue/datatable";
import {usePrimeVue} from "primevue/config";

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

async function add_company(name: string): Promise<void> {
    const {data: {success, message, companies}} = await axios.post('/api/companies/store', {name});

    if (success) {
        toast.add({severity: 'success', summary: 'Success', detail: 'Company successfully added', life: 3000});
        if (companies) {
            utility_companies.value = companies;
            selected_company.value = companies.find((c: any) => c.name == name);
        }
    } else
        toast.add({severity: 'error', summary: 'Error', detail: message, life: 10000});

}

// endregion

// region Bills

const edit_bill_dlg_visible = ref(false);


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

    const {data: {user_bills, user_companies}} = await axios.get('/api/bills');
    bills.value = user_bills ?? [];
    utility_companies.value = user_companies ?? [];

    // bills.value.forEach(b => b.bill_date_date = new Date(b.bill_date));

    waiting.value = false;

}

async function open_add_bill() {
    save_pressed.value = false;
    bill.value = default_bill();
    selected_company.value = null;

    const {data: {new_id}} = await axios.post('/api/bills/store');

    bill.value.id = new_id;

    edit_bill_dlg_visible.value = true;
}

async function open_edit_bill(bill_plain: Bill_Plain_Obj) {
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

    const {data: {success, message, user_bills, user_companies}} = await axios.post('/api/bills/save', o);

    if (success) {
        toast.add({severity: 'success', summary: 'Success', detail: 'Bill successfully saved', life: 3000});
        if (user_bills)
            bills.value = user_bills;
        if (user_companies)
            utility_companies.value = user_companies;
    } else
        toast.add({severity: 'error', summary: 'Error', detail: message, life: 10000});

}

async function delete_bill(bill_plain: Bill_Plain_Obj) {
    console.log(bill_plain)
    const {data: {user_bills, user_companies}} = await axios.post('/api/bills/destroy', {id: bill_plain.id});
    toast.add({severity: 'success', summary: 'Success', detail: 'Bill successfully deleted', life: 3000});
    if (user_bills)
        bills.value = user_bills;
    if (user_companies)
        utility_companies.value = user_companies;
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

async function confirm_delete_bill(event: Event, bill_plain: Bill_Plain_Obj) {
    confirm.require({
        target: event.currentTarget as any,
        message: 'Do you want to delete this bill?',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined p-button-sm',
        acceptClass: 'p-button-danger p-button-sm',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        accept: async () => {
            await delete_bill(bill_plain);
        },
    });
}

// endregion

// region DataTable
FilterService.register('filterByMonthRange', function (date: string, {from, to}) {
    // console.log('filterByDateRange', arguments)
    return (!from || dayjs(from).format('YYYY-MM') <= date) && (!to || date <= dayjs(to).format('YYYY-MM'))
});

FilterService.register('filterByDateRange', function (date: string, {from, to}) {
    // console.log('filterByDateRange', arguments)
    return (!from || dayjs(from).format('YYYY-MM-dd') <= date) && (!to || date <= dayjs(to).format('YYYY-MM-dd'))
});

const filters: Ref<{ [k: string]: DataTableFilterMetaData | DataTableOperatorFilterMetaData }> = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
    utility_company_name: {value: null, matchMode: FilterMatchMode.IN,},
    paid: {value: null, matchMode: FilterMatchMode.EQUALS,},
    bill_date: {value: [], matchMode: 'filterByMonthRange'},
    payment_date: {value: [], matchMode: 'filterByDateRange'},
});
// endregion


</script>

<template>
    <Head title="My Bills"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-white ">
                {{ $t('my_bills') }}
            </h2>
        </template>

        <div class="py-3">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">


                    <DataTable :value="bills" paginator paginator-position="top" :rows="10" :always-show-paginator="true"
                               :rowsPerPageOptions="[10, 20, 50, 100]"
                               v-model:filters="filters" filterDisplay="row"
                               table-class="bills-table" table-style="width: 100%" showGridlines stripedRows>

                        <Column v-if="false" field="id" header="ID" sortable/>

                        <Column field="bill_date" :header="$t('bill_date')" sortable>

                            <template #filter="{ filterModel, filterCallback }">
                                <div class="flex">
                                    <FloatLabel>
                                        <Calendar v-model="filterModel.value.from" view="month" dateFormat="yy-mm" @dateSelect="filterCallback()"
                                                  :input-style="{width:'150px'}" showIcon icon-display="input"/>
                                        <label>{{ $t('date_from') }}</label>
                                    </FloatLabel>
                                    <Button icon="pi pi-filter-slash" text severity="secondary" @click="filterModel.value.from = filterModel.value.to = null"/>
                                </div>
                                <div class="mt-4 flex">
                                    <FloatLabel>
                                        <Calendar v-model="filterModel.value.to" :min-date="filterModel.value.from" view="month"
                                                  dateFormat="yy-mm" @dateSelect="filterCallback()"
                                                  :input-style="{width:'150px'}" showIcon icon-display="input"/>
                                        <label>{{ $t('date_to') }}</label>
                                    </FloatLabel>
                                </div>
                            </template>


                        </Column>

                        <Column field="utility_company_name" :header="$t('utility_company')" sortable>

                            <template #filter="{ filterModel, filterCallback }">
                                <MultiSelect v-model="filterModel.value" @change="filterCallback()" :options="utility_companies.map(c=>c.name)"
                                             :placeholder="$t('any')" class="p-column-filter" style="min-width: 14rem" :maxSelectedLabels="1"/>
                            </template>

                        </Column>

                        <Column field="payment_date" :header="$t('payment_date')" sortable>

                            <template #filter="{ filterModel, filterCallback }">
                                <div class="flex">
                                    <FloatLabel>
                                        <Calendar v-model="filterModel.value.from" dateFormat="yy-mm-dd" @dateSelect="filterCallback()" showButtonBar
                                                  :input-style="{width:'150px'}" showIcon icon-display="input"/>
                                        <label>{{ $t('date_from') }}</label>
                                    </FloatLabel>
                                    <Button icon="pi pi-filter-slash" text severity="secondary" @click="filterModel.value.from = filterModel.value.to = null"/>
                                </div>
                                <div class="mt-4 flex">
                                    <FloatLabel>
                                        <Calendar v-model="filterModel.value.to" :min-date="filterModel.value.from" dateFormat="yy-mm-dd" @dateSelect="filterCallback()" showButtonBar
                                                  :input-style="{width:'150px'}" showIcon icon-display="input"/>
                                        <label>{{ $t('date_to') }}</label>
                                    </FloatLabel>
                                </div>
                            </template>

                        </Column>

                        <Column field="paid" :header="$t('paid')" sortable>

                            <template #body="{data}">
                                <Checkbox :value="data.paid" disabled/>
                            </template>

                            <template #filter="{ filterModel, filterCallback }">
                                <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="[true, false]"
                                          :placeholder="$t('any')" class="p-column-filter" style="width: 120px">
                                    <template #option="{option}">
                                        {{ option ? $t('paid') : $t('unpaid') }}
                                    </template>
                                    <template #value="{value}">
                                        {{ value == true ? $t('paid') : (value === false ? $t('unpaid') : $t('any')) }}
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
                                <a v-if="data.has_bill_pdf" :href="`/api/bills/pdf/${data.id}/bill/download`" class="pdf-link" target="_blank" :title="`${$t('download')} PDF`">
                                    <img src="/img/pdf-svgrepo-com.svg"/>
                                </a>
                            </template>
                        </Column>

                        <Column :header="`${$t('payment_confirmation')} PDF`">
                            <template #body="{data}">
                                <a v-if="data.has_payment_pdf" :href="`/api/bills/pdf/${data.id}/payment_confirmation/download`" class="pdf-link" target="_blank" :title="`${$t('download')} PDF`">
                                    <img src="/img/pdf-svgrepo-com.svg"/>
                                </a>
                            </template>
                        </Column>

                        <Column :header="$t('actions')">
                            <template #body="{data}">
                                <Button severity="info" :aria-label="$t('edit')" class="mr-2 mb-2" size="small" outlined :label="$t('edit')" @click="open_edit_bill(data)"/>
                                <Button severity="danger" :aria-label="$t('delete')" class=" mb-2" size="small" outlined :label="$t('delete')" @click="confirm_delete_bill($event, data)"/>
                            </template>
                        </Column>

                        <template #empty>{{ $t('no_bills_found') }}</template>

                        <template #loading>
                            <div style="background-color: #333; padding: 30px; margin-top: 140px;">
                                {{ $t('loading_data') }}
                            </div>
                        </template>

                        <template #paginatorstart>
                            <em class="hidden md:block" style="margin: 0 25px;">{{ $t('total_records') }}: {{ bills.length }}</em>
                        </template>

                        <template #paginatorend>
                            <Button icon="pi pi-plus" rounded :aria-label="$t('add_bill')" :title="$t('add_bill')" @click="open_add_bill()"/>
                            <Button icon="pi pi-download" rounded class="ml-2" :aria-label="$t('get_zip')" :title="$t('get_zip')" @click="get_zip()"/>
                        </template>

                    </DataTable>


                    <Dialog v-model:visible="edit_bill_dlg_visible" modal :header="$t('edit_bill')" content-class="dlg-edit-bill"
                            :style="{ width: '900px', maxWidth: '95%' }">

                        <div class="feedback-ctr flex align-items-center gap-3 mt-4 mb-6">

                            <FloatLabel class="w-full md:w-24rem">
                                <Dropdown id="utility_company" v-model="selected_company"
                                          editable filter reset-filter-on-clear show-clear :options="utility_companies"
                                          optionLabel="name"
                                          :placeholder="$t('select_a_company_or_add_a_new_company')"
                                          class="w-full"
                                />
                                <label for="utility_company">{{ $t('utility_company') }}</label>
                            </FloatLabel>
                            <span class="feedback text-danger" v-if="save_pressed && typeof selected_company === 'string'">{{ $t('the_company_isnt_saved') }}</span>
                            <span class="feedback text-danger" v-else-if="save_pressed && !(selected_company as any)?.id">{{ $t('this_field_is_required') }}</span>

                            <Button type="button" :label="$t('add_company')"
                                    :disabled="typeof selected_company !== 'string' || selected_company.trim() === '' || utility_companies.some(c=>c.name === (selected_company as string)?.trim())"
                                    @click="add_company(selected_company as string)"></Button>

                        </div>

                        <div class="md:flex align-items-start gap-3 mb-6">
                            <div class="feedback-ctr w-full md:w-24rem">
                                <FloatLabel class="">
                                    <Calendar v-model="bill.bill_date" view="month" dateFormat="yy-mm" showIcon icon-display="input"
                                              class="w-full"
                                              input-id="bill_date"/>
                                    <label for="bill_date">{{ $t('bill_date') }}</label>
                                </FloatLabel>
                                <span class="feedback text-danger" v-if="save_pressed && !bill.bill_date">{{ $t('this_field_is_required') }}</span>
                            </div>

                            <div class="feedback-ctr w-full md:w-24rem md:mt-0 sm:mt-6">
                                <FloatLabel class="">
                                    <Calendar v-model="bill.payment_date" view="date" class="w-full" showIcon icon-display="input"
                                              dateFormat="yy-mm-dd"
                                              input-id="payment_date"/>
                                    <label for="payment_date">{{ $t('payment_date') }}</label>
                                </FloatLabel>
                                <span class="feedback text-danger" v-if="save_pressed && !bill.payment_date">{{ $t('this_field_is_required') }}</span>
                            </div>

                            <FloatLabel class="w-full md:w-24rem md:mt-0 sm:mt-6">
                                <InputNumber mode="currency" currency="EUR" locale="en-US" class="w-full"
                                             v-model.number="bill.amount" input-id="amount"/>
                                <label for="amount">{{ $t('amount') }}</label>
                            </FloatLabel>

                        </div>

                        <div class="flex w-full align-items-start gap-3 mb-6">

                            <div class="feedback-ctr p-float-label" style="min-width: 200px;">
                                <div v-if="bill.has_bill_pdf">
                                    <a :href="`/api/bills/pdf/${bill.id}/bill`" class="pdf-link" target="_blank" title="View PDF">
                                        <img src="/img/pdf-svgrepo-com.svg"/>
                                    </a>
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
                                <span class="feedback text-danger" v-if="save_pressed && !bill.has_bill_pdf">{{ $t('this_field_is_required') }}</span>
                            </div>


                            <div class="p-float-label" style="min-width: 200px;">
                                <div v-if="bill.has_payment_pdf">
                                    <a :href="`/api/bills/pdf/${bill.id}/payment_confirmation`" class="pdf-link" target="_blank" title="View PDF">
                                        <img src="/img/pdf-svgrepo-com.svg"/>
                                    </a>
                                </div>
                                <FileUpload v-else name="pdf" auto :url="`/api/upload/${bill.id ?? 0}/payment_confirmation`"
                                            :multiple="false" accept=".pdf" :show-cancel-button="false"
                                            :choose-label="$t('upload_payment_confirmation')"
                                            :show-upload-button="false"
                                            @before-send="beforeSend($event)"
                                            @upload="bill.has_payment_pdf = true"
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

                </div>
            </div>
        </div>

        <Toast/>
        <ConfirmPopup/>

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

    .pdf-link {
        display: block;
        width: 80px;
    }
}
</style>
