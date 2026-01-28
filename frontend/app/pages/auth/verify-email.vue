<template>
  <NuxtLayout name="auth">
    <div class="animate-fade-in text-center">
      <!-- Header -->
      <div class="mb-8">
        <div class="w-14 h-14 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
           <Icon name="heroicons:envelope" class="w-7 h-7 text-primary-600" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Verify your email</h2>
        <p class="mt-2 text-sm text-gray-500">
          We sent a verification code to <br/>
          <span class="font-medium text-gray-900">{{ email }}</span>
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleVerify" class="space-y-6">
        <div>
          <label for="code" class="sr-only">Verification Code</label>
          <input 
            id="code" 
            v-model="code" 
            name="code" 
            type="text" 
            required 
            class="input w-full px-4 py-3 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 bg-gray-50 focus:bg-white text-center font-mono text-2xl tracking-[0.5em] font-bold text-gray-800 placeholder-gray-300" 
            placeholder="000000"
            maxlength="6"
            autofocus
          />
        </div>

        <button 
          type="submit" 
          class="btn-primary w-full py-3 rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-primary-500/40 transition-all duration-300 transform active:scale-[0.98]"
          :disabled="loading"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-5 h-5 animate-spin mr-2" />
          <span v-else>Verify Email</span>
        </button>
      </form>
      
      <div class="mt-8">
           <p class="text-xs text-gray-500 mb-3">Didn't receive the code?</p>
           <button class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">Resend Code</button>
      </div>
      
      <div class="mt-8 pt-6 border-t border-gray-100">
        <NuxtLink to="/auth/login" class="text-xs font-medium text-gray-500 hover:text-gray-900 flex items-center justify-center gap-2">
            <Icon name="heroicons:arrow-left" class="w-3 h-3" />
            Back to Login
        </NuxtLink>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
const { $api } = useApi()
const toast = useToast()
const router = useRouter()
const route = useRoute()

const email = (route.query.email as string) || ''
const code = ref('')
const loading = ref(false)

definePageMeta({
  layout: false
})

async function handleVerify() {
  loading.value = true
  try {
    const { verifyEmail } = useAuth()
    await verifyEmail(email, code.value)
    
    toast.success('Email verified successfully!')
    router.push('/')
  } catch (e: any) {
    toast.error(e.data?.message || 'Verification failed')
  } finally {
    loading.value = false
  }
}
</script>
