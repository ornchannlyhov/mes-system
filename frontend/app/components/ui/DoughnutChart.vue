<template>
  <Doughnut
    :data="chartData"
    :options="chartOptions"
  />
</template>

<script setup lang="ts">
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import type { ChartData, ChartOptions } from 'chart.js'

ChartJS.register(ArcElement, Title, Tooltip, Legend)

const props = defineProps<{
  data: ChartData<'doughnut'>
  options?: ChartOptions<'doughnut'>
}>()

const chartData = computed(() => props.data)
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  ...props.options
}))
</script>
