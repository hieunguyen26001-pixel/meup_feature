<template>
  <div class="p-6">
    <!-- Debug Info -->
    <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 rounded">
      <h3 class="font-bold text-yellow-800">Debug Info:</h3>
      <p class="text-sm text-yellow-700">currentShop: {{ currentShop }}</p>
      <p class="text-sm text-yellow-700">activeTab: {{ activeTab }}</p>
      <p class="text-sm text-yellow-700">loading: {{ loading }}</p>
    </div>
    
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Phân tích Shop</h1>
          <p class="text-gray-600 mt-1">Theo dõi và phân tích hiệu suất shop của bạn</p>
        </div>
        <div class="flex items-center space-x-4">
          <!-- Shop Selection -->
          <div v-if="currentShop" class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Shop:</span>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
              {{ currentShop.shop_name }}
            </span>
          </div>
          <div v-else class="text-sm text-red-500">
            Chưa chọn shop
          </div>
          
          <!-- Refresh Button -->
          <button 
            @click="refreshData" 
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>{{ loading ? 'Đang tải...' : 'Tải lại' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!currentShop" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa chọn shop</h3>
      <p class="mt-1 text-sm text-gray-500">Vui lòng chọn shop từ menu sidebar để xem phân tích</p>
      
      <!-- Shop Selection -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Chọn shop:</label>
        <select 
          v-model="selectedShopId" 
          @change="selectShop"
          class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">-- Chọn shop --</option>
          <option v-for="shop in availableShops" :key="shop.shop_id" :value="shop.shop_id">
            {{ shop.shop_name }} ({{ shop.shop_id }})
          </option>
        </select>
        <button 
          @click="loadShops" 
          :disabled="loadingShops"
          class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ loadingShops ? 'Đang tải...' : 'Tải danh sách' }}
        </button>
      </div>
      
      <!-- Auto load shops on mount -->
      <div class="mt-4 text-center">
        <button 
          @click="loadShops" 
          :disabled="loadingShops"
          class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
        >
          {{ loadingShops ? 'Đang tải danh sách shop...' : 'Tự động tải danh sách shop' }}
        </button>
      </div>
      
      <div class="mt-4 text-xs text-gray-400">
        Debug: currentShop = {{ currentShop }}
      </div>
    </div>

    <!-- Analytics Content -->
    <div v-else class="space-y-6">
      <!-- Analytics Tabs -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in analyticsTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            <div class="flex items-center space-x-2">
              <component :is="tab.icon" class="h-5 w-5" />
              <span>{{ tab.name }}</span>
            </div>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="mt-6">
        <!-- Shop Overview Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Tổng doanh thu</p>
                  <p class="text-2xl font-semibold text-gray-900">--</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Số đơn hàng</p>
                  <p class="text-2xl font-semibold text-gray-900">--</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Tỷ lệ chuyển đổi</p>
                  <p class="text-2xl font-semibold text-gray-900">--%</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Đánh giá trung bình</p>
                  <p class="text-2xl font-semibold text-gray-900">--</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Development Notice -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Đang phát triển</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>API phân tích tổng quan shop đang được phát triển. Các tính năng sẽ bao gồm:</p>
                  <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Tổng doanh thu</li>
                    <li>Số đơn hàng</li>
                    <li>Tỷ lệ chuyển đổi</li>
                    <li>Đánh giá trung bình</li>
                    <li>Top sản phẩm bán chạy</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Analytics Tab -->
        <div v-else-if="activeTab === 'products'" class="space-y-6">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Đang phát triển</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>API phân tích sản phẩm đang được phát triển. Các tính năng sẽ bao gồm:</p>
                  <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Hiệu suất sản phẩm</li>
                    <li>Top sản phẩm bán chạy</li>
                    <li>Sản phẩm có tỷ lệ hoàn trả cao</li>
                    <li>Phân tích giá cả</li>
                    <li>Phân tích danh mục</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Video Analytics Tab -->
        <div v-else-if="activeTab === 'videos'" class="space-y-6">
          <!-- Video Filters -->
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input 
                  v-model="videoFilters.startDate" 
                  type="date" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input 
                  v-model="videoFilters.endDate" 
                  type="date" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sắp xếp theo</label>
                <select 
                  v-model="videoFilters.sortField" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="gmv">GMV</option>
                  <option value="views">Lượt xem</option>
                  <option value="sku_orders">Đơn hàng</option>
                  <option value="units_sold">Sản phẩm bán</option>
                </select>
              </div>
              <div class="flex items-end">
                <div class="w-full text-center text-sm text-gray-500">
                  Dữ liệu sẽ tự động tải khi chọn tab
                </div>
              </div>
            </div>
          </div>

          <!-- Video Stats -->
          <div v-if="videoStats" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Tổng GMV</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(videoStats.totalGmv) }}</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Tổng lượt xem</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(videoStats.totalViews) }}</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Tổng đơn hàng</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ formatNumber(videoStats.totalOrders) }}</p>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Tỷ lệ CTR</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ videoStats.avgCtr }}%</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Debug Info -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <h4 class="font-bold text-yellow-800">Debug Video Data:</h4>
            <p class="text-sm text-yellow-700">Videos length: {{ videos.length }}</p>
            <p class="text-sm text-yellow-700">Video stats: {{ videoStats ? 'Có' : 'Không' }}</p>
            <p class="text-sm text-yellow-700">Loading: {{ loading }}</p>
            <details class="mt-2">
              <summary class="text-sm text-yellow-700 cursor-pointer">Xem videos array</summary>
              <pre class="text-xs text-yellow-600 mt-2 overflow-auto max-h-32">{{ JSON.stringify(videos, null, 2) }}</pre>
            </details>
          </div>

          <!-- Video List -->
          <div v-if="videos.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <div class="flex justify-between items-center">
                <div>
                  <h3 class="text-lg font-medium text-gray-900">Danh sách Video</h3>
                  <p class="text-sm text-gray-500">Tổng cộng {{ videoPagination.total }} video</p>
                </div>
                <div class="flex items-center space-x-4">
                  <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-500">Hiển thị:</label>
                    <select 
                      v-model="videoPagination.pageSize" 
                      @change="changePageSize(videoPagination.pageSize)"
                      class="px-2 py-1 border border-gray-300 rounded text-sm"
                    >
                      <option value="5">5</option>
                      <option value="10">10</option>
                      <option value="20">20</option>
                      <option value="50">50</option>
                    </select>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-500">Tiền tệ:</label>
                    <button
                      @click="toggleCurrency"
                      :class="[
                        'px-3 py-1 text-sm border rounded transition-colors',
                        currencySettings.showInVND 
                          ? 'bg-green-600 text-white border-green-600' 
                          : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200'
                      ]"
                    >
                      {{ currencySettings.showInVND ? 'VND' : 'USD' }}
                    </button>
                    <span class="text-xs text-gray-400">
                      (1 USD = {{ formatNumber(currencySettings.usdToVndRate) }} VND)
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GMV</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lượt xem</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn hàng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày đăng</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="video in paginatedVideos" :key="video.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                          <div class="h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">{{ video.title || 'Không có tiêu đề' }}</div>
                          <div class="text-sm text-gray-500">@{{ video.username }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatCurrency(video.gmv?.amount || 0, video.gmv?.currency || 'USD') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatNumber(video.views || 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatNumber(video.sku_orders || 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ (video.click_through_rate || 0) }}%
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(video.video_post_time) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="videoPagination.totalPages > 1" class="px-6 py-4 border-t border-gray-200">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                  Hiển thị {{ (videoPagination.currentPage - 1) * videoPagination.pageSize + 1 }} - 
                  {{ Math.min(videoPagination.currentPage * videoPagination.pageSize, videoPagination.total) }} 
                  trong tổng số {{ videoPagination.total }} video
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    @click="goToPage(videoPagination.currentPage - 1)"
                    :disabled="videoPagination.currentPage <= 1"
                    class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Trước
                  </button>
                  
                  <div class="flex space-x-1">
                    <button
                      v-for="page in Math.min(5, videoPagination.totalPages)"
                      :key="page"
                      @click="goToPage(page)"
                      :class="[
                        'px-3 py-1 text-sm border rounded',
                        videoPagination.currentPage === page
                          ? 'bg-blue-600 text-white border-blue-600'
                          : 'border-gray-300 hover:bg-gray-50'
                      ]"
                    >
                      {{ page }}
                    </button>
                  </div>
                  
                  <button
                    @click="goToPage(videoPagination.currentPage + 1)"
                    :disabled="videoPagination.currentPage >= videoPagination.totalPages"
                    class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Sau
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="!loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Không có dữ liệu video</h3>
            <p class="mt-1 text-sm text-gray-500">Không tìm thấy video nào trong khoảng thời gian đã chọn</p>
          </div>
        </div>

        <!-- Live Analytics Tab -->
        <div v-else-if="activeTab === 'live'" class="space-y-6">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Đang phát triển</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>API phân tích live đang được phát triển. Các tính năng sẽ bao gồm:</p>
                  <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Hiệu suất live stream</li>
                    <li>Live có lượt xem cao nhất</li>
                    <li>Live có doanh thu cao nhất</li>
                    <li>Phân tích thời gian live</li>
                    <li>Phân tích tương tác live</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch, computed } from 'vue'

