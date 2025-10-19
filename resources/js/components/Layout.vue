<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 bg-white shadow-lg transform transition-all duration-300 ease-in-out"
         :class="[
           sidebarOpen ? 'w-64' : 'w-16 sidebar-collapsed'
         ]">
      <!-- Sidebar Header -->
      <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-slate-800 to-slate-700">
        <div class="flex items-center">
          <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
            <span class="text-slate-700 font-bold text-sm">TS</span>
          </div>
          <span v-show="sidebarOpen" class="ml-3 text-white font-semibold transition-opacity duration-300">TikTok Shop</span>
        </div>
        <button @click="toggleSidebar" class="text-white hover:text-gray-200 transition-colors">
          <svg v-if="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>

      <!-- Shop Selector -->
      <div v-if="shops.length > 0 && sidebarOpen" class="px-3 py-2">
        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Chọn Shop</label>
        <select v-model="selectedShop" @change="onShopChange" 
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option value="">-- Chọn shop --</option>
          <option v-for="shop in shops" :key="shop.shop_id" :value="shop.shop_id">
            {{ shop.shop_name || `Shop ${shop.shop_id}` }} ({{ shop.region }})
          </option>
        </select>
      </div>

      <!-- Shop Selector Collapsed -->
      <div v-if="shops.length > 0 && !sidebarOpen" class="px-2 py-2">
        <div class="w-full h-8 bg-gray-100 rounded-md flex items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors"
             :title="selectedShop ? shops.find(s => s.shop_id === selectedShop)?.shop_name || 'Chọn shop' : 'Chọn shop'">
          <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </div>
      </div>

      <!-- Sidebar Navigation -->
      <nav class="mt-6" :class="sidebarOpen ? 'px-3' : 'px-2'">
        <div class="space-y-6">
          <!-- Quảng cáo Section -->
          <div>
            <div v-if="sidebarOpen" class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
              </svg>
              Quảng cáo
            </div>
            <div class="mt-2 space-y-1">
              <router-link to="/admin/dashboard" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/dashboard') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Tổng quan' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Tổng quan</span>
              </router-link>
              
              <router-link to="/admin/gmv-products" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/gmv-products') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'GMV Max sản phẩm' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">GMV Max sản phẩm</span>
              </router-link>
              
              <router-link to="/admin/gmv-live" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/gmv-live') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'GMV Max Live' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">GMV Max Live</span>
              </router-link>
            </div>
          </div>

          <!-- Quản lí Section -->
          <div>
            <div v-if="sidebarOpen" class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              Quản lí
            </div>
            <div class="mt-2 space-y-1">
              <router-link to="/admin/video-management" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/video-management') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Quản lí video' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Quản lí video</span>
              </router-link>
              
              <router-link to="/admin/booking-management" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/booking-management') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Quản lí Booking' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Quản lí Booking</span>
              </router-link>
              
              <router-link to="/admin/staff-list" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/staff-list') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Danh sách nhân sự' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Danh sách nhân sự</span>
              </router-link>
            </div>
          </div>

          <!-- Cửa hàng Section -->
          <div>
            <div v-if="sidebarOpen" class="flex items-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
              Cửa hàng
            </div>
            <div class="mt-2 space-y-1">
              <router-link to="/admin/products" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/products') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Quản lí Sản phẩm' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Quản lí Sản phẩm</span>
              </router-link>
              
              <router-link to="/admin/orders" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/orders') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Đơn hàng & Fulfillment' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Đơn hàng & Fulfillment</span>
              </router-link>
              
              <router-link to="/admin/shop-analytics" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/shop-analytics') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Phân tích Shop' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Phân tích Shop</span>
              </router-link>
              
              <router-link to="/admin/cancellations" 
                           class="group flex items-center rounded-md transition-colors duration-200 relative"
                           :class="[
                             sidebarOpen ? 'px-3 py-2' : 'px-2 py-2 justify-center',
                             isActive('/admin/cancellations') ? 'bg-primary-100 text-primary-700' : 'text-gray-700 hover:bg-gray-100'
                           ]"
                           :title="!sidebarOpen ? 'Phân tích Hủy/Trả/Hoàn tiền' : ''">
                <svg class="h-5 w-5" :class="sidebarOpen ? 'mr-3' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span v-show="sidebarOpen" class="text-sm font-medium">Phân tích Hủy/Trả/Hoàn tiền</span>
              </router-link>
            </div>
          </div>
        </div>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'ml-64' : 'ml-16'">
      <!-- Top Navigation -->
      <header class="bg-white shadow-sm border-b border-gray-200 fixed top-0 right-0 z-40 transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'left-64' : 'left-16'">
        <div class="flex items-center justify-between h-16 px-6">
          <div class="flex items-center">
            <button @click="toggleSidebar" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
            <h1 class="ml-4 text-xl font-semibold text-gray-900">MEUP</h1>
          </div>
          
          <div class="flex items-center space-x-4">
            <!-- User Menu -->
            <div class="relative">
              <button @click="toggleUserMenu" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <img class="h-8 w-8 rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="Admin">
                <span class="ml-2 text-gray-700">Admin</span>
                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                <router-link to="/admin/authorization" 
                             class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                             @click="closeUserMenu">
                  <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                  </svg>
                  Ủy quyền TikTok Shop
                </router-link>
                
                <div class="border-t border-gray-100"></div>
                
                <button @click="logout" 
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                  <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                  </svg>
                  Đăng xuất
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 pt-16">
        <div class="container mx-auto px-6 py-8">
          <slot />
        </div>
      </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div v-if="sidebarOpen" @click="closeSidebar" class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"></div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Layout',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const sidebarOpen = ref(true)
    const userMenuOpen = ref(false)
    const shops = ref([])
    const selectedShop = ref('')

    const pageTitle = computed(() => {
      switch (route.name) {
        case 'dashboard':
          return 'Dashboard'
        case 'authorization':
          return 'Ủy Quyền TikTok Shop'
        case 'products':
          return 'Quản Lý Sản Phẩm'
        case 'cancellations':
          return 'Phân Tích Tỉ Lệ Hủy Đơn'
        default:
          return 'TikTok Shop Admin'
      }
    })

    const loadShops = async () => {
      try {
        const response = await axios.get('/api/shops')
        if (response.data.success) {
          shops.value = response.data.shops || []
          // Set default shop if only one
          if (shops.value.length === 1) {
            selectedShop.value = shops.value[0].shop_id
          }
        }
      } catch (error) {
        console.error('Error loading shops:', error)
      }
    }

    const onShopChange = () => {
      if (selectedShop.value) {
        // Store selected shop in localStorage
        localStorage.setItem('selectedShop', selectedShop.value)
        
        // If on products page, reload with new shop
        if (route.name === 'products') {
          router.push(`/admin/products?shop_id=${selectedShop.value}`)
        }
      }
    }

    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const closeSidebar = () => {
      sidebarOpen.value = false
    }

    const toggleUserMenu = () => {
      userMenuOpen.value = !userMenuOpen.value
    }

    const closeUserMenu = () => {
      userMenuOpen.value = false
    }

    const logout = () => {
      // Clear localStorage
      localStorage.removeItem('selectedShop')
      
      // Show logout message
      alert('Đã đăng xuất thành công!')
      
      // Close dropdown
      closeUserMenu()
      
      // You can add more logout logic here (e.g., clear tokens, redirect to login)
    }

    const isActive = (path) => {
      return route.path === path
    }

    onMounted(() => {
      loadShops()
      // Load selected shop from localStorage
      const savedShop = localStorage.getItem('selectedShop')
      if (savedShop) {
        selectedShop.value = savedShop
      }

      // Close user menu when clicking outside
      document.addEventListener('click', (event) => {
        const userMenu = event.target.closest('.relative')
        if (!userMenu && userMenuOpen.value) {
          closeUserMenu()
        }
      })
    })

    return {
      sidebarOpen,
      userMenuOpen,
      shops,
      selectedShop,
      pageTitle,
      toggleSidebar,
      closeSidebar,
      toggleUserMenu,
      closeUserMenu,
      logout,
      isActive,
      onShopChange
    }
  }
}
</script>

<style scoped>
/* Tooltip styles for collapsed sidebar only */
.sidebar-collapsed .group[title]:hover::after {
  content: attr(title);
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  margin-left: 8px;
  padding: 6px 12px;
  background-color: #1f2937;
  color: white;
  border-radius: 6px;
  font-size: 14px;
  white-space: nowrap;
  z-index: 1000;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.sidebar-collapsed .group[title]:hover::before {
  content: '';
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  margin-left: 2px;
  border: 6px solid transparent;
  border-right-color: #1f2937;
  z-index: 1000;
}
</style>
