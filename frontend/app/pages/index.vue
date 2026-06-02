<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Overview of manufacturing operations</p>
      </div>
      <div class="flex items-center gap-2">
        <UiDateRangeFilter v-model="dateRange" default-preset="last7" />
        <button class="btn-primary" @click="refreshData" :disabled="refreshing">
          <Icon name="heroicons:arrow-path" class="w-4 h-4" :class="{ 'animate-spin': refreshing }" />
          <span class="hidden sm:inline">{{ refreshing ? 'Refreshing...' : 'Refresh' }}</span>
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <nav class="-mb-px flex gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap px-4 py-2 border-b-2 font-medium text-sm transition-colors'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Overview Tab -->
    <div v-if="activeTab === 'overview'" class="space-y-6">
      <!-- Stats Cards -->
      <div class="stats-grid">
        <UiStatsCard
          title="Active MOs"
          :value="stats.activeMOs"
          icon="heroicons:clipboard-document-list"
          color="primary"
        />
        <UiStatsCard
          :title="`Completed (${periodLabel})`"
          :value="stats.completedInPeriod"
          icon="heroicons:check-circle"
          color="success"
        />
        <UiStatsCard
          title="Active Work Orders"
          :value="stats.activeWOs"
          icon="heroicons:wrench-screwdriver"
          color="warning"
        />
        <UiStatsCard
          title="Equipment Issues"
          :value="stats.equipmentIssues"
          icon="heroicons:cog-6-tooth"
          color="error"
        />
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Charts Row -->
        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Production chart (period-filtered) -->
            <div class="card">
                <h3 class="font-semibold text-gray-700 mb-4">Production – {{ periodLabel }}</h3>
                <div class="h-48">
                    <UiBarChart v-if="productionChartData" :data="productionChartData" :options="{ plugins: { legend: { display: false } } }" />
                    <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">No data</div>
                </div>
            </div>

            <!-- Order Status -->
            <div class="card">
                <h3 class="font-semibold text-gray-700 mb-4">Order Status</h3>
                <div class="h-48 relative">
                    <UiDoughnutChart v-if="statusChartData" :data="statusChartData" :options="{ cutout: '70%', plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } }" />
                     <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">No data</div>
                </div>
            </div>

            <!-- Quality Yield -->
             <div class="card">
                <h3 class="font-semibold text-gray-700 mb-4">Quality Yield</h3>
                <div class="h-48">
                     <UiDoughnutChart v-if="qualityChartData" :data="qualityChartData" :options="{ cutout: '70%', plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } }" />
                    <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">No checks recorded</div>
                </div>
            </div>
        </div>

        <!-- Recent Manufacturing Orders -->
        <div class="lg:col-span-2 card">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Recent Manufacturing Orders</h2>
            <NuxtLink to="/execution/manufacturing-orders" class="text-sm text-primary-600 hover:text-primary-700">
              View all →
            </NuxtLink>
          </div>
          
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Status</th>
                  <th>Priority</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="mo in recentMOs" :key="mo.id">
                  <td class="font-medium">{{ mo.name }}</td>
                  <td>{{ mo.product?.name || 'N/A' }}</td>
                  <td>{{ Math.floor(mo.qty_produced) }} / {{ Math.floor(mo.qty_to_produce) }}</td>
                  <td>
                    <UiStatusBadge :status="mo.status" />
                  </td>
                  <td>
                    <UiPriorityBadge :priority="mo.priority" />
                  </td>
                </tr>
                <tr v-if="recentMOs.length === 0">
                  <td colspan="5" class="text-center text-gray-500 py-8">
                    No manufacturing orders found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
          <div class="space-y-3">
            <NuxtLink v-if="hasPermission('manufacturing:plan')" to="/execution/manufacturing-orders" class="quick-action">
              <div class="quick-action-icon bg-primary-100 text-primary-600">
                <Icon name="heroicons:plus" class="w-5 h-5" />
              </div>
              <div>
                <p class="font-medium text-gray-800">New MO</p>
                <p class="text-sm text-gray-500">Create manufacturing order</p>
              </div>
            </NuxtLink>

            <NuxtLink v-if="hasPermission('inventory:read')" to="/inventory/stock" class="quick-action">
              <div class="quick-action-icon bg-secondary-100 text-secondary-600">
                <Icon name="heroicons:archive-box" class="w-5 h-5" />
              </div>
              <div>
                <p class="font-medium text-gray-800">Check Stock</p>
                <p class="text-sm text-gray-500">View inventory levels</p>
              </div>
            </NuxtLink>

            <NuxtLink v-if="hasPermission('manufacturing:read')" to="/execution/work-orders" class="quick-action">
              <div class="quick-action-icon bg-blue-100 text-blue-600">
                <Icon name="heroicons:wrench-screwdriver" class="w-5 h-5" />
              </div>
              <div>
                <p class="font-medium text-gray-800">Work Orders</p>
                <p class="text-sm text-gray-500">View active tasks</p>
              </div>
            </NuxtLink>

            <NuxtLink v-if="hasPermission('maintenance:read')" to="/maintenance/requests" class="quick-action">
              <div class="quick-action-icon bg-orange-100 text-orange-600">
                <Icon name="heroicons:wrench" class="w-5 h-5" />
              </div>
              <div>
                <p class="font-medium text-gray-800">Maintenance</p>
                <p class="text-sm text-gray-500">Report equipment issue</p>
              </div>
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <!-- OEE Report Tab -->
    <ReportingOeeReport v-if="activeTab === 'oee'" ref="oeeReportRef" :date-range="dateRange" />

    <!-- Cost Report Tab -->
    <ReportingCostReport v-if="activeTab === 'cost'" ref="costReportRef" :date-range="dateRange" />
  </div>
