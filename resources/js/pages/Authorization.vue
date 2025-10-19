<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Ủy Quyền TikTok Shop</h1>
      <p class="mt-2 text-gray-600">Kết nối với TikTok Shop Partner Platform để quản lý sản phẩm</p>
    </div>

    <!-- Authorization Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Kết Nối TikTok Shop</h3>
        <p class="text-sm text-gray-500">Ủy quyền để truy cập dữ liệu shop và sản phẩm</p>
      </div>

      <div class="p-6">
        <!-- Authorization Steps -->
        <div class="mb-8">
          <h4 class="text-md font-medium text-gray-900 mb-4">Các bước ủy quyền:</h4>
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 font-semibold text-sm">1</span>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Nhấn nút "Ủy quyền TikTok Shop"</p>
                <p class="text-sm text-gray-500">Sẽ chuyển đến trang đăng nhập TikTok Shop Partner Platform</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 font-semibold text-sm">2</span>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Đăng nhập tài khoản TikTok Shop</p>
                <p class="text-sm text-gray-500">Sử dụng tài khoản seller của bạn</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 font-semibold text-sm">3</span>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Xác nhận ủy quyền</p>
                <p class="text-sm text-gray-500">Cho phép ứng dụng truy cập dữ liệu shop</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <span class="text-green-600 font-semibold text-sm">✓</span>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Hoàn thành</p>
                <p class="text-sm text-gray-500">Quay lại ứng dụng và bắt đầu quản lý sản phẩm</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Authorization Button -->
        <div class="text-center">
          <button @click="authorizeTikTok" 
                  :disabled="loading"
                  class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-8 py-3 rounded-lg flex items-center mx-auto">
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            {{ loading ? 'Đang tạo liên kết...' : 'Ủy quyền TikTok Shop' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Authorized Shops -->
    <div v-if="shops.length > 0" class="mt-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Shop Đã Ủy Quyền</h3>
          <p class="text-sm text-gray-500">Danh sách các shop đã kết nối</p>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên Shop</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khu Vực</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại Seller</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng Thái</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ủy Quyền</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao Tác</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="shop in shops" :key="shop.shop_id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ shop.shop_name || 'N/A' }}</div>
                  <div class="text-sm text-gray-500">ID: {{ shop.shop_id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ shop.region }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    {{ shop.seller_type }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="shop.status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ shop.status === 'ACTIVE' ? 'Hoạt động' : 'Không hoạt động' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ formatDate(shop.authorized_at) }}</div>
                  <div class="text-sm text-gray-500">{{ formatTime(shop.authorized_at) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex space-x-2">
                    <button @click="viewShopProducts(shop.shop_id)" 
                            class="text-blue-600 hover:text-blue-900">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                      </svg>
                    </button>
                    <button @click="refreshShopToken(shop.shop_id)" 
                            class="text-green-600 hover:text-green-900">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="mt-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có shop nào được ủy quyền</h3>
          <p class="mt-1 text-sm text-gray-500">Hãy ủy quyền TikTok Shop để bắt đầu quản lý sản phẩm.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Authorization',
  setup() {
    const router = useRouter()
    const shops = ref([])
    const loading = ref(false)

    const loadShops = async () => {
      try {
        const response = await axios.get('/api/shops')
        if (response.data.success) {
          shops.value = response.data.shops || []
        }
      } catch (error) {
        console.error('Error loading shops:', error)
      }
    }

    const authorizeTikTok = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/tiktok/authorize')
        if (response.data.success) {
          window.location.href = response.data.auth_url
        } else {
          alert('Lỗi khi tạo liên kết ủy quyền: ' + response.data.error)
        }
      } catch (error) {
        console.error('Authorization error:', error)
        alert('Lỗi khi ủy quyền TikTok Shop: ' + error.message)
      } finally {
        loading.value = false
      }
    }

    const viewShopProducts = (shopId) => {
      router.push(`/admin/products?shop_id=${shopId}`)
    }

    const refreshShopToken = async (shopId) => {
      try {
        // TODO: Implement token refresh
        alert(`Refresh token cho shop: ${shopId}`)
      } catch (error) {
        console.error('Token refresh error:', error)
        alert('Lỗi khi refresh token: ' + error.message)
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('vi-VN')
    }

    const formatTime = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleTimeString('vi-VN')
    }

    onMounted(() => {
      loadShops()
    })

    return {
      shops,
      loading,
      authorizeTikTok,
      viewShopProducts,
      refreshShopToken,
      formatDate,
      formatTime
    }
  }
}
</script>
