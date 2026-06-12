import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import './index.css'

store.initStore()

const app = createApp(App)
app.config.globalProperties.$store = store
app.use(router)
app.mount('#app')
