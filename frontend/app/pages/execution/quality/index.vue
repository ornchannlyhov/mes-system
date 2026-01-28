<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Quality Assurance</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Review production quality records</p>
      </div>
      <div class="flex gap-2">
        <button class="btn-primary" @click="fetchData">
          <Icon name="heroicons:arrow-path" class="w-4 h-4" />
          <span class="hidden sm:inline">Refresh</span>
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card bg-green-50 border-green-100">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-green-100 text-green-600 rounded-lg">
            <Icon name="heroicons:check-badge" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-green-800">Passed</p>
            <p class="text-2xl font-bold text-green-900">{{ stats.pass }}</p>
          </div>
        </div>
      </div>
      <div class="card bg-red-50 border-red-100">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-red-100 text-red-600 rounded-lg">
            <Icon name="heroicons:x-circle" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-red-800">Failed</p>
            <p class="text-2xl font-bold text-red-900">{{ stats.fail }}</p>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-blue-100 text-blue-600 rounded-lg">
            <Icon name="heroicons:clipboard-document-check" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600">Total Checks</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="flex gap-4">
      <div class="flex-1">
         <input v-model="search" type="text" placeholder="Search by Manufacturing Order, Product..." class="input" />
      </div>
      <div class="w-64">
         <select v-model="filterStatus" class="input">
            <option value="">All Statuses</option>
            <option value="pass">Passed</option>
            <option value="fail">Failed</option>
         </select>
      </div>
    </div>

    <!-- List -->
    <div class="card p-0 overflow-hidden">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Manufacturing Order</th>
            <th>Operation</th>
            <th>Result</th>
            <th>Comments</th>
            <th>User</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in filteredRecords" :key="record.id">
            <td>{{ formatDate(record.qa_at || record.finished_at) }}</td>
            <td class="font-medium">
               <div>{{ record.manufacturing_order?.name || 'N/A' }}</div>
               <div class="text-xs text-gray-500">{{ record.manufacturing_order?.product?.name }}</div>
            </td>
            <td>{{ record.operation?.name }}</td>
            <td>
              <span v-if="record.qa_status === 'pass'" class="badge badge-success">
                 <Icon name="heroicons:check" class="w-3 h-3 mr-1" /> Pass
              </span>
              <span v-else-if="record.qa_status === 'fail'" class="badge badge-error">
                  <Icon name="heroicons:x-mark" class="w-3 h-3 mr-1" /> Fail
              </span>
            </td>
            <td class="max-w-xs truncate" :title="record.qa_comments">{{ record.qa_comments || '-' }}</td>
            <td>{{ record.qa_user?.name || record.assigned_user?.name || 'N/A' }}</td>
          </tr>
           <tr v-if="filteredRecords.length === 0">
              <td colspan="6" class="text-center py-8 text-gray-500">No QA records found</td>
           </tr>
        </tbody>
      </table>
    </div>
     <UiPagination
      v-if="records.length > pageSize"
      v-model="currentPage"
      :total-items="records.length"
      :page-size="pageSize"
    />
  </div>
</template>

<script setup lang="ts">
import type { WorkOrder } from '~/types/models'

const { $api } = useApi()
const { formatDate } = useUtils()

const records = ref<WorkOrder[]>([])
const filterStatus = ref('')
const search = ref('')
const currentPage = ref(1)
const pageSize = 15

const stats = computed(() => {
    const pass = records.value.filter(r => r.qa_status === 'pass').length
    const fail = records.value.filter(r => r.qa_status === 'fail').length
    return {
        pass,
        fail,
        total: pass + fail
    }
})

const filteredRecords = computed(() => {
    let result = records.value
    
    // Status Filter
    if (filterStatus.value) {
        result = result.filter(r => r.qa_status === filterStatus.value)
    }

    // Search
    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(r => 
            r.manufacturing_order?.name.toLowerCase().includes(q) ||
            r.manufacturing_order?.product?.name.toLowerCase().includes(q) ||
            r.operation?.name.toLowerCase().includes(q) ||
            (r.qa_comments || '').toLowerCase().includes(q)
        )
    }

    // Pagination
    const start = (currentPage.value - 1) * pageSize
    return result.slice(start, start + pageSize)
})

async function fetchData() {
    try {
        // Fetch all completed work orders
        const res = await $api<{ data: WorkOrder[] }>('/work-orders', { query: { status: 'done', per_page: 100 }})
        // Filter locally for those with QA status
        records.value = (res.data || []).filter(wo => wo.qa_status && wo.qa_status !== 'pending')
    } catch (e) {
        // quiet fail
    }
}

onMounted(fetchData)
</script>
