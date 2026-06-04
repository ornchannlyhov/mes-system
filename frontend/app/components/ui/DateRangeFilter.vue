<template>
  <div class="flex items-center gap-2">
    <select
      v-model="activePreset"
      @change="onPresetChange"
      class="date-range-select appearance-none text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 pl-3 pr-9 py-2.5 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-colors cursor-pointer hover:border-gray-400"
    >
      <option v-for="p in presets" :key="p.id" :value="p.id">{{ p.label }}</option>
    </select>
    <template v-if="activePreset === 'custom'">
      <input
        type="date"
        v-model="customStart"
        @change="emitCustom"
        :max="customEnd || today"
        class="text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-colors"
      />
      <span class="text-gray-400 text-sm">–</span>
      <input
        type="date"
        v-model="customEnd"
        @change="emitCustom"
        :min="customStart"
        :max="today"
        class="text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-colors"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
export interface DateRange {
  start: string
  end: string
  preset: string
}

const props = withDefaults(defineProps<{
  modelValue?: DateRange
  defaultPreset?: string
}>(), {
  defaultPreset: 'last30',
})

const emit = defineEmits<{
  'update:modelValue': [value: DateRange]
}>()

const today = new Date().toISOString().split('T')[0]!

const presets = [
  { id: 'today', label: 'Today' },
  { id: 'last7', label: 'Last 7 Days' },
  { id: 'last30', label: 'Last 30 Days' },
  { id: 'thisMonth', label: 'This Month' },
  { id: 'lastMonth', label: 'Last Month' },
  { id: 'custom', label: 'Custom Range' },
]

const activePreset = ref(props.modelValue?.preset || props.defaultPreset)
const customStart = ref(props.modelValue?.start || today)
const customEnd = ref(props.modelValue?.end || today)

function getRange(id: string): { start: string; end: string } {
  const pad = (n: number) => String(n).padStart(2, '0')
  const fmt = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
  const now = new Date()
  const todayStr = fmt(now)

  switch (id) {
    case 'today':
      return { start: todayStr, end: todayStr }
    case 'last7': {
      const d = new Date(now)
      d.setDate(d.getDate() - 6)
      return { start: fmt(d), end: todayStr }
    }
    case 'last30': {
      const d = new Date(now)
      d.setDate(d.getDate() - 29)
      return { start: fmt(d), end: todayStr }
    }
    case 'thisMonth':
      return { start: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-01`, end: todayStr }
    case 'lastMonth': {
      const first = new Date(now.getFullYear(), now.getMonth() - 1, 1)
      const last = new Date(now.getFullYear(), now.getMonth(), 0)
      return { start: fmt(first), end: fmt(last) }
    }
    default:
      return { start: customStart.value, end: customEnd.value }
  }
}

function onPresetChange() {
  if (activePreset.value !== 'custom') {
    const range = getRange(activePreset.value)
    emit('update:modelValue', { ...range, preset: activePreset.value })
  }
}

function emitCustom() {
  if (customStart.value && customEnd.value && customStart.value <= customEnd.value) {
    emit('update:modelValue', { start: customStart.value, end: customEnd.value, preset: 'custom' })
  }
}

onMounted(() => {
  if (activePreset.value !== 'custom') {
    const range = getRange(activePreset.value)
    emit('update:modelValue', { ...range, preset: activePreset.value })
  }
})
</script>

<style scoped>
.date-range-select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 0.65rem center;
  background-size: 1.1em 1.1em;
}
</style>
