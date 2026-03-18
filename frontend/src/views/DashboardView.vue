<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="page-heading">Dashboard Overview</h2>
        <p class="page-sub">Real-time factory monitoring — {{ today }}</p>
      </div>
      <v-btn color="primary" variant="tonal" prepend-icon="mdi-refresh" @click="fetchData" :loading="loading" size="small">
        Refresh
      </v-btn>
    </div>

    <!-- Machine Status Cards -->
    <v-row class="mb-4">
      <v-col v-for="stat in machineCards" :key="stat.label" cols="6" md="3">
        <v-card class="stat-card" :class="`border-${stat.color}`" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="stat-icon" :class="`bg-${stat.color}`">
                <v-icon size="20" :color="stat.color">{{ stat.icon }}</v-icon>
              </div>
              <span class="stat-trend" :class="`text-${stat.color}`">{{ stat.status }}</span>
            </div>
            <div class="stat-value">{{ stat.value }}</div>
            <div class="stat-label">{{ stat.label }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Production + Maintenance Row -->
    <v-row class="mb-4">
      <!-- Production Today -->
      <v-col cols="12" md="4">
        <v-card class="info-card h-100" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon color="primary" class="mr-2">mdi-chart-bar</v-icon>
              <span class="card-title">Production Today</span>
            </div>
            <div class="big-number text-primary">{{ production.total_produced ?? 0 }}</div>
            <div class="big-label">Units Produced</div>
            <v-progress-linear
              :model-value="productionPercent"
              color="primary" bg-color="rgba(255,255,255,0.05)"
              rounded height="6" class="mt-3"
            />
            <div class="d-flex justify-space-between mt-1">
              <span class="small-text">Target: {{ production.total_target ?? 0 }}</span>
              <span class="small-text text-primary">{{ productionPercent }}%</span>
            </div>
            <div class="mt-2 d-flex gap-2">
              <v-chip size="x-small" color="error" variant="tonal">
                <v-icon start size="10">mdi-close-circle</v-icon>
                {{ production.total_rejected ?? 0 }} rejected
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Maintenance -->
      <v-col cols="12" md="4">
        <v-card class="info-card h-100" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon color="warning" class="mr-2">mdi-wrench-outline</v-icon>
              <span class="card-title">Maintenance Status</span>
            </div>
            <div class="maintenance-row" v-for="m in maintenanceRows" :key="m.label">
              <div class="d-flex align-center gap-2">
                <div class="dot" :class="`bg-${m.color}`"/>
                <span class="maint-label">{{ m.label }}</span>
              </div>
              <span class="maint-count" :class="`text-${m.color}`">{{ m.value }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Workers + Alerts -->
      <v-col cols="12" md="4">
        <v-card class="info-card h-100" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon color="success" class="mr-2">mdi-account-group-outline</v-icon>
              <span class="card-title">Workforce & Alerts</span>
            </div>
            <div class="workforce-row">
              <div class="wf-stat">
                <div class="wf-value text-success">{{ dashboard.active_workers ?? 0 }}</div>
                <div class="wf-label">Active Workers</div>
              </div>
              <div class="wf-stat">
                <div class="wf-value text-warning">{{ dashboard.unread_alerts ?? 0 }}</div>
                <div class="wf-label">Unread Alerts</div>
              </div>
              <div class="wf-stat">
                <div class="wf-value text-error">{{ dashboard.critical_alerts ?? 0 }}</div>
                <div class="wf-label">Critical</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Machines Status Table -->
    <v-row>
      <v-col cols="12" md="7">
        <v-card class="info-card" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon color="primary" class="mr-2">mdi-cog-outline</v-icon>
              <span class="card-title">Machine Status</span>
            </div>
            <div v-if="!machines.length" class="empty-state">
              <v-icon size="36" color="rgba(255,255,255,0.1)">mdi-cog-off-outline</v-icon>
              <p>No machines registered</p>
            </div>
            <div v-else>
              <div v-for="m in machines" :key="m.id" class="machine-row">
                <div class="machine-info">
                  <div class="machine-name">{{ m.name }}</div>
                  <div class="machine-code">{{ m.code }} · {{ m.type }}</div>
                </div>
                <div class="machine-location">{{ m.location ?? '—' }}</div>
                <v-chip :color="statusColor(m.status)" size="x-small" variant="tonal">
                  <v-icon start size="10">{{ statusIcon(m.status) }}</v-icon>
                  {{ m.status }}
                </v-chip>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Recent Alerts -->
      <v-col cols="12" md="5">
        <v-card class="info-card" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon color="error" class="mr-2">mdi-bell-alert-outline</v-icon>
              <span class="card-title">Recent Alerts</span>
            </div>
            <div v-if="!recentAlerts.length" class="empty-state">
              <v-icon size="36" color="rgba(255,255,255,0.1)">mdi-bell-sleep-outline</v-icon>
              <p>No alerts — all clear!</p>
            </div>
            <div v-for="a in recentAlerts" :key="a.id" class="alert-row">
              <div class="alert-dot" :class="`bg-${severityColor(a.severity)}`"/>
              <div class="alert-info">
                <div class="alert-title">{{ a.title }}</div>
                <div class="alert-time">{{ a.machine?.name ?? 'System' }} · {{ formatDate(a.created_at) }}</div>
              </div>
              <v-chip :color="severityColor(a.severity)" size="x-small" variant="tonal">{{ a.severity }}</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

const loading      = ref(false)
const dashboard    = ref({})
const machines     = ref([])
const recentAlerts = ref([])
const production   = ref({})

const today = new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

const machineCards = computed(() => {
  const s = dashboard.value.machine_stats ?? {}
  return [
    { label: 'Total Machines', value: s.total ?? 0, icon: 'mdi-cog', color: 'primary', status: 'All' },
    { label: 'Online',         value: s.online ?? 0, icon: 'mdi-check-circle', color: 'success', status: 'Running' },
    { label: 'Error',          value: s.error ?? 0, icon: 'mdi-alert-circle', color: 'error', status: 'Fault' },
    { label: 'Maintenance',    value: s.maintenance ?? 0, icon: 'mdi-wrench', color: 'warning', status: 'Service' },
  ]
})

const maintenanceRows = computed(() => {
  const m = dashboard.value.maintenance ?? {}
  return [
    { label: 'Overdue',     value: m.overdue ?? 0,     color: 'error' },
    { label: 'In Progress', value: m.in_progress ?? 0, color: 'warning' },
    { label: 'Upcoming',    value: m.upcoming ?? 0,    color: 'info' },
  ]
})

const productionPercent = computed(() => {
  const p = production.value
  if (!p.total_target || p.total_target === 0) return 0
  return Math.min(100, Math.round((p.total_produced / p.total_target) * 100))
})

function statusColor(s) {
  return { online: 'success', offline: 'default', error: 'error', maintenance: 'warning' }[s] ?? 'default'
}
function statusIcon(s) {
  return { online: 'mdi-check-circle', offline: 'mdi-minus-circle', error: 'mdi-alert-circle', maintenance: 'mdi-wrench' }[s] ?? 'mdi-help-circle'
}
function severityColor(s) {
  return { info: 'info', warning: 'warning', critical: 'error' }[s] ?? 'info'
}
function formatDate(d) {
  return new Date(d).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/dashboard')
    dashboard.value    = data
    machines.value     = data.machines ?? []
    recentAlerts.value = data.recent_alerts ?? []
    production.value   = data.production_today ?? {}
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<style scoped>
.page-heading { font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9); margin: 0; }
.page-sub { font-size: 12px; color: rgba(255,255,255,0.35); margin: 2px 0 0; }
.stat-card { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; transition: all 0.2s; }
.stat-card:hover { background: rgba(255,255,255,0.05) !important; }
.border-primary { border-left: 3px solid #00bcd4 !important; }
.border-success { border-left: 3px solid #4caf50 !important; }
.border-error   { border-left: 3px solid #f44336 !important; }
.border-warning { border-left: 3px solid #ff9800 !important; }
.stat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.bg-primary { background: rgba(0,188,212,0.12) !important; }
.bg-success { background: rgba(76,175,80,0.12) !important; }
.bg-error   { background: rgba(244,67,54,0.12) !important; }
.bg-warning { background: rgba(255,152,0,0.12) !important; }
.stat-value { font-size: 28px; font-weight: 800; color: rgba(255,255,255,0.9); line-height: 1.1; }
.stat-label { font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-trend { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.info-card { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; }
.card-title { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.7); }
.big-number { font-size: 40px; font-weight: 800; line-height: 1; }
.big-label { font-size: 11px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.small-text { font-size: 11px; color: rgba(255,255,255,0.35); }
.maintenance-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
.maintenance-row:last-child { border-bottom: none; }
.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.maint-label { font-size: 12px; color: rgba(255,255,255,0.6); }
.maint-count { font-size: 18px; font-weight: 700; }
.workforce-row { display: flex; justify-content: space-around; margin-top: 12px; }
.wf-stat { text-align: center; }
.wf-value { font-size: 28px; font-weight: 800; line-height: 1; }
.wf-label { font-size: 10px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.machine-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
.machine-row:last-child { border-bottom: none; }
.machine-info { flex: 1; }
.machine-name { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.8); }
.machine-code { font-size: 10px; color: rgba(255,255,255,0.35); }
.machine-location { font-size: 11px; color: rgba(255,255,255,0.3); min-width: 60px; text-align: right; }
.alert-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
.alert-row:last-child { border-bottom: none; }
.alert-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.alert-info { flex: 1; }
.alert-title { font-size: 12px; color: rgba(255,255,255,0.75); font-weight: 500; }
.alert-time  { font-size: 10px; color: rgba(255,255,255,0.3); }
.empty-state { text-align: center; padding: 20px; color: rgba(255,255,255,0.3); font-size: 12px; }
.empty-state p { margin: 8px 0 0; }
</style>
