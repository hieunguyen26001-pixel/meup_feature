<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Quản Lý Đơn Hàng</h1>
      <p class="mt-2 text-gray-600">Quản lý danh sách đơn hàng từ TikTok Shop</p>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Bộ Lọc & Tìm Kiếm</h3>
      </div>
      
      <div class="p-6">
        <!-- Date Range Picker -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-700 mb-4">Khoảng Thời Gian</h4>
          <div class="flex items-center space-x-4">
            <DateRangePicker v-model="dateRange" @update:modelValue="onDateRangeChange" />
          </div>
        </div>

        <!-- Sort Options -->
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <label class="text-sm font-medium text-gray-600">Sắp xếp theo:</label>
              <select
                v-model="sortField"
                @change="loadOrders"
                class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="create_time">Ngày tạo</option>
                <option value="update_time">Ngày cập nhật</option>
                <option value="id">ID đơn hàng</option>
                <option value="status">Trạng thái</option>
              </select>
              <button
                @click="toggleSortDirection"
                class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg v-if="sortDirection === 'asc'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                </svg>
              </button>
            </div>

            <div class="flex items-center space-x-2">
              <label class="text-sm font-medium text-gray-600">Trạng thái:</label>
              <select
                v-model="selectedStatus"
                @change="loadOrders"
                class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="ALL">Tất cả</option>
                <option value="UNPAID">Chưa thanh toán</option>
                <option value="AWAITING_SHIPMENT">Chờ vận chuyển</option>
                <option value="IN_TRANSIT">Đang vận chuyển</option>
                <option value="DELIVERED">Đã giao</option>
                <option value="COMPLETED">Hoàn thành</option>
                <option value="CANCELLED">Đã hủy</option>
              </select>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <button
              @click="loadOrders"
              :disabled="loading"
              class="px-4 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-md text-sm font-medium transition-colors flex items-center"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ loading ? 'Đang tải...' : 'Làm mới' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Dashboard -->
    <OrderKPIDashboard :orders="orders" />

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Danh Sách Đơn Hàng</h3>
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-600">Hiển thị:</span>
              <select
                v-model="itemsPerPage"
                @change="loadOrders"
                class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
              </select>
              <span class="text-sm text-gray-600">đơn hàng</span>
            </div>
            <div class="text-sm text-gray-600">
              Tổng: {{ totalOrders }} đơn hàng
            </div>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID Đơn Hàng
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Trạng Thái
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tổng Tiền
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ngày Tạo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Người Mua
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Thao Tác
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="tableLoading" v-for="n in 5" :key="n">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="animate-pulse bg-gray-200 h-4 rounded w-32"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="animate-pulse bg-gray-200 h-4 rounded w-20"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="animate-pulse bg-gray-200 h-4 rounded w-24"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="animate-pulse bg-gray-200 h-4 rounded w-28"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="animate-pulse bg-gray-200 h-4 rounded w-24"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="animate-pulse bg-gray-200 h-8 rounded w-20"></div>
              </td>
            </tr>
            <tr v-else-if="orders.length === 0">
              <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">Không có đơn hàng nào</h3>
                  <p class="text-gray-500">Chưa có đơn hàng nào trong khoảng thời gian đã chọn.</p>
                </div>
              </td>
            </tr>
            <tr v-else v-for="order in paginatedOrders" :key="order.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ order.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(order.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getStatusText(order.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span v-html="formatPrice(order.payment?.total_amount || 0, order.payment?.currency || 'USD')"></span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.create_time) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ order.recipient_address?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="viewOrder(order)"
                  class="text-blue-600 hover:text-blue-900 transition-colors"
                >
                  Xem chi tiết
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} đến {{ Math.min(currentPage * itemsPerPage, totalOrders) }} 
            trong tổng số {{ totalOrders }} đơn hàng
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Trước
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                page === currentPage
                  ? 'bg-blue-500 text-white'
                  : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Sau
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Details Modal -->
    <div v-if="showOrderModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeOrderModal"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Chi Tiết Đơn Hàng</h3>
              <button @click="closeOrderModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <div v-if="orderLoading" class="flex items-center justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
              <span class="ml-2 text-gray-600">Đang tải chi tiết đơn hàng...</span>
            </div>
            
            <div v-else-if="!selectedOrder" class="text-center py-8">
              <p class="text-gray-500">Không thể tải chi tiết đơn hàng</p>
            </div>
            
            <div v-else class="space-y-6">
              <!-- Order Info -->
              <div class="grid grid-cols-2 gap-6">
                <div>
                  <h4 class="text-sm font-medium text-gray-700 mb-2">Thông tin đơn hàng</h4>
                  <div class="space-y-2 text-sm">
                    <div><span class="font-medium">ID:</span> {{ selectedOrder.id }}</div>
                    <div><span class="font-medium">Trạng thái:</span> 
                      <span :class="getStatusBadgeClass(selectedOrder.status)" class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ getStatusText(selectedOrder.status) }}
                      </span>
                    </div>
                    <div><span class="font-medium">Ngày tạo:</span> {{ formatDate(selectedOrder.create_time) }}</div>
                    <div><span class="font-medium">Ngày cập nhật:</span> {{ formatDate(selectedOrder.update_time) }}</div>
                  </div>
                </div>
                
                <div>
                  <h4 class="text-sm font-medium text-gray-700 mb-2">Thông tin thanh toán</h4>
                  <div class="space-y-2 text-sm">
                    <div><span class="font-medium">Tổng tiền:</span> 
                      <span v-html="formatPrice(selectedOrder.payment?.total_amount || 0, selectedOrder.payment?.currency || 'USD')"></span>
                    </div>
                    <div><span class="font-medium">Phí vận chuyển:</span> 
                      <span v-html="formatPrice(selectedOrder.payment?.shipping_fee || 0, selectedOrder.payment?.currency || 'USD')"></span>
                    </div>
                    <div><span class="font-medium">Giảm giá:</span> 
                      <span v-html="formatPrice(selectedOrder.payment?.seller_discount || 0, selectedOrder.payment?.currency || 'USD')"></span>
                    </div>
                    <div><span class="font-medium">Phương thức:</span> {{ selectedOrder.payment_method_name || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              
              <!-- Shipping Info -->
              <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Thông tin giao hàng</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="space-y-2 text-sm">
                    <div><span class="font-medium">Tên:</span> {{ selectedOrder.recipient_address?.name || 'N/A' }}</div>
                    <div><span class="font-medium">Số điện thoại:</span> {{ selectedOrder.recipient_address?.phone_number || 'N/A' }}</div>
                    <div><span class="font-medium">Địa chỉ:</span> {{ selectedOrder.recipient_address?.full_address || 'N/A' }}</div>
                    <div><span class="font-medium">Mã bưu điện:</span> {{ selectedOrder.recipient_address?.postal_code || 'N/A' }}</div>
                    <div><span class="font-medium">Vùng:</span> {{ selectedOrder.recipient_address?.region_code || 'N/A' }}</div>
                  </div>
                </div>
              </div>
              
              <!-- Order Items -->
              <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Sản phẩm trong đơn hàng</h4>
                <div class="space-y-4">
                  <div v-for="item in selectedOrder.line_items || []" :key="item.id" class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start space-x-4">
                      <img v-if="item.sku_image" :src="item.sku_image" :alt="item.product_name" class="w-16 h-16 object-cover rounded-lg">
                      <div class="flex-1">
                        <h5 class="font-medium text-gray-900">{{ item.product_name }}</h5>
                        <p class="text-sm text-gray-600">SKU: {{ item.seller_sku }}</p>
                        <p class="text-sm text-gray-600">Số lượng: {{ item.sku_count || 1 }}</p>
                        <p class="text-sm text-gray-600">Giá: 
                          <span v-html="formatPrice(item.sale_price || 0, item.currency || 'USD')"></span>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import OrderKPIDashboard from '../components/OrderKPIDashboard.vue'
import DateRangePicker from '../components/DateRangePicker.vue'

// Reactive data
const orders = ref([])
const loading = ref(false)
const tableLoading = ref(false)
const selectedShop = ref('')
const selectedShopName = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(5)
const totalOrders = ref(0)
const sortField = ref('create_time')
const sortDirection = ref('desc')

// Date range
const dateRange = ref({ start: null, end: null })

// Status filter
const selectedStatus = ref('ALL')

// Order details modal
const showOrderModal = ref(false)
const selectedOrder = ref(null)
const orderLoading = ref(false)

// Computed properties
const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return orders.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(totalOrders.value / itemsPerPage.value)
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Methods
const loadOrders = async () => {
  try {
    tableLoading.value = true
    
    // Get selected shop from localStorage
    const shopData = localStorage.getItem('selectedShop')
    if (!shopData) {
      console.error('Không có shop được chọn')
      return
    }
    
    const shop = JSON.parse(shopData)
    selectedShop.value = shop.shop_id
    selectedShopName.value = shop.shop_name
    
    const params = {
      shop_id: selectedShop.value,
      page: currentPage.value,
      per_page: itemsPerPage.value,
      sort_field: sortField.value,
      sort_direction: sortDirection.value
    }
    
    // Add date range if selected
    if (dateRange.value.start && dateRange.value.end) {
      params.start_date = formatDateForAPI(dateRange.value.start)
      params.end_date = formatDateForAPI(dateRange.value.end)
    }
    
    // Add status filter if not ALL
    if (selectedStatus.value !== 'ALL') {
      params.status = selectedStatus.value
    }
    
    const response = await axios.get('/api/orders', { params })
    
    if (response.data.success) {
      orders.value = response.data.data.orders || []
      totalOrders.value = response.data.data.total_count || 0
    } else {
      console.error('Lỗi khi tải đơn hàng:', response.data.error)
    }
  } catch (error) {
    console.error('Lỗi khi tải đơn hàng:', error)
  } finally {
    tableLoading.value = false
  }
}

const onDateRangeChange = (newDateRange) => {
  dateRange.value = newDateRange
  currentPage.value = 1
  loadOrders()
}

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  loadOrders()
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    loadOrders()
  }
}

