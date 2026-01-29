<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Quality Assurance</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Review production quality records</p>
      </div>
      <div class="flex gap-2">
        <button class="btn-primary" @click="refresh">
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
         <select v-model="filters.qa_status" class="input">
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
          <tr v-for="record in records" :key="record.id">
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
           <tr v-if="records.length === 0 && !loading">
              <td colspan="6" class="text-center py-8 text-gray-500">No QA records found</td>
           </tr>
        </tbody>
        <tbody v-if="loading">
             <tr v-for="i in 5" :key="`skel-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4">
                    <div class="h-4 bg-gray-200 rounded w-48 mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-24"></div>
                </td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
             </tr>
        </tbody>
      </table>
    </div>
     <UiPagination
      v-if="Math.ceil(total / perPage) > 1"
      v-model="page"
      :total-items="total"
      :page-size="perPage"
    />
  </div>
</template>

<script setup lang="ts">
import { useServerDataTable } from '~/composables/useServerDataTable'
import type { WorkOrder } from '~/types/models'

const { $api } = useApi()
const { formatDate } = useUtils()

// Data Table
const { 
  items: records, 
  total, 
  loading, 
  page, 
  perPage, 
  search, 
  filters, 
  refresh 
} = useServerDataTable<WorkOrder>({
  url: 'work-orders',
  perPage: 15,
  initialFilters: { status: 'done', has_qa: 'true', qa_status: '' }
})

const stats = ref<{ pass: number, fail: number, total: number }>({ pass: 0, fail: 0, total: 0 })

async function fetchStats() {
    try {
        const res = await $api<any>('/work-orders', { query: { per_page: 1 } })
        if (res.qa_counts || res.meta?.qa_counts) {
            const counts = res.qa_counts || res.meta?.qa_counts
            stats.value = {
                pass: counts.pass || 0,
                fail: counts.fail || 0,
                total: (counts.pass || 0) + (counts.fail || 0)
            }
        }
    } catch (e) {
        // silent fail
    }
}

onMounted(() => {
    fetchStats()
})
</script>
