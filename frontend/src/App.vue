<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800">
    <header class="bg-white/95 backdrop-blur-lg shadow-lg sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h1 class="text-2xl font-bold text-gray-900">企业资源索引站</h1>
            <span
              v-if="isIntranet"
              class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full"
            >
              内网
            </span>
          </div>
          <nav class="flex items-center space-x-2">
            <router-link
              to="/"
              class="px-3 py-2 rounded-lg font-medium transition-all duration-200 text-sm"
              :class="$route.path === '/' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
            >
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>搜索</span>
              </span>
            </router-link>
            <router-link
              to="/favorites"
              class="px-3 py-2 rounded-lg font-medium transition-all duration-200 text-sm"
              :class="$route.path === '/favorites' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
            >
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                <span>收藏</span>
              </span>
            </router-link>
            <router-link
              v-if="isEmployee"
              to="/intranet-resources"
              class="px-3 py-2 rounded-lg font-medium transition-all duration-200 text-sm"
              :class="$route.path === '/intranet-resources' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
            >
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>内网资源</span>
              </span>
            </router-link>
            <router-link
              v-if="isAdmin"
              to="/users"
              class="px-3 py-2 rounded-lg font-medium transition-all duration-200 text-sm"
              :class="$route.path === '/users' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
            >
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>用户管理</span>
              </span>
            </router-link>
            <router-link
              v-if="isAdmin"
              to="/ip-whitelist"
              class="px-3 py-2 rounded-lg font-medium transition-all duration-200 text-sm"
              :class="$route.path === '/ip-whitelist' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
            >
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>IP白名单</span>
              </span>
            </router-link>
            <div class="w-px h-6 bg-gray-200 mx-2"></div>
            <div v-if="currentUser" class="flex items-center space-x-3">
              <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                  {{ currentUser.real_name ? currentUser.real_name.charAt(0) : currentUser.username.charAt(0).toUpperCase() }}
                </div>
                <div class="hidden sm:block">
                  <div class="text-sm font-medium text-gray-900">
                    {{ currentUser.real_name || currentUser.username }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ getRoleName(currentUser.role) }}
                  </div>
                </div>
              </div>
              <button
                @click="handleLogout"
                class="px-3 py-1.5 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                退出
              </button>
            </div>
            <router-link
              v-else
              to="/login"
              class="px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-500 text-white text-sm font-medium rounded-lg hover:from-purple-700 hover:to-blue-600 transition-all"
            >
              登录
            </router-link>
          </nav>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import store from './store'
import { auth } from './api'
import { useRouter } from 'vue-router'

const router = useRouter()

const currentUser = computed(() => store.state.currentUser)
const isIntranet = computed(() => store.state.isIntranet)

const isLoggedIn = computed(() => auth.isLoggedIn())
const isAdmin = computed(() => auth.isAdmin())
const isEmployee = computed(() => auth.isEmployee())

const getRoleName = (role) => {
  const roles = {
    admin: '管理员',
    employee: '员工',
    guest: '访客'
  }
  return roles[role] || role
}

const handleLogout = () => {
  auth.logout()
  router.push('/')
}

onMounted(async () => {
  if (auth.isLoggedIn() && !store.state.isLoaded) {
    await store.loadAccessContext()
  }
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
