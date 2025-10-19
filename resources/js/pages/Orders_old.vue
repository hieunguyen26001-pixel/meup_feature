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
            <div class="relative">
              <div class="flex items-center border border-gray-300 rounded-lg bg-white shadow-sm hover:border-gray-400 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500 transition-all duration-200">
                <div class="flex items-center px-4 py-2.5">
                  <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <span class="text-sm text-gray-600 mr-2">Từ:</span>
                  <input 
                    v-model="startDate" 
                    type="date" 
                    class="text-sm text-gray-900 bg-transparent border-none outline-none focus:ring-0 p-0"
                    placeholder="Chọn ngày bắt đầu"
                  />
                </div>
                
                <div class="flex items-center px-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                  </svg>
                </div>
                
                <div class="flex items-center px-4 py-2.5">
                  <span class="text-sm text-gray-600 mr-2">Đến:</span>
                  <input 
                    v-model="endDate" 
                    type="date" 
                    class="text-sm text-gray-900 bg-transparent border-none outline-none focus:ring-0 p-0"
                    placeholder="Chọn ngày kết thúc"
                  />
                </div>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
              <button 
                @click="applyDateRange"
                :disabled="loading"
                class="px-6 py-2.5 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg text-sm font-medium shadow-sm transition-colors duration-200 flex items-center"
              >
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                {{ loading ? 'Đang tải...' : 'Tìm kiếm' }}
              </button>
              
              <button 
                @click="resetDateRange"
                class="px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-medium shadow-sm transition-colors duration-200 flex items-center"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reset
              </button>
            </div>
          </div>
        </div>

        <!-- Quick Date Presets -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Khoảng Thời Gian Nhanh</h4>
          <div class="flex flex-wrap gap-2">
            <button 
              @click="setDateRange('today')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              Hôm nay
            </button>
            <button 
              @click="setDateRange('yesterday')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              Hôm qua
            </button>
            <button 
              @click="setDateRange('7days')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              7 ngày qua
            </button>
            <button 
              @click="setDateRange('30days')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              30 ngày qua
            </button>
            <button 
              @click="setDateRange('90days')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              90 ngày qua
            </button>
            <button 
              @click="setDateRange('thisMonth')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              Tháng này
            </button>
            <button 
              @click="setDateRange('lastMonth')"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors duration-200"
            >
              Tháng trước
            </button>
          </div>
        </div>

        <!-- Sort Options -->
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <label class="text-sm font-medium text-gray-600">Sắp xếp theo:</label>
              <select 
                v-model="sortField" 
                @change="applySort"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="create_time">Ngày tạo</option>
                <option value="update_time">Ngày cập nhật</option>
                <option value="paid_time">Ngày thanh toán</option>
                <option value="status">Trạng thái</option>
                <option value="total_amount">Tổng tiền</option>
              </select>
              <select 
                v-model="sortDirection" 
                @change="applySort"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="desc">Giảm dần</option>
                <option value="asc">Tăng dần</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Dashboard -->
    <div class="mb-6">
      <OrderKPIDashboard :orders="orders" />
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Danh Sách Đơn Hàng</h3>
            <p class="text-sm text-gray-500">
              Hiển thị {{ startIndex }} - {{ endIndex }} trong {{ filteredOrdersCount }} đơn hàng
              <span v-if="selectedShop" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                Shop: {{ selectedShopName || selectedShop }}
              </span>
              <span v-if="selectedStatus" class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                {{ getStatusText(selectedStatus) }}
              </span>
            </p>
          </div>
          <button @click="loadOrders" 
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

      <div v-else-if="orders.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Không có đơn hàng nào</h3>
        <p class="mt-1 text-sm text-gray-500">Không có dữ liệu đơn hàng từ TikTok Shop.</p>
        <button @click="loadOrders" 
                class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
          Thử lại
        </button>
      </div>

      <div v-else class="relative">
        <!-- Loading overlay for sorting -->
        <div v-if="tableLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
          <div class="text-center">
            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm text-gray-600">Đang sắp xếp...</p>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button @click="sort('id')" class="flex items-center space-x-1 hover:text-gray-700">
                    <span>Mã đơn hàng</span>
                    <component :is="getSortIcon('id')" class="h-4 w-4" />
                  </button>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button @click="sort('status')" class="flex items-center space-x-1 hover:text-gray-700">
                    <span>Trạng thái</span>
                    <component :is="getSortIcon('status')" class="h-4 w-4" />
                  </button>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button @click="sort('total_amount')" class="flex items-center space-x-1 hover:text-gray-700">
                    <span>Tổng tiền</span>
                    <component :is="getSortIcon('total_amount')" class="h-4 w-4" />
                  </button>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button @click="sort('create_time')" class="flex items-center space-x-1 hover:text-gray-700">
                    <span>Ngày tạo</span>
                    <component :is="getSortIcon('create_time')" class="h-4 w-4" />
                  </button>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Khách hàng
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Sản phẩm
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Thao tác
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="order in paginatedOrders" :key="order.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ order.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(order.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ getStatusText(order.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" v-html="formatCurrency(order.payment?.total_amount || 0)">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(order.create_time) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div>
                    <div class="font-medium">{{ order.recipient_address?.name || 'N/A' }}</div>
                    <div class="text-gray-500">{{ order.recipient_address?.phone_number || 'N/A' }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="max-w-xs">
                    <div v-for="item in order.line_items?.slice(0, 2)" :key="item.id" class="truncate">
                      {{ item.product_name }}
                    </div>
                    <div v-if="order.line_items?.length > 2" class="text-gray-500 text-xs">
                      +{{ order.line_items.length - 2 }} sản phẩm khác
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="viewOrder(order)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Xem chi tiết
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Smart Pagination -->
      <div v-if="totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <!-- TailAdmin Pagination -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Info -->
          <div class="flex items-center space-x-4">
            <p class="text-sm text-gray-700">
              Hiển thị <span class="font-medium">{{ startIndex }}</span> - <span class="font-medium">{{ endIndex }}</span> 
              trong <span class="font-medium">{{ filteredOrdersCount }}</span> đơn hàng
              <span v-if="selectedStatus" class="ml-2 text-green-600 font-medium">
                ({{ getStatusText(selectedStatus) }})
              </span>
            </p>
            <div class="flex items-center space-x-2">
              <label class="text-sm text-gray-600">Hiển thị:</label>
              <select 
                v-model="itemsPerPage" 
                @change="currentPage = 1"
                class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
              </select>
            </div>
          </div>
          
          <!-- TailAdmin Pagination -->
          <nav aria-label="Page navigation" v-if="totalPages > 1">
            <ul class="inline-flex -space-x-px text-sm">
              <!-- Previous -->
              <li>
                <button
                  @click="goToPage(currentPage - 1)"
                  :disabled="currentPage === 1"
                  class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Trước
                </button>
              </li>
              
              <!-- Page Numbers -->
              <template v-if="totalPages <= 7">
                <li v-for="page in totalPages" :key="page">
                  <button
                    @click="goToPage(page)"
                    :class="[
                      page === currentPage
                        ? 'flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700'
                        : 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'
                    ]"
                  >
                    {{ page }}
                  </button>
                </li>
              </template>
              
              <template v-else>
                <!-- First page -->
                <li>
                  <button
                    @click="goToPage(1)"
                    :class="[
                      1 === currentPage
                        ? 'flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700'
                        : 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'
                    ]"
                  >
                    1
                  </button>
                </li>
                
                <!-- Ellipsis -->
                <li v-if="currentPage > 4">
                  <span class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300">
                    ...
                  </span>
                </li>
                
                <!-- Current page and neighbors -->
                <template v-if="currentPage > 1 && currentPage < totalPages">
                  <li v-for="page in getPageRange()" :key="page">
                    <button
                      @click="goToPage(page)"
                      :class="[
                        page === currentPage
                          ? 'flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700'
                          : 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'
                      ]"
                    >
                      {{ page }}
                    </button>
                  </li>
                </template>
                
                <!-- Ellipsis -->
                <li v-if="currentPage < totalPages - 3">
                  <span class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300">
                    ...
                  </span>
                </li>
                
                <!-- Last page -->
                <li>
                  <button
                    @click="goToPage(totalPages)"
                    :class="[
                      totalPages === currentPage
                        ? 'flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700'
                        : 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'
                    ]"
                  >
                    {{ totalPages }}
                  </button>
                </li>
              </template>
              
              <!-- Next -->
              <li>
                <button
                  @click="goToPage(currentPage + 1)"
                  :disabled="currentPage === totalPages"
                  class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Tiếp
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Order Details Modal -->
    <div v-if="showOrderModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showOrderModal = false"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                    Chi tiết đơn hàng #{{ selectedOrder?.id }}
                  </h3>
                  <button @click="showOrderModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
                
                <!-- Loading State -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                  <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-600">Đang tải chi tiết đơn hàng...</p>
                  </div>
                </div>
                
                <!-- Order Details -->
                <div v-else-if="selectedOrder" class="space-y-6">
                  <!-- Order Status & Basic Info -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <h4 class="font-medium text-gray-900 mb-2">Thông tin cơ bản</h4>
                      <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Trạng thái:</span> 
                          <span :class="getStatusClass(selectedOrder.status)" class="px-2 py-1 rounded-full text-xs">
                            {{ getStatusText(selectedOrder.status) }}
                          </span>
                        </div>
                        <div><span class="font-medium">Ngày tạo:</span> {{ formatDate(selectedOrder.create_time) }}</div>
                        <div><span class="font-medium">Ngày cập nhật:</span> {{ formatDate(selectedOrder.update_time) }}</div>
                        <div><span class="font-medium">Mã đơn hàng:</span> {{ selectedOrder.id }}</div>
                      </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <h4 class="font-medium text-gray-900 mb-2">Thông tin thanh toán</h4>
                      <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Tổng tiền:</span> <span v-html="formatPrice(selectedOrder.payment?.total_amount)"></span></div>
                        <div><span class="font-medium">Tiền hàng:</span> <span v-html="formatPrice(selectedOrder.payment?.sub_total)"></span></div>
                        <div><span class="font-medium">Phí vận chuyển:</span> <span v-html="formatPrice(selectedOrder.payment?.shipping_fee)"></span></div>
                        <div><span class="font-medium">Giảm giá:</span> <span v-html="formatPrice(selectedOrder.payment?.seller_discount)"></span></div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Shipping Info -->
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-2">Thông tin giao hàng</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                      <div>
                        <div><span class="font-medium">Người nhận:</span> {{ selectedOrder.recipient_address?.name }}</div>
                        <div><span class="font-medium">Số điện thoại:</span> {{ selectedOrder.recipient_address?.phone_number }}</div>
                        <div><span class="font-medium">Địa chỉ:</span> {{ selectedOrder.recipient_address?.full_address }}</div>
                      </div>
                      <div>
                        <div><span class="font-medium">Mã vận đơn:</span> {{ selectedOrder.tracking_number || 'Chưa có' }}</div>
                        <div><span class="font-medium">Đơn vị vận chuyển:</span> {{ selectedOrder.shipping_provider || 'Chưa có' }}</div>
                        <div><span class="font-medium">Email người mua:</span> {{ selectedOrder.buyer_email || 'Chưa có' }}</div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Order Items -->
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-2">Sản phẩm trong đơn hàng</h4>
                    <div class="space-y-3">
                      <div v-for="item in selectedOrder.line_items" :key="item.id" class="flex items-center space-x-4 p-3 bg-white rounded border">
                        <img v-if="item.sku_image" :src="item.sku_image" :alt="item.product_name" class="w-16 h-16 object-cover rounded">
                        <div class="flex-1">
                          <div class="font-medium text-gray-900">{{ item.product_name }}</div>
                          <div class="text-sm text-gray-500">SKU: {{ item.seller_sku }}</div>
                          <div class="text-sm text-gray-500">Số lượng: {{ item.sku_count || 1 }}</div>
                        </div>
                        <div class="text-right">
                          <div class="font-medium" v-html="formatPrice(item.sale_price)"></div>
                          <div class="text-sm text-gray-500">Trạng thái: {{ getStatusText(item.display_status) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Additional Info -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <h4 class="font-medium text-gray-900 mb-2">Thông tin bổ sung</h4>
                      <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Ghi chú người mua:</span> {{ selectedOrder.buyer_message || 'Không có' }}</div>
                        <div><span class="font-medium">Ghi chú người bán:</span> {{ selectedOrder.seller_note || 'Không có' }}</div>
                        <div><span class="font-medium">Loại đơn hàng:</span> {{ selectedOrder.order_type || 'Thường' }}</div>
                        <div><span class="font-medium">Hình thức giao hàng:</span> {{ selectedOrder.delivery_type || 'Giao tận nhà' }}</div>
                      </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <h4 class="font-medium text-gray-900 mb-2">Thời gian</h4>
                      <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Thời gian thanh toán:</span> {{ selectedOrder.paid_time ? formatDate(selectedOrder.paid_time) : 'Chưa thanh toán' }}</div>
                        <div><span class="font-medium">Thời gian giao hàng:</span> {{ selectedOrder.delivery_time ? formatDate(selectedOrder.delivery_time) : 'Chưa giao' }}</div>
                        <div><span class="font-medium">Thời gian hủy:</span> {{ selectedOrder.cancel_time ? formatDate(selectedOrder.cancel_time) : 'Không' }}</div>
                        <div><span class="font-medium">Lý do hủy:</span> {{ selectedOrder.cancel_reason || 'Không có' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Empty State -->
                <div v-else class="flex items-center justify-center py-12">
                  <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Không có dữ liệu</h3>
                    <p class="mt-1 text-sm text-gray-500">Không thể tải chi tiết đơn hàng</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button @click="showOrderModal = false" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
              Đóng
            </button>
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
const startDate = ref('')
const endDate = ref('')
const customDateRange = ref(false)

// Status filter
const selectedStatus = ref('')
const allOrders = ref([])

// Removed advanced filters as requested

// Order modal
const showOrderModal = ref(false)
const selectedOrder = ref(null)

// Computed properties
const filteredOrders = computed(() => {
  let filtered = [...orders.value]
  
  // Status filter (if needed)
  if (selectedStatus.value) {
    filtered = filtered.filter(order => order.status === selectedStatus.value)
  }
  
  // Apply sorting
  filtered.sort((a, b) => {
    const aValue = getFieldValue(a, sortField.value)
    const bValue = getFieldValue(b, sortField.value)
    
    if (sortDirection.value === 'asc') {
      return aValue > bValue ? 1 : -1
    } else {
      return aValue < bValue ? 1 : -1
    }
  })
  
  return filtered
})

const filteredOrdersCount = computed(() => filteredOrders.value.length)

const totalPages = computed(() => Math.ceil(filteredOrdersCount.value / itemsPerPage.value))

const paginatedOrders = computed(() => {
  // Apply pagination
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredOrders.value.slice(start, end)
})

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1)
const endIndex = computed(() => Math.min(currentPage.value * itemsPerPage.value, filteredOrdersCount.value))

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
  loading.value = true
  try {
    // Get shop from localStorage
    const shopData = localStorage.getItem('selectedShop')
    if (shopData) {
      const shop = JSON.parse(shopData)
      selectedShop.value = shop.shop_id
      selectedShopName.value = shop.shop_name
    }
    
    const params = selectedShop.value ? { shop_id: selectedShop.value } : {}
    
    // Add date range parameters if custom range is selected
    if (customDateRange.value && startDate.value && endDate.value) {
      params.start_date = startDate.value
      params.end_date = endDate.value
    }
    
    const response = await axios.get('/api/orders', { params })
    
    if (response.data.success) {
      orders.value = response.data.data.data.orders || []
      totalOrders.value = response.data.data.data.total_count || 0
    } else {
      console.error('Không thể tải đơn hàng:', response.data.error)
    }
  } catch (error) {
    console.error('Lỗi khi tải đơn hàng:', error)
  } finally {
    loading.value = false
  }
}

const applyDateRange = () => {
  if (startDate.value && endDate.value) {
    customDateRange.value = true
    loadOrders()
  } else {
    alert('Vui lòng chọn cả ngày bắt đầu và ngày kết thúc')
  }
}

const resetDateRange = () => {
  customDateRange.value = false
  startDate.value = ''
  endDate.value = ''
  loadOrders()
}

const formatDateForInput = (date) => {
  return new Date(date).toISOString().split('T')[0]
}

const applyFilters = () => {
  currentPage.value = 1 // Reset to first page when filtering
}

const resetFilters = () => {
  selectedStatus.value = ''
  currentPage.value = 1
}

const setDateRange = (preset) => {
  const today = new Date()
  const todayStr = today.toISOString().split('T')[0]
  
  switch (preset) {
    case 'today':
      startDate.value = todayStr
      endDate.value = todayStr
      break
    case 'yesterday':
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)
      const yesterdayStr = yesterday.toISOString().split('T')[0]
      startDate.value = yesterdayStr
      endDate.value = yesterdayStr
      break
    case '7days':
      const weekAgo = new Date(today)
      weekAgo.setDate(weekAgo.getDate() - 7)
      startDate.value = weekAgo.toISOString().split('T')[0]
      endDate.value = todayStr
      break
    case '30days':
      const monthAgo = new Date(today)
      monthAgo.setDate(monthAgo.getDate() - 30)
      startDate.value = monthAgo.toISOString().split('T')[0]
      endDate.value = todayStr
      break
    case '90days':
      const quarterAgo = new Date(today)
      quarterAgo.setDate(quarterAgo.getDate() - 90)
      startDate.value = quarterAgo.toISOString().split('T')[0]
      endDate.value = todayStr
      break
    case 'thisMonth':
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
      startDate.value = firstDay.toISOString().split('T')[0]
      endDate.value = todayStr
      break
    case 'lastMonth':
      const lastMonthFirst = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      const lastMonthLast = new Date(today.getFullYear(), today.getMonth(), 0)
      startDate.value = lastMonthFirst.toISOString().split('T')[0]
      endDate.value = lastMonthLast.toISOString().split('T')[0]
      break
  }
  
  // Auto apply the date range
  applyDateRange()
}

const applySort = () => {
  currentPage.value = 1 // Reset to first page when sorting
}

const getPageRange = () => {
  const current = currentPage.value
  const total = totalPages.value
  
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  
  const range = []
  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)
  
  for (let i = start; i <= end; i++) {
    range.push(i)
  }
  
  return range
}