const viewOrder = async (order) => {
  try {
    showOrderModal.value = true
    orderLoading.value = true
    selectedOrder.value = null
    
    const response = await axios.get(`/api/orders/details/${order.id}`)
    
    if (response.data.success && response.data.data?.data?.orders?.length > 0) {
      selectedOrder.value = response.data.data.data.orders[0]
    } else {
      console.error('Không thể tải chi tiết đơn hàng')
    }
  } catch (error) {
    console.error('Lỗi khi tải chi tiết đơn hàng:', error)
  } finally {
    orderLoading.value = false
  }
}

const closeOrderModal = () => {
  showOrderModal.value = false
  selectedOrder.value = null
  orderLoading.value = false
}

const getStatusText = (status) => {
  const statusMap = {
    'UNPAID': 'Chưa thanh toán',
    'AWAITING_SHIPMENT': 'Chờ vận chuyển',
    'AWAITING_COLLECTION': 'Chờ lấy hàng',
    'PARTIALLY_SHIPPING': 'Vận chuyển một phần',
    'IN_TRANSIT': 'Đang vận chuyển',
    'DELIVERED': 'Đã giao',
    'COMPLETED': 'Hoàn thành',
    'CANCELLED': 'Đã hủy',
    'ON_HOLD': 'Tạm giữ'
  }
  return statusMap[status] || status
}

