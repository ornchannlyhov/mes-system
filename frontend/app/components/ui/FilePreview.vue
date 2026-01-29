<template>
  <div class="file-preview inline-flex items-center">
    <!-- Clickable filename with icon -->
    <button 
      type="button"
      @click="openPreview"
      class="flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 hover:underline"
    >
      <Icon :name="fileIcon" class="w-4 h-4 flex-shrink-0" />
      <span class="truncate max-w-[150px]">{{ fileName }}</span>
    </button>

    <!-- Preview Modal -->
    <UiSlideOver v-model="showPreview" :title="fileName" width="sm:w-[600px]">
      <div class="flex flex-col items-center justify-center min-h-[300px] p-4">
        <!-- Image Preview -->
        <template v-if="isImage">
          <img 
            :src="fullUrl" 
            :alt="fileName" 
            class="max-w-full max-h-[70vh] rounded-lg shadow-lg object-contain"
          />
        </template>

        <!-- PDF Preview -->
        <template v-else-if="isPdf">
          <div class="text-center space-y-4">
            <div class="w-20 h-20 bg-red-100 rounded-2xl flex items-center justify-center mx-auto">
              <Icon name="heroicons:document-text" class="w-10 h-10 text-red-500" />
            </div>
            <p class="text-gray-600">{{ fileName }}</p>
            <a 
              :href="fullUrl" 
              target="_blank" 
              class="btn-primary inline-flex items-center gap-2"
            >
              <Icon name="heroicons:arrow-top-right-on-square" class="w-4 h-4" />
              Open PDF in New Tab
            </a>
          </div>
        </template>

        <!-- Video Preview -->
        <template v-else-if="isVideo">
          <video 
            :src="fullUrl" 
            controls 
            autoplay
            class="max-w-full max-h-[70vh] rounded-lg shadow-lg"
          >
            Your browser does not support the video tag.
          </video>
        </template>

        <!-- Fallback -->
        <template v-else>
          <div class="text-center space-y-4">
            <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto">
              <Icon name="heroicons:paper-clip" class="w-10 h-10 text-gray-400" />
            </div>
            <p class="text-gray-600">{{ fileName }}</p>
            <a 
              :href="fullUrl" 
              target="_blank" 
              class="btn-primary inline-flex items-center gap-2"
            >
              <Icon name="heroicons:arrow-down-tray" class="w-4 h-4" />
              Download File
            </a>
          </div>
        </template>
      </div>
    </UiSlideOver>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  url: string
  baseUrl?: string
}>()

const config = useRuntimeConfig()
const showPreview = ref(false)

// Get full URL
const fullUrl = computed(() => {
  if (!props.url) return ''
  if (props.url.startsWith('http')) return props.url
  const base = props.baseUrl || (config.public.apiBase as string).replace('/api', '')
  return base + props.url
})

// Extract filename from URL
const fileName = computed(() => {
  if (!props.url) return 'File'
  const parts = props.url.split('/')
  return parts[parts.length - 1] || 'File'
})

// File extension
const extension = computed(() => {
  const name = fileName.value.toLowerCase()
  const dotIndex = name.lastIndexOf('.')
  return dotIndex > -1 ? name.substring(dotIndex + 1) : ''
})

// Type checks
const isImage = computed(() => ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension.value))
const isPdf = computed(() => extension.value === 'pdf')
const isVideo = computed(() => ['mp4', 'webm', 'ogg'].includes(extension.value))

// Icon based on file type
const fileIcon = computed(() => {
  if (isImage.value) return 'heroicons:photo'
  if (isPdf.value) return 'heroicons:document-text'
  if (isVideo.value) return 'heroicons:film'
  return 'heroicons:paper-clip'
})

function openPreview() {
  showPreview.value = true
}
</script>