const sort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
  
  currentPage.value = 1
  tableLoading.value = true
  
  // Simulate loading
  setTimeout(() => {
    tableLoading.value = false
  }, 300)
}

const getFieldValue = (order, field) => {
  switch (field) {
    case 'id':
      return order.id || ''
    case 'status':
      return order.status || ''
    case 'total_amount':
      return parseFloat(order.payment?.total_amount) || 0
    case 'create_time':
      return order.create_time || 0
    default:
      return ''
  }
}

const getSortIcon = (field) => {
  if (sortField.value !== field) {
    return 'svg'
  }
  
  if (sortDirection.value === 'asc') {
    return 'svg'
  } else {
    return 'svg'
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const formatCurrency = (amount) => {
  if (!amount) return '0<sup>đ</sup>'
  return new Intl.NumberFormat('vi-VN').format(amount) + '<sup>đ</sup>'
}

const formatDate = (timestamp) => {
  if (!timestamp) return 'N/A'
  return new Date(timestamp * 1000).toLocaleDateString('vi-VN')
}

const getStatusText = (status) => {
  const statusMap = {
    'UNPAID': 'Chưa thanh toán',
    'ON_HOLD': 'Tạm giữ',
    'AWAITING_SHIPMENT': 'Chờ vận chuyển',
    'PARTIALLY_SHIPPING': 'Giao một phần',
    'AWAITING_COLLECTION': 'Chờ thu gom',
    'IN_TRANSIT': 'Đang vận chuyển',
    'DELIVERED': 'Đã giao hàng',
    'COMPLETED': 'Hoàn thành',
    'CANCELLED': 'Đã hủy'
  }
  return statusMap[status] || status
}

const getStatusClass = (status) => {
  const classMap = {
    'UNPAID': 'bg-yellow-100 text-yellow-800',
    'ON_HOLD': 'bg-orange-100 text-orange-800',
    'AWAITING_SHIPMENT': 'bg-blue-100 text-blue-800',
    'PARTIALLY_SHIPPING': 'bg-indigo-100 text-indigo-800',
    'AWAITING_COLLECTION': 'bg-purple-100 text-purple-800',
    'IN_TRANSIT': 'bg-cyan-100 text-cyan-800',
    'DELIVERED': 'bg-green-100 text-green-800',
    'COMPLETED': 'bg-emerald-100 text-emerald-800',
    'CANCELLED': 'bg-red-100 text-red-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const formatPrice = (price) => {
  if (!price) return '0<sup>đ</sup>'
  const numPrice = parseFloat(price)
  if (isNaN(numPrice)) return '0<sup>đ</sup>'
  return new Intl.NumberFormat('vi-VN').format(numPrice) + '<sup>đ</sup>'
}

const viewOrder = async (order) => {
  try {
    // Mở modal ngay lập tức với loading state
    showOrderModal.value = true
    selectedOrder.value = null // Clear data cũ
    loading.value = true
    
    const response = await axios.get('/api/orders/details', {
      params: {
        order_id: order.id,
        shop_id: selectedShop.value
      }
    })
    
    // Debug log để xem response structure
    
    if (response.data.success) {
      // Kiểm tra xem có orders không và orders có dữ liệu không
      // Có 2 lớp data: response.data.data.data.orders
      if (response.data.data && response.data.data.data && response.data.data.data.orders && response.data.data.data.orders.length > 0) {
        selectedOrder.value = response.data.data.data.orders[0]
      } else {
        alert('Không tìm thấy chi tiết đơn hàng')
        showOrderModal.value = false
      }
    } else {
      alert('Lỗi khi tải chi tiết đơn hàng: ' + response.data.error)
      showOrderModal.value = false // Đóng modal nếu lỗi
    }
  } catch (error) {
    console.error('Lỗi khi tải chi tiết đơn hàng:', error)
    alert('Lỗi khi tải chi tiết đơn hàng')
    showOrderModal.value = false // Đóng modal nếu lỗi
  } finally {
    loading.value = false
  }
}

// Watch for shop changes
watch(() => localStorage.getItem('selectedShop'), () => {
  loadOrders()
})

// Lifecycle
onMounted(() => {
  // Set default date range (7 days ago to today)
  const today = new Date()
  const sevenDaysAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
  
  endDate.value = formatDateForInput(today)
  startDate.value = formatDateForInput(sevenDaysAgo)
  
  loadOrders()
})
</script>