export interface User {
    id: number;
    name: string;
    email: string;
    language: string;
    notifications: string;
    email_verified_at: string;
    is_admin: number;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
        is_impersonating: boolean;
    };
};
