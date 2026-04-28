<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <div class="w-16 h-16 bg-primary-50 rounded-2xl flex items-center justify-center">
            <Icon name="heroicons:shield-check" class="w-8 h-8 text-primary-600" />
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">System Administration</h1>
        <p class="text-gray-500 mt-1">Superadmin portal login</p>
      </div>

      <!-- Error -->
      <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-3">
        <Icon name="heroicons:exclamation-circle" class="w-5 h-5 flex-shrink-0" />
        {{ error }}
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
            placeholder="superadmin@example.com"
            required
            :disabled="loading"
          />
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
          <div class="relative">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all pr-10"
              placeholder="••••••••"
              required
              :disabled="loading"
            />
            <button 
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
            >
              <Icon :name="showPassword ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <button 
          type="submit" 
          class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="loading"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-5 h-5 animate-spin" />
          <span v-else>Sign in to System Admin</span>
        </button>
      </form>

      <!-- Back link -->
      <p class="mt-6 text-center text-sm text-gray-500">
        <NuxtLink to="/auth/login" class="text-primary-600 hover:text-primary-700 font-medium">
          ← Back to organization login
        </NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

const { login, isAuthenticated, checkAuth } = useSuperadminAuth()
const router = useRouter()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

// Redirect if already logged in
onMounted(async () => {
  await checkAuth()
  if (isAuthenticated.value) {
    router.push('/sysadmin')
  }
})

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    await login(email.value, password.value)
    router.push('/sysadmin')
  } catch (e: any) {
    error.value = e.data?.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>
