<script setup lang="ts">
import {ref} from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import {Link} from '@inertiajs/vue3';
import axios from "axios";
import PrimaryNavigationMenu from "@/Layouts/PrimaryNavigationMenu.vue";

const showingNavigationDropdown = ref(false);

</script>

<template>
    <div>
        <div class="min-h-screen">
            <nav class=" border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <PrimaryNavigationMenu />
            </nav>

            <!-- Page Content -->
            <div class="flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
                <main class="w-full sm:max-w-md mt-6 px-6 py-4 shadow-md overflow-hidden sm:rounded-lg"
                      style="border: solid 1px var(--surface-border)">
                    <slot/>
                </main>
            </div>
        </div>
    </div>

</template>

<style lang="scss">
.layout-wrapper, body {
    background-color: var(--surface-ground);

    nav {
        background-color: var(--surface-card);
        border-color: var(--surface-border) !important;
        border-bottom-width: 2px;
    }
}
//--surface-border
</style>
