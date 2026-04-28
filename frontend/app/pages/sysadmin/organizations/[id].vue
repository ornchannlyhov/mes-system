<template>
  <div class="space-y-6">
    <!-- Header with back link -->
    <div class="flex items-center gap-4">
      <UiIconButton
        to="/sysadmin/organizations"
        icon="heroicons:arrow-left"
        tooltip="Back to Organizations"
        color="text-gray-500 hover:text-gray-700"
      />
      <h1 class="page-title">Organization Details</h1>
    </div>

    <div v-if="organization" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Organization Info Card -->
      <div class="card p-6">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-16 h-16 rounded-xl bg-primary-100 flex items-center justify-center">
            <Icon name="heroicons:building-office" class="w-8 h-8 text-primary-600" />
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-900">{{ organization.name }}</h2>
            <span class="badge mt-1" :class="organization.is_active ? 'badge-success' : 'badge-gray'">
              {{ organization.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>

        <div class="space-y-4">
          <UiFormGroup label="Organization ID">
            <p class="text-sm text-gray-900 font-mono">{{ organization.id }}</p>
          </UiFormGroup>
          <UiFormGroup label="Created">
            <p class="text-sm text-gray-900">{{ formatDate(organization.created_at) }}</p>
          </UiFormGroup>
          <UiFormGroup label="Status">
            <p class="text-sm text-gray-900">
              <span v-if="organization.is_active">
                Activated on {{ formatDate(organization.activated_at) }}
              </span>
              <span v-else class="text-red-600">Inactive - awaiting activation</span>
            </p>
          </UiFormGroup>
          <UiFormGroup v-if="organization.activated_by" label="Activated By">
            <p class="text-sm text-gray-900">{{ organization.owner?.name || 'System' }}</p>
          </UiFormGroup>
        </div>

        <!-- Owner Card -->
        <div class="card p-6">
          <h3 class="text-sm font-semibold text-gray-900 uppercase mb-4">Organization Owner</h3>
          <div v-if="organization.owner" class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
              <span class="text-sm font-semibold text-gray-500">{{ organization.owner.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ organization.owner.name }}</p>
              <p class="text-sm text-gray-500">{{ organization.owner.email }}</p>
              <span class="badge badge-primary">{{ organization.owner.is_approved ? 'Approved' : 'Pending' }}</span>
            </div>
          </div>
          <p v-else class="text-gray-500 text-sm">No owner assigned</p>
        </div>

        <!-- Actions -->
        <div class="card p-6">
          <h3 class="text-sm font-semibold text-gray-900 uppercase mb-4">Actions</h3>
          <div class="space-y-2">
            <button 
              v-if="!organization.is_active"
              @click="activateOrg"
              :disabled="isProcessing"
              class="btn-success w-full justify-center"
            >
              <Icon v-if="isProcessing && action === 'activate'" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
              <Icon v-else name="heroicons:check" class="w-4 h-4" />
              Activate Organization
            </button>
            <button 
              v-else
              @click="showDeactivateModal"
              :disabled="isProcessing"
              class="btn-danger w-full justify-center"
            >
              <Icon v-if="isProcessing && action === 'deactivate'" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
              <Icon v-else name="heroicons:x-mark" class="w-4 h-4" />
              Deactivate Organization
            </button>
          </div>
        </div>
      </div>

      <!-- Members List -->
      <div class="lg:col-span-2">
        <div class="card p-0 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Members ({{ organization.members?.length || 0 }})</h3>
          </div>
          <div v-if="organization.members && organization.members.length > 0" class="divide-y divide-gray-200">
            <div v-for="member in organization.members" :key="member.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
                  <span class="text-sm font-semibold text-gray-500">{{ member.name.charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ member.name }}</p>
                  <p class="text-sm text-gray-500">{{ member.email }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="badge badge-gray">{{ member.role?.label || 'No Role' }}</span>
                    <span v-if="!member.is_approved" class="badge badge-warning">Pending</span>
                    <span v-else-if="!member.is_active" class="badge badge-gray">Inactive</span>
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
      </div>
    </div>

    <div v-else-if="!loading" class="text-center py-12">
      <Icon name="heroicons:exclamation-circle" class="w-12 h-12 mx-auto mb-4 text-red-400" />
      <h3 class="text-lg font-medium text-gray-900 mb-1">Organization not found</h3>
      <p class="text-gray-500">The requested organization does not exist.</p>
    </div>

    <!-- Deactivate Confirm Modal -->
    <UiConfirmModal
      v-model="showModal"
      title="Deactivate Organization"
      :message="`Warning: All members of ${organization?.name} will be unable to login until reactivated.`"
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

const route = useRoute()
const orgId = parseInt(route.params.id as string)

const { fetchOrganizationDetail, activateOrganization, deactivateOrganization } = useSuperadmin()
const { formatDate } = useUtils()
const toast = useToast()

const organization = ref<Organization | null>(null)
const loading = ref(false)
const isProcessing = ref(false)
const action = ref<string>('')
const showModal = ref(false)
const deactivateReason = ref('')

async function fetchData() {
  loading.value = true
  try {
    organization.value = await fetchOrganizationDetail(orgId)
  } catch (e: any) {
    if (e.statusCode === 404) {
      organization.value = null
    }
  } finally {
    loading.value = false
  }
}

async function activateOrg() {
  isProcessing.value = true
  action.value = 'activate'
  try {
    await activateOrganization(orgId)
    await fetchData()
  } finally {
    isProcessing.value = false
    action.value = ''
  }
}

function showDeactivateModal() {
  deactivateReason.value = ''
  showModal.value = true
}

async function confirmDeactivate() {
  isProcessing.value = true
  action.value = 'deactivate'
  try {
    await deactivateOrganization(orgId, deactivateReason.value)
    showModal.value = false
    await fetchData()
  } finally {
    isProcessing.value = false
    action.value = ''
  }
}

onMounted(fetchData)
</script>
