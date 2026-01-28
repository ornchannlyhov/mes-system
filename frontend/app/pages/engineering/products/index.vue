<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Products</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Manage raw materials, components, and finished goods</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">Add Product</span>
        <span class="sm:hidden">Add</span>
      </button>
    </div>

    <!-- Type Tabs (like MO page) -->
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <button 
        @click="filterType = ''" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filterType === '' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        All <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filterType === '' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">{{ products.length }}</span>
      </button>
      <button 
        @click="filterType = 'raw'" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filterType === 'raw' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        Raw <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filterType === 'raw' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600']">{{ countByType('raw') }}</span>
      </button>
      <button 
        @click="filterType = 'component'" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filterType === 'component' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        Component <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filterType === 'component' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600']">{{ countByType('component') }}</span>
      </button>
      <button 
        @click="filterType = 'finished'" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filterType === 'finished' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        Finished <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filterType === 'finished' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600']">{{ countByType('finished') }}</span>
      </button>
    </div>

    <!-- Search -->
    <div class="flex gap-4">
      <input v-model="search" type="text" placeholder="Search products..." class="input max-w-full sm:max-w-xs" />
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table">
        <thead>
          <tr>
            <th class="w-16">Image</th>
            <th>Code</th>
            <th>Name</th>
            <th>Type</th>
            <th>Tracking</th>
            <th>UOM</th>
            <th>Cost</th>
            <th>Status</th>
            <th class="w-20">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in paginatedProducts" :key="product.id">
            <td>
              <div 
                  class="w-10 h-10 rounded bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary-500 hover:ring-offset-1 transition-all"
                  @click="openImage(product.image_url, product.name)"
              >
                <img 
                  v-if="product.image_url" 
                  :src="getImageUrl(product.image_url)" 
                  class="w-full h-full object-cover" 
                />
                <Icon v-else name="heroicons:cube" class="w-5 h-5 text-gray-300" />
              </div>
            </td>
            <td class="font-mono text-sm">{{ product.code }}</td>
            <td class="font-medium">
                <button @click="openDetail(product)" class="text-gray-900 hover:text-primary-600 hover:underline">
                    {{ product.name }}
                </button>
            </td>
            <td>
              <span :class="typeClass(product.type)">{{ product.type }}</span>
            </td>
            <td>{{ product.tracking }}</td>
            <td>{{ product.uom }}</td>
            <td>${{ Number(product.cost || 0).toFixed(2) }}</td>
            <td>
              <span :class="product.is_active ? 'badge-success' : 'badge-gray'">
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="flex gap-2">
                <UiIconButton
                  @click="openDetail(product)"
                  icon="heroicons:eye"
                  tooltip="View Details"
                />
                <UiIconButton
                  @click="openModal(product)"
                  icon="heroicons:pencil"
                  tooltip="Edit Product"
                />
                <UiIconButton
                  @click="confirmDelete(product)"
                  icon="heroicons:trash"
                  tooltip="Delete Product"
                  color="text-red-400 hover:text-red-600"
                />
              </div>
            </td>
          </tr>
          <tr v-if="filteredProducts.length === 0">
            <td colspan="9">
              <UiEmptyState 
                title="No products found" 
                description="Get started by creating your first product."
                icon="heroicons:cube"
              >
                <button class="btn-primary" @click="openModal()">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  Add Product
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      
      <!-- Pagination -->
      <UiPagination v-model="currentPage" :total-items="filteredProducts.length" :page-size="pageSize" />
    </div>

    <!-- Image Preview -->
    <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />

    <!-- Edit SlideOver -->
    <UiSlideOver v-model="showModal" :title="editingProduct ? 'Edit Product' : 'Add Product'">
      <form @submit.prevent="saveProduct" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
            <input v-model="form.code" type="text" class="input" required />
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="form.name" type="text" class="input" required />
          </div>
  
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select v-model="form.type" class="input" required>
              <option value="raw">Raw Material</option>
              <option value="component">Component</option>
              <option value="finished">Finished Good</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tracking</label>
            <select v-model="form.tracking" class="input" required>
              <option value="none">None</option>
              <option value="lot">Lot</option>
              <option value="serial">Serial</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">UOM</label>
            <input v-model="form.uom" type="text" class="input" required />
          </div>
  
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cost</label>
            <input v-model.number="form.cost" type="number" step="0.01" class="input" />
          </div>

            <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
            <div class="flex items-center gap-4">
              <div class="w-20 h-20 rounded bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden relative">
                <img 
                  v-if="imagePreview || form.image_url" 
                  :src="imagePreview || getImageUrl(form.image_url || undefined)" 
                  class="w-full h-full object-cover" 
                />
                <Icon v-else name="heroicons:photo" class="w-8 h-8 text-gray-300" />
                <button 
                  v-if="imagePreview || form.image_url" 
                  @click.prevent="clearImage"
                  class="absolute top-0 right-0 bg-red-500 text-white p-0.5 rounded-bl hover:bg-red-600 transition-colors"
                  title="Remove image"
                >
                  <Icon name="heroicons:x-mark" class="w-3 h-3" />
                </button>
              </div>
              <div class="flex-1">
                <input 
                  type="file" 
                  accept="image/*" 
                  @change="handleImageSelect" 
                  ref="fileInput"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                />
                <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 2MB</p>
              </div>
            </div>
          </div>

          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" class="input" rows="2"></textarea>
          </div>
          <div class="flex items-center col-span-2">
            <label class="flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" class="rounded text-primary-600" />
              <span class="text-sm text-gray-700">Active</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </UiSlideOver>
    
    <!-- Detail SlideOver -->
    <UiSlideOver v-model="showDetailModal" title="Product Details" width="sm:w-[60vw]">
        <EngineeringProductDetail v-if="selectedProductId" :product-id="selectedProductId" />
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Product"
      :message="`Are you sure you want to delete '${deletingItem?.name}'? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="deleteProduct"
    />
  </div>
