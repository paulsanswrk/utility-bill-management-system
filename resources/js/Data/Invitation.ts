export interface Invitation {
    id: number;
    uuid: string;
    household_ids: string;
    invited_by: number;
    hh_names: string[];
    inviter: {
        id: number;
        name: string;
        email: string;
    };
}
