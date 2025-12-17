import dayjs from "dayjs";
import {ref, Ref} from "vue";

export interface Bill {
    id: number;
    amount: number;
    bill_date: Date;
    payment_date: Date;
    utility_company_id: number;
    utility_company_name: string;
    paid: boolean;
    has_bill_pdf: boolean;
    has_payment_pdf: boolean;
    bill_summary?: string;
}

export const bills: Ref<Bill_Plain_Obj[]> = ref([]);


export type Bill_Plain_Obj = {
    id: number,
    amount: number,
    bill_date: string,
    // bill_date_date?: Date,
    payment_date: string,
    utility_company_id: number;
    utility_company_name: string;
    paid: boolean,
    has_bill_pdf: boolean,
    has_payment_pdf: boolean,
    bill_summary?: string
};

export function plain_to_bill(o: Bill_Plain_Obj): Bill {
    return {
        id: o.id,
        amount: o.amount,
        bill_date: new Date(o.bill_date + '-01'),
        payment_date: new Date(o.payment_date),
        utility_company_id: o.utility_company_id,
        utility_company_name: o.utility_company_name,
        paid: o.paid,
        has_bill_pdf: o.has_bill_pdf,
        has_payment_pdf: o.has_payment_pdf,
        bill_summary: o.bill_summary,
    }
}

export function bill_to_plain(o: Bill): Bill_Plain_Obj {
    return {
        id: o.id,
        amount: o.amount,
        bill_date: dayjs(o.bill_date).format('YYYY-MM'),
        payment_date: dayjs(o.payment_date).format('YYYY-MM-DD'),
        utility_company_id: o.utility_company_id,
        utility_company_name: o.utility_company_name,
        paid: o.paid,
        has_bill_pdf: o.has_bill_pdf,
        has_payment_pdf: o.has_payment_pdf,
        bill_summary: o.bill_summary,
    }
}
