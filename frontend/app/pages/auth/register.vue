<template>
  <NuxtLayout name="auth">
    <div class="animate-fade-in">
      <!-- Header -->
      <div class="text-center mb-6">
        <div class="w-14 h-14 bg-primary-50 active:scale-95 transition-transform duration-300 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Icon name="heroicons:cube" class="w-7 h-7 text-primary-600" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Create account</h1>
        <p class="text-gray-500 mt-1 text-sm">Get started with MES today</p>
      </div>

      <!-- Error -->
      <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-xl text-xs flex items-center gap-2">
        <Icon name="heroicons:exclamation-circle" class="w-4 h-4 flex-shrink-0" />
        {{ error }}
      </div>

      <!-- Form -->
      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label for="name" class="block text-xs font-semibold text-gray-700 mb-1.5">Full name</label>
          <input
            id="name"
            v-model="name"
            type="text"
            class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 transition-colors bg-gray-50 focus:bg-white text-sm"
            placeholder="John Doe"
            required
            :disabled="loading"
          />
        </div>

        <div>
          <label for="email" class="block text-xs font-semibold text-gray-700 mb-1.5">Email address</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 transition-colors bg-gray-50 focus:bg-white text-sm"
            placeholder="name@company.com"
            required
            :disabled="loading"
          />
        </div>

        <div>
          <label for="password" class="block text-xs font-semibold text-gray-700 mb-1.5">Password</label>
          <div class="relative">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 transition-colors bg-gray-50 focus:bg-white text-sm pr-10"
              placeholder="••••••••"
              required
              minlength="8"
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

        <div>
          <label for="password_confirmation" class="block text-xs font-semibold text-gray-700 mb-1.5">Confirm password</label>
          <div class="relative">
            <input
              id="password_confirmation"
              v-model="passwordConfirmation"
              :type="showPassword ? 'text' : 'password'"
              class="input w-full px-4 py-2.5 rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500 transition-colors bg-gray-50 focus:bg-white text-sm pr-10"
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
          class="btn-primary w-full py-3 text-sm font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-primary-500/40 transition-all duration-300 transform active:scale-[0.98] mt-2"
          :disabled="loading"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-5 h-5 animate-spin" />
          <span v-else>Create account</span>
        </button>
      </form>

      <!-- Login link -->
      <p class="mt-6 text-center text-xs text-gray-500">
        Already have an account?
        <NuxtLink to="/auth/login" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">
          Sign in
        </NuxtLink>
      </p>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

const { register, isAuthenticated } = useAuth()
const toast = useToast()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

// Redirect if already logged in
if (isAuthenticated.value) {
  navigateTo('/')
}

async function handleRegister() {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const res = await register(name.value, email.value, password.value, passwordConfirmation.value)
    
    if (res?.requires_verification) {
        toast.success('Account created! Please verify your email.')
        navigateTo(`/auth/verify-email?email=${encodeURIComponent(email.value)}`)
    } else {
        toast.success('Account created successfully!')
        navigateTo('/')
    }
  } catch (e: any) {
    error.value = e.data?.message || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
