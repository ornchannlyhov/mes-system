<template>
  <div class="space-y-6">
    <!-- Cost Type Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="(amount, type) in displaySummary" :key="type" class="card p-4">
        <p class="text-xs text-gray-500 uppercase font-medium">{{ formatLabel(type) }}</p>
        <p :class="['text-xl font-bold mt-1', getTypeColor(type)]">
            {{ formatCurrency(amount) }}
        </p>
      </div>
    </div>

    <!-- Total -->
    <div class="card bg-primary-50 border-primary-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-primary-600 uppercase font-medium">Total Production Cost</p>
          <p class="text-3xl font-bold text-primary-700">{{ formatCurrency(summary.total) }}</p>
        </div>
        <div class="w-16 h-16 bg-primary-100 rounded-xl flex items-center justify-center">
          <Icon name="heroicons:currency-dollar" class="w-8 h-8 text-primary-600" />
        </div>
      </div>
    </div>

    <!-- Cost Entries -->
    <div class="card p-0 overflow-hidden">
      <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
          <h3 class="font-medium text-gray-900">Cost Breakdown</h3>
          <span class="text-xs text-gray-500">{{ details.length }} entries</span>
      </div>
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Description / Note</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in details" :key="entry.id">
            <td class="text-sm text-gray-500">{{ formatDate(entry.created_at) }}</td>
            <td>
              <span :class="typeClass(entry.cost_type)">{{ formatLabel(entry.cost_type) }}</span>
            </td>
            <td class="max-w-xs truncate" :title="entry.notes">
                <div class="font-medium text-gray-900">{{ entry.product?.name || '-' }}</div>
                <div class="text-xs text-gray-500">{{ entry.notes }}</div>
            </td>
            <td>{{ entry.quantity ? Number(entry.quantity) : '-' }}</td>
            <td>{{ formatCurrency(entry.unit_cost) }}</td>
            <td class="font-medium">{{ formatCurrency(entry.total_cost) }}</td>
          </tr>
          <tr v-if="details.length === 0">
            <td colspan="6" class="text-center text-gray-500 py-8">No cost entries found</td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CostEntry } from '~/types/models'

const props = defineProps<{
  moId: number
}>()

const { $api } = useApi()
const { formatCurrency, formatDate } = useUtils()

const summary = ref<any>({ total: 0, material: 0, labor: 0, overhead: 0, scrap: 0, material_variance: 0 })
const details = ref<CostEntry[]>([])

// Filter out total from display dict and reorder
const displaySummary = computed(() => {
    const { total, ...rest } = summary.value
    // Ensure order
    return {
        material: rest.material || 0,
        overhead: rest.overhead || 0,
        scrap: rest.scrap || 0,
        material_variance: rest.material_variance || 0
    }
})

async function fetchAnalysis() {
  try {
    const res = await $api<any>(`/reporting/cost/${props.moId}`)
    summary.value = res.summary || {}
    // Filter labor out of details
    details.value = (res.details || []).filter((d: CostEntry) => d.cost_type !== 'labor')
  } catch (e) {
    console.error('Failed to fetch cost analysis', e)
  }
}

function formatLabel(key: string | number) {
    if (key === 'material_variance') return 'Losses'
    return String(key)
}

function getTypeColor(type: string | number) {
  const colors: Record<string, string> = {
    material: 'text-blue-600',
    overhead: 'text-yellow-600',
    scrap: 'text-red-600',
    material_variance: 'text-orange-600'
  }
  return colors[String(type)] || 'text-gray-600'
}

function typeClass(type: string) {
  return {
    material: 'badge bg-blue-100 text-blue-800',
    labor: 'badge bg-green-100 text-green-800',
    overhead: 'badge bg-yellow-100 text-yellow-800',
    scrap: 'badge bg-red-100 text-red-800',
  }[type] || 'badge-gray'
}

onMounted(fetchAnalysis)
</script>
