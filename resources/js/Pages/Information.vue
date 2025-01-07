<script setup lang="ts">

import {Head} from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";

defineProps<{
    title?: string;
    text?: string;
    status?: string;
}>();

</script>

<template>
    <GuestLayout>
        <Head :title="$t(title || 'Information')" />

        <div class="mb-4 text-sm text-white" v-html="text">
        </div>

    </GuestLayout>
</template>

<style scoped lang="scss">

</style>
