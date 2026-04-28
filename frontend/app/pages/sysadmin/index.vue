<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="page-title">System Dashboard</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Overview of system health and pending actions</p>
      </div>
      <UiIconButton
        @click="refreshData"
        icon="heroicons:arrow-path"
        tooltip="Refresh"
        :loading="loading"
      />
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <NuxtLink to="/sysadmin/pending-approvals" class="card p-6 hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center">
            <Icon name="heroicons:clock" class="w-6 h-6 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats?.pending_approvals_count || 0 }}</p>
          </div>
        </div>
      </NuxtLink>

      <div class="card p-6">
        <div class="flex items-center">
          <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
            <Icon name="heroicons:building-office" class="w-6 h-6 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Active Organizations</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats?.active_organizations_count || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="card p-6">
        <div class="flex items-center">
          <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
            <Icon name="heroicons:building-office-2" class="w-6 h-6 text-red-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Inactive Organizations</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats?.inactive_organizations_count || 0 }}</p>
          </div>
        </div>
      </div>

      <NuxtLink to="/sysadmin/organizations" class="card p-6 hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
            <Icon name="heroicons:building-library" class="w-6 h-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Organizations</p>
            <p class="text-2xl font-bold text-gray-900">{{ (stats?.active_organizations_count || 0) + (stats?.inactive_organizations_count || 0) }}</p>
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
      <div class="flex flex-wrap gap-3">
        <NuxtLink to="/sysadmin/pending-approvals" class="btn-primary">
          <Icon name="heroicons:user-plus" class="w-5 h-5" />
          Review Pending Approvals
        </NuxtLink>
        <NuxtLink to="/sysadmin/organizations" class="btn-secondary">
          <Icon name="heroicons:building-office" class="w-5 h-5" />
          Manage Organizations
        </NuxtLink>
      </div>
    </div>

    <!-- Recent Pending Approvals -->
    <div class="card p-0 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Recent Pending Approvals</h2>
      </div>
      <div v-if="recentPending.length > 0" class="divide-y divide-gray-200">
        <div v-for="user in recentPending" :key="user.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
              <span class="text-sm font-semibold text-gray-500">{{ user.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ user.name }}</p>
              <p class="text-sm text-gray-500">{{ user.email }}</p>
              <p class="text-xs text-gray-400">Organization: {{ user.organization?.name || 'N/A' }}</p>
            </div>
          </div>
          <NuxtLink :to="`/sysadmin/pending-approvals`" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
            Review
          </NuxtLink>
        </div>
      </div>
      <UiEmptyState 
        v-else
        title="No pending approvals" 
        description="All user registrations have been reviewed."
        icon="heroicons:check-circle"
        iconColor="text-green-400"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { User } from '~/types/models'

definePageMeta({
  layout: 'superadmin',
  middleware: ['superadmin'],
})

const { stats, fetchDashboardStats } = useSuperadmin()
const recentPending = ref<User[]>([])
const loading = ref(false)

async function refreshData() {
  loading.value = true
  try {
    const response = await fetchDashboardStats()
    recentPending.value = response.recent_pending
  } finally {
    loading.value = false
  }
}

onMounted(refreshData)
</script>
