<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-900">API Endpoints</h2>
      <p class="text-sm text-gray-600 mt-1">Click on any endpoint to test it</p>
    </div>
    
    <div class="p-6">
      <!-- Search and Filter -->
      <div class="mb-6">
        <div class="flex space-x-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search endpoints..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <select
            v-model="selectedCategory"
            class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Categories</option>
            <option value="orders">Orders</option>
            <option value="returns">Returns</option>
            <option value="cancellations">Cancellations</option>
            <option value="analytics">Analytics</option>
            <option value="products">Products</option>
            <option value="shops">Shops</option>
          </select>
          <select
            v-model="selectedMethod"
            class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Methods</option>
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="DELETE">DELETE</option>
          </select>
        </div>
      </div>

      <!-- API Endpoints List -->
      <div class="space-y-4">
        <div
          v-for="endpoint in filteredEndpoints"
          :key="endpoint.path"
          class="border border-gray-200 rounded-lg hover:border-blue-300 transition-colors"
        >
          <div class="p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getMethodClass(endpoint.method)"
                >
                  {{ endpoint.method }}
                </span>
                <div>
                  <h3 class="text-sm font-medium text-gray-900">{{ endpoint.name }}</h3>
                  <p class="text-sm text-gray-500 font-mono">{{ endpoint.path }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="testEndpoint(endpoint)"
                  class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  Test
                </button>
                <button
                  @click="toggleDetails(endpoint.path)"
                  class="p-1 text-gray-400 hover:text-gray-600"
                >
                  <svg
                    class="w-4 h-4 transform transition-transform"
                    :class="{ 'rotate-180': expandedEndpoints.includes(endpoint.path) }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Expanded Details -->
            <div v-if="expandedEndpoints.includes(endpoint.path)" class="mt-4 pt-4 border-t border-gray-200">
              <div class="space-y-4">
                <!-- Description -->
                <div v-if="endpoint.description">
                  <h4 class="text-sm font-medium text-gray-900 mb-2">Description</h4>
                  <p class="text-sm text-gray-600">{{ endpoint.description }}</p>
                </div>

                <!-- Parameters -->
                <div v-if="endpoint.parameters && endpoint.parameters.length > 0">
                  <h4 class="text-sm font-medium text-gray-900 mb-2">Parameters</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                          <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="param in endpoint.parameters" :key="param.name">
                          <td class="px-3 py-2 font-mono text-sm text-gray-900">{{ param.name }}</td>
                          <td class="px-3 py-2 text-sm text-gray-600">{{ param.type }}</td>
                          <td class="px-3 py-2 text-sm text-gray-600">
                            <span
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                              :class="param.required ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'"
                            >
                              {{ param.required ? 'Required' : 'Optional' }}
                            </span>
                          </td>
                          <td class="px-3 py-2 text-sm text-gray-600">{{ param.description }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Example Request -->
                <div v-if="endpoint.example">
                  <h4 class="text-sm font-medium text-gray-900 mb-2">Example Request</h4>
                  <div class="bg-gray-900 rounded-md p-4 overflow-x-auto">
                    <pre class="text-sm text-green-400 font-mono">{{ endpoint.example }}</pre>
                  </div>
                </div>

                <!-- Response Example -->
                <div v-if="endpoint.responseExample">
                  <h4 class="text-sm font-medium text-gray-900 mb-2">Response Example</h4>
                  <div class="bg-gray-900 rounded-md p-4 overflow-x-auto">
                    <pre class="text-sm text-blue-400 font-mono">{{ endpoint.responseExample }}</pre>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Results -->
      <div v-if="filteredEndpoints.length === 0" class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No endpoints found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const emit = defineEmits(['test-endpoint'])

const searchQuery = ref('')
const selectedCategory = ref('')
const selectedMethod = ref('')
const expandedEndpoints = ref([])

const endpoints = ref([
  // Orders API
  {
    name: 'Get Orders',
    method: 'GET',
    path: '/api/orders',
    category: 'orders',
    description: 'Retrieve a list of orders with optional filtering and pagination',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Specific shop ID to filter orders' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 20)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' },
      { name: 'start_date', type: 'string', required: false, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date', type: 'string', required: false, description: 'End date filter (Y-m-d format)' },
      { name: 'sort_field', type: 'string', required: false, description: 'Field to sort by (default: create_time)' },
      { name: 'sort_order', type: 'string', required: false, description: 'Sort order ASC/DESC (default: DESC)' }
    ],
    example: 'GET /api/orders?page_size=10&start_date=2024-01-01&end_date=2024-12-31',
    responseExample: `{
  "success": true,
  "data": {
    "orders": [...],
    "total_count": 100,
    "next_page_token": "abc123..."
  },
  "shop_id": "7496239622529452872",
  "source": "tiktok_api"
}`
  },
  {
    name: 'Get Order Details',
    method: 'GET',
    path: '/api/orders/details',
    category: 'orders',
    description: 'Get detailed information about a specific order',
    parameters: [
      { name: 'order_id', type: 'string', required: true, description: 'The order ID to retrieve details for' }
    ],
    example: 'GET /api/orders/details?order_id=580432783816426626',
    responseExample: `{
  "success": true,
  "data": {
    "order_id": "580432783816426626",
    "status": "DELIVERED",
    "payment": {...},
    "line_items": [...],
    "shipping": {...}
  }
}`
  },
  {
    name: 'Get Orders Stats',
    method: 'GET',
    path: '/api/orders/stats',
    category: 'orders',
    description: 'Get statistical information about orders',
    parameters: [
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' }
    ],
    example: 'GET /api/orders/stats?page_size=10',
    responseExample: `{
  "success": true,
  "data": {
    "total_orders": 1500,
    "total_revenue": 2500000,
    "average_order_value": 1666.67
  }
}`
  },

  // Returns API
  {
    name: 'Get Returns',
    method: 'GET',
    path: '/api/returns',
    category: 'returns',
    description: 'Retrieve a list of returns with optional filtering',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Specific shop ID to filter returns' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' },
      { name: 'start_date', type: 'string', required: false, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date', type: 'string', required: false, description: 'End date filter (Y-m-d format)' },
      { name: 'return_ids', type: 'array', required: false, description: 'Array of return IDs to filter' },
      { name: 'order_ids', type: 'array', required: false, description: 'Array of order IDs to filter' },
      { name: 'return_types', type: 'array', required: false, description: 'Array of return types to filter' },
      { name: 'return_status', type: 'array', required: false, description: 'Array of return statuses to filter' }
    ],
    example: 'GET /api/returns?page_size=10&start_date=2024-01-01&end_date=2024-12-31',
    responseExample: `{
  "success": true,
  "data": {
    "returns": [...],
    "total_count": 50,
    "next_page_token": "def456..."
  }
}`
  },
  {
    name: 'Get Return Details',
    method: 'GET',
    path: '/api/returns/details',
    category: 'returns',
    description: 'Get detailed information about a specific return',
    parameters: [
      { name: 'return_id', type: 'string', required: true, description: 'The return ID to retrieve details for' }
    ],
    example: 'GET /api/returns/details?return_id=4036824464401204354',
    responseExample: `{
  "success": true,
  "data": {
    "return_id": "4036824464401204354",
    "order_id": "580432783816426626",
    "return_type": "REFUND",
    "return_status": "COMPLETED",
    "refund_amount": 50000
  }
}`
  },
  {
    name: 'Get Returns Stats',
    method: 'GET',
    path: '/api/returns/stats',
    category: 'returns',
    description: 'Get statistical information about returns',
    parameters: [
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' }
    ],
    example: 'GET /api/returns/stats?page_size=10',
    responseExample: `{
  "success": true,
  "data": {
    "total_returns": 25,
    "total_refund_amount": 1250000,
    "return_rate": 1.67
  }
}`
  },

  // Cancellations API
  {
    name: 'Get Cancellations',
    method: 'GET',
    path: '/api/cancellations',
    category: 'cancellations',
    description: 'Retrieve a list of cancellations with optional filtering',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Specific shop ID to filter cancellations' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' },
      { name: 'start_date', type: 'string', required: false, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date', type: 'string', required: false, description: 'End date filter (Y-m-d format)' },
      { name: 'cancel_ids', type: 'array', required: false, description: 'Array of cancel IDs to filter' },
      { name: 'order_ids', type: 'array', required: false, description: 'Array of order IDs to filter' },
      { name: 'cancel_types', type: 'array', required: false, description: 'Array of cancel types to filter' },
      { name: 'cancel_status', type: 'array', required: false, description: 'Array of cancel statuses to filter' }
    ],
    example: 'GET /api/cancellations?page_size=10&start_date=2024-01-01&end_date=2024-12-31',
    responseExample: `{
  "success": true,
  "data": {
    "cancellations": [...],
    "total_count": 30,
    "next_page_token": "ghi789..."
  }
}`
  },
  {
    name: 'Get Cancellation Details',
    method: 'GET',
    path: '/api/cancellations/details',
    category: 'cancellations',
    description: 'Get detailed information about a specific cancellation',
    parameters: [
      { name: 'cancel_id', type: 'string', required: true, description: 'The cancellation ID to retrieve details for' }
    ],
    example: 'GET /api/cancellations/details?cancel_id=4035318504086604100',
    responseExample: `{
  "success": true,
  "data": {
    "cancel_id": "4035318504086604100",
    "order_id": "580432783816426626",
    "cancel_type": "CUSTOMER_CANCEL",
    "cancel_status": "COMPLETED",
    "refund_amount": 75000
  }
}`
  },
  {
    name: 'Get Cancellations Stats',
    method: 'GET',
    path: '/api/cancellations/stats',
    category: 'cancellations',
    description: 'Get statistical information about cancellations',
    parameters: [
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' }
    ],
    example: 'GET /api/cancellations/stats?page_size=10',
    responseExample: `{
  "success": true,
  "data": {
    "total_cancellations": 15,
    "total_refund_amount": 750000,
    "cancellation_rate": 1.0
  }
}`
  },

  // Analytics API
  {
    name: 'Get Shop Analytics',
    method: 'GET',
    path: '/api/analytics/shops',
    category: 'analytics',
    description: 'Get analytics data for shops',
    parameters: [],
    example: 'GET /api/analytics/shops',
    responseExample: `{
  "success": true,
  "data": {
    "shops": [...],
    "total_shops": 5,
    "active_shops": 3
  }
}`
  },
  {
    name: 'Get Shop Overview',
    method: 'GET',
    path: '/api/analytics/shop/overview',
    category: 'analytics',
    description: 'Get overview analytics for a specific shop',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to get overview for' }
    ],
    example: 'GET /api/analytics/shop/overview?shop_id=7496239622529452872',
    responseExample: `{
  "success": true,
  "data": {
    "shop_id": "7496239622529452872",
    "total_orders": 1500,
    "total_revenue": 2500000,
    "conversion_rate": 3.2
  }
}`
  },
  {
    name: 'Get Shop Performance',
    method: 'GET',
    path: '/api/analytics/shop/performance',
    category: 'analytics',
    description: 'Get performance metrics for a specific shop',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to get performance for' }
    ],
    example: 'GET /api/analytics/shop/performance?shop_id=7496239622529452872',
    responseExample: `{
  "success": true,
  "data": {
    "shop_id": "7496239622529452872",
    "performance_metrics": {...}
  }
}`
  },
  {
    name: 'Get Product Analytics',
    method: 'GET',
    path: '/api/analytics/products',
    category: 'analytics',
    description: 'Get analytics data for products',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to filter products' }
    ],
    example: 'GET /api/analytics/products?shop_id=7496239622529452872',
    responseExample: `{
  "success": true,
  "data": {
    "products": [...],
    "total_products": 100,
    "top_performing": [...]
  }
}`
  },
  {
    name: 'Get Product Performance',
    method: 'GET',
    path: '/api/analytics/product/performance',
    category: 'analytics',
    description: 'Get performance metrics for a specific product',
    parameters: [
      { name: 'product_id', type: 'string', required: true, description: 'Product ID to get performance for' }
    ],
    example: 'GET /api/analytics/product/performance?product_id=123456789',
    responseExample: `{
  "success": true,
  "data": {
    "product_id": "123456789",
    "performance_metrics": {...}
  }
}`
  },
  {
    name: 'Get Products Performance List',
    method: 'GET',
    path: '/api/analytics/products/performance',
    category: 'analytics',
    description: 'Get performance list for multiple products',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to filter products' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page' }
    ],
    example: 'GET /api/analytics/products/performance?shop_id=7496239622529452872&page_size=20',
    responseExample: `{
  "success": true,
  "data": {
    "products_performance": [...],
    "total_count": 100
  }
}`
  },
  {
    name: 'Get Video Analytics',
    method: 'GET',
    path: '/api/analytics/videos',
    category: 'analytics',
    description: 'Get analytics data for videos',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to filter videos' }
    ],
    example: 'GET /api/analytics/videos?shop_id=7496239622529452872',
    responseExample: `{
  "success": true,
  "data": {
    "videos": [...],
    "total_videos": 50,
    "total_views": 1000000
  }
}`
  },
  {
    name: 'Get Live Analytics',
    method: 'GET',
    path: '/api/analytics/live',
    category: 'analytics',
    description: 'Get analytics data for live streams',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to filter live streams' }
    ],
    example: 'GET /api/analytics/live?shop_id=7496239622529452872',
    responseExample: `{
  "success": true,
  "data": {
    "live_streams": [...],
    "total_streams": 25,
    "total_viewers": 500000
  }
}`
  },
  {
    name: 'Get Analytics Stats',
    method: 'GET',
    path: '/api/analytics/stats',
    category: 'analytics',
    description: 'Get general analytics statistics',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to filter stats' }
    ],
    example: 'GET /api/analytics/stats?shop_id=7496239622529452872',
    responseExample: `{
  "success": true,
  "data": {
    "total_orders": 1500,
    "total_revenue": 2500000,
    "total_products": 100,
    "conversion_rate": 3.2
  }
}`
  },
  {
    name: 'Get Shop SKUs Performance',
    method: 'GET',
    path: '/api/analytics/shop/skus/performance',
    category: 'analytics',
    description: 'Get detailed performance analytics for shop SKUs',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' },
      { name: 'sort_field', type: 'string', required: false, description: 'Field to sort by (default: gmv)' },
      { name: 'sort_order', type: 'string', required: false, description: 'Sort order ASC/DESC (default: DESC)' },
      { name: 'currency', type: 'string', required: false, description: 'Currency code (default: USD)' },
      { name: 'product_id', type: 'string', required: false, description: 'Filter by specific product ID' },
      { name: 'timestamp', type: 'integer', required: false, description: 'Request timestamp' }
    ],
    example: 'GET /api/analytics/shop/skus/performance?start_date_ge=2024-04-01&end_date_lt=2024-04-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&page_size=10&sort_field=gmv&sort_order=DESC',
    responseExample: `{
  "success": true,
  "data": {
    "skus_performance": [
      {
        "sku_id": 18354627263453,
        "product_id": 123456789,
        "gmv": {
          "amount": 1000.00,
          "currency": "USD",
          "formatted": "$1,000.00"
        },
        "orders": {
          "count": 10,
          "formatted": "10"
        },
        "units_sold": {
          "count": 10,
          "formatted": "10"
        },
        "average_order_value": {
          "amount": 100.00,
          "currency": "USD",
          "formatted": "$100.00"
        },
        "conversion_rate": 100.0
      }
    ],
    "pagination": {
      "next_page_token": "cGFnZV9udW1iZXI9MQ==",
      "total_count": 20,
      "page_size": 10,
      "has_more": true
    },
    "date_range": {
      "start_date": "2024-04-01",
      "end_date": "2024-04-08",
      "latest_available_date": "2024-04-07"
    }
  }
}`
  },
  {
    name: 'Get Shop SKUs Summary',
    method: 'GET',
    path: '/api/analytics/shop/skus/summary',
    category: 'analytics',
    description: 'Get summary statistics for shop SKUs performance',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' }
    ],
    example: 'GET /api/analytics/shop/skus/summary?start_date_ge=2024-04-01&end_date_lt=2024-04-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd',
    responseExample: `{
  "success": true,
  "data": {
    "summary": {
      "total_gmv": {
        "amount": 50000.00,
        "currency": "USD",
        "formatted": "$50,000.00"
      },
      "total_orders": 500,
      "total_units_sold": 500,
      "average_gmv_per_sku": {
        "amount": 2500.00,
        "currency": "USD",
        "formatted": "$2,500.00"
      },
      "average_orders_per_sku": 25.0,
      "average_units_per_sku": 25.0,
      "total_skus": 20
    },
    "date_range": {
      "start_date": "2024-04-01",
      "end_date": "2024-04-08"
    }
  }
}`
  },
  {
    name: 'Get Top SKUs',
    method: 'GET',
    path: '/api/analytics/shop/skus/top',
    category: 'analytics',
    description: 'Get top performing SKUs ranked by GMV',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'limit', type: 'integer', required: false, description: 'Number of top SKUs to return (max: 50, default: 10)' }
    ],
    example: 'GET /api/analytics/shop/skus/top?start_date_ge=2024-04-01&end_date_lt=2024-04-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=10',
    responseExample: `{
  "success": true,
  "data": {
    "top_skus": [
      {
        "sku_id": 18354627263453,
        "product_id": 123456789,
        "gmv": {
          "amount": 10000.00,
          "currency": "USD",
          "formatted": "$10,000.00"
        },
        "orders": {
          "count": 100,
          "formatted": "100"
        },
        "units_sold": {
          "count": 100,
          "formatted": "100"
        },
        "rank": 1
      }
    ],
    "date_range": {
      "start_date": "2024-04-01",
      "end_date": "2024-04-08"
    },
    "total_found": 10
  }
}`
  },
  {
    name: 'Get Shop Videos Performance',
    method: 'GET',
    path: '/api/analytics/shop/videos/performance',
    category: 'analytics',
    description: 'Get detailed performance analytics for shop videos',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' },
      { name: 'sort_field', type: 'string', required: false, description: 'Field to sort by (default: gmv)' },
      { name: 'sort_order', type: 'string', required: false, description: 'Sort order ASC/DESC (default: DESC)' },
      { name: 'currency', type: 'string', required: false, description: 'Currency code (default: USD)' },
      { name: 'account_type', type: 'string', required: false, description: 'Account type filter (default: ALL)' },
      { name: 'timestamp', type: 'integer', required: false, description: 'Request timestamp' }
    ],
    example: 'GET /api/analytics/shop/videos/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&page_size=10&sort_field=gmv&sort_order=DESC',
    responseExample: `{
  "success": true,
  "data": {
    "videos_performance": [
      {
        "video_id": "172xxxxxxxxxxxxx089",
        "title": "Video Title",
        "username": "Video Username",
        "gmv": {
          "amount": 1000.00,
          "currency": "USD",
          "formatted": "$1,000.00"
        },
        "orders": {
          "count": 10,
          "formatted": "10"
        },
        "units_sold": {
          "count": 10,
          "formatted": "10"
        },
        "views": {
          "count": 5000,
          "formatted": "5,000"
        },
        "click_through_rate": {
          "rate": 2.5,
          "formatted": "2.50%"
        },
        "products": [
          {
            "product_id": "105xxxxxxxxxxxxx247",
            "name": "Product Name",
            "display_name": "Product Name"
          }
        ],
        "video_post_time": "2025-01-01 00:00:00",
        "video_post_date": {
          "raw": "2025-01-01 00:00:00",
          "formatted": "Jan 01, 2025 00:00",
          "relative": "2 days ago",
          "timestamp": 1735689600
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "conversion_rate": 0.2,
          "units_per_order": 1.0,
          "revenue_per_view": {
            "amount": 0.2,
            "currency": "USD",
            "formatted": "$0.20"
          },
          "engagement_score": 45.5
        }
      }
    ],
    "pagination": {
      "next_page_token": "cGFnZV9udW1iZXI9MQ==",
      "total_count": 10,
      "page_size": 10,
      "has_more": true
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    }
  }
}`
  },
  {
    name: 'Get Shop Videos Summary',
    method: 'GET',
    path: '/api/analytics/shop/videos/summary',
    category: 'analytics',
    description: 'Get summary statistics for shop videos performance',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' }
    ],
    example: 'GET /api/analytics/shop/videos/summary?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd',
    responseExample: `{
  "success": true,
  "data": {
    "summary": {
      "total_gmv": {
        "amount": 25000.00,
        "currency": "USD",
        "formatted": "$25,000.00"
      },
      "total_orders": 250,
      "total_units_sold": 250,
      "total_views": 125000,
      "average_gmv_per_video": {
        "amount": 2500.00,
        "currency": "USD",
        "formatted": "$2,500.00"
      },
      "average_orders_per_video": 25.0,
      "average_views_per_video": 12500.0,
      "average_ctr": 2.5,
      "total_videos": 10
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    }
  }
}`
  },
  {
    name: 'Get Top Videos',
    method: 'GET',
    path: '/api/analytics/shop/videos/top',
    category: 'analytics',
    description: 'Get top performing videos ranked by GMV',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'limit', type: 'integer', required: false, description: 'Number of top videos to return (max: 50, default: 10)' }
    ],
    example: 'GET /api/analytics/shop/videos/top?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=10',
    responseExample: `{
  "success": true,
  "data": {
    "top_videos": [
      {
        "video_id": "172xxxxxxxxxxxxx089",
        "title": "Top Performing Video",
        "username": "Video Username",
        "gmv": {
          "amount": 5000.00,
          "currency": "USD",
          "formatted": "$5,000.00"
        },
        "orders": {
          "count": 50,
          "formatted": "50"
        },
        "units_sold": {
          "count": 50,
          "formatted": "50"
        },
        "views": {
          "count": 25000,
          "formatted": "25,000"
        },
        "rank": 1
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_found": 10
  }
}`
  },
  {
    name: 'Get Videos By Product',
    method: 'GET',
    path: '/api/analytics/shop/videos/by-product',
    category: 'analytics',
    description: 'Get videos that feature a specific product',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'product_id', type: 'string', required: true, description: 'Product ID to filter videos' }
    ],
    example: 'GET /api/analytics/shop/videos/by-product?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&product_id=105xxxxxxxxxxxxx247',
    responseExample: `{
  "success": true,
  "data": {
    "videos": [
      {
        "video_id": "172xxxxxxxxxxxxx089",
        "title": "Video featuring Product",
        "username": "Video Username",
        "gmv": {
          "amount": 2000.00,
          "currency": "USD",
          "formatted": "$2,000.00"
        },
        "products": [
          {
            "product_id": "105xxxxxxxxxxxxx247",
            "name": "Product Name",
            "display_name": "Product Name"
          }
        ]
      }
    ],
    "product_id": "105xxxxxxxxxxxxx247",
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_found": 3
  }
}`
  },
  {
    name: 'Get Shop Videos Overview',
    method: 'GET',
    path: '/api/analytics/shop/videos/overview',
    category: 'analytics',
    description: 'Get overview performance analytics for shop videos with comparison data',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'currency', type: 'string', required: false, description: 'Currency code (default: USD)' },
      { name: 'account_type', type: 'string', required: false, description: 'Account type filter (default: ALL)' },
      { name: 'with_comparison', type: 'boolean', required: false, description: 'Include comparison data (default: true)' },
      { name: 'granularity', type: 'string', required: false, description: 'Data granularity (default: ALL)' },
      { name: 'timestamp', type: 'integer', required: false, description: 'Request timestamp' }
    ],
    example: 'GET /api/analytics/shop/videos/overview?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=ALL',
    responseExample: `{
  "success": true,
  "data": {
    "overview_performance": [
      {
        "start_date": "2024-09-01",
        "end_date": "2024-09-08",
        "gmv": {
          "amount": 50000.00,
          "currency": "USD",
          "formatted": "$50,000.00"
        },
        "click_through_rate": {
          "rate": 2.5,
          "formatted": "2.50%"
        },
        "orders": {
          "count": 500,
          "formatted": "500"
        },
        "units_sold": {
          "count": 500,
          "formatted": "500"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "units_per_order": 1.0,
          "gmv_per_unit": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "conversion_efficiency": 85.5
        }
      }
    ],
    "comparison_data": [
      {
        "start_date": "2024-08-25",
        "end_date": "2024-09-01",
        "gmv": {
          "amount": 45000.00,
          "currency": "USD",
          "formatted": "$45,000.00"
        },
        "click_through_rate": {
          "rate": 2.2,
          "formatted": "2.20%"
        },
        "orders": {
          "count": 450,
          "formatted": "450"
        },
        "units_sold": {
          "count": 450,
          "formatted": "450"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "units_per_order": 1.0,
          "gmv_per_unit": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "conversion_efficiency": 82.0
        }
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-08"
    },
    "filters": {
      "currency": "USD",
      "account_type": "ALL",
      "with_comparison": true,
      "granularity": "ALL"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
  },
  {
    name: 'Get Video Performance By ID',
    method: 'GET',
    path: '/api/analytics/shop/videos/{videoId}/performance',
    category: 'analytics',
    description: 'Get detailed performance analytics for a specific video by ID with engagement data',
    parameters: [
      { name: 'videoId', type: 'string', required: true, description: 'Video ID from TikTok Shop' },
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'currency', type: 'string', required: false, description: 'Currency code (default: USD)' },
      { name: 'with_comparison', type: 'boolean', required: false, description: 'Include comparison data (default: true)' },
      { name: 'granularity', type: 'string', required: false, description: 'Data granularity (default: ALL)' },
      { name: 'timestamp', type: 'integer', required: false, description: 'Request timestamp' }
    ],
    example: 'GET /api/analytics/shop/videos/1111/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=ALL',
    responseExample: `{
  "success": true,
  "data": {
    "video_id": "1111",
    "performance": [
      {
        "start_date": "2024-09-01",
        "end_date": "2024-09-08",
        "gmv": {
          "amount": 10000.00,
          "currency": "USD",
          "formatted": "$10,000.00"
        },
        "click_through_rate": {
          "rate": 3.5,
          "formatted": "3.50%"
        },
        "daily_avg_buyers": {
          "count": 25.5,
          "formatted": "25.50"
        },
        "views": {
          "count": 50000,
          "formatted": "50,000"
        },
        "performance_metrics": {
          "gmv_per_view": {
            "amount": 0.2,
            "currency": "USD",
            "formatted": "$0.20"
          },
          "buyer_conversion_rate": 0.05,
          "engagement_rate": 3.5,
          "performance_score": 78.5
        }
      }
    ],
    "comparison_data": [
      {
        "start_date": "2024-08-25",
        "end_date": "2024-09-01",
        "gmv": {
          "amount": 8000.00,
          "currency": "USD",
          "formatted": "$8,000.00"
        },
        "click_through_rate": {
          "rate": 3.2,
          "formatted": "3.20%"
        },
        "daily_avg_buyers": {
          "count": 20.0,
          "formatted": "20.00"
        },
        "views": {
          "count": 45000,
          "formatted": "45,000"
        },
        "performance_metrics": {
          "gmv_per_view": {
            "amount": 0.18,
            "currency": "USD",
            "formatted": "$0.18"
          },
          "buyer_conversion_rate": 0.044,
          "engagement_rate": 3.2,
          "performance_score": 72.0
        }
      }
    ],
    "engagement_data": {
      "total_likes": {
        "count": 2500,
        "formatted": "2,500"
      },
      "total_shares": {
        "count": 500,
        "formatted": "500"
      },
      "total_comments": {
        "count": 300,
        "formatted": "300"
      },
      "total_views": {
        "count": 50000,
        "formatted": "50,000"
      },
      "engagement_metrics": {
        "like_rate": 5.0,
        "share_rate": 1.0,
        "comment_rate": 0.6,
        "total_engagement": 3300,
        "engagement_score": 6.6
      }
    },
    "video_info": {
      "video_post_time": "2025-01-01 00:00:00",
      "video_post_date": {
        "raw": "2025-01-01 00:00:00",
        "formatted": "Jan 01, 2025 00:00",
        "relative": "2 days ago",
        "timestamp": 1735689600
      }
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    },
    "filters": {
      "currency": "USD",
      "with_comparison": true,
      "granularity": "ALL"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
  },
  {
    name: 'Get Shop Lives Performance',
    method: 'GET',
    path: '/api/analytics/shop/lives/performance',
    category: 'analytics',
    description: 'Get detailed performance analytics for shop live streams',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 10)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' },
      { name: 'sort_field', type: 'string', required: false, description: 'Field to sort by (default: gmv)' },
      { name: 'sort_order', type: 'string', required: false, description: 'Sort order ASC/DESC (default: DESC)' },
      { name: 'currency', type: 'string', required: false, description: 'Currency code (default: USD)' },
      { name: 'account_type', type: 'string', required: false, description: 'Account type filter (default: ALL)' },
      { name: 'timestamp', type: 'integer', required: false, description: 'Request timestamp' }
    ],
    example: 'GET /api/analytics/shop/lives/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&page_size=30&sort_field=gmv&sort_order=ASC',
    responseExample: `{
  "success": true,
  "data": {
    "live_stream_sessions": [
      {
        "live_id": "75xxxxxxxxxxxxxxx28",
        "title": "Live Stream Title",
        "username": "rey20195",
        "duration": {
          "seconds": 200,
          "formatted": "3m 20s",
          "start_time": {
            "timestamp": 1623812664,
            "formatted": "2021-06-16 10:04:24"
          },
          "end_time": {
            "timestamp": 1623812864,
            "formatted": "2021-06-16 10:07:44"
          }
        },
        "sales_performance": {
          "gmv": {
            "amount": 99.00,
            "currency": "USD",
            "formatted": "$99.00"
          },
          "products_added": {
            "count": 12,
            "formatted": "12"
          },
          "different_products_sold": {
            "count": 5,
            "formatted": "5"
          },
          "created_sku_orders": {
            "count": 100,
            "formatted": "100"
          },
          "sku_orders": {
            "count": 80,
            "formatted": "80"
          },
          "units_sold": {
            "count": 122,
            "formatted": "122"
          },
          "customers": {
            "count": 50,
            "formatted": "50"
          },
          "avg_price": {
            "amount": 9.00,
            "currency": "USD",
            "formatted": "$9.00"
          },
          "click_to_order_rate": {
            "rate": 18.0,
            "formatted": "18%"
          },
          "24h_live_gmv": {
            "amount": 340.00,
            "currency": "USD",
            "formatted": "$340.00"
          }
        },
        "interaction_performance": {
          "acu": {
            "count": 123,
            "formatted": "123"
          },
          "pcu": {
            "count": 1332,
            "formatted": "1,332"
          },
          "viewers": {
            "count": 18323,
            "formatted": "18,323"
          },
          "views": {
            "count": 112993,
            "formatted": "112,993"
          },
          "avg_viewing_duration": {
            "seconds": 46,
            "formatted": "46s"
          },
          "comments": {
            "count": 534,
            "formatted": "534"
          },
          "shares": {
            "count": 156,
            "formatted": "156"
          },
          "likes": {
            "count": 2442,
            "formatted": "2,442"
          },
          "new_followers": {
            "count": 12,
            "formatted": "12"
          },
          "product_impressions": {
            "count": 12,
            "formatted": "12"
          },
          "product_clicks": {
            "count": 3882,
            "formatted": "3,882"
          },
          "click_through_rate": {
            "rate": 13.99,
            "formatted": "13.99%"
          }
        },
        "performance_metrics": {
          "gmv_per_view": {
            "amount": 0.0009,
            "currency": "USD",
            "formatted": "$0.00"
          },
          "conversion_rate": 0.04,
          "order_rate": 0.07,
          "engagement_score": 2.77,
          "sales_efficiency": 12.5
        }
      }
    ],
    "pagination": {
      "next_page_token": "cGFnZV9udW1iZXI9MQ==",
      "total_count": 233,
      "page_size": 30,
      "has_more": true
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    },
    "filters": {
      "sort_field": "gmv",
      "sort_order": "ASC",
      "currency": "USD",
      "account_type": "OFFICIAL_ACCOUNTS"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
  },
  {
    name: 'Get Shop Lives Summary',
    method: 'GET',
    path: '/api/analytics/shop/lives/summary',
    category: 'analytics',
    description: 'Get summary statistics for shop live streams performance',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' }
    ],
    example: 'GET /api/analytics/shop/lives/summary?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd',
    responseExample: `{
  "success": true,
  "data": {
    "summary": {
      "total_gmv": {
        "amount": 50000.00,
        "currency": "USD",
        "formatted": "$50,000.00"
      },
      "total_views": 500000,
      "total_customers": 2500,
      "total_orders": 2000,
      "total_units_sold": 3000,
      "total_engagement": 15000,
      "average_gmv_per_live": {
        "amount": 2500.00,
        "currency": "USD",
        "formatted": "$2,500.00"
      },
      "average_views_per_live": 25000.0,
      "average_customers_per_live": 125.0,
      "average_duration_per_live": {
        "seconds": 1800,
        "formatted": "30m 0s"
      },
      "total_live_streams": 20
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_live_streams": 20,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
  },
  {
    name: 'Get Top Lives',
    method: 'GET',
    path: '/api/analytics/shop/lives/top',
    category: 'analytics',
    description: 'Get top performing live streams ranked by GMV',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'limit', type: 'integer', required: false, description: 'Number of top lives to return (max: 50, default: 10)' }
    ],
    example: 'GET /api/analytics/shop/lives/top?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=10',
    responseExample: `{
  "success": true,
  "data": {
    "top_lives": [
      {
        "live_id": "75xxxxxxxxxxxxxxx28",
        "title": "Top Performing Live",
        "username": "rey20195",
        "sales_performance": {
          "gmv": {
            "amount": 10000.00,
            "currency": "USD",
            "formatted": "$10,000.00"
          }
        },
        "interaction_performance": {
          "views": {
            "count": 100000,
            "formatted": "100,000"
          }
        },
        "rank": 1
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_found": 20,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
  },
  {
    name: 'Get Shop Lives Overview',
    method: 'GET',
    path: '/api/analytics/shop/lives/overview',
    category: 'analytics',
    description: 'Get overview performance analytics for shop live streams with comparison data',
    parameters: [
      { name: 'start_date_ge', type: 'string', required: true, description: 'Start date filter (Y-m-d format)' },
      { name: 'end_date_lt', type: 'string', required: true, description: 'End date filter (Y-m-d format)' },
      { name: 'shop_cipher', type: 'string', required: true, description: 'Shop cipher from TikTok Shop' },
      { name: 'app_key', type: 'string', required: true, description: 'App key from TikTok Shop' },
      { name: 'currency', type: 'string', required: false, description: 'Currency code (default: USD)' },
      { name: 'account_type', type: 'string', required: false, description: 'Account type filter (default: ALL)' },
      { name: 'with_comparison', type: 'boolean', required: false, description: 'Include comparison data (default: true)' },
      { name: 'granularity', type: 'string', required: false, description: 'Data granularity (default: 1D)' },
      { name: 'timestamp', type: 'integer', required: false, description: 'Request timestamp' }
    ],
    example: 'GET /api/analytics/shop/lives/overview?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=1D',
    responseExample: `{
  "success": true,
  "data": {
    "overview_performance": [
      {
        "start_date": "2024-09-01",
        "end_date": "2024-09-08",
        "gmv": {
          "amount": 11.00,
          "currency": "USD",
          "formatted": "$11.00"
        },
        "sku_orders": {
          "count": 20,
          "formatted": "20"
        },
        "customers": {
          "count": 4,
          "formatted": "4"
        },
        "units_sold": {
          "count": 2,
          "formatted": "2"
        },
        "click_to_order_rate": {
          "rate": 32.0,
          "formatted": "32%"
        },
        "click_through_rate": {
          "rate": 10.0,
          "formatted": "10%"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 0.55,
            "currency": "USD",
            "formatted": "$0.55"
          },
          "units_per_order": 0.1,
          "orders_per_customer": 5.0,
          "gmv_per_customer": {
            "amount": 2.75,
            "currency": "USD",
            "formatted": "$2.75"
          },
          "conversion_efficiency": 12.0
        }
      }
    ],
    "comparison_data": [
      {
        "start_date": "2024-08-24",
        "end_date": "2024-08-31",
        "gmv": {
          "amount": 11.00,
          "currency": "USD",
          "formatted": "$11.00"
        },
        "sku_orders": {
          "count": 20,
          "formatted": "20"
        },
        "customers": {
          "count": 4,
          "formatted": "4"
        },
        "units_sold": {
          "count": 2,
          "formatted": "2"
        },
        "click_to_order_rate": {
          "rate": 32.0,
          "formatted": "32%"
        },
        "click_through_rate": {
          "rate": 10.0,
          "formatted": "10%"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 0.55,
            "currency": "USD",
            "formatted": "$0.55"
          },
          "units_per_order": 0.1,
          "orders_per_customer": 5.0,
          "gmv_per_customer": {
            "amount": 2.75,
            "currency": "USD",
            "formatted": "$2.75"
          },
          "conversion_efficiency": 12.0
        }
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    },
    "filters": {
      "currency": "USD",
      "account_type": "ALL",
      "with_comparison": true,
      "granularity": "1D"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
  },

  // Products API
  {
    name: 'Get Products',
    method: 'GET',
    path: '/api/products',
    category: 'products',
    description: 'Retrieve a list of products',
    parameters: [
      { name: 'shop_id', type: 'string', required: false, description: 'Shop ID to filter products' },
      { name: 'page_size', type: 'integer', required: false, description: 'Number of records per page (default: 20)' },
      { name: 'page_token', type: 'string', required: false, description: 'Token for pagination' }
    ],
    example: 'GET /api/products?page_size=10',
    responseExample: `{
  "success": true,
  "data": {
    "products": [...],
    "total_count": 100,
    "next_page_token": "jkl012..."
  }
}`
  },

  // Shops API
  {
    name: 'Get Shops',
    method: 'GET',
    path: '/api/shops',
    category: 'shops',
    description: 'Retrieve a list of authorized shops',
    parameters: [],
    example: 'GET /api/shops',
    responseExample: `{
  "success": true,
  "data": {
    "shops": [
      {
        "shop_id": "7496239622529452872",
        "shop_name": "My TikTok Shop",
        "region": "VN",
        "is_active": true
      }
    ]
  }
}`
  }
])

const filteredEndpoints = computed(() => {
  return endpoints.value.filter(endpoint => {
    const matchesSearch = !searchQuery.value || 
      endpoint.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      endpoint.path.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      endpoint.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesCategory = !selectedCategory.value || endpoint.category === selectedCategory.value
    const matchesMethod = !selectedMethod.value || endpoint.method === selectedMethod.value
    
    return matchesSearch && matchesCategory && matchesMethod
  })
})

const getMethodClass = (method) => {
  const classes = {
    GET: 'bg-green-100 text-green-800',
    POST: 'bg-blue-100 text-blue-800',
    PUT: 'bg-yellow-100 text-yellow-800',
    PATCH: 'bg-orange-100 text-orange-800',
    DELETE: 'bg-red-100 text-red-800'
  }
  return classes[method] || 'bg-gray-100 text-gray-800'
}

const toggleDetails = (path) => {
  const index = expandedEndpoints.value.indexOf(path)
  if (index > -1) {
    expandedEndpoints.value.splice(index, 1)
  } else {
    expandedEndpoints.value.push(path)
  }
}

const testEndpoint = (endpoint) => {
  emit('test-endpoint', endpoint)
}
</script>
