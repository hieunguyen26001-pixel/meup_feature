import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import '../css/app.css'

// Import pages
import Dashboard from './pages/Dashboard.vue'
import Authorization from './pages/Authorization.vue'
import TestPage from './pages/TestPage.vue'
import ApiExplorerPage from './pages/ApiExplorerPage.vue'

// Import components
import Layout from './components/Layout.vue'

// Create Pinia store
const pinia = createPinia()

// Create router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/admin/dashboard'
    },
    {
      path: '/test',
      name: 'test',
      component: TestPage
    },
    {
      path: '/api-explorer',
      name: 'api-explorer',
      component: ApiExplorerPage
    },
    {
      path: '/admin/dashboard',
      name: 'dashboard',
      component: Dashboard
    },
    {
      path: '/admin/authorization',
      name: 'authorization',
      component: Authorization
    },
  ]
})

// Create Vue app
const app = createApp(App)

app.use(pinia)
app.use(router)
app.component('Layout', Layout)

app.mount('#app')