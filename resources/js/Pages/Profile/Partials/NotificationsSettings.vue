<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Link, useForm, usePage} from '@inertiajs/vue3';
import {ref} from "vue";

/*defineProps<{
    // mustVerifyEmail?: Boolean;
    // status?: String;
}>();*/

const user = usePage().props.auth.user;

const locale = user.language ?? 'en';

const notifications_options: {
    [k: string]: { text: string; key: string; desc: string }
} = locale == 'en' ? {
    'off': {text: 'off', key: 'off', desc: 'All notifications are turned off'},
    'monthly': {text: 'monthly', key: 'monthly', desc: 'Get notifications on 14th of every month'},
    'weekly': {text: 'weekly', key: 'weekly', desc: 'Get notifications every Monday'},
    'daily': {text: 'daily', key: 'daily', desc: 'Get notifications daily at 10am'},
} : {
    'off': {text: 'isključen', key: 'off', desc: 'Sve obavijesti su isključene'},
    'monthly': {text: 'mjesečno', key: 'monthly', desc: 'Primajte obavijesti 14. dana svakog mjeseca'},
    'weekly': {text: 'tjedno', key: 'weekly', desc: 'Primajte obavijesti svakog ponedjeljka'},
    'daily': {text: 'dnevno', key: 'daily', desc: 'Primajte obavijesti svakog dana u 10 sati ujutro'},
};

const form = useForm({
    notifications: user.notifications
    // name: user.name,
    // email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-white">{{ $t('notifications') }}</h2>

            <p class="mt-1 text-sm text-white">
                {{ $t('setup_notifications') }}
            </p>
        </header>

        <form @submit.prevent="form.post(route('set_notifications'))" class="mt-6 space-y-6">

            <div class="card flex justify-content-start" style="text-transform: capitalize;">
                <SelectButton v-model="form.notifications" :options="Object.values(notifications_options)"
                              option-label="text" option-value="key"
                />
            </div>

            <p class="text-white text-sm">{{ notifications_options[form.notifications as string]?.desc }}</p>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">{{ $t('save') }}</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-white">{{ $t('saved') }}.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
