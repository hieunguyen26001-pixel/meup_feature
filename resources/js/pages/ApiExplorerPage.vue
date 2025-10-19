<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">API Explorer</h1>
            <p class="text-sm text-gray-600">Browse and test all available API endpoints</p>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <ApiExplorer @test-endpoint="handleTestEndpoint" />
    </div>

    <!-- Test Modal -->
    <div v-if="showTestModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeTestModal"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Test API Endpoint</h3>
              <button @click="closeTestModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <div class="space-y-4">
              <div class="flex items-center space-x-4">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getMethodClass(selectedEndpoint?.method)"
                >
                  {{ selectedEndpoint?.method }}
                </span>
                <span class="font-mono text-sm text-gray-900">{{ selectedEndpoint?.path }}</span>
              </div>
              
              <div v-if="selectedEndpoint?.description">
                <p class="text-sm text-gray-600">{{ selectedEndpoint.description }}</p>
              </div>
              
              <div class="flex space-x-3">
                <button
                  @click="copyEndpoint"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  Copy URL
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import ApiExplorer from '../components/ApiExplorer.vue'

const router = useRouter()
const showTestModal = ref(false)
const selectedEndpoint = ref(null)

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

const handleTestEndpoint = (endpoint) => {
  selectedEndpoint.value = endpoint
  showTestModal.value = true
}

const closeTestModal = () => {
  showTestModal.value = false
  selectedEndpoint.value = null
}


const copyEndpoint = async () => {
  const baseUrl = import.meta.env.VITE_APP_URL || 'http://localhost:8000'
  const fullUrl = `${baseUrl}${selectedEndpoint.value.path}`
  
  try {
    await navigator.clipboard.writeText(fullUrl)
    // You could add a toast notification here
    alert('URL copied to clipboard!')
  } catch (err) {
    console.error('Failed to copy URL:', err)
  }
}
</script>
