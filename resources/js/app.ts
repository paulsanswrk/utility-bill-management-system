import './bootstrap';
import '../css/app.css';

import {createApp, DefineComponent, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
// import {getActiveLanguage, i18nVue} from 'laravel-vue-i18n';
import * as en_primevue_locale from '@/lang/primevue-en.json'
import * as hr_primevue_locale from '@/lang/primevue-hr.json'

import * as en_messages from '@/lang/messages-en.json'
import * as hr_messages from '@/lang/messages-hr.json'


import 'primeicons/primeicons.css'
import 'primeflex/primeflex.css'

import '@/../css/theme.scss'
import axios from "axios";

import { VueReCaptcha, useReCaptcha } from 'vue-recaptcha-v3';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

import {createI18n} from 'vue-i18n'

const language = document.documentElement.lang;
// console.log({language})


const i18n = createI18n({
    legacy: false,
    locale: language,
    fallbackLng: 'en',
    messages: {
        en: {
            ...en_messages
        },
        hr: {
            ...hr_messages
        }
    }
});

// console.log('hr_locale', hr_locale.hr)
// console.log('en_locale', en_locale.en)

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        const captchaKey = props.initialPage.props.recaptcha_site_key;
        createApp({render: () => h(App, props)})
            .use(plugin)
            .use(VueReCaptcha, { siteKey: captchaKey } )
            .use(i18n)
            .use(ZiggyVue)
            .use(PrimeVue, {locale: language == 'hr' ? hr_primevue_locale.hr : en_primevue_locale.en})
            // .use(PrimeVue)
            .use(ToastService)
            .use(ConfirmationService)

            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
}).then(r => {
});