</template>

<script setup lang="ts">
import type { Product } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const masterStore = useMasterStore()
const { getImageUrl } = useUtils()

// State - products now comes from the store
const products = computed(() => masterStore.products)
const search = ref('')
const filterType = ref('')
const showModal = ref(false)
const editingProduct = ref<Product | null>(null)
const saving = ref(false)
const currentPage = ref(1)
const pageSize = 10

// Detail view state
const showDetailModal = ref(false)
const selectedProductId = ref<number | null>(null)

// Delete state
const showDeleteModal = ref(false)
const deletingItem = ref<Product | null>(null)
const deleting = ref(false)

const form = ref({
  code: '',
  name: '',
  description: '',
  type: 'raw' as 'raw' | 'component' | 'finished',
  tracking: 'none' as 'none' | 'lot' | 'serial',
  uom: 'pcs',
  cost: 0,
  is_active: true,
  image_url: '' as string | null, // Current image URL
})

const selectedFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

// Computed
function countByType(type: string) {
  return products.value.filter(p => p.type === type).length
}

const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchesSearch = !search.value || 
      p.name.toLowerCase().includes(search.value.toLowerCase()) ||
      p.code.toLowerCase().includes(search.value.toLowerCase())
    const matchesType = !filterType.value || p.type === filterType.value
    return matchesSearch && matchesType
  })
})

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredProducts.value.slice(start, start + pageSize)
})

// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })

function openImage(src?: string, alt?: string) {
  console.log('Open Image:', src);
  if (!src) return
  previewImage.value = { src: getImageUrl(src), alt: alt || 'Product Image' }
  showImagePreview.value = true
}

// Reset to page 1 when filter changes
watch([search, filterType], () => {
  currentPage.value = 1
})

function typeClass(type: string) {
  const classes: Record<string, string> = {
    raw: 'badge bg-amber-100 text-amber-800',
    component: 'badge bg-blue-100 text-blue-800',
    finished: 'badge bg-green-100 text-green-800',
  }
  return classes[type] || 'badge-gray'
}

async function fetchProducts() {
  try {
    await masterStore.fetchProducts()
  } catch (e) {
    toast.error('Failed to fetch products')
  }
}

function handleImageSelect(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    selectedFile.value = file
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

function clearImage() {
  selectedFile.value = null
  imagePreview.value = null
  form.value.image_url = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function openModal(product?: Product) {
  selectedFile.value = null
  imagePreview.value = null
  if (fileInput.value) fileInput.value.value = ''

  if (product) {
    editingProduct.value = product
    form.value = { 
        ...product, 
        description: product.description ?? '',
        image_url: product.image_url ?? null
    }
  } else {
    editingProduct.value = null
    form.value = {
      code: '',
      name: '',
      description: '',
      type: 'raw',
      tracking: 'none',
      uom: 'pcs',
      cost: 0,
      is_active: true,
      image_url: null,
    }
  }
  showModal.value = true
}

async function saveProduct() {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('code', form.value.code)
    formData.append('name', form.value.name)
    formData.append('type', form.value.type)
    formData.append('tracking', form.value.tracking)
    formData.append('uom', form.value.uom)
    formData.append('cost', String(form.value.cost))
    formData.append('is_active', form.value.is_active ? '1' : '0')
    if (form.value.description) {
        formData.append('description', form.value.description)
    }

    if (selectedFile.value) {
      formData.append('image', selectedFile.value)
    }

    if (editingProduct.value) {
      formData.append('_method', 'PUT')
      await $api(`/products/${editingProduct.value.id}`, {
        method: 'POST', // Use POST for FormData with file upload
        body: formData,
      })
      toast.success('Product updated successfully')
    } else {
      await $api('/products', {
        method: 'POST',
        body: formData,
      })
      toast.success('Product created successfully')
    }
    showModal.value = false
    await masterStore.fetchProducts(true) // Force refresh cache
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save product')
  } finally {
    saving.value = false
  }
}

function openDetail(product: Product) {
  selectedProductId.value = product.id
  showDetailModal.value = true
}

function confirmDelete(product: Product) {
  deletingItem.value = product
  showDeleteModal.value = true
}

async function deleteProduct() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    await $api(`/products/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('Product deleted successfully')
    showDeleteModal.value = false
    await masterStore.fetchProducts(true) // Force refresh cache
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete product')
  } finally {
    deleting.value = false
  }
}

const route = useRoute()

onMounted(async () => {
    await fetchProducts()
    if (route.query.id) {
         const id = Number(route.query.id)
         if (!isNaN(id)) {
             selectedProductId.value = id
             showDetailModal.value = true
         }
    }
})
</script>
