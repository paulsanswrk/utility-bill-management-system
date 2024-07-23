<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import {onMounted, Ref, ref} from "vue";
import axios from "axios";
import {Bill, bill_to_plain} from "@/Data/Bill";
import {UtilityCompany} from "@/Data/UtilityCompany";
import {useToast} from "primevue/usetoast";

import Toast from 'primevue/toast';
import dayjs from "dayjs";

const toast = useToast();

onMounted(async () => {
    await load_bills();
    await load_companies();
});

// region DataTable

const waiting = ref(false);
const totalRecords = ref(1);

// endregion


// region Utility Companies
const utility_companies: Ref<UtilityCompany[]> = ref([]);

async function add_company(name: string): Promise<void> {
    const {data: {success, message, companies}} = await axios.post('/api/companies/store', {name});

    if (success) {
        toast.add({severity: 'success', summary: 'Success', detail: 'Company successfully added', life: 3000});
        if (companies)
            utility_companies.value = companies;
    } else
        toast.add({severity: 'error', summary: 'Error', detail: message, life: 10000});

}
async function load_companies() {
    const {data} = await axios.get('/api/companies');
    utility_companies.value = data ?? [];
}
// endregion

// region Bills

const edit_bill_dlg_visible = ref(false);

const bills = ref([]);

const bill: Ref<Bill> = ref(default_bill());

function default_bill(): Bill {
    return {
        id: 0,
        amount: 0,
        bill_date: dayjs().startOf('month').subtract(1, 'month').toDate(),
        payment_date: dayjs().subtract(1, 'day').toDate(),
        utility_company: '',
        paid: false,
        has_bill_pdf: false,
        has_payment_pdf: false
    }
}

async function load_bills() {
    waiting.value = true;

    const {data} = await axios.get('/api/bills');
    // console.log(data)
    bills.value = data ?? [];

    waiting.value = false;

}

function open_add_bill(): void {
    bill.value = default_bill();

    edit_bill_dlg_visible.value = true;
}

const onFileUpload = (e: Event) => {
    toast.add({severity: 'info', summary: 'Success', detail: 'File Uploaded', life: 3000});
};

async function save_bill(bill: Bill): Promise<void> {
    edit_bill_dlg_visible.value = false;
    const o = bill_to_plain(bill);

    const {data: {success, message, bills}} = await axios.post('/api/bills/store', o);

    if (success) {
        toast.add({severity: 'success', summary: 'Success', detail: 'Bill successfully saved', life: 3000});
        if (bills)
            bills.value = bills;
    } else
        toast.add({severity: 'error', summary: 'Error', detail: message, life: 10000});

}
// endregion

</script>

