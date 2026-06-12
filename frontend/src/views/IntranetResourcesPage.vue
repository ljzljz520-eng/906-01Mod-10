<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-bold text-white mb-2">内网资源管理</h2>
          <p class="text-white/80">管理企业内部组件仓库、设计规范和部署脚本</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button
            v-if="isAdmin"
            @click="showMaintenanceModal = true"
            class="px-5 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white font-semibold rounded-xl hover:from-orange-600 hover:to-red-600 transition-all flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
            <span>维护工具</span>
          </button>
          <button
            v-if="isEmployee"
            @click="showCreateModal = true"
            class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>添加资源</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="isAdmin && (expiringStats.expiring_count > 0 || expiringStats.expired_count > 0 || orphanedCount > 0)" 
         class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-if="expiringStats.expiring_count > 0" 
           @click="currentFilter = 'expiring'"
           class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl p-6 text-white cursor-pointer hover:shadow-xl hover:-translate-y-1 transition-all">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-white/80 text-sm mb-1">即将过期</div>
            <div class="text-4xl font-bold">{{ expiringStats.expiring_count }}</div>
            <div class="text-white/80 text-xs mt-1">30天内到期</div>
          </div>
          <svg class="w-12 h-12 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div v-if="expiringStats.expired_count > 0"
           @click="currentFilter = 'expired'"
           class="bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl p-6 text-white cursor-pointer hover:shadow-xl hover:-translate-y-1 transition-all">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-white/80 text-sm mb-1">已过期</div>
            <div class="text-4xl font-bold">{{ expiringStats.expired_count }}</div>
            <div class="text-white/80 text-xs mt-1">已自动停用</div>
          </div>
          <svg class="w-12 h-12 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      </div>

      <div v-if="orphanedCount > 0"
           @click="showOrphanedModal = true"
           class="bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl p-6 text-white cursor-pointer hover:shadow-xl hover:-translate-y-1 transition-all">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-white/80 text-sm mb-1">无负责人资源</div>
            <div class="text-4xl font-bold">{{ orphanedCount }}</div>
            <div class="text-white/80 text-xs mt-1">需重新分配</div>
          </div>
          <svg class="w-12 h-12 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="flex flex-wrap gap-4">
      <button
        v-for="filter in filters"
        :key="filter.value"
        @click="currentFilter = filter.value"
        class="px-6 py-3 rounded-xl font-medium transition-all"
        :class="currentFilter === filter.value 
          ? 'bg-white text-purple-600 shadow-lg' 
          : 'bg-white/10 text-white hover:bg-white/20'"
      >
        {{ filter.label }}
        <span v-if="filter.count > 0" class="ml-2 px-2 py-0.5 text-xs rounded-full" 
              :class="currentFilter === filter.value ? 'bg-purple-100 text-purple-700' : 'bg-white/20'">
          {{ filter.count }}
        </span>
      </button>
    </div>

    <div class="grid gap-4">
      <div
        v-for="resource in filteredResources"
        :key="resource.id"
        class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all overflow-hidden"
        :class="{
          'border-l-4 border-yellow-500': resource.expire_status === 'expiring',
          'border-l-4 border-red-500': resource.expire_status === 'expired'
        }"
      >
        <div class="p-6">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2 flex-wrap">
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="getResourceTypeClass(resource.resource_type)"
                >
                  {{ getResourceTypeName(resource.resource_type) }}
                </span>
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="resource.expire_status === 'expired' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'"
                >
                  {{ resource.version }}
                </span>
                <span
                  v-if="resource.expire_status === 'expiring'"
                  class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium flex items-center gap-1"
                >
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  即将过期
                </span>
                <span
                  v-if="resource.expire_status === 'expired'"
                  class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium"
                >
                  已过期
                </span>
                <span
                  v-if="!resource.is_active"
                  class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium"
                >
                  已停用
                </span>
                <span
                  v-if="resource.maintainer_status && resource.maintainer_status !== 'active'"
                  class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium"
                >
                  ⚠️ 负责人异常
                </span>
              </div>
              <h4 class="text-xl font-semibold text-gray-900 mb-2">{{ resource.name }}</h4>
              <p class="text-gray-600 mb-4">{{ resource.description }}</p>
              <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  负责人: {{ resource.maintainer_name }}
                  <span v-if="resource.maintainer_status && resource.maintainer_status !== 'active'" class="text-purple-600 ml-1">
                    ({{ getMaintainerStatusName(resource.maintainer_status) }})
                  </span>
                </span>
                <span v-if="resource.expire_date" class="flex items-center gap-1"
                      :class="{
                        'text-yellow-600': resource.expire_status === 'expiring',
                        'text-red-600': resource.expire_status === 'expired'
                      }">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  失效日期: {{ resource.expire_date }}
                  <span v-if="resource.days_until_expiry !== undefined" class="ml-1">
                    (剩余 {{ resource.days_until_expiry }} 天)
                  </span>
                  <span v-if="resource.days_expired !== undefined" class="ml-1">
                    (已过期 {{ resource.days_expired }} 天)
                  </span>
                </span>
              </div>
            </div>
            <div class="flex gap-2">
              <button
                v-if="isAdmin"
                @click="handleToggle(resource)"
                class="p-2 rounded-lg transition-colors"
                :class="resource.is_active ? 'text-yellow-600 hover:bg-yellow-50' : 'text-green-600 hover:bg-green-50'"
                :title="resource.is_active ? '停用资源' : '启用资源'"
              >
                <svg v-if="resource.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>
              <button
                @click="handleEdit(resource)"
                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                title="编辑"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="handleFavorite(resource)"
                class="p-2 transition-colors rounded-lg"
                :class="resource.isFavorited ? 'text-red-600 bg-red-50' : 'text-yellow-600 hover:bg-yellow-50'"
                :title="resource.isFavorited ? '已收藏' : '添加收藏'"
              >
                <svg v-if="resource.isFavorited" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </button>
              <button
                v-if="isAdmin"
                @click="handleDelete(resource.id)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="删除"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
          <a
            v-if="resource.url && resource.is_active"
            :href="resource.url"
            target="_blank"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            访问资源
          </a>
        </div>
      </div>
    </div>

    <div v-if="resources.length === 0 && !loading" class="text-center py-16">
      <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
      </svg>
      <p class="text-xl text-white/80">
        {{ currentFilter === 'all' ? '暂无内网资源' : '该分类下暂无资源' }}
      </p>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-full mb-3"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3 mb-3"></div>
        <div class="flex gap-2">
          <div class="h-8 bg-gray-200 rounded w-20"></div>
          <div class="h-8 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 mb-6">
          {{ showEditModal ? '编辑资源' : '添加资源' }}
        </h3>
        
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">资源名称 *</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="请输入资源名称"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">资源类型 *</label>
            <select
              v-model="form.resource_type"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
            >
              <option value="component">组件仓库</option>
              <option value="design">设计规范</option>
              <option value="deploy">部署脚本</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">版本号 *</label>
            <input
              v-model="form.version"
              type="text"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="例如: v1.0.0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">资源描述</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none resize-none"
              placeholder="请输入资源描述"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">资源地址</label>
            <input
              v-model="form.url"
              type="url"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
              placeholder="https://..."
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">负责人 *</label>
            <select
              v-model="form.maintainer_id"
              required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
            >
              <option value="" disabled>请选择负责人</option>
              <option v-for="user in employees" :key="user.id" :value="user.id">
                {{ user.real_name || user.username }} ({{ user.department || '未分配部门' }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">失效日期 (可选)</label>
            <input
              v-model="form.expire_date"
              type="date"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none"
            />
            <p class="text-xs text-gray-500 mt-1">设置失效日期后，到期资源将自动停用并从收藏中移除</p>
          </div>

          <div class="flex items-center gap-3">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
            />
            <label for="is_active" class="text-sm text-gray-700">启用该资源</label>
          </div>

          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
            >
              取消
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
            >
              {{ submitting ? '保存中...' : '保存' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Maintenance Modal -->
    <div v-if="showMaintenanceModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 mb-6">维护工具面板</h3>
        
        <div class="space-y-6">
          <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h4 class="font-semibold text-gray-900 mb-1">即将过期资源</h4>
                <p class="text-sm text-gray-600 mb-2">30天内到期的资源 ({{ expiringStats.expiring_count }} 个)</p>
                <div v-if="expiringResources.length > 0" class="space-y-2 max-h-40 overflow-y-auto">
                  <div v-for="r in expiringResources" :key="r.id" class="flex items-center justify-between text-sm p-2 bg-white rounded-lg">
                    <span class="text-gray-800">{{ r.name }} ({{ r.version }})</span>
                    <span class="text-yellow-600 font-medium">剩余 {{ r.days_until_expiry }} 天</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">暂无即将过期的资源</div>
              </div>
            </div>
          </div>

          <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900 mb-1">已过期资源</h4>
                <p class="text-sm text-gray-600 mb-2">超过失效日期的资源 ({{ expiringStats.expired_count }} 个)</p>
                <div v-if="expiredResources.length > 0" class="space-y-2 max-h-40 overflow-y-auto mb-3">
                  <div v-for="r in expiredResources" :key="r.id" class="flex items-center justify-between text-sm p-2 bg-white rounded-lg">
                    <span class="text-gray-800">{{ r.name }} ({{ r.version }})</span>
                    <span class="text-red-600 font-medium">过期 {{ r.days_expired }} 天</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">暂无已过期资源</div>
              </div>
            </div>
            <button
              @click="runMaintenance"
              :disabled="maintenanceRunning || expiringStats.expired_count === 0"
              class="flex-shrink-0 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
            >
              {{ maintenanceRunning ? '处理中...' : '一键清理' }}
            </button>
          </div>

          <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900 mb-1">孤儿资源 (无有效负责人)</h4>
                <p class="text-sm text-gray-600 mb-2">负责人已离职或停用的资源 ({{ orphanedCount }} 个)</p>
                <div v-if="orphanedResources.length > 0" class="space-y-2 max-h-40 overflow-y-auto mb-3">
                  <div v-for="r in orphanedResources" :key="r.id" class="flex items-center justify-between text-sm p-2 bg-white rounded-lg">
                    <span class="text-gray-800">{{ r.name }} ({{ r.version }})</span>
                    <span class="text-purple-600 font-medium">{{ getMaintainerStatusName(r.maintainer_status) }}</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">暂无孤儿资源</div>
              </div>
              <button
                @click="showOrphanedModal = true; showMaintenanceModal = false"
                :disabled="orphanedCount === 0"
                class="flex-shrink-0 px-4 py-2 bg-purple-500 hover:bg-purple-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
              >
                分配负责人
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end mt-6 pt-4 border-t border-gray-100">
          <button
            @click="showMaintenanceModal = false"
            class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
          >
            关闭
          </button>
        </div>
      </div>
    </div>

    <!-- Orphaned Resources Modal -->
    <div v-if="showOrphanedModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 mb-2">孤儿资源管理</h3>
        <p class="text-gray-600 text-sm mb-6">重新分配以下资源的负责人</p>
        
        <div v-if="orphanedResources.length === 0" class="text-center py-8 text-gray-500">
          暂无需要处理的孤儿资源
        </div>

        <div v-else class="space-y-4 mb-6">
          <div v-for="r in orphanedResources" :key="r.id" class="p-4 border border-gray-200 rounded-xl">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-sm font-medium px-2 py-0.5 rounded" :class="getResourceTypeClass(r.resource_type)">
                    {{ getResourceTypeName(r.resource_type) }}
                  </span>
                  <span class="text-sm text-gray-500">{{ r.version }}</span>
                </div>
                <div class="font-medium text-gray-900">{{ r.name }}</div>
                <div class="text-sm text-gray-500">
                  原负责人: {{ r.maintainer_name || '未指定' }} 
                  ({{ getMaintainerStatusName(r.maintainer_status) }})
                </div>
              </div>
              <div class="sm:w-64">
                <select
                  v-model="reassignMap[r.id]"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none text-sm"
                >
                  <option value="">选择新负责人...</option>
                  <option v-for="user in employees" :key="user.id" :value="user.id">
                    {{ user.real_name || user.username }} ({{ user.department }})
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-3">
          <button
            @click="showOrphanedModal = false"
            class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
          >
            取消
          </button>
          <button
            @click="bulkReassignMaintainers"
            :disabled="reassignRunning || Object.keys(reassignMap).length === 0"
            class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-700 hover:to-blue-600 disabled:opacity-50 text-white font-medium rounded-xl transition-colors"
          >
            {{ reassignRunning ? '处理中...' : '批量重新分配' }}
          </button>
        </div>
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
          <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ confirmTitle }}</h3>
          <p class="text-gray-600 mb-6">{{ confirmMessage }}</p>
          <div class="flex gap-3">
            <button
              @click="showConfirm = false"
              class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
            >
              取消
            </button>
            <button
              @click="confirmAction"
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
import { ref, computed, onMounted } from 'vue'
import api, { auth } from '../api'

const resources = ref([])
const employees = ref([])
const loading = ref(false)
const submitting = ref(false)
const maintenanceRunning = ref(false)
const reassignRunning = ref(false)
const currentFilter = ref('all')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showMaintenanceModal = ref(false)
const showOrphanedModal = ref(false)
const showConfirm = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmCallback = ref(null)
const deleteId = ref(null)
const editingId = ref(null)
const toast = ref({ show: false, message: '', type: 'success' })

const expiringStats = ref({ expiring_count: 0, expired_count: 0 })
const expiringResources = ref([])
const expiredResources = ref([])
const orphanedResources = ref([])
const reassignMap = ref({})

const isEmployee = computed(() => auth.isEmployee())
const isAdmin = computed(() => auth.isAdmin())
const orphanedCount = computed(() => orphanedResources.value.length)

const filters = computed(() => [
  { label: '全部', value: 'all', count: resources.value.length },
  { label: '组件仓库', value: 'component', count: resources.value.filter(r => r.resource_type === 'component').length },
  { label: '设计规范', value: 'design', count: resources.value.filter(r => r.resource_type === 'design').length },
  { label: '部署脚本', value: 'deploy', count: resources.value.filter(r => r.resource_type === 'deploy').length },
  { label: '即将过期', value: 'expiring', count: expiringStats.value.expiring_count },
  { label: '已过期', value: 'expired', count: expiringStats.value.expired_count }
])

const form = ref({
  name: '',
  resource_type: 'component',
  version: '',
  description: '',
  url: '',
  maintainer_id: '',
  expire_date: '',
  is_active: 1
})

const filteredResources = computed(() => {
  let result = [...resources.value]
  
  if (currentFilter.value === 'all') {
    return result
  }
  
  if (['component', 'design', 'deploy'].includes(currentFilter.value)) {
    return result.filter(r => r.resource_type === currentFilter.value)
  }
  
  if (currentFilter.value === 'expiring') {
    return result.filter(r => r.expire_status === 'expiring')
  }
  
  if (currentFilter.value === 'expired') {
    return result.filter(r => r.expire_status === 'expired' || !r.is_active)
  }
  
  return result
})

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const getResourceTypeName = (type) => {
  const types = {
    component: '组件仓库',
    design: '设计规范',
    deploy: '部署脚本'
  }
  return types[type] || type
}

const getResourceTypeClass = (type) => {
  const classes = {
    component: 'bg-purple-100 text-purple-700',
    design: 'bg-pink-100 text-pink-700',
    deploy: 'bg-orange-100 text-orange-700'
  }
  return classes[type] || 'bg-gray-100 text-gray-700'
}

const getMaintainerStatusName = (status) => {
  const statuses = {
    active: '在职',
    inactive: '已禁用',
    resigned: '已离职'
  }
  return statuses[status] || '未知'
}

const loadResources = async () => {
  loading.value = true
  try {
    const res = await api.getIntranetResources(null, true)
    if (res.success) {
      resources.value = (res.data || []).map(r => {
        const enriched = { ...r, isFavorited: false }
        if (r.expire_date && r.is_active) {
          const today = new Date()
          const expire = new Date(r.expire_date)
          const diffDays = Math.ceil((expire - today) / (1000 * 60 * 60 * 24))
          if (diffDays >= 0 && diffDays <= 30) {
            enriched.expire_status = 'expiring'
            enriched.days_until_expiry = diffDays
          } else if (diffDays < 0) {
            enriched.expire_status = 'expired'
            enriched.days_expired = Math.abs(diffDays)
          }
        }
        return enriched
      })
    }
  } catch (error) {
    showToast(error.response?.data?.message || '加载失败', 'error')
  } finally {
    loading.value = false
  }
}

const loadExpiringStats = async () => {
  if (!isAdmin.value) return
  try {
    const res = await api.getExpiringResources(30)
    if (res.success && res.data) {
      expiringStats.value = res.data.stats || { expiring_count: 0, expired_count: 0 }
      expiringResources.value = res.data.expiring || []
      expiredResources.value = res.data.expired || []
    }
  } catch (error) {
    console.error('Load expiring stats error:', error)
  }
}

const loadOrphanedResources = async () => {
  if (!isAdmin.value) return
  try {
    const res = await api.getOrphanedResources()
    if (res.success) {
      orphanedResources.value = res.data || []
      reassignMap.value = {}
    }
  } catch (error) {
    console.error('Load orphaned resources error:', error)
  }
}

const loadEmployees = async () => {
  try {
    const res = await api.getUsers('employee', 'active')
    if (res.success) {
      employees.value = res.data || []
    }
  } catch (error) {
    console.error('Load employees error:', error)
  }
}

const handleEdit = (resource) => {
  editingId.value = resource.id
  form.value = {
    name: resource.name,
    resource_type: resource.resource_type,
    version: resource.version,
    description: resource.description || '',
    url: resource.url || '',
    maintainer_id: resource.maintainer_id,
    expire_date: resource.expire_date || '',
    is_active: resource.is_active
  }
  showEditModal.value = true
}

const handleSubmit = async () => {
  if (!form.value.name || !form.value.version || !form.value.maintainer_id) {
    showToast('请填写必填信息', 'error')
    return
  }

  submitting.value = true
  try {
    let res
    if (showEditModal.value && editingId.value) {
      res = await api.updateIntranetResource(editingId.value, form.value)
    } else {
      res = await api.createIntranetResource(form.value)
    }

    if (res.success) {
      showToast(showEditModal.value ? '更新成功' : '创建成功', 'success')
      closeModal()
      loadResources()
      loadExpiringStats()
      loadOrphanedResources()
    } else {
      showToast(res.message || '操作失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '操作失败', 'error')
  } finally {
    submitting.value = false
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingId.value = null
  form.value = {
    name: '',
    resource_type: 'component',
    version: '',
    description: '',
    url: '',
    maintainer_id: '',
    expire_date: '',
    is_active: 1
  }
}

const handleDelete = (id) => {
  deleteId.value = id
  confirmTitle.value = '确认删除'
  confirmMessage.value = '确定要删除这个资源吗？相关收藏也会被移除。'
  confirmCallback.value = confirmDelete
  showConfirm.value = true
}

const confirmDelete = async () => {
  try {
    const res = await api.deleteIntranetResource(deleteId.value)
    if (res.success) {
      resources.value = resources.value.filter(r => r.id !== deleteId.value)
      showToast('删除成功', 'success')
      loadExpiringStats()
      loadOrphanedResources()
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

const handleToggle = async (resource) => {
  const newStatus = resource.is_active ? 0 : 1
  confirmTitle.value = newStatus ? '确认启用' : '确认停用'
  confirmMessage.value = newStatus 
    ? `确定要启用「${resource.name}」吗？`
    : `确定要停用「${resource.name}」吗？所有用户收藏的该资源也将被移除。`
  confirmCallback.value = () => confirmToggle(resource, newStatus)
  showConfirm.value = true
}

const confirmToggle = async (resource, newStatus) => {
  try {
    const res = await api.toggleIntranetResource(resource.id, newStatus)
    if (res.success) {
      resource.is_active = newStatus
      showToast(newStatus ? '资源已启用' : '资源已停用，相关收藏已移除', 'success')
      loadExpiringStats()
    } else {
      showToast(res.message || '操作失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '操作失败', 'error')
  } finally {
    showConfirm.value = false
  }
}

const handleFavorite = async (resource) => {
  if (resource.isFavorited) return

  try {
    const res = await api.addFavorite({
      resource_type: 'intranet',
      intranet_resource_id: resource.id,
      name: resource.name + ' ' + resource.version,
      category: getResourceTypeName(resource.resource_type)
    })
    if (res.success) {
      resource.isFavorited = true
      showToast('收藏成功', 'success')
    }
  } catch (error) {
    if (error.response?.data?.message?.includes('已在收藏列表中')) {
      resource.isFavorited = true
      showToast('该资源已在收藏列表中', 'info')
    } else {
      showToast(error.response?.data?.message || '收藏失败', 'error')
    }
  }
}

const runMaintenance = async () => {
  maintenanceRunning.value = true
  try {
    const res = await api.runMaintenance()
    if (res.success) {
      showToast(`维护完成：停用 ${res.data?.deactivated || 0} 个资源，移除 ${res.data?.favorites_removed || 0} 条收藏`, 'success')
      loadResources()
      loadExpiringStats()
    } else {
      showToast(res.message || '维护失败', 'error')
    }
  } catch (error) {
    showToast(error.response?.data?.message || '维护失败', 'error')
  } finally {
    maintenanceRunning.value = false
  }
}

const bulkReassignMaintainers = async () => {
  const assignments = Object.entries(reassignMap.value).filter(([, newId]) => newId)
  if (assignments.length === 0) {
    showToast('请至少为一个资源选择新负责人', 'error')
    return
  }

  reassignRunning.value = true
  try {
    let successCount = 0
    let failCount = 0

    for (const [oldMaintainerId, group] of Object.entries(
      assignments.reduce((acc, [resId, newId]) => {
        const resource = orphanedResources.value.find(r => r.id === parseInt(resId))
        const oldId = resource?.maintainer_id || 'unknown'
        if (!acc[oldId]) acc[oldId] = {}
        acc[oldId][newId] = true
        return acc
      }, {})
    )) {
      for (const newMaintainerId of Object.keys(group)) {
        try {
          const res = await api.reassignMaintainer(parseInt(oldMaintainerId), parseInt(newMaintainerId))
          if (res.success) {
            successCount++
          } else {
            failCount++
          }
        } catch {
          failCount++
        }
      }
    }

    showToast(`重新分配完成：成功 ${successCount} 组，失败 ${failCount} 组`, failCount === 0 ? 'success' : 'error')
    showOrphanedModal.value = false
    loadResources()
    loadOrphanedResources()
  } catch (error) {
    showToast(error.response?.data?.message || '操作失败', 'error')
  } finally {
    reassignRunning.value = false
  }
}

const confirmAction = () => {
  if (confirmCallback.value) {
    confirmCallback.value()
  }
  confirmCallback.value = null
}

onMounted(() => {
  loadResources()
  loadEmployees()
  loadExpiringStats()
  loadOrphanedResources()
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