</template>

<script setup lang="ts">
import type { ManufacturingOrder, WorkOrder } from '~/types/models'
import type { ChartData } from 'chart.js'
import type { DateRange } from '~/components/ui/DateRangeFilter.vue'
import { useExecutionStore } from '~/stores/execution'

const { $api } = useApi()
const { hasPermission, hasRole } = usePermissions()

// Tabs
const allTabs = [
  { id: 'overview', name: 'Overview', show: true },
  { id: 'oee', name: 'OEE Report', show: hasRole('admin') || hasRole('manager') },
  { id: 'cost', name: 'Cost Analysis', show: hasRole('admin') || hasRole('manager') },
]

const tabs = computed(() => {
  return allTabs.filter(tab => tab.show)
})

const activeTab = ref('overview')

// Component Refs
const oeeReportRef = ref()
const costReportRef = ref()

// Stats
const stats = ref({
  activeMOs: 0,
  completedInPeriod: 0,
  activeWOs: 0,
  equipmentIssues: 0,
})

// Loaded data
const allMOs = ref<ManufacturingOrder[]>([])
const allWOs = ref<WorkOrder[]>([])

// Recent MOs (always top 10 of all loaded)
const recentMOs = computed(() => allMOs.value.slice(0, 10))

// Period filter
const dateRange = ref<DateRange>({ start: '', end: '', preset: 'last7' })

const presetLabels: Record<string, string> = {
  today: 'Today',
  last7: 'Last 7 Days',
  last30: 'Last 30 Days',
  thisMonth: 'This Month',
  lastMonth: 'Last Month',
}
const periodLabel = computed(() => {
  if (dateRange.value.preset === 'custom') {
    return `${dateRange.value.start} – ${dateRange.value.end}`
  }
  return presetLabels[dateRange.value.preset] || 'Selected Period'
})

// Chart Data
const productionChartData = ref<ChartData<'bar'> | null>(null)
const statusChartData = ref<ChartData<'doughnut'> | null>(null)
const qualityChartData = ref<ChartData<'doughnut'> | null>(null)

