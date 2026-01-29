<template>
  <div v-if="loading" class="p-8 flex justify-center">
    <Icon name="heroicons:arrow-path" class="w-8 h-8 animate-spin text-gray-400" />
  </div>
  
  <div v-else-if="product" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Image Column -->
        <div class="md:col-span-1">
            <div 
                class="aspect-square w-full bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all p-4"
                @click="openImage(product.image_url, product.name)"
            >
                <img 
                v-if="product.image_url" 
                :src="getImageUrl(product.image_url)" 
                class="w-full h-full object-contain" 
                />
                <Icon v-else name="heroicons:cube" class="w-24 h-24 text-gray-300" />
            </div>
             <div class="mt-4 card p-4 bg-gray-50 border-gray-100">
                <div class="text-xs text-gray-500 uppercase font-medium tracking-wider mb-2">Description</div>
                <p class="text-gray-600 text-sm whitespace-pre-line">{{ product.description || 'No description provided.' }}</p>
            </div>
        </div>

        <!-- Details Column -->
        <div class="md:col-span-2 space-y-6">
            <!-- Header -->
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900">{{ product.name }}</h1>
                    <span :class="product.is_active ? 'badge-success' : 'badge-gray'">
                        {{ product.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                 <div class="flex flex-wrap gap-2 text-sm">
                    <span class="bg-gray-100 px-2 py-0.5 rounded font-mono text-gray-600">{{ product.code }}</span>
                    <span :class="typeClass(product.type)">{{ product.type }}</span>
                    <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 capitalize">Tracking: {{ product.tracking }}</span>
                 </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="card p-4 bg-blue-50 border-blue-100">
                    <div class="text-xs text-blue-600 uppercase font-medium tracking-wider mb-1">On Hand</div>
                    <div class="text-2xl font-bold text-blue-900">{{ Number(product.on_hand || 0).toFixed(2) }} <span class="text-sm font-normal text-blue-600 mb-1">{{ product.uom }}</span></div>
                </div>
                <div class="card p-4 bg-gray-50 border-gray-100">
                    <div class="text-xs text-gray-500 uppercase font-medium tracking-wider mb-1">Cost</div>
                    <div class="text-2xl font-bold text-gray-900">${{ Number(product.cost || 0).toFixed(2) }}</div>
                </div>
            </div>

            <!-- Relations Tabs -->
             <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-6">
                    <button 
                         v-if="product.type === 'finished'"
                         @click="activeTab = 'produces'"
                         :class="[activeTab === 'produces' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm']"
                    >
                        Produces (BOMs)
                         <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">{{ relatedBoms.length }}</span>
                    </button>
                    <button 
                         v-else
                         @click="activeTab = 'used_in'"
                         :class="[activeTab === 'used_in' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm']"
                    >
                        Used In (BOMs)
                         <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">{{ usedInBoms.length }}</span>
                    </button>
                     <button 
                         v-if="product.type === 'finished'"
                         @click="activeTab = 'recent_mos'"
                         :class="[activeTab === 'recent_mos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm']"
                    >
                        Recent MOs
                         <span class="ml-1 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">{{ recentMos.length }}</span>
                    </button>
                </nav>
            </div>

            <div class="py-4">
                 <!-- Produces Tab -->
                <div v-if="activeTab === 'produces'" class="space-y-3">
                    <div v-if="relatedBoms.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg dashed border border-gray-200">
                        No BOMs found where this product is the finish good.
                    </div>
                     <div v-for="bom in relatedBoms" :key="bom.id" class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div>
                             <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">BOM #{{ bom.id }}</span>
                                <span :class="bom.type === 'phantom' ? 'badge-warning' : 'badge-info'">{{ bom.type }}</span>
                             </div>
                             <div class="text-sm text-gray-500 mt-1">
                                Produces {{ Number(bom.qty_produced) }} {{ product.uom }}
                             </div>
                        </div>
                        <NuxtLink :to="`/engineering/boms?id=${bom.id}`" class="btn-sm btn-outline">
                            View BOM
                        </NuxtLink>
                    </div>
                </div>

                <!-- Used In Tab -->
                <div v-if="activeTab === 'used_in'" class="space-y-3">
                    <div v-if="usedInBoms.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg dashed border border-gray-200">
                        This product is not used as a component in any BOM.
                    </div>
                    <div v-for="bom in usedInBoms" :key="bom.id" class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div>
                             <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">BOM #{{ bom.id }}</span>
                                <span class="text-xs text-gray-500">for</span>
                                <span class="font-medium text-gray-900">{{ bom.product?.name }}</span>
                             </div>
                        </div>
                         <NuxtLink :to="`/engineering/boms?id=${bom.id}`" class="btn-sm btn-outline">
                            View BOM
                        </NuxtLink>
                    </div>
                </div>
                
                 <!-- Recent MOs Tab -->
                <div v-if="activeTab === 'recent_mos'" class="space-y-3">
                    <div v-if="recentMos.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg dashed border border-gray-200">
                        No recent Manufacturing Orders found.
                    </div>
                    <div v-for="mo in recentMos" :key="mo.id" class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div>
                             <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ mo.name }}</span>
                                <UiStatusBadge :status="mo.status" size="sm" />
                             </div>
                             <div class="text-xs text-gray-500 mt-1">
                                {{ formatDate(mo.created_at) }} &bull; Qty: {{ Number(mo.qty_to_produce) }}
                             </div>
                        </div>
                         <NuxtLink :to="`/execution/manufacturing-orders?id=${mo.id}`" class="btn-sm btn-outline">
                            View
                        </NuxtLink>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Image Preview -->
    <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />
  </div>
</template>

<script setup lang="ts">
import type { Product, Bom, ManufacturingOrder } from '~/types/models'

const props = defineProps<{
  productId: number
}>()

const { $api } = useApi()
const { getImageUrl, formatDate } = useUtils()

const loading = ref(true)
const product = ref<Product | null>(null)
const relatedBoms = ref<Bom[]>([]) // Produces
const usedInBoms = ref<Bom[]>([]) // Used In
const recentMos = ref<ManufacturingOrder[]>([])

const activeTab = ref('produces')

// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })

function openImage(src?: string, alt?: string) {
  if (!src) return
  previewImage.value = { src: getImageUrl(src), alt: alt || 'Product Image' }
  showImagePreview.value = true
}

function typeClass(type: string) {
  const classes: Record<string, string> = {
    raw: 'badge bg-amber-100 text-amber-800',
    component: 'badge bg-blue-100 text-blue-800',
    finished: 'badge bg-green-100 text-green-800',
  }
  return classes[type] || 'badge-gray'
}

async function fetchDetails() {
  loading.value = true
  try {
    const res = await $api<{ data: any }>(`/products/${props.productId}`)
    product.value = res.data
    relatedBoms.value = res.data.boms || []
    usedInBoms.value = res.data.used_in_boms || [] // from backend update
    recentMos.value = res.data.recent_mos || [] // from backend update
    
    // Set default tab based on type
    if (product.value && product.value.type === 'finished') {
        activeTab.value = 'produces'
    } else {
        activeTab.value = 'used_in'
    }
    
  } catch (e) {
    console.error('Failed to fetch product details', e)
  } finally {
    loading.value = false
  }
}

watch(() => props.productId, fetchDetails, { immediate: true })

</script>
