<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-bold text-white mb-2">用户管理</h2>
          <p class="text-white/80">管理企业员工账户、角色和权限</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>添加用户</span>
        </button>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">用户名</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">姓名</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">部门</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">角色</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">状态</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ user.username }}</div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4 text-gray-700">{{ user.real_name || '-' }}</td>
              <td class="px-6 py-4 text-gray-700">{{ user.department || '-' }}</td>
              <td class="px-6 py-4">
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="getRoleClass(user.role)"
                >
                  {{ getRoleName(user.role) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="getStatusClass(user.status)"
                >
                  {{ getStatusName(user.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <select
                    :value="user.status"
                    @change="handleStatusChange(user.id, $event.target.value)"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none"
                  >
                    <option value="active">在职</option>
                    <option value="inactive">禁用</option>
                    <option value="resigned">已离职</option>
                  </select>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="bg-white rounded-2xl p-6 animate-pulse">
        <div class="flex gap-4">
          <div class="h-6 bg-gray-200 rounded w-32"></div>
          <div class="h-6 bg-gray-200 rounded w-24"></div>
          <div class="h-6 bg-gray-200 rounded w-24"></div>
          <div class="h-6 bg-gray-200 rounded w-20"></div>
          <div class="h-6 bg-gray-200 rounded w-20"></div>
        </div>
      </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 mb-6">添加用户</h3>
        
        <form @submit.prevent="handleCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
            <input
              v-model="form.username"
              type="text"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="请输入用户名"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">邮箱</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="请输入邮箱"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="请输入密码"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">真实姓名</label>
            <input
              v-model="form.real_name"
              type="text"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="请输入真实姓名"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">部门</label>
            <input
              v-model="form.department"
              type="text"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="请输入部门"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">角色</label>
            <select
              v-model="form.role"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
            >
              <option value="employee">普通员工</option>
              <option value="admin">管理员</option>
            </select>
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
              {{ submitting ? '创建中...' : '创建' }}
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
          <h3 class="text-lg font-semibold text-gray-900 mb-2">确认状态变更</h3>
          <p class="text-gray-600 mb-6">{{ confirmMessage }}</p>
          <div class="flex gap-3">
            <button
              @click="cancelStatusChange"
              class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
            >
              取消
            </button>
            <button
              @click="confirmStatusChange"
              class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors"
            >
              确认
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

const users = ref([])
const loading = ref(false)
const submitting = ref(false)
const showCreateModal = ref(false)
const showConfirm = ref(false)
const confirmMessage = ref('')
const pendingStatusChange = ref({ userId: null, status: null })
const toast = ref({ show: false, message: '', type: 'success' })

const form = ref({
  username: '',
  email: '',
  password: '',
  real_name: '',
  department: '',
  role: 'employee'
})

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const getRoleName = (role) => {
  const roles = {
    admin: '管理员',
    employee: '普通员工',
    guest: '访客'
  }
  return roles[role] || role
}

const getRoleClass = (role) => {
  const classes = {
    admin: 'bg-red-100 text-red-700',
    employee: 'bg-blue-100 text-blue-700',
    guest: 'bg-gray-100 text-gray-700'
  }
  return classes[role] || 'bg-gray-100 text-gray-700'
}

const getStatusName = (status) => {
  const statuses = {
    active: '在职',
    inactive: '禁用',
    resigned: '已离职'
  }
  return statuses[status] || status
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-yellow-100 text-yellow-700',
    resigned: 'bg-gray-100 text-gray-700'
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

const loadUsers = async () => {
  loading.value = true
  try {
    const res = await api.getUsers()
    if (res.success) {
      users.value = res.data || []
    }
  } catch (error) {
    showToast(error.response?.data?.message || '加载失败', 'error')
  } finally {
    loading.value = false
  }
}

const handleStatusChange = (userId, newStatus) => {
  const user = users.value.find(u => u.id === userId)
  if (!user) return

  if (newStatus === 'resigned') {
    confirmMessage.value = `确定要将 "${user.real_name || user.username}" 标记为已离职吗？该用户收藏的所有内网资源将被自动收回。`
    pendingStatusChange.value = { userId, status: newStatus }
    showConfirm.value = true
  } else {
    updateUserStatus(userId, newStatus)
  }
}

const updateUserStatus = async (userId, status) => {
  try {
    const res = await api.updateUserStatus(userId, status)
    if (res.success) {
      const user = users.value.find(u => u.id === userId)
      if (user) user.status = status
      showToast(res.message || '状态更新成功', 'success')
    } else {
      showToast(res.message || '更新失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '更新失败', 'error')
  }
}

const cancelStatusChange = () => {
  showConfirm.value = false
  pendingStatusChange.value = { userId: null, status: null }
  loadUsers()
}

const confirmStatusChange = () => {
  if (pendingStatusChange.value.userId && pendingStatusChange.value.status) {
    updateUserStatus(pendingStatusChange.value.userId, pendingStatusChange.value.status)
  }
  showConfirm.value = false
  pendingStatusChange.value = { userId: null, status: null }
}

const handleCreate = async () => {
  if (!form.value.username || !form.value.email || !form.value.password) {
    showToast('请填写必填信息', 'error')
    return
  }

  submitting.value = true
  try {
    const res = await api.createUser(form.value)
    if (res.success) {
      showToast('用户创建成功', 'success')
      showCreateModal.value = false
      form.value = {
        username: '',
        email: '',
        password: '',
        real_name: '',
        department: '',
        role: 'employee'
      }
      loadUsers()
    } else {
      showToast(res.message || '创建失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '创建失败', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadUsers()
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