function buildCharts() {
  const { start, end } = dateRange.value
  if (!start || !end || !allMOs.value.length && !allWOs.value.length) {
    productionChartData.value = null
    return
  }

  // 1. Production chart — one bar per day in the selected range
  const productionMap = new Map<string, number>()
  const labels: string[] = []
  const startDate = new Date(start + 'T00:00:00')
  const endDate = new Date(end + 'T00:00:00')
  const d = new Date(startDate)
  while (d <= endDate) {
    const ds = d.toISOString().split('T')[0]!
    productionMap.set(ds, 0)
    // Use short label: weekday for ≤7 days, otherwise "Mon DD"
    const daySpan = Math.round((endDate.getTime() - startDate.getTime()) / 86400000) + 1
    const label = daySpan <= 7
      ? d.toLocaleDateString('en-US', { weekday: 'short' })
      : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    labels.push(label)
    d.setDate(d.getDate() + 1)
  }

  allMOs.value.forEach(mo => {
    const dateRaw = mo.actual_end || mo.created_at || ''
    const date = dateRaw.split('T')[0]
    if (date && productionMap.has(date)) {
      productionMap.set(date, (productionMap.get(date) || 0) + Number(mo.qty_produced || 0))
    }
  })

  productionChartData.value = {
    labels,
    datasets: [{
      label: 'Units Produced',
      data: Array.from(productionMap.values()),
      backgroundColor: '#6366f1',
      borderRadius: 6,
      hoverBackgroundColor: '#4f46e5',
    }],
  }

  // Completed in period stat
  stats.value.completedInPeriod = allMOs.value.filter(mo => {
    if (mo.status !== 'done') return false
    const date = (mo.actual_end || mo.created_at || '').split('T')[0] || ''
    return date >= start && date <= end
  }).length

  // 2. Order Status (all-time current state)
  const statusCounts = { ready: 0, in_progress: 0, done: 0, other: 0 }
  allMOs.value.forEach(mo => {
    if (['scheduled', 'confirmed', 'ready'].includes(mo.status)) statusCounts.ready++
    else if (mo.status === 'in_progress') statusCounts.in_progress++
    else if (mo.status === 'done') statusCounts.done++
    else statusCounts.other++
  })

  statusChartData.value = {
    labels: ['Ready', 'In Progress', 'Done', 'Other'],
    datasets: [{
      data: [statusCounts.ready, statusCounts.in_progress, statusCounts.done, statusCounts.other],
      backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981', '#94a3b8'],
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 4,
    }],
  }

  // 3. Quality Yield (all-time)
  const passCount = allWOs.value.filter(w => w.qa_status === 'pass').length
  const failCount = allWOs.value.filter(w => w.qa_status === 'fail').length
  const totalChecks = passCount + failCount

  qualityChartData.value = totalChecks > 0
    ? {
        labels: ['Pass', 'Fail'],
        datasets: [{
          data: [passCount, failCount],
          backgroundColor: ['#10b981', '#f43f5e'],
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverOffset: 4,
        }],
      }
    : null
}

// Rebuild charts whenever the period changes (data already loaded)
watch(dateRange, buildCharts, { deep: true })

const executionStore = useExecutionStore()

async function fetchData() {
  try {
    const [moRes, woRes, mrRes] = await Promise.all([
      $api<any>('/manufacturing-orders', { query: { per_page: 1 } }),
      $api<any>('/work-orders', { query: { per_page: 1 } }),
      $api<any>('/maintenance/requests', { query: { per_page: 1 } }),
    ])

    await Promise.all([
      executionStore.fetchManufacturingOrders(),
      executionStore.fetchWorkOrders(),
    ])

    allMOs.value = executionStore.manufacturingOrders as ManufacturingOrder[]
    allWOs.value = executionStore.workOrders as WorkOrder[]

    // Real-time status counts from backend
    const moCounts = moRes.meta?.counts || {}
    stats.value.activeMOs = (moCounts.confirmed || 0) + (moCounts.in_progress || 0)

    const woCounts = woRes.meta?.counts || {}
    stats.value.activeWOs = (woCounts.ready || 0) + (woCounts.in_progress || 0)

    stats.value.equipmentIssues = mrRes.meta?.active_count || 0

    buildCharts()
  } catch (e) {
    console.error('Failed to fetch dashboard data:', e)
  }
}

const refreshing = ref(false)

async function refreshData() {
  refreshing.value = true
  try {
    if (activeTab.value === 'overview') {
      await fetchData()
    } else if (activeTab.value === 'oee') {
      await oeeReportRef.value?.fetchRecords()
    } else if (activeTab.value === 'cost') {
      await costReportRef.value?.fetchEntries()
    }
  } finally {
    refreshing.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="postcss" scoped>
.quick-action {
  @apply flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors;
}

.quick-action-icon {
  @apply w-10 h-10 rounded-lg flex items-center justify-center;
}
</style>
