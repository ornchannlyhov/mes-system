<template>
  <NuxtLayout name="auth">
    <div class="animate-fade-in">
      <!-- Header -->
      <div class="text-center mb-10">
        <div class="w-16 h-16 bg-primary-50 active:scale-95 transition-transform duration-300 rounded-2xl flex items-center justify-center mx-auto mb-6">
          <Icon name="heroicons:key" class="w-8 h-8 text-primary-600" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Forgot password?</h1>
        <p class="text-gray-500 mt-2">No worries, we'll send you reset instructions.</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSendOtp" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email address</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="input w-full px-4 py-3 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 transition-colors bg-gray-50 focus:bg-white"
            placeholder="name@company.com"
            required
            :disabled="loading"
          />
        </div>

        <button 
          type="submit" 
          class="btn-primary w-full py-3.5 text-base font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-primary-500/40 transition-all duration-300 transform active:scale-[0.98]"
          :disabled="loading"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-5 h-5 animate-spin" />
          <span v-else>Send Reset Code</span>
        </button>
      </form>

      <!-- Back to Login -->
      <div class="mt-8 text-center space-y-4">
        <p class="text-sm text-gray-500">
          Remember your password?
          <NuxtLink to="/auth/login" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">
            Sign in
          </NuxtLink>
        </p>
        
        <p class="text-sm text-gray-500">
           Don't have an account?
           <NuxtLink to="/auth/register" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">
             Create account
           </NuxtLink>
        </p>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

const { $api } = useApi()
const toast = useToast()
const router = useRouter()

const email = ref('')
const loading = ref(false)

async function handleSendOtp() {
  loading.value = true
  try {
    await $api('/auth/forgot-password', {
      method: 'POST',
      body: { email: email.value }
    })
    toast.success('Reset code sent! Please check your email.')
    router.push({ path: '/auth/reset-password', query: { email: email.value } })
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to send reset code')
  } finally {
    loading.value = false
  }
}
</script>
