<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Vue.js Test Page</h1>
    <p class="mb-4">Nếu bạn thấy trang này, Vue.js đã hoạt động!</p>
    <p class="mb-4">API Status: {{ apiStatus }}</p>
    <button @click="testApi" class="bg-blue-500 text-white px-4 py-2 rounded">Test API</button>
    
    <div v-if="products.length > 0" class="mt-6">
      <h2 class="text-xl font-semibold mb-2">Sản phẩm:</h2>
      <div class="grid gap-4">
        <div v-for="product in products" :key="product.id" class="border p-4 rounded">
          <h3 class="font-medium">{{ product.title }}</h3>
          <p class="text-sm text-gray-600">SKU: {{ product.skus?.[0]?.seller_sku || 'N/A' }}</p>
          <p class="text-sm text-gray-600">Giá: {{ formatPrice(product) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'TestPage',
  setup() {
    const apiStatus = ref('Chưa test')
    const products = ref([])

    const formatPrice = (product) => {
      if (!product.skus?.[0]?.price) return 'N/A'
      const price = product.skus[0].price.tax_exclusive_price
      return `${parseFloat(price).toLocaleString('vi-VN')} VND`
    }

    const testApi = async () => {
      try {
        apiStatus.value = 'Đang test...'
        const response = await axios.get('/api/products')
        console.log('API Response:', response.data)
        
        if (response.data.success && response.data.data?.data?.products) {
          products.value = response.data.data.data.products
          apiStatus.value = 'Thành công!'
        } else {
          apiStatus.value = 'Lỗi cấu trúc dữ liệu'
        }
      } catch (error) {
        console.error('API Error:', error)
        apiStatus.value = 'Lỗi: ' + error.message
      }
    }

    onMounted(() => {
      testApi()
    })

    return {
      apiStatus,
      products,
      testApi,
      formatPrice
    }
  }
}
</script>