export default {
  name: 'ShopAnalytics',
  setup() {
    const loading = ref(false)
    const currentShop = ref(null)
    const activeTab = ref('overview')
    
    // Video analytics data
    const videos = ref([])
    const videoStats = ref(null)
    const videoFilters = ref({
      startDate: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      endDate: new Date().toISOString().split('T')[0],
      sortField: 'gmv'
    })
    
    // Video Pagination
    const videoPagination = ref({
      currentPage: 1,
      pageSize: 10,
      total: 0,
      totalPages: 0
    })
    
    // Currency conversion
    const currencySettings = ref({
      showInVND: true,
      usdToVndRate: 25000 // 1 USD = 25,000 VND (có thể cập nhật từ API)
    })
    
    // Shop selection
    const availableShops = ref([])
    const selectedShopId = ref('')
    const loadingShops = ref(false)

    // Computed properties
    const currentShopName = computed(() => {
      return currentShop.value?.shop_name || 'Chưa chọn shop'
    })
    
    // Paginated videos
    const paginatedVideos = computed(() => {
      const start = (videoPagination.value.currentPage - 1) * videoPagination.value.pageSize
      const end = start + videoPagination.value.pageSize
      return videos.value.slice(start, end)
    })

    const analyticsTabs = [
      {
        id: 'overview',
        name: 'Tổng quan Shop',
        icon: 'svg'
      },
      {
        id: 'products',
        name: 'Phân tích Sản phẩm',
        icon: 'svg'
      },
      {
        id: 'videos',
        name: 'Phân tích Video',
        icon: 'svg'
      },
      {
        id: 'live',
        name: 'Phân tích Live',
        icon: 'svg'
      }
    ]

    const getCurrentShop = () => {
      const selectedShop = localStorage.getItem('selectedShop')
      if (selectedShop) {
        try {
          const parsed = JSON.parse(selectedShop)
          return parsed
        } catch (e) {
          console.error('Lỗi khi parse selectedShop:', e)
          return null
        }
      }
      return null
    }

    const loadCurrentShop = () => {
      currentShop.value = getCurrentShop()
    }

    const loadShops = async () => {
      loadingShops.value = true
      try {
        const response = await fetch('/api/shops')
        const data = await response.json()
        
        if (data.success) {
          availableShops.value = data.data || []
        } else {
          console.error('Lỗi khi tải danh sách shop:', data.error)
        }
      } catch (error) {
        console.error('Lỗi khi tải danh sách shop:', error)
      } finally {
        loadingShops.value = false
      }
    }

    const selectShop = () => {
      if (selectedShopId.value) {
        const shop = availableShops.value.find(s => s.shop_id === selectedShopId.value)
        if (shop) {
          currentShop.value = shop
          localStorage.setItem('selectedShop', JSON.stringify(shop))
          
          // Auto load video data if on videos tab
          if (activeTab.value === 'videos') {
            loadVideoAnalytics()
          }
        }
      }
    }

    const loadVideoAnalytics = async () => {
      if (!currentShop.value) return
      
      loading.value = true
      try {
        const params = new URLSearchParams({
          shop_id: currentShop.value.shop_id,
          start_date_ge: videoFilters.value.startDate,
          end_date_lt: videoFilters.value.endDate,
          sort_field: videoFilters.value.sortField,
          sort_order: 'DESC',
          page_size: 50
        })
        
        const response = await fetch(`/api/analytics/videos?${params}`)
        const data = await response.json()
        
        if (data.ok) {
          videos.value = data.data?.data?.data?.videos || []
          
          // Update pagination info
          videoPagination.value.total = videos.value.length
          videoPagination.value.totalPages = Math.ceil(videos.value.length / videoPagination.value.pageSize)
          videoPagination.value.currentPage = 1
          
          calculateVideoStats()
        } else {
          console.error('Lỗi khi tải dữ liệu video:', data.error)
        }
        
      } catch (error) {
        console.error('Lỗi khi tải dữ liệu video:', error)
      } finally {
        loading.value = false
      }
    }

    const calculateVideoStats = () => {
      if (videos.value.length === 0) {
        videoStats.value = null
        return
      }

      const totalGmv = videos.value.reduce((sum, video) => {
        const usdAmount = parseFloat(video.gmv?.amount || 0)
        return sum + (currencySettings.value.showInVND ? convertToVND(usdAmount) : usdAmount)
      }, 0)
      const totalViews = videos.value.reduce((sum, video) => sum + (video.views || 0), 0)
      const totalOrders = videos.value.reduce((sum, video) => sum + (video.sku_orders || 0), 0)
      const avgCtr = videos.value.length > 0 
        ? (videos.value.reduce((sum, video) => sum + parseFloat(video.click_through_rate || 0), 0) / videos.value.length).toFixed(2)
        : 0

      videoStats.value = {
        totalGmv,
        totalViews,
        totalOrders,
        avgCtr
      }
      
    }

    // Pagination methods
    const goToPage = (page) => {
      if (page >= 1 && page <= videoPagination.value.totalPages) {
        videoPagination.value.currentPage = page
      }
    }

    const changePageSize = (size) => {
      videoPagination.value.pageSize = size
      videoPagination.value.currentPage = 1
      videoPagination.value.totalPages = Math.ceil(videos.value.length / size)
    }

    const refreshData = async () => {
      if (!currentShop.value) return
      
      if (activeTab.value === 'videos') {
        await loadVideoAnalytics()
      } else {
        // TODO: Implement other tabs
      }
    }

    const formatCurrency = (amount, currency = 'USD') => {
      if (!amount) return '0'
      const numAmount = parseFloat(amount)
      
      if (currencySettings.value.showInVND && currency === 'USD') {
        const vndAmount = numAmount * currencySettings.value.usdToVndRate
        return new Intl.NumberFormat('vi-VN', {
          style: 'currency',
          currency: 'VND'
        }).format(vndAmount)
      }
      
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: currency
      }).format(numAmount)
    }
    
    const convertToVND = (usdAmount) => {
      if (!usdAmount) return 0
      return parseFloat(usdAmount) * currencySettings.value.usdToVndRate
    }
    
    const toggleCurrency = () => {
      currencySettings.value.showInVND = !currencySettings.value.showInVND
    }

    const formatNumber = (number) => {
      return new Intl.NumberFormat('vi-VN').format(number)
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('vi-VN')
    }

    // Watch for shop changes
    watch(() => localStorage.getItem('selectedShop'), () => {
      loadCurrentShop()
    })

    // Watch for tab changes to auto-load data
    watch(activeTab, (newTab) => {
      if (newTab === 'videos' && currentShop.value) {
        loadVideoAnalytics()
      }
    })

    onMounted(() => {
      loadCurrentShop()
      // Auto load shops on mount
      loadShops()
    })

    return {
      loading,
      currentShop,
      activeTab,
      analyticsTabs,
      videos,
      videoStats,
      videoFilters,
      videoPagination,
      paginatedVideos,
      availableShops,
      selectedShopId,
      loadingShops,
      refreshData,
      loadVideoAnalytics,
      loadShops,
      selectShop,
      goToPage,
      changePageSize,
      currencySettings,
      convertToVND,
      toggleCurrency,
      formatCurrency,
      formatNumber,
      formatDate
    }
  }
}
</script>
