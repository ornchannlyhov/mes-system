<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="page-title">Pending Approvals</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Review and approve new user registrations</p>
      </div>
      <UiIconButton
        @click="fetchData"
        icon="heroicons:arrow-path"
        tooltip="Refresh"
        :loading="loading"
      />
    </div>

    <!-- Pending Users Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive" v-if="pendingUsers.length > 0">
        <table class="table">
          <thead>
            <tr>
              <th>User</th>
              <th>Organization</th>
              <th>Registered</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in pendingUsers" :key="user.id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
                    <span class="text-sm font-semibold text-gray-500">{{ user.name.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="text-sm text-gray-900">{{ user.organization?.name || 'No organization yet' }}</div>
              </td>
              <td>
                <span class="text-sm text-gray-500">{{ formatDate(user.created_at) }}</span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <UiIconButton
                    @click="openApproveModal(user)"
                    :disabled="!!processing[user.id]"
                    icon="heroicons:check"
                    tooltip="Approve"
                    color="text-green-600"
                    :loading="processing[user.id] === 'approve'"
                  />
                  <UiIconButton
                    @click="openRejectModal(user)"
                    :disabled="!!processing[user.id]"
                    icon="heroicons:x-mark"
                    tooltip="Reject"
                    color="text-red-600"
                    :loading="processing[user.id] === 'reject'"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <UiEmptyState 
        v-else-if="!loading"
        title="No pending approvals"
        description="All user registrations have been reviewed."
        icon="heroicons:check-circle"
        iconColor="text-green-400"
      />
    </div>

    <!-- Approve Confirm Modal -->
    <UiConfirmModal
      v-model="isApproveModalOpen"
      title="Approve User Registration"
      :message="`Are you sure you want to approve ${selectedUser?.name}'s registration? This will activate their organization and allow them to login.`"
      confirm-text="Approve"
      cancel-text="Cancel"
      variant="primary"
      :loading="isApproving"
      @confirm="confirmApprove"
    />

    <!-- Reject Confirm Modal -->
    <UiConfirmModal
      v-model="isRejectModalOpen"
      title="Reject User Registration"
      :message="`Are you sure you want to reject ${selectedUser?.name}'s registration? This will delete the user and their organization.`"
      confirm-text="Reject"
      cancel-text="Cancel"
      variant="danger"
      :loading="isRejecting"
      @confirm="confirmReject"
    />
  </div>
</template>

<script setup lang="ts">
import type { User } from '~/types/models'

definePageMeta({
  layout: 'superadmin',
  middleware: ['superadmin'],
})

const { pendingUsers, fetchPendingUsers, approveUser, rejectUser } = useSuperadmin()
const { formatDate } = useUtils()

const loading = ref(false)
const processing = ref<Record<number, string | undefined>>({})
const isApproveModalOpen = ref(false)
const isRejectModalOpen = ref(false)
const selectedUser = ref<User | null>(null)
const isApproving = ref(false)
const isRejecting = ref(false)

async function fetchData() {
  loading.value = true
  try {
    await fetchPendingUsers()
  } finally {
    loading.value = false
  }
}

function openApproveModal(user: User) {
  selectedUser.value = user
  isApproveModalOpen.value = true
}

function openRejectModal(user: User) {
  selectedUser.value = user
  isRejectModalOpen.value = true
}

async function confirmApprove() {
  if (!selectedUser.value) return
  
  isApproving.value = true
  processing.value[selectedUser.value.id] = 'approve'
  
  try {
    await approveUser(selectedUser.value.id)
    isApproveModalOpen.value = false
    await fetchData()
  } finally {
    isApproving.value = false
    processing.value[selectedUser.value.id] = undefined
    selectedUser.value = null
  }
}

async function confirmReject() {
  if (!selectedUser.value) return
  
  isRejecting.value = true
  processing.value[selectedUser.value.id] = 'reject'
  
  try {
    await rejectUser(selectedUser.value.id)
    isRejectModalOpen.value = false
    await fetchData()
  } finally {
    isRejecting.value = false
    processing.value[selectedUser.value.id] = undefined
    selectedUser.value = null
  }
}

onMounted(fetchData)
</script>
