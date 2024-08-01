<script setup lang="ts">

import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import Dropdown from "@/Components/Dropdown.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import {Link} from "@inertiajs/vue3";
import {ref} from "vue";
import axios from "axios";

const menu = ref();

function toggle(event: any) {
    menu.value.toggle(event);
}

async function switch_locale(lang: string) {
    await axios.post(`/api/profile/set_locale/${lang}`);
    location.reload();
}
</script>

<template>
    <div class="max-w-full mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <Link :href="route('dashboard')">
                        <ApplicationLogo class=""/>
                    </Link>
                </div>

                <!-- Navigation Links -->
                <div v-if="false" class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        My Bills
                    </NavLink>
                </div>
            </div>


            <div class="flex items-center sm:ms-6 min-w-16">
                <div>
                    <!-- Language Dropdown -->
                    <Button type="button" icon="pi pi-globe" text @click="toggle" aria-haspopup="true" aria-controls="overlay_menu"/>
                    <Menu ref="menu" id="overlay_menu" :model="[{label: 'Hrvatski', command: ()=>switch_locale('hr')},
                                {label: 'English', command: ()=>switch_locale('en')}]" :popup="true"/>
                </div>

                <!-- Settings Dropdown -->
                <div v-if="!!$page.props.auth.user" class="ms-3 relative">
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <span class="inline-flex rounded-md">
                                <button
                                    type="button"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white hover:text-gray-200 focus:outline-none transition ease-in-out duration-150"
                                >
                                    {{ $page.props.auth.user.name }}

                                    <svg
                                        class="ms-2 -me-0.5 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </span>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')"> {{ $t('profile') }}</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                {{ $t('log_out') }}
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>

            <slot name="hamburger"/>
        </div>
    </div>
</template>

<style lang="scss">

</style>
