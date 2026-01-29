<template>
  <div class="space-y-6">
    <!-- Header removed -->

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="card text-center">
        <p class="text-sm text-gray-500 uppercase">Avg OEE</p>
        <p class="text-3xl font-bold text-primary-600 mt-2">{{ avgOee.toFixed(1) }}%</p>
      </div>
      <div class="card text-center">
        <p class="text-sm text-gray-500 uppercase">Availability</p>
        <p class="text-3xl font-bold text-blue-600 mt-2">{{ avgAvailability.toFixed(1) }}%</p>
      </div>
      <div class="card text-center">
        <p class="text-sm text-gray-500 uppercase">Performance</p>
        <p class="text-3xl font-bold text-emerald-600 mt-2">{{ avgPerformance.toFixed(1) }}%</p>
      </div>
      <div class="card text-center">
        <p class="text-sm text-gray-500 uppercase">Quality</p>
        <p class="text-3xl font-bold text-amber-600 mt-2">{{ avgQuality.toFixed(1) }}%</p>
      </div>
    </div>

    <!-- OEE Trend Chart -->
    <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4">OEE Trend (Last 30 Days)</h3>
        <div class="h-64">
            <UiLineChart v-if="oeeChartData" :data="oeeChartData" />
            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">No sufficient data</div>
        </div>
    </div>

    <!-- Records Table -->
    <div class="card p-0 overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Work Center</th>
            <th>Availability</th>
            <th>Performance</th>
            <th>Quality</th>
            <th>OEE</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="rec in paginatedRecords" :key="rec.id">
            <td>{{ formatDate(rec.record_date) }}</td>
            <td class="font-medium">{{ rec.work_center?.name || 'N/A' }}</td>
            <td>{{ Number(rec.availability_score).toFixed(1) }}%</td>
            <td>{{ Number(rec.performance_score).toFixed(1) }}%</td>
            <td>{{ Number(rec.quality_score).toFixed(1) }}%</td>
            <td class="font-bold" :class="oeeColor(Number(rec.oee_score))">
              {{ Number(rec.oee_score).toFixed(1) }}%
            </td>
          </tr>
          <tr v-if="records.length === 0">
            <td colspan="6" class="text-center text-gray-500 py-8">No OEE records found</td>
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
import type { ChartData } from 'chart.js'

interface OeeRecord {
  id: number
  work_center_id: number
  work_center?: { name: string }
  record_date: string
  availability_score: number
  performance_score: number
  quality_score: number
  oee_score: number
}

const { $api } = useApi()
const records = ref<OeeRecord[]>([])
const { formatDate } = useUtils()

const avgOee = computed(() => {
  if (!records.value.length) return 0
  return records.value.reduce((sum, r) => sum + Number(r.oee_score), 0) / records.value.length
})

const avgAvailability = computed(() => {
  if (!records.value.length) return 0
  return records.value.reduce((sum, r) => sum + Number(r.availability_score), 0) / records.value.length
})

const avgPerformance = computed(() => {
  if (!records.value.length) return 0
  return records.value.reduce((sum, r) => sum + Number(r.performance_score), 0) / records.value.length
})

const avgQuality = computed(() => {
  if (!records.value.length) return 0
  return records.value.reduce((sum, r) => sum + Number(r.quality_score), 0) / records.value.length
})

const currentPage = ref(1)
const pageSize = 10

const paginatedRecords = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return records.value.slice(start, start + pageSize)
})

const oeeChartData = ref<ChartData<'line'> | null>(null)

function oeeColor(score: number) {
  if (score >= 85) return 'text-green-600'
  if (score >= 60) return 'text-yellow-600'
  return 'text-red-600'
}

async function fetchRecords() {
  try {
    const res = await $api<{ data: OeeRecord[] }>('/reporting/oee')
    records.value = res.data || []
    
    // Prepare Chart Data
    if (records.value.length > 0) {
        // Sort by date ascending
        const sorted = [...records.value].sort((a, b) => new Date(a.record_date).getTime() - new Date(b.record_date).getTime())
        
        oeeChartData.value = {
            labels: sorted.map(r => formatDate(r.record_date)),
            datasets: [
                { label: 'OEE', data: sorted.map(r => Number(r.oee_score)), borderColor: '#6366f1', backgroundColor: '#6366f1', tension: 0.3, pointRadius: 4 }, // Indigo
                { label: 'Availability', data: sorted.map(r => Number(r.availability_score)), borderColor: '#3b82f6', backgroundColor: '#3b82f6', borderDash: [5, 5], hidden: true }, // Blue
                { label: 'Performance', data: sorted.map(r => Number(r.performance_score)), borderColor: '#10b981', backgroundColor: '#10b981', borderDash: [5, 5], hidden: true }, // Emerald
                { label: 'Quality', data: sorted.map(r => Number(r.quality_score)), borderColor: '#f59e0b', backgroundColor: '#f59e0b', borderDash: [5, 5], hidden: true }, // Amber
            ]
        }
    } else {
        oeeChartData.value = null
    }

  } catch (e) {
    console.error('Failed to fetch:', e)
  }
}

onMounted(fetchRecords)

defineExpose({
  fetchRecords
})
</script>
