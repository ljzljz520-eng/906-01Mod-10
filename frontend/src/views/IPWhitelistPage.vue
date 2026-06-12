<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-bold text-white mb-2">IP白名单管理</h2>
          <p class="text-white/80">管理可访问内网资源的IP地址范围</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>添加IP段</span>
        </button>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-gray-500">当前访问IP</p>
          <p class="text-xl font-semibold text-gray-900 font-mono">{{ currentIp || '检测中...' }}</p>
        </div>
        <span
          class="ml-auto px-4 py-2 rounded-full text-sm font-medium"
          :class="isIntranet ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
        >
          {{ isIntranet ? '内网访问' : '公网访问' }}
        </span>
      </div>
    </div>

    <div class="grid gap-4">
      <div
        v-for="item in whitelist"
        :key="item.id"
        class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all p-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium font-mono">
                {{ item.ip_start }} - {{ item.ip_end }}
              </span>
              <span v-if="item.created_by_name" class="text-sm text-gray-500">
                创建者: {{ item.created_by_name }}
              </span>
            </div>
            <p class="text-gray-700">{{ item.description || '无描述' }}</p>
            <p class="text-sm text-gray-400 mt-2">
              创建时间: {{ formatDate(item.created_at) }}
            </p>
          </div>
          <button
            @click="handleDelete(item.id)"
            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            title="删除"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div v-if="whitelist.length === 0 && !loading" class="text-center py-16">
      <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
      </svg>
      <p class="text-xl text-white/80">暂无IP白名单配置</p>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-full mb-3"></div>
        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
      </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-lg w-full">
        <h3 class="text-xl font-bold text-gray-900 mb-6">添加IP白名单</h3>
        
        <form @submit.prevent="handleCreate" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">起始IP</label>
              <input
                v-model="form.ip_start"
                type="text"
                required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none font-mono"
                placeholder="192.168.0.0"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">结束IP</label>
              <input
                v-model="form.ip_end"
                type="text"
                required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none font-mono"
                placeholder="192.168.255.255"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">描述</label>
            <input
              v-model="form.description"
              type="text"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="例如：公司办公网A段"
            />
          </div>

          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="showCreateModal = false"
              class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
            >
              取消
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
            >
              {{ submitting ? '添加中...' : '添加' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <transition name="slide-up">
      <div
        v-if="toast.show"
        class="fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-2xl text-white font-medium z-50"
        :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : 'bg-blue-500'"
      >
        {{ toast.message }}
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">确认删除</h3>
          <p class="text-gray-600 mb-6">确定要删除这个IP白名单吗？该IP段将无法访问内网资源。</p>
          <div class="flex gap-3">
            <button
              @click="showConfirm = false"
              class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
            >
              取消
            </button>
            <button
              @click="confirmDelete"
              class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors"
            >
              删除
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const whitelist = ref([])
const currentIp = ref('')
const isIntranet = ref(false)
const loading = ref(false)
const submitting = ref(false)
const showCreateModal = ref(false)
const showConfirm = ref(false)
const deleteId = ref(null)
const toast = ref({ show: false, message: '', type: 'success' })

const form = ref({
  ip_start: '',
  ip_end: '',
  description: ''
})

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleString('zh-CN')
}

const loadAccessContext = async () => {
  try {
    const res = await api.healthCheck()
    if (res.data?.access_context) {
      currentIp.value = res.data.access_context.client_ip
      isIntranet.value = res.data.access_context.is_intranet
    }
  } catch (error) {
    console.error('Load access context error:', error)
  }
}

const loadWhitelist = async () => {
  loading.value = true
  try {
    const res = await api.getIPWhitelist()
    if (res.success) {
      whitelist.value = res.data || []
    }
  } catch (error) {
    showToast(error.response?.data?.message || '加载失败', 'error')
  } finally {
    loading.value = false
  }
}

const handleDelete = (id) => {
  deleteId.value = id
  showConfirm.value = true
}

const confirmDelete = async () => {
  try {
    const res = await api.deleteIPWhitelist(deleteId.value)
    if (res.success) {
      whitelist.value = whitelist.value.filter(item => item.id !== deleteId.value)
      showToast('删除成功', 'success')
    } else {
      showToast(res.message || '删除失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '删除失败', 'error')
  } finally {
    showConfirm.value = false
    deleteId.value = null
  }
}

const handleCreate = async () => {
  if (!form.value.ip_start || !form.value.ip_end) {
    showToast('请填写IP范围', 'error')
    return
  }

  submitting.value = true
  try {
    const res = await api.createIPWhitelist(form.value)
    if (res.success) {
      showToast('添加成功', 'success')
      showCreateModal.value = false
      form.value = {
        ip_start: '',
        ip_end: '',
        description: ''
      }
      loadWhitelist()
    } else {
      showToast(res.message || '添加失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '添加失败', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadAccessContext()
  loadWhitelist()
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
