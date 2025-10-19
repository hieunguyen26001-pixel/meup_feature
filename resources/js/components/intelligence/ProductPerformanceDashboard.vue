<template>
  <div class="space-y-6">
    <!-- Top Performers -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900">Sản Phẩm Bán Chạy Nhất</h3>
        </div>
      </div>
      <div class="p-6">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        <div v-else-if="topPerformers.length === 0" class="text-center py-8">
          <p class="text-gray-500">Chưa có dữ liệu sản phẩm bán chạy</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="(product, index) in topPerformers.slice(0, 5)"
            :key="product.product_id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <span class="text-lg font-bold text-blue-600">#{{ index + 1 }}</span>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">{{ product.product_name }}</h4>
                <p class="text-xs text-gray-500">ID: {{ product.product_id }}</p>
              </div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900">
                {{ formatCurrency(product.total_revenue) }}
              </div>
              <div class="text-sm text-gray-500">
                {{ product.total_quantity }} sản phẩm
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Low Performers -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900">Sản Phẩm Cần Chú Ý</h3>
        </div>
      </div>
      <div class="p-6">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        <div v-else-if="lowPerformers.length === 0" class="text-center py-8">
          <p class="text-gray-500">Tất cả sản phẩm đều có đơn hàng</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="product in lowPerformers.slice(0, 5)"
            :key="product.id"
            class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200"
          >
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">{{ product.title }}</h4>
                <p class="text-xs text-gray-500">ID: {{ product.id }}</p>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm text-red-600 font-medium">
                Chưa có đơn hàng
              </div>
              <div class="text-xs text-gray-500">
                {{ formatPrice(product) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SKU Analysis -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="w-6 h-6 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900">Phân Tích SKU</h3>
        </div>
      </div>
      <div class="p-6">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        <div v-else-if="skuAnalysis.length === 0" class="text-center py-8">
          <p class="text-gray-500">Chưa có dữ liệu SKU</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản Phẩm</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn Hàng</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số Lượng</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doanh Thu</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giá TB</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="sku in skuAnalysis.slice(0, 10)" :key="sku.sku" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ sku.sku }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ sku.product_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ sku.total_orders }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ sku.total_quantity }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ formatCurrency(sku.total_revenue) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(sku.avg_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Price Analysis -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="w-6 h-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900">Phân Tích Giá</h3>
        </div>
      </div>
      <div class="p-6">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(priceAnalysis.averageOrderValue) }}</div>
            <div class="text-sm text-blue-800">Giá trị đơn hàng TB</div>
          </div>
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ priceAnalysis.discountRate.toFixed(1) }}%</div>
            <div class="text-sm text-green-800">Tỷ lệ giảm giá</div>
          </div>
          <div class="text-center p-4 bg-yellow-50 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ priceAnalysis.priceRanges.under_100k }}</div>
            <div class="text-sm text-yellow-800">Dưới 100K</div>
          </div>
          <div class="text-center p-4 bg-purple-50 rounded-lg">
            <div class="text-2xl font-bold text-purple-600">{{ priceAnalysis.priceRanges['100k_500k'] }}</div>
            <div class="text-sm text-purple-800">100K-500K</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Health Scores -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900">Điểm Chất Lượng Sản Phẩm</h3>
        </div>
      </div>
      <div class="p-6">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        <div v-else-if="healthScores.length === 0" class="text-center py-8">
          <p class="text-gray-500">Chưa có dữ liệu điểm chất lượng</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="product in healthScores.slice(0, 10)"
            :key="product.product_id"
            class="flex items-center justify-between p-4 rounded-lg"
            :class="getHealthScoreClass(product.health_score)"
          >
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold"
                     :class="getHealthScoreBadgeClass(product.health_score)">
                  {{ product.health_score }}
                </div>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">{{ product.product_name }}</h4>
                <div class="flex items-center space-x-4 text-xs text-gray-500">
                  <span>Hủy: {{ product.cancel_rate.toFixed(1) }}%</span>
                  <span>Trả: {{ product.return_rate.toFixed(1) }}%</span>
                  <span>Đơn: {{ product.total_orders }}</span>
                </div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium" :class="getHealthScoreTextClass(product.health_score)">
                {{ getHealthScoreStatus(product.health_score) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import IntelligenceEngine from '../../services/IntelligenceEngine.js'

// Props
const props = defineProps({
  products: {
    type: Array,
    default: () => []
  },
  orders: {
    type: Array,
    default: () => []
  },
  cancellations: {
    type: Array,
    default: () => []
  },
  returns: {
    type: Array,
    default: () => []
  }
})

// Reactive data
const loading = ref(false)
const analysis = ref({})

// Computed properties
const topPerformers = computed(() => analysis.value.topPerformers || [])
const lowPerformers = computed(() => analysis.value.lowPerformers || [])
const skuAnalysis = computed(() => analysis.value.skuAnalysis || [])
const priceAnalysis = computed(() => analysis.value.priceAnalysis || {})
const healthScores = computed(() => analysis.value.healthScores || [])

// Methods
const loadAnalysis = async () => {
  loading.value = true
  try {
    analysis.value = IntelligenceEngine.analyzeProducts(
      props.products,
      props.orders,
      props.cancellations,
      props.returns
    )
  } catch (error) {
    console.error('Error loading product analysis:', error)
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(amount)
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
    return formatCurrency(minPrice)
  } else {
    return `${formatCurrency(minPrice)} - ${formatCurrency(maxPrice)}`
  }
}

const getHealthScoreClass = (score) => {
  if (score >= 80) return 'bg-green-50 border border-green-200'
  if (score >= 60) return 'bg-yellow-50 border border-yellow-200'
  if (score >= 40) return 'bg-orange-50 border border-orange-200'
  return 'bg-red-50 border border-red-200'
}

const getHealthScoreBadgeClass = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 60) return 'bg-yellow-500'
  if (score >= 40) return 'bg-orange-500'
  return 'bg-red-500'
}

const getHealthScoreTextClass = (score) => {
  if (score >= 80) return 'text-green-700'
  if (score >= 60) return 'text-yellow-700'
  if (score >= 40) return 'text-orange-700'
  return 'text-red-700'
}

const getHealthScoreStatus = (score) => {
  if (score >= 80) return 'Xuất sắc'
  if (score >= 60) return 'Tốt'
  if (score >= 40) return 'Trung bình'
  return 'Kém'
}

// Lifecycle
onMounted(() => {
  loadAnalysis()
})

// Watch for changes in props
watch(() => [props.products, props.orders, props.cancellations, props.returns], () => {
  loadAnalysis()
}, { deep: true })
</script>
