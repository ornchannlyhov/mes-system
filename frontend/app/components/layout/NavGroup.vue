<template>
  <div>
    <!-- Group Header (Clickable) -->
    <button 
      @click="toggle"
      class="w-full flex items-center px-3 py-2.5 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors text-sm"
      :class="[
        isActiveGroup ? 'bg-primary-50 text-primary-600' : '',
        collapsed ? 'justify-center' : 'justify-between'
      ]"
    >
      <div class="flex items-center gap-3">
        <Icon :name="icon" class="w-5 h-5 shrink-0" />
        <span v-if="!collapsed" class="font-medium whitespace-nowrap">{{ label }}</span>
      </div>
      <Icon 
        v-if="!collapsed"
        name="heroicons:chevron-down" 
        class="w-4 h-4 transition-transform duration-200 shrink-0"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <!-- Dropdown Items -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-show="isOpen" class="overflow-hidden">
        <div 
          :class="collapsed ? 'ml-3 pl-3 border-l border-gray-200 mt-1 space-y-1' : 'ml-4 pl-4 border-l border-gray-200 mt-1 space-y-1'"
        >
          <NuxtLink 
            v-for="item in items" 
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 py-2 rounded-lg hover:bg-gray-100 hover:text-gray-700 transition-colors text-sm"
            :class="[
              activeClass(item.to) ? '!bg-primary-50 !text-primary-600 font-medium' : 'text-gray-500',
              collapsed ? 'px-2' : 'px-3'
            ]"
          >
            <Icon :name="item.icon" class="w-4 h-4 shrink-0" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </NuxtLink>
        </div>
      </div>
    </Transition>
  </div>

</template>

<script setup lang="ts">
interface NavItem {
  to: string
  label: string
  icon: string
}

const props = defineProps<{
  label: string
  icon: string
  items: NavItem[]
  collapsed?: boolean
}>()

const emit = defineEmits<{
  expand: []
}>()

const route = useRoute()
const isOpen = ref(false)

// Check if any child route is active
const isActiveGroup = computed(() => {
  return props.items.some(item => route.path.startsWith(item.to))
})

// Auto-open if a child is active
watch(isActiveGroup, (active) => {
  if (active) isOpen.value = true
}, { immediate: true })

// Helper for active class since we handle it manually in v-for for better control
function activeClass(path: string) {
  return route.path.startsWith(path)
}

function toggle() {
  isOpen.value = !isOpen.value
}
</script>
