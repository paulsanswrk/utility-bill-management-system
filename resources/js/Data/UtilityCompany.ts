import {ref, Ref} from "vue";

export interface UtilityCompany {
    id: number;
    name: string;
}

export const utility_companies: Ref<UtilityCompany[]> = ref([]);
