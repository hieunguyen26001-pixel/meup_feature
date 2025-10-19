<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Quản Lý Sản Phẩm</h1>
      <p class="mt-2 text-gray-600">Quản lý danh sách sản phẩm từ TikTok Shop</p>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Danh Sách Sản Phẩm</h3>
            <p class="text-sm text-gray-500">
              Hiển thị {{ startIndex }} - {{ endIndex }} trong {{ totalProducts }} sản phẩm
              <span v-if="selectedShop" class="ml-2 text-blue-600">
                (Shop: {{ selectedShopName || selectedShop }})
              </span>
            </p>
          </div>
          <button @click="loadProducts" 
                  :disabled="loading"
                  class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg flex items-center">
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            {{ loading ? 'Đang tải...' : 'Tải lại' }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span class="ml-3 text-gray-600">Đang tải dữ liệu...</span>
      </div>

      <div v-else-if="products.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Không có sản phẩm nào</h3>
        <p class="mt-1 text-sm text-gray-500">Không có dữ liệu sản phẩm từ TikTok Shop.</p>
        <button @click="loadProducts" 
                class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
          Thử lại
        </button>
      </div>

      <div v-else class="overflow-x-auto relative">
        <!-- Loading Overlay -->
        <div v-if="tableLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
          <div class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">Đang sắp xếp...</span>
          </div>
        </div>
        
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Hình Ảnh
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sort('title')" class="flex items-center space-x-1 hover:text-gray-700 transition-colors">
                  <span>Tên Sản Phẩm</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('title')"></path>
                  </svg>
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sort('sku')" class="flex items-center space-x-1 hover:text-gray-700 transition-colors">
                  <span>SKU</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('sku')"></path>
                  </svg>
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sort('price')" class="flex items-center space-x-1 hover:text-gray-700 transition-colors">
                  <span>Giá</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('price')"></path>
                  </svg>
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sort('stock')" class="flex items-center space-x-1 hover:text-gray-700 transition-colors">
                  <span>Kho</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('stock')"></path>
                  </svg>
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sort('status')" class="flex items-center space-x-1 hover:text-gray-700 transition-colors">
                  <span>Trạng Thái</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSortIcon('status')"></path>
                  </svg>
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Thao Tác
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="product in paginatedProducts" :key="product.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex-shrink-0 h-12 w-12">
                  <img v-if="getProductImage(product)" 
                       class="h-12 w-12 rounded-lg object-cover" 
                       :src="getProductImage(product)" 
                       :alt="product.title">
                  <div v-else class="h-12 w-12 bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ getProductTitle(product) }}</div>
                <div v-if="product.id" class="text-xs text-gray-500">
                  ID: {{ product.id }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <code class="text-sm text-gray-900">{{ getProductSku(product) }}</code>
                <div v-if="product.skus && product.skus.length > 1" class="text-xs text-gray-500">
                  +{{ product.skus.length - 1 }} SKU khác
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900" v-html="formatPrice(product)"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getStockClass(product)">
                  {{ getTotalStock(product) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClass(getProductStatus(product))">
                  {{ getStatusText(getProductStatus(product)) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button @click="viewProduct(product.id)" 
                        class="text-primary-600 hover:text-primary-900">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <span class="text-sm text-gray-700">
              Trang {{ currentPage }} / {{ totalPages }}
            </span>
          </div>
          
          <div class="flex items-center space-x-2">
            <!-- Previous Button -->
            <button @click="goToPage(currentPage - 1)" 
                    :disabled="currentPage <= 1"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            
            <!-- Page Numbers -->
            <div class="flex items-center space-x-1">
              <button v-for="page in visiblePages" 
                      :key="page"
                      @click="goToPage(page)"
                      :class="[
                        'px-3 py-2 text-sm font-medium rounded-md',
                        page === currentPage 
                          ? 'bg-blue-500 text-white' 
                          : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                      ]">
                {{ page }}
              </button>
            </div>
            
            <!-- Next Button -->
            <button @click="goToPage(currentPage + 1)" 
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Products',
  setup() {
    const route = useRoute()
    const products = ref([])
    const loading = ref(false)
    const selectedShop = ref('')
    const selectedShopName = ref('')
    const currentPage = ref(1)
    const itemsPerPage = ref(5)
    const totalProducts = ref(0)
    
    // Sorting
    const sortField = ref('')
    const sortDirection = ref('asc') // 'asc' or 'desc'
    const tableLoading = ref(false)

    // Computed properties for pagination
    const totalPages = computed(() => Math.ceil(totalProducts.value / itemsPerPage.value))
    const paginatedProducts = computed(() => {
      let sortedProducts = [...products.value]
      
      // Apply sorting
      if (sortField.value) {
        sortedProducts.sort((a, b) => {
          let aValue = getFieldValue(a, sortField.value)
          let bValue = getFieldValue(b, sortField.value)
          
          // Handle different data types
          if (typeof aValue === 'string' && typeof bValue === 'string') {
            aValue = aValue.toLowerCase()
            bValue = bValue.toLowerCase()
          }
          
          if (aValue < bValue) return sortDirection.value === 'asc' ? -1 : 1
          if (aValue > bValue) return sortDirection.value === 'asc' ? 1 : -1
          return 0
        })
      }
      
      // Apply pagination
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return sortedProducts.slice(start, end)
    })
    const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1)
    const endIndex = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalProducts.value))
    
    // Computed property for visible page numbers
    const visiblePages = computed(() => {
      const pages = []
      const maxVisible = 5
      const start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
      const end = Math.min(totalPages.value, start + maxVisible - 1)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    })

    const loadProducts = async () => {
      loading.value = true
      try {
        // Get shop_id from query params or localStorage
        let shopId = route.query.shop_id
        if (!shopId) {
          const selectedShopData = localStorage.getItem('selectedShop')
          if (selectedShopData) {
            try {
              const shopData = JSON.parse(selectedShopData)
              shopId = shopData.shop_id
            } catch (e) {
              console.error('Error parsing selectedShop:', e)
              shopId = selectedShopData // fallback to raw value
            }
          }
        }
        
        
        // Store shop info
        selectedShop.value = shopId || ''
        
        const params = shopId ? { shop_id: shopId } : {}
        
        const response = await axios.get('/api/products', { params })
        
        if (response.data.success) {
          // Handle TikTok API response structure
          let productsData = null
          
          if (response.data.data?.data?.products) {
            // TikTok API response: { success: true, data: { data: { products: [...] } } }
            productsData = response.data.data.data.products
          } else if (response.data.data?.products) {
            // Fallback structure: { success: true, data: { products: [...] } }
            productsData = response.data.data.products
          } else if (Array.isArray(response.data.data)) {
            // Direct array: { success: true, data: [...] }
            productsData = response.data.data
          }
          
          if (productsData && Array.isArray(productsData)) {
            products.value = productsData
            totalProducts.value = productsData.length
            
            // Reset to first page when loading new data
            currentPage.value = 1
            
          } else {
            products.value = []
            totalProducts.value = 0
          }
        } else {
          products.value = []
          
          // Show user-friendly error message
          const errorMessage = response.data.error || 'Lỗi không xác định'
          alert(`Lỗi khi tải sản phẩm: ${errorMessage}`)
        }
      } catch (error) {
        console.error('Error loading products:', error)
        products.value = []
        
        // Show detailed error message
        let errorMessage = 'Lỗi khi tải dữ liệu sản phẩm'
        
        if (error.response?.data?.error) {
          errorMessage = error.response.data.error
        } else if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.message) {
          errorMessage = error.message
        }
        
        // Check if it's an authorization error
        if (error.response?.status === 401 || errorMessage.includes('token') || errorMessage.includes('ủy quyền')) {
          errorMessage = 'Token hết hạn hoặc chưa ủy quyền. Vui lòng ủy quyền lại TikTok Shop.'
        }
        
        alert(errorMessage)
      } finally {
        loading.value = false
      }
    }

    const getProductImage = (product) => {
      // TikTok products don't have images in the API response
      // Return placeholder or first available image
      return product.images?.[0]?.url_list?.[0] || null
    }

    const getProductSku = (product) => {
      // Get first SKU's seller_sku
      return product.skus?.[0]?.seller_sku || 'N/A'
    }

    const formatPrice = (product) => {
      if (!product.skus || product.skus.length === 0) return 'N/A'
      
      const prices = product.skus
        .map(sku => parseFloat(sku.price?.tax_exclusive_price || 0))
        .filter(price => price > 0)
      
      if (prices.length === 0) return 'N/A'
      
      const minPrice = Math.min(...prices)
      const maxPrice = Math.max(...prices)
      
      if (minPrice === maxPrice) {
        return `${minPrice.toLocaleString('vi-VN')}<sup>đ</sup>`
      } else {
        return `${minPrice.toLocaleString('vi-VN')}<sup>đ</sup> - ${maxPrice.toLocaleString('vi-VN')}<sup>đ</sup>`
      }
    }

    const getTotalStock = (product) => {
      if (!product.skus) return 0
      return product.skus.reduce((total, sku) => {
        return total + (sku.inventory?.[0]?.quantity || 0)
      }, 0)
    }

    const getProductTitle = (product) => {
      // TikTok products have title field
      return product.title || 'N/A'
    }

    const getProductStatus = (product) => {
      // TikTok products have status field (ACTIVATE, DRAFT, etc.)
      return product.status || 'N/A'
    }

    const getStockClass = (product) => {
      const stock = getTotalStock(product)
      if (stock > 0) return 'bg-green-100 text-green-800'
      return 'bg-red-100 text-red-800'
    }

    const getStatusClass = (status) => {
      switch (status) {
        case 'ACTIVATE': return 'bg-green-100 text-green-800'
        case 'DRAFT': return 'bg-yellow-100 text-yellow-800'
        case 'INACTIVE': return 'bg-gray-100 text-gray-800'
        case 'PENDING': return 'bg-blue-100 text-blue-800'
        case 'REJECTED': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
      }
    }

    const getStatusText = (status) => {
      switch (status) {
        case 'ACTIVATE': return 'Kích Hoạt'
        case 'DRAFT': return 'Bản Nháp'
        case 'INACTIVE': return 'Không Hoạt Động'
        case 'PENDING': return 'Chờ Duyệt'
        case 'REJECTED': return 'Từ Chối'
        default: return status || 'N/A'
      }
    }

    const viewProduct = (productId) => {
      alert(`Xem chi tiết sản phẩm: ${productId}`)
    }

    const loadShopInfo = async () => {
      try {
        const response = await axios.get('/api/shops')
        if (response.data.success && response.data.data) {
          const shops = response.data.data
          const currentShop = shops.find(shop => shop.shop_id === selectedShop.value)
          if (currentShop) {
            selectedShopName.value = currentShop.shop_name || currentShop.shop_id
          }
        }
      } catch (error) {
        console.error('Error loading shop info:', error)
      }
    }

    const authorizeTikTok = async () => {
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
      }
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const getFieldValue = (product, field) => {
      switch (field) {
        case 'title':
          return getProductTitle(product)
        case 'sku':
          return getProductSku(product)
        case 'price':
          return getPriceForSort(product)
        case 'stock':
          return getTotalStock(product)
        case 'status':
          return getProductStatus(product)
        default:
          return ''
      }
    }

    const getPriceForSort = (product) => {
      if (!product.skus || product.skus.length === 0) return 0
      const prices = product.skus
        .map(sku => parseFloat(sku.price?.tax_exclusive_price || 0))
        .filter(price => price > 0)
      return prices.length > 0 ? Math.min(...prices) : 0
    }

    const sort = (field) => {
      tableLoading.value = true
      
      // Simulate loading delay for better UX
      setTimeout(() => {
        if (sortField.value === field) {
          // Toggle direction if same field
          sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
        } else {
          // New field, start with ascending
          sortField.value = field
          sortDirection.value = 'asc'
        }
        
        // Reset to first page when sorting
        currentPage.value = 1
        
        tableLoading.value = false
      }, 300)
    }

    const getSortIcon = (field) => {
      if (sortField.value !== field) {
        return 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'
      }
      return sortDirection.value === 'asc' 
        ? 'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12'
        : 'M3 4h13M3 8h9m-9 4h6m4 0l4 4m0 0l4-4m-4 4v12'
    }

    onMounted(() => {
      loadProducts()
      loadShopInfo()
    })

    return {
      products,
      loading,
      selectedShop,
      selectedShopName,
      currentPage,
      itemsPerPage,
      totalProducts,
      totalPages,
      paginatedProducts,
      startIndex,
      endIndex,
      visiblePages,
      sortField,
      sortDirection,
      tableLoading,
      getProductImage,
      getProductSku,
      getProductTitle,
      getProductStatus,
      formatPrice,
      getTotalStock,
      getStockClass,
      getStatusClass,
      getStatusText,
      viewProduct,
      loadProducts,
      loadShopInfo,
      goToPage,
      sort,
      getSortIcon
    }
  }
}
</script>
