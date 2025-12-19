<template>

    <Head title="AI Bill Summaries"/>

    <AuthenticatedLayout>

        <template #header>
            <h2 class="text-white text-2xl">
                <i class="pi pi-sparkles mr-2"></i>AI Bill Summaries
            </h2>
        </template>

        <div class="py-3">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">

                    <ProgressSpinner v-if="waiting" class="w-full"/>

                    <DataTable v-else :value="summaries"
                               :paginator="true" :total-records="totalRecords"
                               :rowsPerPageOptions="[10, 20, 50, 100]"
                               v-model:rows="rows"
                               :lazy="true" @page="onPage"
                               @sort="onSort($event)"
                               paginator-position="both"
                               v-model:expandedRows="expandedRows"
                               dataKey="id"
                               class="summaries-table"
                               showGridlines stripedRows
                    >
                        <Column expander style="width: 3rem"/>
                        <Column field="bill_date" header="Bill Date" sortable></Column>
                        <Column field="utility_company_name" header="Company" sortable></Column>
                        <Column field="user_name" header="User" sortable>
                            <template #body="{data}">
                                <span>{{ data.user_name }}</span>
                                <br/>
                                <small class="text-gray-400">{{ data.user_email }}</small>
                            </template>
                        </Column>
                        <Column field="household_name" header="Household"></Column>
                        <Column field="amount" header="Amount" sortable>
                            <template #body="{data}">
                                {{ data.amount ? formatCurrency(data.amount) : '' }}
                            </template>
                        </Column>
                        <Column field="created_at" header="Analyzed" sortable>
                            <template #body="{data}">
                                {{ formatDate(data.created_at) }}
                            </template>
                        </Column>

                        <template #expansion="{data}">
                            <div class="p-4">
                                <h4 class="text-sm font-semibold mb-2 text-gray-300">
                                    <i class="pi pi-sparkles mr-1"></i> AI Summary
                                </h4>
                                <pre class="summary-text">{{ data.bill_summary }}</pre>
                            </div>
                        </template>

                        <template #empty>No AI summaries found.</template>

                        <template #paginatorstart>
                            <em class="hidden lg:block" style="margin: 0 25px;">Total: {{ totalRecords }}</em>
                        </template>

                    </DataTable>

                </div>
            </div>
        </div>

    </AuthenticatedLayout>

</template>

<script setup lang="ts">
import {onMounted, ref} from 'vue';
import {Head} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";
import dayjs from "dayjs";

const waiting = ref(false);
const summaries = ref([]);
const totalRecords = ref(0);
const rows = ref(20);
const page = ref(0);
const expandedRows = ref([]);
let sortField = 'bill_date';
let sortOrder = 'desc';

onMounted(async () => {
    await loadSummaries();
});

async function loadSummaries() {
    waiting.value = true;

    const {data: {success, summaries: result}} = await axios.get('/api/admin/bill-summaries', {
        params: {
            page: page.value + 1,
            rows: rows.value,
            sortField,
            sortOrder,
        }
    });

    if (success) {
        summaries.value = result.data ?? [];
        totalRecords.value = result.total;
    } else {
        summaries.value = [];
    }

    waiting.value = false;
}

async function onPage(ev: any) {
    page.value = ev.page;
    await loadSummaries();
}

async function onSort(event: any) {
    sortField = event.sortField;
    sortOrder = event.sortOrder == 1 ? 'asc' : 'desc';
    page.value = 0;
    await loadSummaries();
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat('de-DE', {style: 'currency', currency: 'EUR'}).format(value);
}

function formatDate(dateStr: string) {
    return dateStr ? dayjs(dateStr).format('YYYY-MM-DD HH:mm') : '';
}
</script>

<style lang="scss">
.summaries-table {
    .summary-text {
        white-space: pre-wrap;
        word-wrap: break-word;
        font-family: inherit;
        font-size: 0.9rem;
        line-height: 1.6;
        color: #e0e0e0;
        background: rgba(255, 255, 255, 0.03);
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
}
</style>