<template>
    <Head title="My Bills"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Bills</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">


                    <DataTable :value="bills" paginator paginator-position="top" :rows="5"
                               :rowsPerPageOptions="[5, 10, 20, 50]"
                               table-style="width: 100%">
                        <Column field="id" header="ID" sortable/>

                        <Column field="utility_company" header="Utility Company" sortable/>

                        <Column field="paid" header="Paid" sortable>
                            <template #body="slotProps">
                                {{ slotProps.data.paid ? 'yes' : 'no' }}
                            </template>
                        </Column>
                        <Column field="amount" header="Amount" sortable></Column>
                        <Column field="bill_date" header="Bill Date" sortable></Column>
                        <Column field="payment_date" header="Payment Date" sortable></Column>


                        <template #empty> No bills found.</template>

                        <template #loading>
                            <div style="background-color: #333; padding: 30px; margin-top: 140px;">
                                Loading data. Please wait.
                            </div>
                        </template>

                        <template #paginatorstart>
                            <em style="margin: 0 25px;">Total records: {{ totalRecords }}</em>
                        </template>

                        <template #paginatorend>
                            <Button icon="pi pi-plus" rounded aria-label="Add" @click="open_add_bill()"/>

                        </template>

                    </DataTable>


                    <Dialog v-model:visible="edit_bill_dlg_visible" modal header="Edit Bill"
                            :style="{ width: '900px', maxWidth: '95%' }">

                        <div class="flex align-items-center gap-3 mt-4 mb-6">

                            <FloatLabel class="w-full md:w-24rem">
                                <Dropdown id="utility_company" v-model="bill.utility_company"
                                          editable filter reset-filter-on-clear show-clear :options="utility_companies"
                                          optionLabel="name"
                                          placeholder="Select a company or add a new company"
                                          class="w-full"
                                />
                                <label for="utility_company">Utility Company</label>
                            </FloatLabel>

                            <Button type="button" label="Add Company"
                                    :disabled="typeof bill.utility_company !== 'string' || bill.utility_company.trim() === '' || utility_companies.some(c=>c.name === bill.utility_company?.trim())"
                                    @click="add_company(bill.utility_company.name ?? bill.utility_company)"></Button>

                        </div>

                        <div class="md:flex align-items-center gap-3 mb-6">
                            <FloatLabel class="w-full md:w-24rem">
                                <Calendar v-model="bill.bill_date" view="month" dateFormat="yy-mm"
                                          class="w-full"
                                          input-id="bill_date"/>
                                <label for="bill_date">Bill Month</label>
                            </FloatLabel>

                            <FloatLabel class="w-full md:w-24rem md:mt-0 sm:mt-6">
                                <Calendar v-model="bill.payment_date" view="date" class="w-full"
                                          dateFormat="yy-mm-dd"
                                          input-id="payment_date"/>
                                <label for="payment_date">Payment Date</label>
                            </FloatLabel>

                            <FloatLabel class="w-full md:w-24rem md:mt-0 sm:mt-6">
                                <InputNumber mode="currency" currency="USD" locale="en-US" class="w-full"
                                             v-model.number="bill.amount" input-id="amount"/>
                                <label for="amount">Amount</label>
                            </FloatLabel>

                        </div>

                        <div class="flex w-full align-items-center gap-3 mb-6">

                            <div class="p-float-label">
                                <FileUpload name="bill_pdf" auto :url="`/api/upload/${bill.id ?? 0}`"
                                            @upload="onFileUpload($event)"
                                            :multiple="false" accept=".pdf" :show-cancel-button="false"
                                            choose-label="Upload Bill"
                                            :show-upload-button="false"
                                            :maxFileSize="1000000" input-id="bill_pdf_path">
                                    <template #empty>
                                        <p>Drag and drop file to here to upload.</p>
                                    </template>
                                </FileUpload>
                                <input type="hidden" class="p-filled"/>
                                <label for="amount">Upload Bill</label>
                            </div>


                            <div class="p-float-label">
                                <FileUpload name="bill_pdf" auto :url="`/api/upload/${bill.id ?? 0}`"
                                            @upload="onFileUpload($event)"
                                            :multiple="false" accept=".pdf" :show-cancel-button="false"
                                            choose-label="Upload Payment Confirmation"
                                            :show-upload-button="false"
                                            :maxFileSize="1000000" input-id="bill_pdf_path">
                                    <template #empty>
                                        <p>Drag and drop file to here to upload.</p>
                                    </template>
                                </FileUpload>
                                <input type="hidden" class="p-filled"/>
                                <label for="amount">Upload Payment Confirmation</label>
                            </div>
                        </div>

                        <div class="flex justify-content-end gap-2">
                            <Button type="button" label="Cancel" severity="secondary"
                                    @click="edit_bill_dlg_visible = false"></Button>
                            <Button type="button" label="Save" @click="save_bill(bill)"></Button>
                        </div>

                    </Dialog>

                </div>
            </div>
        </div>

        <Toast/>

    </AuthenticatedLayout>
</template>

<style lang="scss">
.p-fileupload {
    .p-fileupload-file-thumbnail {
        display: none;
    }
}
</style>
