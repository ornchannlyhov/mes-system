<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="page-title">Organizations</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Manage all registered organizations</p>
      </div>
      <div class="flex items-center gap-3">
        <select 
          v-model="filterStatus"
          @change="fetchData"
          class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none cursor-pointer hover:border-gray-400 transition-colors"
        >
          <option :value="undefined">All Status</option>
          <option :value="true">Active Only</option>
          <option :value="false">Inactive Only</option>
        </select>
        <UiIconButton
          @click="fetchData"
          icon="heroicons:arrow-path"
          tooltip="Refresh"
          :loading="loading"
        />
      </div>
    </div>

    <!-- Organizations Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive" v-if="organizations.length > 0">
        <table class="table">
          <thead>
            <tr>
              <th>Organization</th>
              <th class="text-center">Members</th>
              <th>Status</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="org in organizations" 
              :key="org.id" 
              class="cursor-pointer"
              @click="showOrgDetail(org)"
            >
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                    <Icon name="heroicons:building-office" class="w-5 h-5 text-primary-600" />
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ org.name }}</div>
                  </div>
                </div>
              </td>
              <td class="text-center">
                <span class="badge badge-gray">{{ org.members_count || 0 }}</span>
              </td>
              <td>
                <span class="badge" :class="org.is_active ? 'badge-success' : 'badge-gray'">
                  {{ org.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <UiIconButton
                    @click.stop="showOrgDetail(org)"
                    icon="heroicons:eye"
                    tooltip="View Details"
                    color="text-primary-600"
                  />
                  <button
                    v-if="!org.is_active"
                    @click.stop="openActivateModal(org)"
                    :disabled="!!processing[org.id]"
                    class="btn-success text-sm py-1 px-2"
                  >
                    <Icon v-if="processing[org.id] === 'activate'" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                    <Icon v-else name="heroicons:check" class="w-4 h-4" />
                  </button>
                  <button 
                    v-else
                    @click.stop="showDeactivateModal(org)"
                    :disabled="!!processing[org.id]"
                    class="btn-danger text-sm py-1 px-2"
                  >
                    <Icon v-if="processing[org.id] === 'deactivate'" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                    <Icon v-else name="heroicons:x-mark" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <UiEmptyState 
        v-else-if="!loading"
        title="No organizations found"
        :description="filterStatus === undefined ? 'No organizations registered yet.' : 'No organizations match the selected filter.'"
        icon="heroicons:building-office-2"
      />
    </div>

    <!-- Organization Detail SlideOver -->
    <UiSlideOver v-model="showDetail" title="Organization Details" width="sm:w-[75vw]">
      <div v-if="selectedOrg" class="space-y-6">
        <!-- Organization Info -->
        <div class="card p-6">
          <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-xl bg-primary-100 flex items-center justify-center">
              <Icon name="heroicons:building-office" class="w-8 h-8 text-primary-600" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">{{ selectedOrg.name }}</h2>
              <span class="badge mt-1" :class="selectedOrg.is_active ? 'badge-success' : 'badge-gray'">
                {{ selectedOrg.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>

          <div class="space-y-4">
            <UiFormGroup label="Created">
              <p class="text-sm text-gray-900">{{ formatDate(selectedOrg.created_at) }}</p>
            </UiFormGroup>
            <UiFormGroup label="Status">
              <p class="text-sm text-gray-900">
                <span v-if="selectedOrg.is_active">
                  Activated on {{ formatDate(selectedOrg.activated_at) }}
                </span>
                <span v-else class="text-red-600">Inactive - awaiting activation</span>
              </p>
            </UiFormGroup>
            <UiFormGroup label="User Limit">
              <div class="flex items-center gap-2">
                <input 
                  v-model="userLimitInput"
                  type="number" 
                  min="1" 
                  max="100"
                  class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none w-24"
                />
                <button 
                  @click="updateUserLimit"
                  :disabled="isUpdatingLimit || userLimitInput === selectedOrg.user_limit"
                  class="btn-primary text-sm"
                >
                  <Icon v-if="isUpdatingLimit" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                  <Icon v-else name="heroicons:check" class="w-4 h-4" />
                </button>
              </div>
            </UiFormGroup>
          </div>
        </div>


        <!-- Members List -->
        <div class="card p-0 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Members ({{ selectedOrg.members?.length || 0 }} / {{ selectedOrg.user_limit || 3 }})</h3>
          </div>
          <div v-if="selectedOrg.members && selectedOrg.members.length > 0" class="divide-y divide-gray-200">
            <div v-for="member in selectedOrg.members" :key="member.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
                  <span class="text-sm font-semibold text-gray-500">{{ member.name.charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ member.name }}</p>
                  <p class="text-sm text-gray-500">{{ member.email }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="badge badge-gray">{{ member.role?.label || 'No Role' }}</span>
                    <span v-if="!member.is_active" class="badge badge-gray">Inactive</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <UiEmptyState 
            v-else
            title="No members found"
            description="This organization has no members yet."
            icon="heroicons:users"
          />
        </div>

        <!-- Actions -->
        <div class="card p-6">
          <h3 class="text-sm font-semibold text-gray-900 uppercase mb-4">Actions</h3>
          <div class="space-y-2">
            <button 
              v-if="!selectedOrg.is_active"
              @click="openActivateModal(selectedOrg)"
              :disabled="isProcessing"
              class="btn-primary w-full justify-center"
            >
              <Icon v-if="isProcessing" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
              <Icon v-else name="heroicons:check" class="w-4 h-4" />
              Activate Organization
            </button>
            <button 
              v-else
              @click="showDeactivateModal(selectedOrg)"
              :disabled="isProcessing"
              class="btn-danger w-full justify-center"
            >
              <Icon v-if="isProcessing" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
              <Icon v-else name="heroicons:x-mark" class="w-4 h-4" />
              Deactivate Organization
            </button>
          </div>
        </div>
      </div>
    </UiSlideOver>

    <!-- Activate Confirm Modal -->
    <UiConfirmModal
      v-model="isActivateModalOpen"
      title="Activate Organization"
      :message="`Are you sure you want to activate ${selectedOrg?.name}? This will allow all members to login.`"
      confirm-text="Activate"
      cancel-text="Cancel"
      variant="primary"
      :loading="isProcessing"
      @confirm="onConfirmActivate"
    />

    <!-- Deactivate Confirm Modal -->
    <UiConfirmModal
      v-model="showModal"
      title="Deactivate Organization"
      :message="`Warning: All members of ${selectedOrg?.name} will be unable to login until reactivated.`"
      confirm-text="Deactivate"
      cancel-text="Cancel"
      variant="danger"
      :loading="isProcessing"
      @confirm="confirmDeactivate"
    />
  </div>
</template>

<script setup lang="ts">
import type { Organization } from '~/types/models'

definePageMeta({
  layout: 'superadmin',
  middleware: ['superadmin'],
})

const { organizations, fetchOrganizations, activateOrganization, deactivateOrganization, fetchOrganizationDetail, updateUserLimit: updateUserLimitOrg } = useSuperadmin()
const { formatDate } = useUtils()

const loading = ref(false)
const filterStatus = ref<boolean | undefined>(undefined)
const processing = ref<Record<number, string | undefined>>({})
const showModal = ref(false)
const isActivateModalOpen = ref(false)
const showDetail = ref(false)
const selectedOrg = ref<Organization | null>(null)
const deactivateReason = ref('')
const isProcessing = ref(false)
const userLimitInput = ref(3)
const isUpdatingLimit = ref(false)

async function fetchData() {
  loading.value = true
  try {
    await fetchOrganizations(1, 10, filterStatus.value)
  } finally {
    loading.value = false
  }
}

async function showOrgDetail(org: Organization) {
  selectedOrg.value = org
  userLimitInput.value = org.user_limit || 3
  // Fetch full details with members
  try {
    const details = await fetchOrganizationDetail(org.id)
    selectedOrg.value = details
    showDetail.value = true
  } catch (e) {
    // Fallback to basic org data if fetch fails
    showDetail.value = true
  }
}

async function updateUserLimit() {
  if (!selectedOrg.value) return
  
  isUpdatingLimit.value = true
  try {
    await updateUserLimitOrg(selectedOrg.value.id, userLimitInput.value)
    selectedOrg.value = await fetchOrganizationDetail(selectedOrg.value.id)
  } finally {
    isUpdatingLimit.value = false
  }
}

async function activateAndRefresh(org: Organization) {
  isProcessing.value = true
  try {
    await activateOrganization(org.id)
    // Refresh org details
    const details = await fetchOrganizationDetail(org.id)
    selectedOrg.value = details
    await fetchData()
  } finally {
    isProcessing.value = false
  }
}

function openActivateModal(org: Organization) {
  selectedOrg.value = org
  isActivateModalOpen.value = true
}

function showDeactivateModal(org: Organization) {
  selectedOrg.value = org
  deactivateReason.value = ''
  showModal.value = true
}

async function onConfirmActivate() {
  if (!selectedOrg.value) return
  
  isProcessing.value = true
  try {
    await activateOrganization(selectedOrg.value.id)
    isActivateModalOpen.value = false
    // Refresh org details
    const details = await fetchOrganizationDetail(selectedOrg.value.id)
    selectedOrg.value = details
    await fetchData()
  } finally {
    isProcessing.value = false
  }
}

async function confirmDeactivate() {
  if (!selectedOrg.value) return
  
  isProcessing.value = true
  if (selectedOrg.value.id) {
    processing.value[selectedOrg.value.id] = 'deactivate'
  }
  
  try {
    await deactivateOrganization(selectedOrg.value.id, deactivateReason.value)
    showModal.value = false
    await fetchData()
  } finally {
    isProcessing.value = false
    processing.value[selectedOrg.value.id] = undefined
    selectedOrg.value = null
  }
}

onMounted(fetchData)
</script>
