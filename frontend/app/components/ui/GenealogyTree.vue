<template>
  <div class="flex flex-col items-center">
    <!-- Node Card -->
    <div 
        class="relative border rounded-lg bg-white shadow-sm hover:shadow-md transition-all p-3 min-w-[180px] max-w-[220px] text-center z-10 cursor-pointer border-gray-200"
        :class="{'border-primary-500 ring-1 ring-primary-100': isRoot}"
    >
        <div class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">{{ node.type }}</div>
        <div class="font-medium text-gray-900 text-sm truncate" :title="node.identifier">
            {{ node.identifier }}
        </div>
        <div class="text-xs text-gray-600 truncate mb-1" :title="node.product">
            {{ node.product }}
        </div>
        <div class="flex justify-center">
            <span :class="['px-2 py-0.5 text-[10px] rounded-full', statusColor]">{{ node.status || 'Active' }}</span>
        </div>

        <!-- Connector Line Down (only if children exist) -->
        <div v-if="hasChildren" class="absolute -bottom-4 left-1/2 w-px h-4 bg-gray-300"></div>
    </div>

    <!-- Children Container -->
    <div v-if="hasChildren" class="flex items-start gap-4 mt-8 relative">
        <!-- Horizontal Connection Line (spans from first child to last child) -->
        <div 
            v-if="node.children.length > 1" 
            class="absolute -top-4 left-0 right-0 h-px bg-gray-300 mx-auto"
            :style="{ width: 'calc(100% - 100px)' }" 
        ></div>
        <!-- Note: Pure CSS tree lines are hard to get perfect without set widths. 
             Simplifying: Just render children with a top connector. -->
        
        <div 
            v-for="(child, index) in node.children" 
            :key="child.id" 
            class="relative flex flex-col items-center"
        >
             <!-- Connector Line Up from Child -->
             <div class="absolute -top-8 w-px h-8 bg-gray-300"></div>

             <GenealogyTree :node="child" :is-root="false" />
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface GenealogyNode {
    id: number
    type: 'serial' | 'lot'
    identifier: string
    product: string
    status: string
    children: GenealogyNode[]
}

const props = defineProps<{
    node: GenealogyNode
    isRoot?: boolean
}>()

const hasChildren = computed(() => props.node.children && props.node.children.length > 0)

const statusColor = computed(() => {
    switch (props.node.status?.toLowerCase()) {
        case 'available': return 'bg-green-100 text-green-800'
        case 'consumed': return 'bg-blue-100 text-blue-800'
        case 'scrapped': return 'bg-red-100 text-red-800'
        case 'active': return 'bg-green-100 text-green-800'
        default: return 'bg-gray-100 text-gray-800'
    }
})
</script>
