<template>
  <Bar
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
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import type { ChartData, ChartOptions } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{
  data: ChartData<'bar'>
  options?: ChartOptions<'bar'>
}>()

const chartData = computed(() => props.data)
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  ...props.options
}))
</script>
