import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'
import { createApp } from 'vue'

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'
import { createHead } from '@unhead/vue/client'
// Create vue app
const app = createApp(App)


// Register plugins
registerPlugins(app)
const head = createHead()
app.use(head)
// Mount vue app
app.mount('#app')
