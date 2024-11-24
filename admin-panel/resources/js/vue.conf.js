import {createApp} from 'vue/dist/vue.esm-bundler';


// import {createVuetify} from 'vuetify'
// import {fa} from 'vuetify/locale'
//
// const vuetify = createVuetify({
//
//     locale: {
//         locale: 'fa',
//         fallback: 'fa',
//         messages: {fa},
//         rtl: {fa: true},
//     },
// })

// import DynamicSelect from './components/miscellaneous/DynamicSelect.vue'
import NoteModal from './components/miscellaneous/NoteModal.vue'
import Filepond from "./components/libs/Filepond.vue";


const app = createApp({});

app.use()
    .component('filepond', Filepond)
    .component("note-modal", NoteModal)

const mountedApp = app.mount("#app");
