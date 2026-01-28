<template>
  <div class="relative" ref="containerRef">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>
    
    <div class="relative">
      <div
        @click="toggle"
        class="input flex items-center justify-between cursor-pointer pr-10 bg-white"
        :class="{ 'ring-2 ring-primary-500 border-primary-500': isOpen }"
      >
        <span v-if="selectedLabel" class="truncate text-gray-900">
          {{ selectedLabel }}
        </span>
        <span v-else class="text-gray-400 truncate">
          {{ placeholder || 'Select an option...' }}
        </span>

        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
          <Icon name="heroicons:chevron-up-down" class="w-5 h-5 text-gray-400" />
        </span>
      </div>

      <!-- Dropdown -->
      <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="transform scale-95 opacity-0"
        enter-to-class="transform scale-100 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="transform scale-100 opacity-100"
        leave-to-class="transform scale-95 opacity-0"
      >
        <div
          v-if="isOpen"
          class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-hidden flex flex-col"
        >
          <!-- Search Input -->
          <div class="p-2 border-b border-gray-100 sticky top-0 bg-white">
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              class="w-full px-3 py-1.5 text-sm bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:bg-white transition-colors"
              placeholder="Search..."
              @click.stop
            />
          </div>

          <!-- Options List -->
          <ul class="overflow-y-auto flex-1 py-1">
            <li
              v-for="option in filteredOptions"
              :key="option.value"
              @click="select(option)"
              class="px-3 py-2 text-sm cursor-pointer hover:bg-primary-50 hover:text-primary-700 transition-colors flex items-center justify-between group"
              :class="{ 'bg-primary-50 text-primary-700 font-medium': option.value === modelValue }"
            >
              <span class="truncate">{{ option.label }}</span>
              <Icon 
                v-if="option.value === modelValue" 
                name="heroicons:check" 
                class="w-4 h-4 text-primary-600" 
              />
            </li>
            <li v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500 text-center italic">
              No results found
            </li>
          </ul>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onClickOutside } from '@vueuse/core'

interface Option {
  label: string
  value: any
}

const props = defineProps<{
  modelValue?: any
  options: Option[]
  label?: string
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

const isOpen = ref(false)
const searchQuery = ref('')
const containerRef = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)

// Calculate selected label based on modelValue
const selectedLabel = computed(() => {
  const selected = props.options.find(o => o.value === props.modelValue)
  return selected ? selected.label : null
})

// Filter options based on search query
const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(option => 
    option.label.toLowerCase().includes(query)
  )
})

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
    nextTick(() => {
      searchInput.value?.focus()
    })
  }
}

function select(option: Option) {
  emit('update:modelValue', option.value)
  isOpen.value = false
}

// Close when clicking outside
onClickOutside(containerRef, () => {
  isOpen.value = false
})
</script>
