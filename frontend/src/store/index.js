import { reactive, readonly } from 'vue'
import api, { auth } from '../api'

const state = reactive({
  currentUser: null,
  isIntranet: false,
  clientIp: '',
  isLoaded: false
})

const setUser = (user) => {
  state.currentUser = user
}

const setAccessContext = (context) => {
  state.isIntranet = context.is_intranet || false
  state.clientIp = context.client_ip || ''
  state.isLoaded = true
}

const clearAccessContext = () => {
  state.isIntranet = false
  state.clientIp = ''
  state.isLoaded = false
}

const canAccessIntranet = () => {
  return state.isIntranet && auth.isEmployee()
}

const loadAccessContext = async () => {
  try {
    const res = await api.getAccessContext()
    if (res.success && res.data) {
      setAccessContext(res.data)
    }
  } catch (error) {
    console.error('Load access context error:', error)
    clearAccessContext()
  }
  return state.isLoaded
}

const initStore = () => {
  state.currentUser = auth.getCurrentUser()
  if (auth.isLoggedIn()) {
    loadAccessContext()
  }
  window.addEventListener('auth:login', () => {
    state.currentUser = auth.getCurrentUser()
    loadAccessContext()
  })
  window.addEventListener('auth:logout', () => {
    state.currentUser = null
    clearAccessContext()
  })
}

export const store = {
  state: readonly(state),
  setUser,
  setAccessContext,
  clearAccessContext,
  canAccessIntranet,
  loadAccessContext,
  initStore
}

export default store
