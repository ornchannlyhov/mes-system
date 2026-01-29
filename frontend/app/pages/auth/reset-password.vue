<template>
  <NuxtLayout name="auth">
    <div class="animate-fade-in">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 bg-primary-50 active:scale-95 transition-transform duration-300 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Icon name="heroicons:lock-closed" class="w-7 h-7 text-primary-600" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
          Reset Password
        </h2>
        <p class="text-gray-500 mt-2 text-sm">
          Enter your code and new password.
        </p>
      </div>

      <form @submit.prevent="handleReset" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1.5 uppercase tracking-wide">Email</label>
          <div class="relative">
             <input 
              v-model="form.email" 
              type="email" 
              required 
              class="input w-full px-4 py-2.5 rounded-xl border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed text-sm font-medium" 
              readonly
            />
             <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <Icon name="heroicons:lock-closed" class="h-4 w-4 text-gray-400" />
             </div>
          </div>
        </div>

        <div>
           <label class="block text-xs font-semibold text-gray-700 mb-1.5 uppercase tracking-wide">Verification Code</label>
           <input 
              v-model="form.code" 
              type="text" 
              required 
              class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 font-mono tracking-[0.25em] text-center text-lg" 
              placeholder="000000"
              maxlength="6"
            />
        </div>

        <div>
           <label class="block text-xs font-semibold text-gray-700 mb-1.5 uppercase tracking-wide">New Password</label>
           <div class="relative">
              <input 
                v-model="form.password" 
                :type="showPassword ? 'text' : 'password'" 
                required 
                class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm pr-10" 
                placeholder="••••••••"
              />
              <button 
                type="button" 
                @click="showPassword = !showPassword" 
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
              >
                <Icon :name="showPassword ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
              </button>
           </div>
        </div>

        <div>
           <label class="block text-xs font-semibold text-gray-700 mb-1.5 uppercase tracking-wide">Confirm Password</label>
           <div class="relative">
             <input 
                v-model="form.password_confirmation" 
                :type="showConfirmPassword ? 'text' : 'password'" 
                required 
                class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 text-sm pr-10" 
                placeholder="••••••••"
              />
              <button 
                type="button" 
                @click="showConfirmPassword = !showConfirmPassword" 
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
              >
                <Icon :name="showConfirmPassword ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
              </button>
           </div>
        </div>

        <button 
          type="submit" 
          class="btn-primary w-full py-3 text-sm font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-primary-500/40 transition-all duration-300 transform active:scale-[0.98] mt-2"
          :disabled="loading"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-5 h-5 animate-spin" />
          <span v-else>Reset Password</span>
        </button>
      </form>
      
      <!-- Back to Login -->
      <p class="mt-8 text-center text-xs text-gray-500">
        Remember your password?
        <NuxtLink to="/auth/login" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">
          Sign in
        </NuxtLink>
      </p>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
const { $api } = useApi()
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const form = ref({
    email: (route.query.email as string) || '',
    code: '',
    password: '',
    password_confirmation: ''
})

definePageMeta({
  layout: false
})

async function handleReset() {
  if (form.value.password !== form.value.password_confirmation) {
      toast.error('Passwords do not match')
      return
  }

  loading.value = true
  try {
    await $api('/auth/reset-password', {
      method: 'POST',
      body: form.value
    })
    toast.success('Password reset successfully! Please login.')
    router.push('/auth/login')
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to reset password')
  } finally {
    loading.value = false
  }
}
</script>
