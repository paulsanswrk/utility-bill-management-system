<template>
    <Dialog v-model:visible="dialogVisible" :header="`${$t('change_password.header')} ${_user_name}`" :modal="true"
            class="change-pwd-dialog w-30rem max-w-full" @hide="dialogVisible = false">

        <Message v-if="errorMessages.length" severity="error" :sticky="true" :life="4000" @close="errorMessages=[]"
                 @life-end="errorMessages=[]" style="max-width:100%">
            <ul class="list-disc">
                <li v-for="error in errorMessages" :key="error">
                    {{ error }}
                </li>
            </ul>
        </Message>

        <form @submit.prevent="validateForm">
            <div class="field flex justify-content-between align-items-center w-full">
                <label for="password">{{ $t('change_password.new_password') }}</label>
                &nbsp;
                <InputText type="password" id="password" v-model="password" name="password" required autofocus
                           autocomplete="new-password"/>
            </div>

            <div class="field flex justify-content-between align-items-center w-full">
                <label for="confirmPassword" class="mr-2">{{ $t('change_password.confirm_password') }}</label>
                &nbsp;
                <InputText type="password" id="confirmPassword" v-model="confirmPassword" name="confirm_password"
                           autocomplete="new-password" required/>
            </div>

            <div class="field text-right">
                <Button type="submit" :label="$t('change_password.submit')"/>
            </div>
        </form>
    </Dialog>

</template>

<script setup lang="ts">
import {Ref, ref} from 'vue';
import axios from "axios";
import {useToast} from "primevue/usetoast";
import {useI18n} from "vue-i18n";

const {t} = useI18n();

const toast = useToast();

const _user_id = ref(0);
const _user_name = ref('');

async function open(user_id: number, user_name: string) {
    _user_id.value = user_id;
    _user_name.value = user_name;
    dialogVisible.value = true;
}

defineExpose({
    open
});

const dialogVisible = ref(false);
const password = ref('');
const confirmPassword = ref('');
const errorMessages: Ref<string[]> = ref([]);

async function validateForm() {
    if (password.value === '' || confirmPassword.value === '') {
        errorMessages.value.push(t('change_password.errors.required_fields'));
    } else if (password.value !== confirmPassword.value) {
        errorMessages.value.push(t('change_password.errors.passwords_mismatch'));
    } else {
        errorMessages.value = [];

        const { data: { success, message, errors } } = await axios.post('/api/change_pwd', {
            user_id: _user_id.value,
            password: password.value,
            password_confirmation: confirmPassword.value,
        }, { validateStatus: () => true });

        if (success) {
            errorMessages.value = [];
            dialogVisible.value = false;
            toast.add({ severity: 'success', summary: t('change_password.success.title'), detail: t('change_password.success.message'), life: 3000 });
        } else {
            errorMessages.value = errors ? Object.values(errors).flat() : [message];
        }
    }
}</script>

<style lang="scss">

.change-pwd-dialog {
    .p-message {
        .p-message-wrapper {
            align-items: flex-start;

            .p-message-icon {
                display: none;
            }
        }
    }

    form input {
        color: var(--surface-text) !important;
    }

    .field {
        margin-bottom: 1rem;
    }

}

</style>
