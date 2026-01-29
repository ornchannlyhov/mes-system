<template>
  <span :class="badgeClass">{{ label }}</span>
</template>

<script setup lang="ts">
const props = defineProps<{
  status: string
}>()

const statusConfig: Record<string, { class: string; label: string }> = {
  // Manufacturing Order statuses
  draft: { class: 'badge-gray', label: 'Draft' },
  confirmed: { class: 'badge-info', label: 'Confirmed' },
  in_progress: { class: 'badge-warning', label: 'In Progress' },
  done: { class: 'badge-success', label: 'Done' },
  cancelled: { class: 'badge-error', label: 'Cancelled' },
  // Work Order statuses
  pending: { class: 'badge-gray', label: 'Pending' },
  ready: { class: 'badge-info', label: 'Ready' },
  paused: { class: 'badge-warning', label: 'Paused' },
  blocked: { class: 'badge-error', label: 'Blocked' },
  // Equipment statuses
  operational: { class: 'badge-success', label: 'Operational' },
  maintenance: { class: 'badge-warning', label: 'Maintenance' },
  broken: { class: 'badge-error', label: 'Broken' },
  // Quality statuses
  pass: { class: 'badge-success', label: 'Pass' },
  fail: { class: 'badge-error', label: 'Fail' },
  warning: { class: 'badge-warning', label: 'Warning' },
  // Alert statuses
  open: { class: 'badge-error', label: 'Open' },
  resolved: { class: 'badge-success', label: 'Resolved' },
  closed: { class: 'badge-gray', label: 'Closed' },
}

const badgeClass = computed(() => statusConfig[props.status]?.class || 'badge-gray')
const label = computed(() => statusConfig[props.status]?.label || props.status)
</script>
