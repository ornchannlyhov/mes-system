<template>
  <NuxtLayout name="auth">
    <div class="animate-fade-in">
      <!-- Header -->
      <div class="text-center mb-10">
        <div class="flex justify-center mb-6">
          <img v-show="!logoError" :src="logoSrc" alt="CamSME Logo" class="h-12 w-auto object-contain" @error="logoError = true" />
          <div v-if="logoError" class="w-16 h-16 bg-primary-50 rounded-2xl flex items-center justify-center">
            <Icon name="heroicons:cube" class="w-8 h-8 text-primary-600" />
          </div>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Welcome back</h1>
        <p class="text-gray-500 mt-2">Sign in to your account to continue</p>
      </div>

      <!-- Error -->
      <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm flex items-center gap-3">
        <Icon name="heroicons:exclamation-circle" class="w-5 h-5 flex-shrink-0" />
        {{ error }}
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-6">
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

        <div>
          <div class="flex items-center justify-between mb-2">
            <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
            <NuxtLink to="/auth/forgot-password" class="text-sm font-medium text-primary-600 hover:text-primary-700">
              Forgot password?
            </NuxtLink>
          </div>
          <div class="relative">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="input w-full px-4 py-3 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 transition-colors bg-gray-50 focus:bg-white pr-10"
              placeholder="••••••••"
              required
              :disabled="loading"
            />
            <button 
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none flex items-center justify-center cursor-pointer"
            >
              <Icon :name="showPassword ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <button 
          type="submit" 
          class="btn-primary w-full py-3.5 text-base font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-primary-500/40 transition-all duration-300 transform active:scale-[0.98]"
          :disabled="loading"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-5 h-5 animate-spin" />
          <span v-else>Sign in</span>
        </button>
      </form>

      <!-- Register link -->
      <p class="mt-8 text-center text-sm text-gray-500">
        Don't have an account?
        <NuxtLink to="/auth/register" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">
          Create account
        </NuxtLink>
      </p>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

const { login, isAuthenticated } = useAuth()
const toast = useToast()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const logoSrc = '/images/logo.png'
const logoError = ref(false)

// Redirect if already logged in
if (isAuthenticated.value) {
  navigateTo('/')
}

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    await login(email.value, password.value)
    toast.success('Welcome back!')
    navigateTo('/')
  } catch (e: any) {
    error.value = e.data?.message || 'Invalid credentials. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
