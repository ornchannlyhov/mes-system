<template>
  <div class="space-y-6">

    <!-- Cost Type Summary -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
      <div class="card">
        <p class="text-sm text-gray-500 uppercase">Material</p>
        <p class="text-2xl font-bold text-blue-600 mt-2">${{ costByType.material.toFixed(2) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-500 uppercase">Overhead</p>
        <p class="text-2xl font-bold text-amber-600 mt-2">${{ costByType.overhead.toFixed(2) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-500 uppercase">Labor</p>
        <p class="text-2xl font-bold text-emerald-600 mt-2">${{ costByType.labor.toFixed(2) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-500 uppercase">Scrap</p>
        <p class="text-2xl font-bold text-rose-600 mt-2">${{ costByType.scrap.toFixed(2) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-500 uppercase">Losses</p>
        <p class="text-2xl font-bold text-orange-600 mt-2">${{ costByType.material_variance.toFixed(2) }}</p>
      </div>
    </div>

    <!-- Cost Analysis Row -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Total Cost Card (25%) -->
        <div class="card bg-primary-50 border-primary-200 lg:col-span-1 flex flex-col justify-center items-center text-center py-8">
             <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mb-4">
                  <Icon name="heroicons:currency-dollar" class="w-8 h-8 text-primary-600" />
             </div>
             <p class="text-sm text-primary-600 uppercase font-bold tracking-wider">Total Cost</p>
             <p class="text-4xl font-extrabold text-primary-700 mt-2">${{ totalCost.toFixed(2) }}</p>
        </div>

        <!-- Cost Distribution Chart (75%) -->
        <div class="card lg:col-span-3">
            <h3 class="font-semibold text-gray-700 mb-4">Cost Distribution</h3>
            <div class="h-64 relative">
                 <UiDoughnutChart v-if="costChartData" :data="costChartData" :options="{ maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 20, font: { size: 12 } } }, tooltip: { callbacks: { label: (c) => ` $${Number(c.raw).toFixed(2)}` } } } }" />
                <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">No cost data</div>
            </div>
        </div>
    </div>

    <!-- Cost Entries -->
    <div class="card p-0 overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>MO</th>
            <th>Type</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in paginatedEntries" :key="entry.id">
            <td>MO #{{ entry.manufacturing_order_id }}</td>
            <td>
              <span :class="typeClass(entry.cost_type)">{{ formatCostType(entry.cost_type) }}</span>
            </td>
            <td>{{ entry.product?.name || 'N/A' }}</td>
            <td>{{ entry.quantity ? Number(entry.quantity) : '-' }}</td>
            <td>${{ Number(entry.unit_cost).toFixed(2) }}</td>
            <td class="font-medium">${{ Number(entry.total_cost).toFixed(2) }}</td>
          </tr>
          <tr v-if="entries.length === 0">
            <td colspan="6" class="text-center text-gray-500 py-8">No cost entries found</td>
          </tr>
        </tbody>
      </table>
    </div>
      <UiPagination 
        v-if="entries.length > pageSize"
        v-model="currentPage" 
        :total-items="entries.length" 
        :page-size="pageSize" 
      />
  </div>
</template>

<script setup lang="ts">
import type { ChartData } from 'chart.js'

interface CostEntry {
  id: number
  manufacturing_order_id: number
  product_id: number
  product?: { name: string }
  cost_type: string
  quantity: number
  unit_cost: number
  total_cost: number
}

const { $api } = useApi()
const entries = ref<CostEntry[]>([])

const costByType = computed(() => {
  const result = { material: 0, overhead: 0, labor: 0, scrap: 0, material_variance: 0 }
  entries.value.forEach(e => {
    if (e.cost_type in result) {
      result[e.cost_type as keyof typeof result] += Number(e.total_cost || 0)
    }
  })
  return result
})

const costChartData = computed<ChartData<'doughnut'> | null>(() => {
    const data = costByType.value
    const total = Object.values(data).reduce((a, b) => a + b, 0)
    if (total === 0) return null

    return {
        labels: ['Material', 'Overhead', 'Labor', 'Scrap', 'Losses'],
        datasets: [{
             data: [data.material, data.overhead, data.labor, data.scrap, data.material_variance],
             backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#f43f5e', '#f97316'], // Blue, Amber, Emerald, Rose, Orange
             borderWidth: 2,
             borderColor: '#ffffff',
             hoverOffset: 4
        }]
    }
})

const totalCost = computed(() => entries.value.reduce((sum, e) => sum + Number(e.total_cost || 0), 0))

const currentPage = ref(1)
const pageSize = 10

const paginatedEntries = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return entries.value.slice(start, start + pageSize)
})


// Filter removed to show all entries including labor

function formatCostType(type: string) {
  if (type === 'material_variance') return 'Losses'
  return type
}

function typeClass(type: string) {
  return {
    material: 'badge bg-blue-100 text-blue-800',
    overhead: 'badge bg-amber-100 text-amber-800',
    labor: 'badge bg-emerald-100 text-emerald-800',
    scrap: 'badge bg-rose-100 text-rose-800',
    material_variance: 'badge bg-orange-100 text-orange-800',
  }[type] || 'badge-gray'
}

async function fetchEntries() {
  try {
    const res = await $api<{ data: CostEntry[] }>('/reporting/cost')
    entries.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch:', e)
  }
}

onMounted(fetchEntries)

defineExpose({
  fetchEntries
})
</script>
