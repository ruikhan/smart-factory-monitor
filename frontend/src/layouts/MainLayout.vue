<template>
  <v-app theme="dark">
    <!-- Sidebar -->
    <v-navigation-drawer v-model="drawer" :rail="rail" permanent color="#0d1117">
      <!-- Logo -->
      <div class="sidebar-logo" @click="rail = !rail">
        <div class="logo-icon">
          <v-icon size="22" color="#00bcd4">mdi-factory</v-icon>
        </div>
        <div v-if="!rail" class="logo-text">
          <span class="logo-title">Factory Monitor</span>
          <span class="logo-sub">Industrial Platform</span>
        </div>
        <v-icon v-if="!rail" size="18" color="rgba(255,255,255,0.3)">mdi-chevron-left</v-icon>
      </div>

      <v-divider color="rgba(255,255,255,0.05)"/>

      <!-- Nav items -->
      <v-list density="compact" nav class="mt-2">
        <v-list-item
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          :active="route.path.startsWith(item.to)"
          color="#00bcd4"
          rounded="lg"
          class="nav-item mb-1"
        >
          <template v-if="item.badge && !rail" #append>
            <v-badge :content="item.badge" color="error" inline/>
          </template>
        </v-list-item>
      </v-list>

      <!-- Bottom -->
      <template #append>
        <v-divider color="rgba(255,255,255,0.05)" class="mb-2"/>
        <div class="sidebar-user" v-if="!rail">
          <v-avatar size="32" color="#00bcd4" class="mr-2">
            <span style="font-size:12px;font-weight:700">{{ userInitials }}</span>
          </v-avatar>
          <div class="user-info">
            <div class="user-name">{{ auth.user?.name }}</div>
            <div class="user-role">{{ auth.user?.role }}</div>
          </div>
          <v-btn icon size="small" variant="text" @click="handleLogout" color="rgba(255,255,255,0.4)">
            <v-icon size="16">mdi-logout</v-icon>
          </v-btn>
        </div>
        <div v-else class="d-flex justify-center pb-3">
          <v-btn icon size="small" variant="text" @click="handleLogout" color="rgba(255,255,255,0.4)">
            <v-icon size="16">mdi-logout</v-icon>
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Top Bar -->
    <v-app-bar color="#0d1117" border="b" elevation="0" height="56">
      <v-btn icon @click="drawer = !drawer" variant="text" class="ml-1">
        <v-icon>mdi-menu</v-icon>
      </v-btn>
      <v-app-bar-title>
        <span class="page-title">{{ currentPageTitle }}</span>
      </v-app-bar-title>
      <template #append>
        <div class="d-flex align-center gap-2 mr-3">
          <!-- Alert bell -->
          <v-btn icon variant="text" size="small" @click="router.push('/alerts')">
            <v-badge :content="alertCount" color="error" :model-value="alertCount > 0">
              <v-icon>mdi-bell-outline</v-icon>
            </v-badge>
          </v-btn>
          <!-- Factory name -->
          <v-chip size="small" color="#00bcd4" variant="tonal" prepend-icon="mdi-factory">
            {{ auth.user?.factory_id ? 'Main Factory' : 'No Factory' }}
          </v-chip>
        </div>
      </template>
    </v-app-bar>

    <!-- Main content -->
    <v-main style="background:#060b14; min-height:100vh;">
      <v-container fluid class="pa-4">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const route  = useRoute()
const drawer = ref(true)
const rail   = ref(false)
const alertCount = ref(0)

const navItems = [
  { to: '/dashboard',   icon: 'mdi-view-dashboard-outline', title: 'Dashboard'   },
  { to: '/machines',    icon: 'mdi-cog-outline',             title: 'Machines'    },
  { to: '/production',  icon: 'mdi-chart-bar',               title: 'Production'  },
  { to: '/maintenance', icon: 'mdi-wrench-outline',           title: 'Maintenance' },
  { to: '/shifts',      icon: 'mdi-clock-outline',            title: 'Shifts'      },
  { to: '/workers',     icon: 'mdi-account-group-outline',    title: 'Workers'     },
  { to: '/alerts',      icon: 'mdi-bell-alert-outline',       title: 'Alerts'      },
]

const currentPageTitle = computed(() => {
  const item = navItems.find(n => route.path.startsWith(n.to))
  return item?.title ?? 'Smart Factory Monitor'
})

const userInitials = computed(() => {
  return auth.user?.name?.split(' ').map(w => w[0]).join('').toUpperCase() ?? 'U'
})

async function fetchAlertCount() {
  try {
    const { data } = await axios.get('/api/alerts/unread-count')
    alertCount.value = data.unread_count
  } catch {}
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(() => {
  fetchAlertCount()
  setInterval(fetchAlertCount, 30000)
})
</script>

<style scoped>
.sidebar-logo {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 12px; cursor: pointer;
  transition: background 0.2s; border-radius: 8px; margin: 4px;
}
.sidebar-logo:hover { background: rgba(255,255,255,0.04); }
.logo-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, #00bcd4, #006064);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.logo-title { display: block; font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.9); }
.logo-sub   { display: block; font-size: 10px; color: rgba(0,188,212,0.6); letter-spacing: 0.5px; }
.logo-text  { flex: 1; }
.nav-item   { font-size: 13px; }
.page-title { font-size: 15px; font-weight: 600; color: rgba(255,255,255,0.9); }
.sidebar-user {
  display: flex; align-items: center; padding: 10px 12px; margin: 4px;
  border-radius: 10px; background: rgba(255,255,255,0.04);
}
.user-name { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.85); line-height: 1.3; }
.user-role { font-size: 10px; color: rgba(0,188,212,0.6); text-transform: capitalize; }
.user-info { flex: 1; }
</style>
