<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Phân Tích Tỉ Lệ Hủy Đơn</h1>
      <p class="mt-2 text-gray-600">Theo dõi và phân tích dữ liệu hủy đơn hàng từ TikTok Shop</p>
      <div v-if="currentShop" class="mt-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
          {{ currentShop.shop_name || `Shop ${currentShop.shop_id}` }} ({{ currentShop.region }})
        </span>
      </div>
    </div>

    <!-- Empty State - No Shop Selected -->
    <div v-if="!currentShop" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa chọn shop</h3>
      <p class="mt-1 text-sm text-gray-500">Vui lòng chọn shop từ sidebar để xem dữ liệu hủy đơn.</p>
    </div>

    <!-- Analytics Cards -->
    <div v-else-if="analytics && currentShop" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Tổng Hủy Đơn</p>
            <p class="text-2xl font-semibold text-gray-900">{{ totalCancellations.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Tỉ Lệ Hủy</p>
            <p class="text-2xl font-semibold text-gray-900">{{ cancellationRate }}%</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Chờ Xử Lý</p>
            <p class="text-2xl font-semibold text-gray-900">{{ pendingCancellations }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Đã Duyệt</p>
            <p class="text-2xl font-semibold text-gray-900">{{ approvedCancellations }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Date Filters -->
    <div v-if="selectedShop" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Lọc Theo Thời Gian</h3>
        <div class="flex space-x-2">
          <button @click="setDateRange('today')" 
                  class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            Hôm nay
          </button>
          <button @click="setDateRange('yesterday')" 
                  class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            Hôm qua
          </button>
          <button @click="setDateRange('last7days')" 
                  class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            7 ngày qua
          </button>
          <button @click="clearDateRange" 
                  class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded-md hover:bg-red-200">
            Xóa
          </button>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
          <input v-model="dateFrom" type="date" 
                 class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
          <input v-model="dateTo" type="date" 
                 class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
        </div>
      </div>
    </div>

    <!-- Cancellations Table -->
    <div v-else-if="currentShop" class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Danh Sách Hủy Đơn</h3>
        <p class="text-sm text-gray-500">Hiển thị {{ cancellations.length }} / {{ totalCancellations }} hủy đơn</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span class="ml-3 text-gray-600">Đang tải dữ liệu...</span>
      </div>

      <div v-else-if="cancellations.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Không có hủy đơn nào</h3>
        <p class="mt-1 text-sm text-gray-500">Không có dữ liệu hủy đơn để hiển thị.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã Hủy Đơn</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại Hủy</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng Thái</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lý Do Hủy</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số Tiền Hoàn</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản Phẩm</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời Gian Tạo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao Tác</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="cancellation in cancellations" :key="cancellation.cancel_id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <code class="text-sm text-gray-900">{{ cancellation.cancel_id || 'N/A' }}</code>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getCancelTypeClass(cancellation.cancel_type)">
                  {{ getCancelTypeText(cancellation.cancel_type) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClass(cancellation.cancel_status)">
                  {{ getStatusText(cancellation.cancel_status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 max-w-xs truncate">
                  {{ cancellation.cancel_reason_text || 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ formatRefundAmount(cancellation.refund_amount) }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div v-if="getProductImage(cancellation)" class="flex-shrink-0 h-10 w-10">
                    <img class="h-10 w-10 rounded-lg object-cover" 
                         :src="getProductImage(cancellation)" 
                         :alt="getProductName(cancellation)">
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ getProductName(cancellation) }}
                    </div>
                    <div class="text-sm text-gray-500">
                      SKU: {{ getProductSku(cancellation) }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(cancellation.create_time) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex space-x-2">
                  <button @click="viewCancellation(cancellation.cancel_id)" 
                          class="text-primary-600 hover:text-primary-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </button>
                  <button @click="processCancellation(cancellation.cancel_id)" 
                          class="text-yellow-600 hover:text-yellow-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Load More Button -->
      <div v-if="nextPageToken" class="px-6 py-4 border-t border-gray-200 text-center">
        <button @click="loadMore" 
                :disabled="loadingMore"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
          <svg v-if="loadingMore" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="-ml-1 mr-3 h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          {{ loadingMore ? 'Đang tải...' : 'Tải Thêm' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Cancellations',
  setup() {
    const route = useRoute()
    const cancellations = ref([])
    const analytics = ref({})
    const totalCancellations = ref(0)
    const nextPageToken = ref(null)
    const loading = ref(false)
    const loadingMore = ref(false)
    const dateFrom = ref('')
    const dateTo = ref('')
    const currentShop = ref(null)

    // Computed properties
    const cancellationRate = computed(() => {
      if (totalCancellations.value === 0) return 0
      const totalOrders = Math.round(totalCancellations.value / 0.035) // Estimate based on 3.5% cancellation rate
      return ((totalCancellations.value / totalOrders) * 100).toFixed(1)
    })

    const pendingCancellations = computed(() => {
      return analytics.value.by_status?.CANCELLATION_REQUEST_PENDING || 0
    })

    const approvedCancellations = computed(() => {
      return analytics.value.by_status?.CANCELLATION_APPROVED || 0
    })

    // Methods
    const getCurrentShop = () => {
      // Get shop from localStorage (set by Layout component)
      const selectedShopId = localStorage.getItem('selectedShop')
      if (selectedShopId) {
        // Find shop details from shops API
        loadCurrentShop(selectedShopId)
      }
    }

    const loadCurrentShop = async (shopId) => {
      try {
        const response = await axios.get('/api/shops')
        const shops = response.data.shops || []
        currentShop.value = shops.find(shop => shop.shop_id === shopId) || null
      } catch (error) {
        console.error('Error loading current shop:', error)
      }
    }

    const loadCancellations = async (pageToken = null) => {
      if (!currentShop.value) return

      loading.value = true
      try {
        const params = {
          shop_id: currentShop.value.shop_id,
          page_size: 20
        }
        
        if (pageToken) {
          params.page_token = pageToken
        }
        
        if (dateFrom.value) {
          params.update_time_ge = new Date(dateFrom.value).getTime() / 1000
        }
        
        if (dateTo.value) {
          params.update_time_lt = new Date(dateTo.value).getTime() / 1000
        }

        const response = await axios.get('/api/cancellations', { params })
        const data = response.data

        if (pageToken) {
          cancellations.value.push(...(data.cancellations || []))
        } else {
          cancellations.value = data.cancellations || []
          analytics.value = data.analytics || {}
          totalCancellations.value = data.total_cancellations || 0
        }

        nextPageToken.value = data.next_page_token
      } catch (error) {
        console.error('Error loading cancellations:', error)
      } finally {
        loading.value = false
        loadingMore.value = false
      }
    }

    const loadMore = () => {
      if (nextPageToken.value) {
        loadingMore.value = true
        loadCancellations(nextPageToken.value)
      }
    }

    const setDateRange = (range) => {
      const today = new Date()
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)
      const lastWeek = new Date(today)
      lastWeek.setDate(lastWeek.getDate() - 7)
      const lastMonth = new Date(today)
      lastMonth.setDate(lastMonth.getDate() - 30)

      switch (range) {
        case 'today':
          dateFrom.value = today.toISOString().split('T')[0]
          dateTo.value = today.toISOString().split('T')[0]
          break
        case 'yesterday':
          dateFrom.value = yesterday.toISOString().split('T')[0]
          dateTo.value = yesterday.toISOString().split('T')[0]
          break
        case 'last7days':
          dateFrom.value = lastWeek.toISOString().split('T')[0]
          dateTo.value = today.toISOString().split('T')[0]
          break
        case 'last30days':
          dateFrom.value = lastMonth.toISOString().split('T')[0]
          dateTo.value = today.toISOString().split('T')[0]
          break
      }
      
      loadCancellations()
    }

    const clearDateRange = () => {
      dateFrom.value = ''
      dateTo.value = ''
      loadCancellations()
    }

    const getStatusClass = (status) => {
      switch (status) {
        case 'CANCELLATION_REQUEST_PENDING': return 'bg-yellow-100 text-yellow-800'
        case 'CANCELLATION_APPROVED': return 'bg-green-100 text-green-800'
        case 'CANCELLATION_REJECTED': return 'bg-red-100 text-red-800'
        case 'CANCELLATION_REQUEST_COMPLETE': return 'bg-blue-100 text-blue-800'
        default: return 'bg-gray-100 text-gray-800'
      }
    }

    const getStatusText = (status) => {
      switch (status) {
        case 'CANCELLATION_REQUEST_PENDING': return 'Chờ Xử Lý'
        case 'CANCELLATION_APPROVED': return 'Đã Duyệt'
        case 'CANCELLATION_REJECTED': return 'Từ Chối'
        case 'CANCELLATION_REQUEST_COMPLETE': return 'Hoàn Thành'
        default: return status || 'N/A'
      }
    }

    const getCancelTypeClass = (type) => {
      switch (type) {
        case 'BUYER_CANCEL': return 'bg-yellow-100 text-yellow-800'
        case 'CANCEL': return 'bg-red-100 text-red-800'
        case 'SELLER_CANCEL': return 'bg-blue-100 text-blue-800'
        default: return 'bg-gray-100 text-gray-800'
      }
    }

    const getCancelTypeText = (type) => {
      switch (type) {
        case 'BUYER_CANCEL': return 'Khách Hủy'
        case 'CANCEL': return 'Hệ Thống Hủy'
        case 'SELLER_CANCEL': return 'Người Bán Hủy'
        default: return type || 'N/A'
      }
    }

    const formatRefundAmount = (refundAmount) => {
      if (!refundAmount) return 'N/A'
      const currency = refundAmount.currency || 'VND'
      const amount = refundAmount.refund_total || 0
      return `${currency} ${parseFloat(amount).toLocaleString('vi-VN')}`
    }

    const getProductImage = (cancellation) => {
      return cancellation.cancel_line_items?.[0]?.product_image?.url
    }

    const getProductName = (cancellation) => {
      return cancellation.cancel_line_items?.[0]?.product_name || 'N/A'
    }

    const getProductSku = (cancellation) => {
      return cancellation.cancel_line_items?.[0]?.seller_sku || 'N/A'
    }

    const formatDate = (timestamp) => {
      if (!timestamp) return 'N/A'
      return new Date(timestamp * 1000).toLocaleString('vi-VN')
    }

    const viewCancellation = (cancelId) => {
      alert(`Xem chi tiết hủy đơn: ${cancelId}`)
    }

    const processCancellation = (cancelId) => {
      alert(`Xử lý hủy đơn: ${cancelId}`)
    }

    // Watch for shop changes in localStorage
    watch(() => localStorage.getItem('selectedShop'), (newShopId) => {
      if (newShopId) {
        loadCurrentShop(newShopId)
      }
    })

    onMounted(() => {
      getCurrentShop()
    })

    return {
      currentShop,
      cancellations,
      analytics,
      totalCancellations,
      nextPageToken,
      loading,
      loadingMore,
      dateFrom,
      dateTo,
      cancellationRate,
      pendingCancellations,
      approvedCancellations,
      loadCancellations,
      loadMore,
      setDateRange,
      clearDateRange,
      getStatusClass,
      getStatusText,
      getCancelTypeClass,
      getCancelTypeText,
      formatRefundAmount,
      getProductImage,
      getProductName,
      getProductSku,
      formatDate,
      viewCancellation,
      processCancellation
    }
  }
}
</script>
