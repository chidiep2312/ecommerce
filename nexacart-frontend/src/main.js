import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/styles/tokens.css'
import './assets/styles/base.css'
import './assets/styles/utilities.css'


import '@/assets/styles/admin_variables.css'
import '@/assets/styles/admin_base.css'
import '@/assets/styles/admin.css'
const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')