const getStatusBadgeClass = (status) => {
  const classMap = {
    'UNPAID': 'bg-yellow-100 text-yellow-800',
    'AWAITING_SHIPMENT': 'bg-blue-100 text-blue-800',
    'AWAITING_COLLECTION': 'bg-purple-100 text-purple-800',
    'PARTIALLY_SHIPPING': 'bg-indigo-100 text-indigo-800',
    'IN_TRANSIT': 'bg-orange-100 text-orange-800',
    'DELIVERED': 'bg-green-100 text-green-800',
    'COMPLETED': 'bg-green-100 text-green-800',
    'CANCELLED': 'bg-red-100 text-red-800',
    'ON_HOLD': 'bg-gray-100 text-gray-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const formatPrice = (amount, currency = 'USD') => {
  const numAmount = parseFloat(amount) || 0
  const formatted = new Intl.NumberFormat('vi-VN').format(numAmount)
  return `${formatted}<sup>đ</sup>`
}

const formatDate = (timestamp) => {
  if (!timestamp) return 'N/A'
  return new Date(timestamp * 1000).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateForAPI = (date) => {
  if (!date) return null
  return date.toISOString().split('T')[0]
}

// Lifecycle
onMounted(() => {
  // Set default date range to last 7 days
  const today = new Date()
  const lastWeek = new Date(today)
  lastWeek.setDate(today.getDate() - 7)
  
  dateRange.value = {
    start: lastWeek,
    end: today
  }
  
  loadOrders()
})

// Watch for shop changes
watch(() => localStorage.getItem('selectedShop'), () => {
  loadOrders()
})
</script>


