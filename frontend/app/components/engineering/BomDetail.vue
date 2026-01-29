<template>
  <div v-if="bom" class="space-y-6">
    <!-- Header Summary Card -->
    <div class="card p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
      <div class="flex gap-4">
          <!-- Product Image -->
          <div 
            class="w-20 h-20 rounded-lg overflow-hidden bg-gray-50 border border-gray-200 flex-shrink-0 flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all"
            @click="openImage(bom.product?.image_url, bom.product?.name)"
            >
            <img 
                v-if="bom.product?.image_url" 
                :src="getImageUrl(bom.product.image_url)" 
                class="w-full h-full object-contain p-1" 
                :alt="bom.product?.name" 
            />
            <Icon v-else name="heroicons:cube" class="w-10 h-10 text-gray-300" />
          </div>

          <!-- Info -->
          <div class="flex-1">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">{{ bom.product?.name }}</h3>
                    <p class="text-sm text-gray-500">BOM #{{ bom.id }}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span :class="bom.is_active ? 'badge-success' : 'badge-gray'">
                        {{ bom.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span :class="bom.type === 'phantom' ? 'badge-warning' : 'badge-info'">
                        {{ bom.type }}
                    </span>
                </div>
            </div>
            <div class="mt-2 flex gap-4 text-sm">
                <div>
                    <span class="text-gray-500 block text-xs uppercase">Qty Produced</span>
                    <span class="font-medium">{{ Math.floor(bom.qty_produced) }}</span>
                </div>
                 <div>
                    <span class="text-gray-500 block text-xs uppercase">Operations</span>
                    <span class="font-medium">{{ operations.length }} steps</span>
                </div>
            </div>
          </div>
      </div>
    </div>

    <!-- Tabs Header -->
    <div class="border-b border-gray-200 overflow-x-auto scrollbar-hide">
      <nav class="flex gap-6">
        <button
          @click="activeTab = 'components'"
          class="py-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap"
          :class="activeTab === 'components' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Icon name="heroicons:cube" class="w-4 h-4 inline mr-1" />
          Components <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'components' ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600']">{{ lines.length }}</span>
        </button>
        <button
          @click="activeTab = 'operations'"
          class="py-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap"
          :class="activeTab === 'operations' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Icon name="heroicons:cog-6-tooth" class="w-4 h-4 inline mr-1" />
          Operations <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'operations' ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600']">{{ operations.length }}</span>
        </button>
      </nav>
    </div>

    <!-- Components Tab -->
    <div v-if="activeTab === 'components'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">Components / Materials</h3>
        <button class="btn-primary text-sm" @click="openLineModal()">
          <Icon name="heroicons:plus" class="w-4 h-4" />
          Add
        </button>
      </div>

      <div class="card p-0 overflow-hidden border border-gray-200 shadow-sm overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th class="w-16">Image</th>
              <th>Seq</th>
              <th>Product</th>
              <th>Quantity</th>
              <th class="w-20">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in paginatedLines" :key="line.id">
              <td>
                 <div 
                    class="w-8 h-8 rounded bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all"
                    @click="openImage(line.product?.image_url, line.product?.name)"
                    >
                    <img 
                        v-if="line.product?.image_url" 
                        :src="getImageUrl(line.product.image_url)" 
                        class="w-full h-full object-cover" 
                    />
                    <Icon v-else name="heroicons:cube" class="w-4 h-4 text-gray-300" />
                 </div>
              </td>
              <td class="font-mono text-sm text-gray-500">{{ line.sequence }}</td>
              <td class="font-medium">{{ line.product?.name || 'N/A' }}</td>
              <td>{{ Number(line.quantity) }}</td>
              <td>
                <div class="flex gap-1">
                  <button @click="openLineModal(line)" class="p-1 text-gray-500 hover:text-primary-600">
                    <Icon name="heroicons:pencil" class="w-4 h-4" />
                  </button>
                  <button @click="confirmDeleteLine(line)" class="p-1 text-gray-500 hover:text-red-600">
                    <Icon name="heroicons:trash" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="lines.length === 0">
              <td colspan="5" class="text-center text-gray-500 py-8">No components added yet</td>
            </tr>
          </tbody>
        </table>
      </div>
      <UiPagination
        v-if="lines.length > pageSize"
        v-model="compPage"
        :total-items="lines.length"
        :page-size="pageSize"
      />
    </div>

    <!-- Operations Tab -->
    <div v-if="activeTab === 'operations'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">Operations / Routing</h3>
        <button class="btn-primary text-sm" @click="openOperationModal()">
          <Icon name="heroicons:plus" class="w-4 h-4" />
          Add
        </button>
      </div>

      <div class="card p-0 overflow-hidden border border-gray-200 shadow-sm overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th>Seq</th>
              <th>Operation</th>
              <th>Work Center</th>
              <th>Duration</th>
              <th>QA</th>
              <th>Instruction</th>
              <th class="w-20">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="op in paginatedOperations" :key="op.id">
              <td class="font-mono text-sm text-gray-500">{{ op.sequence }}</td>
              <td class="font-medium">{{ op.name }}</td>
              <td>{{ op.work_center?.name || 'N/A' }}</td>
              <td>{{ op.duration_minutes }} min</td>
              <td>
                <span v-if="op.needs_quality_check" class="badge bg-yellow-100 text-yellow-800 text-xs">Required</span>
                <span v-else class="text-gray-400 text-sm">-</span>
              </td>
              <td>
                <UiFilePreview v-if="(op as any).instruction_file_url" :url="(op as any).instruction_file_url" />
                <span v-else class="text-gray-400 text-sm">-</span>
              </td>
              <td>
                <div class="flex gap-1">
                  <button @click="openOperationModal(op)" class="p-1 text-gray-500 hover:text-primary-600">
                    <Icon name="heroicons:pencil" class="w-4 h-4" />
                  </button>
                  <button @click="confirmDeleteOperation(op)" class="p-1 text-gray-500 hover:text-red-600">
                    <Icon name="heroicons:trash" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="operations.length === 0">
              <td colspan="7" class="text-center text-gray-500 py-8">No operations defined yet</td>
            </tr>
          </tbody>
        </table>
      </div>
      <UiPagination
        v-if="operations.length > pageSize"
        v-model="opPage"
        :total-items="operations.length"
        :page-size="pageSize"
      />
    </div>

    <!-- Component SlideOver (Nested) -->
    <UiSlideOver v-model="showLineModal" :title="editingLine ? 'Edit Component' : 'Add Component'" width="sm:w-[400px]">
      <form @submit.prevent="saveLine" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
          <select v-model="lineForm.product_id" class="input" required>
            <option value="">Select product...</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
          <input v-model.number="lineForm.quantity" type="number" min="0.0001" step="any" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sequence</label>
          <input v-model.number="lineForm.sequence" type="number" min="0" class="input" />
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showLineModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="savingLine">{{ savingLine ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>


    <!-- Operation SlideOver (Nested) -->
    <UiSlideOver v-model="showOperationModal" :title="editingOperation ? 'Edit Operation' : 'Add Operation'" width="sm:w-[400px]">
      <form @submit.prevent="saveOperation" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Operation Name</label>
          <input v-model="operationForm.name" type="text" class="input" required placeholder="e.g., Assembly" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Work Center</label>
          <select v-model="operationForm.work_center_id" class="input" required>
            <option value="">Select work center...</option>
            <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">{{ wc.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
          <input v-model.number="operationForm.duration_minutes" type="number" min="0" step="0.01" class="input" required />
        </div>
         <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sequence</label>
          <input v-model.number="operationForm.sequence" type="number" min="0" class="input" />
        </div>
        
        <!-- File Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Instruction File (PDF/Image)</label>
          
          <div v-if="operationForm.instruction_file_url" class="mb-3">
             <UiFilePreview :url="operationForm.instruction_file_url" />
             <button type="button" @click="operationForm.instruction_file_url = ''" class="mt-2 text-sm text-red-500 hover:text-red-700 flex items-center gap-1">
               <Icon name="heroicons:trash" class="w-4 h-4" />
               Remove File
             </button>
          </div>

          <div class="flex items-center gap-2">
            <input 
              type="file" 
              ref="fileInput" 
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
              accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp4"
              @change="handleFileUpload"
            />
            <Icon v-if="uploading" name="svg-spinners:180-ring" class="w-5 h-5 text-primary-600" />
          </div>
          <p class="text-xs text-gray-500 mt-1">Upload PDF, Image, or Video instructions.</p>
        </div>

        <!-- Requires Quality Check -->
        <div class="pt-2 border-t">
          <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50">
            <input v-model="operationForm.needs_quality_check" type="checkbox" class="rounded text-primary-600 w-5 h-5" />
            <div>
              <span class="text-sm font-medium text-gray-700">Requires Quality Check</span>
              <p class="text-xs text-gray-500">Enable if this operation requires QA inspection</p>
            </div>
          </label>
        </div>



        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showOperationModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="savingOperation || uploading">{{ savingOperation ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Modals -->
     <UiConfirmModal
      v-model="showDeleteLineModal"
      title="Delete Component"
      :message="`Are you sure?`"
      confirm-text="Delete"
      variant="danger"
      :loading="deletingLine"
      @confirm="deleteLine"
    />
     <UiConfirmModal
      v-model="showDeleteOperationModal"
      title="Delete Operation"
      :message="`Are you sure?`"
      confirm-text="Delete"
      variant="danger"
      :loading="deletingOperation"
      @confirm="deleteOperation"
    />

  </div>
  <div v-else class="flex justify-center py-12">
     <Icon name="svg-spinners:180-ring" class="w-8 h-8 text-primary-600" />
  </div>
  
  <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />
</template>

<script setup lang="ts">
import type { Bom, BomLine, Product, WorkCenter, Operation } from '~/types/models'

const props = defineProps<{
  bomId: number
}>()

const { $api } = useApi()
const toast = useToast()
const config = useRuntimeConfig()
const masterStore = useMasterStore()
const { getImageUrl } = useUtils()

// State
const bom = ref<Bom | null>(null)
const lines = ref<BomLine[]>([])
const operations = ref<Operation[]>([])
const products = computed(() => masterStore.products)
const workCenters = computed(() => masterStore.workCenters)

const activeTab = ref<'components' | 'operations'>('components')

// Pagination
const compPage = ref(1)
const opPage = ref(1)
const pageSize = 10

const paginatedLines = computed(() => {
  const start = (compPage.value - 1) * pageSize
  return lines.value.slice(start, start + pageSize)
})

const paginatedOperations = computed(() => {
  const start = (opPage.value - 1) * pageSize
  return operations.value.slice(start, start + pageSize)
})

// Nested Modals
const showLineModal = ref(false)
const editingLine = ref<BomLine | null>(null)
const savingLine = ref(false)
const lineForm = ref({ product_id: null as number | null, quantity: 1, sequence: 0 })

const showOperationModal = ref(false)
const editingOperation = ref<Operation | null>(null)
const savingOperation = ref(false)
const uploading = ref(false)
const operationForm = ref({
  name: '',
  work_center_id: null as number | null,
  duration_minutes: 10,
  sequence: 0,
  needs_quality_check: false,
  instruction_file_url: '' as string | null | undefined, // Added
})

// Delete State
const showDeleteLineModal = ref(false)
const deletingLineItem = ref<BomLine | null>(null)
const deletingLine = ref(false)

const showDeleteOperationModal = ref(false)
const deletingOperationItem = ref<Operation | null>(null)
const deletingOperation = ref(false)



// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })

function openImage(src?: string, alt?: string) {
  if (!src) return
  previewImage.value = { src: getImageUrl(src), alt: alt || 'Image' }
  showImagePreview.value = true
}



// Data Fetching
async function fetchData() {
    if (!props.bomId) return
    try {
        const [bomRes] = await Promise.all([
        $api<{ data: Bom & { lines: BomLine[], operations: Operation[] } }>(`/boms/${props.bomId}`),
        masterStore.fetchProducts(),
        masterStore.fetchWorkCenters(),
        ])
        bom.value = bomRes.data
        lines.value = bomRes.data.lines || []
        operations.value = bomRes.data.operations || []
    } catch (e) {
        toast.error('Failed to fetch BOM data')
    }
}

watch(() => props.bomId, fetchData, { immediate: true })

// --- Line Logic ---
function openLineModal(line?: BomLine) {
  if (line) {
    editingLine.value = line
    lineForm.value = { product_id: line.product_id, quantity: Number(line.quantity), sequence: line.sequence }
  } else {
    editingLine.value = null
    lineForm.value = { product_id: null, quantity: 1, sequence: lines.value.length }
  }
  showLineModal.value = true
}

async function saveLine() {
  savingLine.value = true
  try {
    if (editingLine.value) {
      await $api(`/boms/${props.bomId}/lines/${editingLine.value.id}`, { method: 'PUT', body: lineForm.value })
      toast.success('Component updated')
    } else {
      await $api(`/boms/${props.bomId}/lines`, { method: 'POST', body: lineForm.value })
      toast.success('Component added')
    }
    showLineModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save component')
  } finally {
    savingLine.value = false
  }
}

function confirmDeleteLine(line: BomLine) {
  deletingLineItem.value = line
  showDeleteLineModal.value = true
}

async function deleteLine() {
  if (!deletingLineItem.value) return
  deletingLine.value = true
  try {
    await $api(`/boms/${props.bomId}/lines/${deletingLineItem.value.id}`, { method: 'DELETE' })
    toast.success('Component removed')
    showDeleteLineModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete component')
  } finally {
    deletingLine.value = false
  }
}

// --- Operation Logic ---
function openOperationModal(op?: Operation) {
  if (op) {
    editingOperation.value = op
    operationForm.value = {
      name: op.name,
      work_center_id: op.work_center_id,
      duration_minutes: Number(op.duration_minutes),
      sequence: op.sequence,
      needs_quality_check: op.needs_quality_check,
      instruction_file_url: (op as any).instruction_file_url || '',
    }
  } else {
    editingOperation.value = null
    operationForm.value = {
      name: '',
      work_center_id: null,
      duration_minutes: 10,
      sequence: operations.value.length,
      needs_quality_check: false,
      instruction_file_url: '',
    }
  }

  showOperationModal.value = true
}


async function handleFileUpload(event: Event) {
    const target = event.target as HTMLInputElement
    // Ensure file exists
    if (!target.files || target.files.length === 0) return

    const file = target.files[0]
    if (!file) return

    uploading.value = true
    const formData = new FormData()
    formData.append('file', file)

    try {
        const res = await $api<{ url: string }>('/upload', {
            method: 'POST',
            body: formData,
        })
        operationForm.value.instruction_file_url = res.url
        toast.success('File uploaded')
    } catch (e: any) {
        console.error('File Upload Error:', e)
        const msg = e.data?.message || e.message || 'Upload failed'
        toast.error(`Upload error: ${msg}`)
    } finally {
        uploading.value = false
    }
}

async function saveOperation() {
  savingOperation.value = true
  try {
    if (editingOperation.value) {
      await $api(`/boms/${props.bomId}/operations/${editingOperation.value.id}`, { method: 'PUT', body: operationForm.value })
      toast.success('Operation updated')
    } else {
      await $api(`/boms/${props.bomId}/operations`, { method: 'POST', body: operationForm.value })
      toast.success('Operation added')
    }
    showOperationModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save operation')
  } finally {
    savingOperation.value = false
  }
}

function confirmDeleteOperation(op: Operation) {
  deletingOperationItem.value = op
  showDeleteOperationModal.value = true
}

async function deleteOperation() {
  if (!deletingOperationItem.value) return
  deletingOperation.value = true
  try {
    await $api(`/boms/${props.bomId}/operations/${deletingOperationItem.value.id}`, { method: 'DELETE' })
    toast.success('Operation removed')
    showDeleteOperationModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete operation')
  } finally {
    deletingOperation.value = false
  }
}


</script>
