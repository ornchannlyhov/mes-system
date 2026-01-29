<template>
  <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-dark-bg transition-colors duration-300">
    <div class="w-full max-w-[1200px] bg-white dark:bg-dark-surface rounded-3xl shadow-2xl overflow-hidden flex flex-col lg:flex-row ring-1 ring-gray-100 dark:ring-gray-800 shadow-primary-900/10 dark:shadow-black/30 min-h-[600px] transition-colors duration-300">
      
      <!-- Left Side - Image (Hidden on mobile) -->
      <div class="hidden lg:block lg:w-[60%] relative overflow-hidden bg-primary-900">
        <img
          src="/images/auth-sidebar.png"
          alt="Manufacturing Execution System"
          class="absolute inset-0 w-full h-full object-cover opacity-90 transition-transform duration-700 hover:scale-105"
        />
        <!-- Overlay gradient for text readability and branding -->
        <div class="absolute inset-0 bg-gradient-to-b from-primary-900/90 via-primary-900/40 to-transparent" />
        
        <div class="absolute top-0 left-0 p-10 text-white z-10 w-full">
          <h2 class="text-3xl font-bold mb-4 font-display tracking-tight">Manufacturing Execution System</h2>
          <p class="text-primary-100 text-base leading-relaxed opacity-90">
            Streamline your production, optimize resources, and drive efficiency with real-time insights.
          </p>
        </div>

        <!-- Decorational elements at bottom -->
        <div class="absolute bottom-0 left-0 w-full p-8 bg-gradient-to-t from-primary-950/80 to-transparent">
           <div class="flex items-center gap-3 text-xs text-primary-200/60 font-mono">
              <span>MES v1.0</span>
              <span class="w-1 h-1 rounded-full bg-primary-500"></span>
              <span>Secure Connection</span>
           </div>
        </div>
      </div>

      <!-- Right Side - Form -->
      <div class="w-full lg:w-[40%] p-6 sm:p-10 lg:p-10 flex items-center justify-center bg-white relative">
        <div class="w-full max-w-[380px] mx-auto">
          <slot />
        </div>
      </div>

    </div>
    <UiToast />
  </div>
</template>
