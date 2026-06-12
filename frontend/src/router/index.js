import { createRouter, createWebHistory } from 'vue-router'
import SearchPage from '../views/SearchPage.vue'
import FavoritesPage from '../views/FavoritesPage.vue'
import HistoryPage from '../views/HistoryPage.vue'
import LoginPage from '../views/LoginPage.vue'
import IntranetResourcesPage from '../views/IntranetResourcesPage.vue'
import UserManagementPage from '../views/UserManagementPage.vue'
import IPWhitelistPage from '../views/IPWhitelistPage.vue'
import { auth } from '../api'
import store from '../store'

const routes = [
  {
    path: '/',
    name: 'Search',
    component: SearchPage
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: FavoritesPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/history',
    name: 'History',
    component: HistoryPage
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/intranet-resources',
    name: 'IntranetResources',
    component: IntranetResourcesPage,
    meta: { requiresEmployee: true, requiresIntranet: true }
  },
  {
    path: '/users',
    name: 'UserManagement',
    component: UserManagementPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/ip-whitelist',
    name: 'IPWhitelist',
    component: IPWhitelistPage,
    meta: { requiresAdmin: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const showAccessDeniedToast = (message) => {
  const toastEvent = new CustomEvent('toast:show', {
    detail: { message, type: 'error' }
  })
  window.dispatchEvent(toastEvent)
}

router.beforeEach(async (to, from, next) => {
  if (auth.isLoggedIn() && !store.state.isLoaded) {
    await store.loadAccessContext()
  }

  if (to.meta.requiresAdmin && !auth.isAdmin()) {
    if (!auth.isLoggedIn()) {
      next('/login')
    } else {
      showAccessDeniedToast('需要管理员权限才能访问该页面')
      next(from.path || '/')
    }
    return
  }

  if (to.meta.requiresEmployee && !auth.isEmployee()) {
    if (!auth.isLoggedIn()) {
      next('/login')
    } else {
      showAccessDeniedToast('需要员工权限才能访问该页面')
      next(from.path || '/')
    }
    return
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn()) {
    showAccessDeniedToast('请先登录后再访问')
    next('/login')
    return
  }

  if (to.meta.requiresIntranet && !store.state.isIntranet) {
    showAccessDeniedToast(`内网资源仅允许在企业内网访问。当前IP: ${store.state.clientIp || '未知'}`)
    next(from.path || '/')
    return
  }

  next()
})

export default router
