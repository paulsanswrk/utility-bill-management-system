import dayjs from "dayjs";

export interface Bill {
    id: number;
    amount: number;
    bill_date: Date;
    payment_date: Date;
    utility_company: string;
    paid: boolean;
    has_bill_pdf: boolean;
    has_payment_pdf: boolean;
}

type Bill_Plain_Obj = {
    id: number,
    amount: number,
    bill_date: string,
    payment_date: string,
    utility_company: string,
    paid: boolean,
    has_bill_pdf: boolean,
    has_payment_pdf: boolean
};

export function plain_to_bill(o: Bill_Plain_Obj): Bill {
    return {
        id: o.id,
        amount: o.amount,
        bill_date: new Date(o.bill_date + '-01'),
        payment_date: new Date(o.payment_date),
        utility_company: o.utility_company,
        paid: o.paid,
        has_bill_pdf: o.has_bill_pdf,
        has_payment_pdf: o.has_payment_pdf,
    }
}

export function bill_to_plain(o: Bill): Bill_Plain_Obj {
    return {
        id: o.id,
        amount: o.amount,
        bill_date: dayjs(o.bill_date).format('YYYY-MM'),
        payment_date: dayjs(o.payment_date).format('YYYY-MM-DD'),
        utility_company: o.utility_company,
        paid: o.paid,
        has_bill_pdf: o.has_bill_pdf,
        has_payment_pdf: o.has_payment_pdf,
    }
}
