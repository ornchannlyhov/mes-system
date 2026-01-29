<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Bill of Materials</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Define product recipes, components, and manufacturing operations</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">Add BOM</span>
        <span class="sm:hidden">Add</span>
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <button 
        @click="filters.type = ''" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filters.type === '' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        All <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filters.type === '' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">{{ counts.all || total }}</span>
      </button>
      <button 
        @click="filters.type = 'normal'" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filters.type === 'normal' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        Normal <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filters.type === 'normal' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600']">{{ counts.normal || 0 }}</span>
      </button>
      <button 
        @click="filters.type = 'phantom'" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filters.type === 'phantom' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        Phantom <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filters.type === 'phantom' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600']">{{ counts.phantom || 0 }}</span>
      </button>
    </div>

    <!-- Search -->
    <div class="flex gap-4">
      <input v-model="search" type="text" placeholder="Search product name or code..." class="input max-w-full sm:max-w-xs" />
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th class="w-16">Image</th>
            <th>ID</th>
            <th>Product</th>
            <th>Type</th>
            <th>Components</th>
            <th>Operations</th>
            <th>Qty Produced</th>
            <th>Status</th>
            <th class="w-20">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="bom in boms" :key="bom.id">
            <td>
                <div 
                    class="w-10 h-10 rounded bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary-500 hover:ring-offset-1 transition-all"
                     @click="openImage(bom.product?.image_url, bom.product?.name)"
                >
                <img 
                    v-if="bom.product?.image_url" 
                    :src="getImageUrl(bom.product.image_url)" 
                    class="w-full h-full object-cover" 
                />
                <Icon v-else name="heroicons:cube" class="w-5 h-5 text-gray-300" />
                </div>
            </td>
            <td class="font-mono text-sm">#{{ bom.id }}</td>
            <td class="font-medium">
                <button @click="openDetail(bom)" class="text-primary-600 hover:underline font-medium">
                    {{ bom.product?.name || 'N/A' }}
                </button>
            </td>
            <td>
              <span :class="bom.type === 'phantom' ? 'badge-warning' : 'badge-info'">
                {{ bom.type }}
              </span>
            </td>
            <td>{{ bom.lines?.length || 0 }} items</td>
            <td>{{ bom.operations?.length || 0 }} steps</td>
            <td>{{ Math.floor(bom.qty_produced) }}</td>
            <td>
              <span :class="bom.is_active ? 'badge-success' : 'badge-gray'">
                {{ bom.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="flex gap-2">
                <UiIconButton
                  @click="openDetail(bom)"
                  icon="heroicons:eye"
                  tooltip="View Details"
                />
                <UiIconButton
                  @click="openModal(bom)"
                  icon="heroicons:pencil"
                  tooltip="Edit BOM"
                />
                <UiIconButton
                  @click="duplicateBom(bom)"
                  icon="heroicons:document-duplicate"
                  tooltip="Duplicate BOM"
                />
                <UiIconButton
                  @click="confirmDelete(bom)"
                  icon="heroicons:trash"
                  tooltip="Delete BOM"
                  color="text-red-400 hover:text-red-600"
                />
              </div>
            </td>
          </tr>
           <tr v-if="boms.length === 0 && !loading">
            <td colspan="9">
              <UiEmptyState 
                title="No BOMs found" 
                description="Create a Bill of Materials to define your product structure."
                icon="heroicons:document-text"
              >
                <button class="btn-primary" @click="openModal()">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  Add BOM
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
        <tbody v-if="loading">
          <tr v-for="i in 5" :key="i" class="animate-pulse">
            <td class="px-6 py-4"><div class="w-10 h-10 bg-gray-200 rounded"></div></td>
             <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
            <td class="px-6 py-4"><div class="h-5 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-12"></div></td>
            <td class="px-6 py-4"><div class="h-5 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-24"></div></td>
          </tr>
        </tbody>
      </table>
      </div>
      <UiPagination 
        v-if="Math.ceil(total / perPage) > 1"
        v-model="page" 
        :total-items="total" 
        :page-size="perPage" 
      />
    </div>



    <!-- Image Preview -->
    <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />

    <!-- Create/Edit BOM SlideOver -->
    <UiSlideOver v-model="showModal" :title="editing ? 'Edit BOM' : 'Create Bill of Materials'">
      <form @submit.prevent="save" class="space-y-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product (Finished Good)</label>
            <UiSearchableSelect
              v-model="form.product_id"
              :options="finishedGoods.map(p => ({ label: `${p.name} (${p.code})`, value: p.id }))"
              placeholder="Select product to manufacture..."
            />
          </div>
          <div class="col-span-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">BOM Type</label>
            <select v-model="form.type" class="input">
              <option value="normal">Normal</option>
              <option value="phantom">Kit / Phantom</option>
            </select>
          </div>
          <div class="col-span-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Qty Produced</label>
            <input v-model.number="form.qty_produced" type="number" min="1" class="input" required />
          </div>
          <div class="col-span-4 flex items-end pb-3">
            <label class="flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" class="rounded text-primary-600" />
              <span class="text-sm text-gray-700">Active</span>
            </label>
          </div>
        </div>

        <!-- Components Section -->
        <div class="space-y-3">
          <div class="flex items-center justify-between border-b pb-2">
            <h4 class="font-medium text-gray-800">Components (Materials)</h4>
            <button type="button" @click="addComponent" class="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1">
              <Icon name="heroicons:plus" class="w-4 h-4" />
              Add Component
            </button>
          </div>
          <div v-if="form.lines.length === 0" class="text-center text-gray-400 py-4 border border-dashed rounded-lg">
            No components added yet
          </div>
          <div v-else class="space-y-2">
            <div v-for="(line, idx) in form.lines" :key="idx" class="flex gap-2 items-start p-3 bg-gray-50 rounded-lg">
              <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">Product</label>
                <select v-model="line.product_id" class="input text-sm" required>
                  <option value="">Select...</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="w-24">
                <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                <input v-model.number="line.quantity" type="number" min="0.0001" step="any" class="input text-sm" required />
              </div>
              <button type="button" @click="removeComponent(idx)" class="mt-6 p-1 text-gray-400 hover:text-red-600">
                <Icon name="heroicons:trash" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Operations Section -->
        <div class="space-y-3">
          <div class="flex items-center justify-between border-b pb-2">
            <h4 class="font-medium text-gray-800">Operations (Routing)</h4>
            <button type="button" @click="addOperation" class="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1">
              <Icon name="heroicons:plus" class="w-4 h-4" />
              Add Operation
            </button>
          </div>
          <div v-if="form.operations.length === 0" class="text-center text-gray-400 py-4 border border-dashed rounded-lg">
            No operations defined yet
          </div>
          <div v-else class="space-y-2">
            <div v-for="(op, idx) in form.operations" :key="idx" class="p-3 bg-gray-50 rounded-lg space-y-2">
              <div class="flex gap-2 items-start">
                <div class="w-8 text-center">
                  <span class="text-xs text-gray-400">{{ idx + 1 }}</span>
                </div>
                <div class="flex-1">
                  <label class="block text-xs text-gray-500 mb-1">Operation Name</label>
                  <input v-model="op.name" type="text" class="input text-sm" placeholder="e.g., Assembly" required />
                </div>
                <div class="flex-1">
                  <label class="block text-xs text-gray-500 mb-1">Work Center</label>
                  <select v-model="op.work_center_id" class="input text-sm" required>
                    <option value="">Select...</option>
                    <option v-for="wc in workCenters" :key="wc.id" :value="wc.id">{{ wc.name }}</option>
                  </select>
                </div>
                <div class="w-24">
                  <label class="block text-xs text-gray-500 mb-1">Duration</label>
                  <input v-model.number="op.duration_minutes" type="number" min="0" class="input text-sm" placeholder="min" required />
                </div>
                <button type="button" @click="removeOperation(idx)" class="mt-6 p-1 text-gray-400 hover:text-red-600">
                  <Icon name="heroicons:trash" class="w-4 h-4" />
                </button>
              </div>
              <div class="flex gap-4 ml-10 mt-2 items-center">
                <div class="border-l pl-4 flex items-center gap-2">
                   <div v-if="op.instruction_file_url" class="flex items-center gap-2">
                       <UiFilePreview :url="op.instruction_file_url" />
                       <button type="button" @click="op.instruction_file_url = ''" class="text-red-400 hover:text-red-600">
                           <Icon name="heroicons:trash" class="w-4 h-4" />
                       </button>
                   </div>
                   <label v-else class="cursor-pointer flex items-center gap-1 text-xs text-primary-600 hover:text-primary-800">
                       <Icon name="heroicons:paper-clip" class="w-3.5 h-3.5" />
                       <span>Attach File</span>
                       <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp4" @change="(e) => handleFileUpload(e, idx)" />
                   </label>
                </div>
                <div class="border-l pl-4">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="op.needs_quality_check" type="checkbox" class="rounded text-primary-600" />
                    <span class="text-xs text-gray-700">Requires QA</span>
                  </label>
                </div>
              </div>
              

            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save BOM' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Detail SlideOver (75% width) -->
    <UiSlideOver v-model="showDetailModal" title="BOM Details" width="sm:w-[75vw]">
       <EngineeringBomDetail v-if="selectedBomId" :bom-id="selectedBomId" />
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete BOM"
      :message="`Are you sure you want to delete BOM #${deletingItem?.id}? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="deleteBom"
    />
  </div>
</template>

<script setup lang="ts">
import type { Bom, BomLine, Operation } from '~/types/models'

interface BomLineForm {
  id?: number
  product_id: number | null
  quantity: number
  sequence: number
}

interface OperationForm {
  id?: number
  name: string
  work_center_id: number | null
  duration_minutes: number
  sequence: number
  needs_quality_check: boolean
  instruction_file_url?: string
}

const { $api } = useApi()
const toast = useToast()
const config = useRuntimeConfig()
const masterStore = useMasterStore()
const { getImageUrl } = useUtils()

// Server Data Table
const { 
  items: boms, 
  total, 
  loading, 
  counts, 
  page, 
  perPage, 
  search, 
  filters, 
  refresh 
} = useServerDataTable<Bom>({
  url: 'boms',
  perPage: 10,
  initialFilters: { type: '' }
})

const products = computed(() => masterStore.products)
const finishedGoods = computed(() => products.value.filter(p => p.type === 'finished'))
const workCenters = computed(() => masterStore.workCenters)

const showModal = ref(false)
const editing = ref<Bom | null>(null)
const saving = ref(false)

// Detail SlideOver State
const showDetailModal = ref(false)
const selectedBomId = ref<number | null>(null)

// Delete state
const showDeleteModal = ref(false)
const deletingItem = ref<Bom | null>(null)
const deleting = ref(false)

const form = ref({
  product_id: null as number | null,
  type: 'normal' as 'normal' | 'phantom',
  qty_produced: 1,
  is_active: true,
  lines: [] as BomLineForm[],
  operations: [] as OperationForm[],
})


async function fetchData() {
  try {
     await Promise.all([
        masterStore.fetchProducts(),
        masterStore.fetchWorkCenters(),
     ])
  } catch (e) {
     console.error(e)
  }
}

// Watchers handled by composable

function addComponent() {
  form.value.lines.push({
    product_id: null,
    quantity: 1,
    sequence: form.value.lines.length,
  })
}

function removeComponent(index: number) {
  form.value.lines.splice(index, 1)
}

function addOperation() {
  form.value.operations.push({
    name: '',
    work_center_id: null,
    duration_minutes: 10,
    sequence: form.value.operations.length,
    needs_quality_check: false,
    instruction_file_url: '',
  })
}

function removeOperation(index: number) {
  form.value.operations.splice(index, 1)
}

function duplicateBom(bom: Bom & { lines?: BomLine[], operations?: Operation[] }) {
  editing.value = null
  form.value = {
    product_id: bom.product_id,
    type: bom.type,
    qty_produced: bom.qty_produced,
    is_active: bom.is_active,
    lines: (bom.lines || []).map(l => ({
      product_id: l.product_id,
      quantity: Number(l.quantity),
      sequence: l.sequence,
    })),
    operations: (bom.operations || []).map(o => ({
      name: o.name,
      work_center_id: o.work_center_id,
      duration_minutes: Number(o.duration_minutes),
      sequence: o.sequence,
      needs_quality_check: o.needs_quality_check,
      instruction_file_url: (o as any).instruction_file_url || '',
    })),
  }
  showModal.value = true
}

function openModal(bom?: Bom & { lines?: BomLine[], operations?: Operation[] }) {
  if (bom) {
    editing.value = bom
    form.value = {
      product_id: bom.product_id,
      type: bom.type,
      qty_produced: bom.qty_produced,
      is_active: bom.is_active,
      lines: (bom.lines || []).map(l => ({
        id: l.id,
        product_id: l.product_id,
        quantity: Number(l.quantity),
        sequence: l.sequence,
      })),
      operations: (bom.operations || []).map(o => ({
        id: o.id,
        name: o.name,
        work_center_id: o.work_center_id,
        duration_minutes: Number(o.duration_minutes),
        sequence: o.sequence,
        needs_quality_check: o.needs_quality_check,
        instruction_file_url: (o as any).instruction_file_url || '',
      })),
    }
  } else {
    editing.value = null
    form.value = {
      product_id: null,
      type: 'normal',
      qty_produced: 1,
      is_active: true,
      lines: [],
      operations: [],
    }
  }
  showModal.value = true
}


function openDetail(bom: Bom) {
    selectedBomId.value = bom.id
    showDetailModal.value = true
}

async function save() {
  saving.value = true
  try {
    const payload = {
      ...form.value,
      lines: form.value.lines.map((l, i) => ({ ...l, sequence: i })),
      operations: form.value.operations.map((o, i) => ({ ...o, sequence: i })),
    }

    if (editing.value) {
      await $api(`/boms/${editing.value.id}`, { method: 'PUT', body: payload })
      toast.success('BOM updated successfully')
    } else {
      await $api('/boms', { method: 'POST', body: payload })
      toast.success('BOM created successfully')
    }
    showModal.value = false
    refresh()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save BOM')
  } finally {
    saving.value = false
  }
}

function confirmDelete(bom: Bom) {
  deletingItem.value = bom
  showDeleteModal.value = true
}

async function deleteBom() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    await $api(`/boms/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('BOM deleted successfully')
    showDeleteModal.value = false
    refresh()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete BOM')
  } finally {
    deleting.value = false
  }
}



// File upload logic
async function handleFileUpload(event: Event, index: number) {
  const target = event.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return

  const file = target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    const res = await $api<{ url: string }>('/upload', {
      method: 'POST',
      body: formData,
    })
    
    if (form.value.operations[index]) {
        form.value.operations[index].instruction_file_url = res.url
        toast.success('File uploaded')
    }
  } catch (e: any) {
    console.error('File Upload Error:', e)
    const msg = e.data?.message || e.message || 'Upload failed'
    toast.error(`Upload error: ${msg}`)
  }
}

// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })

function openImage(src?: string, alt?: string) {
  if (!src) return
  previewImage.value = { src: getImageUrl(src), alt: alt || 'BOM Product' }
  showImagePreview.value = true
}

const route = useRoute()

onMounted(async () => {
    // initial fetch of master data
    await fetchData()
    if (route.query.id) {
        const id = Number(route.query.id)
        if (!isNaN(id)) {
            selectedBomId.value = id
            showDetailModal.value = true
        }
    }
    }
)

</script>
