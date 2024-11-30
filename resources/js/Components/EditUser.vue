<template>
    <Dialog header="Edit User" v-model:visible="visible" modal @hide="onHide" :style="{ width: '500px', maxWidth: '90vw' }">


        <Message v-if="!!_message" severity="error" :sticky="true" :life="4000">{{ _message }}</Message>

        <form class="p-fluid" @submit.prevent="saveUser">
            <div class="p-field">
                <label for="name">Name</label>
                <InputText id="name" v-model="userData.name" required/>
            </div>
            <div class="p-field">
                <label for="email">Email</label>
                <input type="email" id="email" v-model="userData.email" required
                       class="p-inputtext p-component p-filled"/>

                <div v-if="userData.email_change_request" class="text-sm mt-2">
                    Waiting for confirmation from the user. New email: {{ userData.email_change_request.new_email }}
                </div>

            </div>

            <div class="p-field">
                <label for="is_admin">Is Admin</label>&nbsp;
                <Checkbox id="is_admin" v-model="userData.is_admin" :binary="true"/>
            </div>
            <div class="p-field">
                <Button type="submit" label="Save"/>
            </div>
        </form>
    </Dialog>
</template>


<script setup>
import {ref} from 'vue';
import axios from "axios";

const userData = ref();
const _message = ref();

import {useToast} from "primevue/usetoast";

const toast = useToast();

function open(_userData) {
    userData.value = {..._userData};
    userData.value.is_admin = !!userData.value.is_admin;
    visible.value = true;
}

defineExpose({
    open
});

const emit = defineEmits(['update:user']);

const visible = ref(false);

const onHide = () => {
    visible.value = false;
};

async function saveUser() {

    const {
        data: {
            success,
            message,
            notification,
        }
    } = await axios.post('/api/update_user', userData.value);

    if (success) {
        emit('update:user', userData.value);
        visible.value = false;
        if (notification)
            toast.add({
                severity: success ? 'success' : 'error',
                summary: success ? 'Success' : 'Error',
                detail: notification,
                life: 3000
            });
    } else {
        _message.value = message;
    }
}


</script>


<style lang="scss" scoped>
form input {
    color: var(--surface-text) !important;
}

.p-field {
    margin-bottom: 1.5rem;
}
</style>
