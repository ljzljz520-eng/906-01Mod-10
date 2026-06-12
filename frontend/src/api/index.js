import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 30000
})

const TOKEN_KEY = 'auth_token'
const USER_KEY = 'current_user'

api.interceptors.request.use(
  config => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

api.interceptors.response.use(
  response => {
    return response.data
  },
  error => {
    console.error('API Error:', error)
    if (error.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
      window.dispatchEvent(new CustomEvent('auth:logout'))
    }
    return Promise.reject(error)
  }
)

export const auth = {
  login(username, password) {
    return api.post('/login', { username, password }).then(res => {
      if (res.success && res.data) {
        localStorage.setItem(TOKEN_KEY, res.data.token)
        localStorage.setItem(USER_KEY, JSON.stringify(res.data.user))
        window.dispatchEvent(new CustomEvent('auth:login', { detail: res.data.user }))
      }
      return res
    })
  },
  
  logout() {
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
    window.dispatchEvent(new CustomEvent('auth:logout'))
  },
  
  getToken() {
    return localStorage.getItem(TOKEN_KEY)
  },
  
  getCurrentUser() {
    const userStr = localStorage.getItem(USER_KEY)
    return userStr ? JSON.parse(userStr) : null
  },
  
  isLoggedIn() {
    return !!localStorage.getItem(TOKEN_KEY)
  },
  
  isAdmin() {
    const user = this.getCurrentUser()
    return user?.role === 'admin'
  },
  
  isEmployee() {
    const user = this.getCurrentUser()
    return user?.role === 'employee' || user?.role === 'admin'
  }
}

export default {
  getProviders() {
    return api.get('/providers')
  },
  
  search(keyword, query, page = 1) {
    return api.get(`/search/${keyword}/${encodeURIComponent(query)}/${page}`)
  },
  
  getHistory(limit = 20) {
    return api.get('/history', { params: { limit } })
  },
  
  clearHistory() {
    return api.delete('/history')
  },
  
  getFavorites(type = null) {
    const params = type ? { type } : {}
    return api.get('/favorites', { params })
  },
  
  addFavorite(data) {
    return api.post('/favorites', data)
  },
  
  deleteFavorite(id) {
    return api.delete(`/favorites/${id}`)
  },
  
  getUsers(role = null, status = null) {
    const params = {}
    if (role) params.role = role
    if (status) params.status = status
    return api.get('/users', { params })
  },
  
  createUser(data) {
    return api.post('/users', data)
  },
  
  updateUserStatus(userId, status) {
    return api.put(`/users/${userId}/status`, { status })
  },
  
  getIntranetResources(type = null, includeExpired = false) {
    const params = {}
    if (type) params.type = type
    if (includeExpired) params.include_expired = 'true'
    return api.get('/intranet-resources', { params })
  },
  
  getIntranetResource(id) {
    return api.get(`/intranet-resources/${id}`)
  },
  
  createIntranetResource(data) {
    return api.post('/intranet-resources', data)
  },
  
  updateIntranetResource(id, data) {
    return api.put(`/intranet-resources/${id}`, data)
  },
  
  deleteIntranetResource(id) {
    return api.delete(`/intranet-resources/${id}`)
  },
  
  getExpiringResources(days = 30) {
    return api.get('/intranet-resources/expiring', { params: { days } })
  },
  
  runMaintenance() {
    return api.post('/intranet-resources/maintenance')
  },
  
  getIPWhitelist() {
    return api.get('/ip-whitelist')
  },
  
  createIPWhitelist(data) {
    return api.post('/ip-whitelist', data)
  },
  
  deleteIPWhitelist(id) {
    return api.delete(`/ip-whitelist/${id}`)
  },
  
  updateIPWhitelist(id, data) {
    return api.put(`/ip-whitelist/${id}`, data)
  },
  
  toggleIntranetResource(id, isActive) {
    return api.post(`/intranet-resources/${id}/toggle`, { is_active: isActive })
  },
  
  reassignMaintainer(oldMaintainerId, newMaintainerId) {
    return api.post('/intranet-resources/reassign-maintainer', {
      old_maintainer_id: oldMaintainerId,
      new_maintainer_id: newMaintainerId
    })
  },
  
  getOrphanedResources() {
    return api.get('/intranet-resources/orphaned')
  },
  
  getSystemLogs(limit = 50, type = null) {
    const params = { limit }
    if (type) params.type = type
    return api.get('/system-logs', { params })
  },
  
  getAccessContext() {
    return api.get('/access-context')
  },
  
  healthCheck() {
    return axios.get('/health')
  }
}

