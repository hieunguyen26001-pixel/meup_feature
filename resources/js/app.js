import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import '../css/app.css'

// Import pages
import Dashboard from './pages/Dashboard.vue'
import Cancellations from './pages/Cancellations.vue'
import Products from './pages/Products.vue'
import Orders from './pages/Orders.vue'
import Authorization from './pages/Authorization.vue'
import TestPage from './pages/TestPage.vue'

// Import components
import Layout from './components/Layout.vue'

// Create Pinia store
const pinia = createPinia()

// Create router
const router = createRouter({
  history: createWebHashHistory(),
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
      path: '/admin/dashboard',
      name: 'dashboard',
      component: Dashboard
    },
    {
      path: '/admin/authorization',
      name: 'authorization',
      component: Authorization
    },
    {
      path: '/admin/cancellations',
      name: 'cancellations',
      component: Cancellations
    },
    {
      path: '/admin/products',
      name: 'products',
      component: Products
    },
    {
      path: '/admin/orders',
      name: 'orders',
      component: Orders
    },
    // Redirect new routes to existing ones for now
    {
      path: '/admin/gmv-products',
      redirect: '/admin/products'
    },
    {
      path: '/admin/gmv-live',
      redirect: '/admin/orders'
    },
    {
      path: '/admin/video-management',
      redirect: '/admin/products'
    },
    {
      path: '/admin/booking-management',
      redirect: '/admin/orders'
    },
    {
      path: '/admin/staff-list',
      redirect: '/admin/dashboard'
    },
    {
      path: '/admin/logistics',
      redirect: '/admin/orders'
    },
    {
      path: '/admin/shop-analytics',
      redirect: '/admin/dashboard'
    }
  ]
})

// Create Vue app
const app = createApp(App)

app.use(pinia)
app.use(router)
app.component('Layout', Layout)

app.mount('#app')