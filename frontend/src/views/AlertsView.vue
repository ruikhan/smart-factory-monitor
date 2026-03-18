<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div>
        <h2 class="page-heading">Alerts</h2>
        <p class="page-sub">System notifications and critical warnings</p>
      </div>
      <v-btn v-if="unreadCount > 0" color="primary" variant="tonal" size="small" @click="markAllRead" :loading="markingAll">
        <v-icon start size="14">mdi-check-all</v-icon>
        Mark All Read
      </v-btn>
    </div>

    <!-- Summary -->
    <v-row class="mb-4">
      <v-col v-for="s in summaryCards" :key="s.label" cols="6" md="3">
        <v-card class="stat-card" :class="`border-${s.color}`" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center gap-2 mb-1">
              <v-icon :color="s.color" size="18">{{ s.icon }}</v-icon>
              <span class="stat-label">{{ s.label }}</span>
            </div>
            <div class="stat-value" :class="`text-${s.color}`">{{ s.value }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-card class="filter-bar mb-4" rounded="lg">
      <v-card-text class="pa-3">
        <div class="d-flex gap-2 align-center flex-wrap">
          <div class="severity-tabs">
            <button v-for="t in severityTabs" :key="t.value" class="sev-btn" :class="{ active: activeSeverity === t.value, [`sev-${t.color}`]: activeSeverity === t.value }" @click="activeSeverity = t.value; fetchAlerts()">
              <v-icon size="11" class="mr-1">{{ t.icon }}</v-icon>{{ t.label }}
              <span class="tab-count">{{ t.count }}</span>
            </button>
          </div>
          <v-spacer/>
          <v-btn-toggle v-model="showUnread" density="compact" color="primary" rounded="lg">
            <v-btn size="x-small" :value="true">Unread Only</v-btn>
            <v-btn size="x-small" :value="false">All</v-btn>
          </v-btn-toggle>
        </div>
      </v-card-text>
    </v-card>

    <!-- Alerts List -->
    <div v-if="loading" class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary"/>
    </div>

    <div v-else-if="!alerts.length" class="empty-state">
      <v-icon size="48" color="rgba(255,255,255,0.1)">mdi-bell-sleep-outline</v-icon>
      <p>No alerts — all systems clear!</p>
    </div>

    <div v-else class="alerts-list">
      <v-card
        v-for="a in alerts"
        :key="a.id"
        class="alert-card"
        :class="{ unread: !a.is_read, [`sev-${a.severity}`]: true }"
        rounded="lg"
      >
        <v-card-text class="pa-4">
          <div class="d-flex align-start gap-3">
            <!-- Severity icon -->
            <div class="alert-icon" :class="`icon-${a.severity}`">
              <v-icon size="18" :color="severityColor(a.severity)">{{ severityIcon(a.severity) }}</v-icon>
            </div>

            <!-- Content -->
            <div class="flex-1">
              <div class="d-flex align-start justify-space-between gap-2">
                <div>
                  <div class="alert-title">{{ a.title }}</div>
                  <div class="alert-message">{{ a.message }}</div>
                </div>
                <div class="d-flex flex-column align-end gap-1">
                  <v-chip :color="severityColor(a.severity)" size="x-small" variant="tonal">{{ a.severity }}</v-chip>
                  <span v-if="!a.is_read" class="unread-dot-label">● NEW</span>
                </div>
              </div>

              <div class="alert-meta mt-2">
                <span v-if="a.machine">
                  <v-icon size="11">mdi-cog-outline</v-icon>
                  {{ a.machine.name }}
                </span>
                <span>
                  <v-icon size="11">mdi-clock-outline</v-icon>
                  {{ formatDateTime(a.created_at) }}
                </span>
                <v-chip v-if="a.is_resolved" size="x-small" color="success" variant="tonal">Resolved</v-chip>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2 mt-3 justify-end">
            <v-btn v-if="!a.is_read" size="x-small" variant="tonal" color="primary" @click="markRead(a)" :loading="markingId === a.id">
              <v-icon start size="11">mdi-check</v-icon> Mark Read
            </v-btn>
            <v-btn v-if="!a.is_resolved" size="x-small" variant="tonal" color="success" @click="resolveAlert(a)" :loading="resolvingId === a.id">
              <v-icon start size="11">mdi-check-circle</v-icon> Resolve
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="d-flex justify-center mt-4">
      <v-pagination v-model="pagination.current_page" :length="pagination.last_page" density="compact" color="primary" @update:model-value="fetchAlerts"/>
    </div>

    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="bottom right" rounded="lg">
      {{ snack.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref, watch } from 'vue'

const loading    = ref(false)
const markingAll = ref(false)
const alerts     = ref([])
const allAlerts  = ref([])
const markingId  = ref(null)
const resolvingId = ref(null)
const activeSeverity = ref('')
const showUnread     = ref(false)
const pagination     = ref({ current_page:1, last_page:1 })
const snack          = ref({ show:false, text:'', color:'success' })

const unreadCount = computed(() => allAlerts.value.filter(a => !a.is_read).length)
const criticalCount = computed(() => allAlerts.value.filter(a => a.severity === 'critical' && !a.is_read).length)

const summaryCards = computed(() => [
  { label:'Total Alerts',    value: allAlerts.value.length,                                       icon:'mdi-bell',          color:'primary' },
  { label:'Unread',          value: unreadCount.value,                                             icon:'mdi-bell-ring',     color:'warning' },
  { label:'Critical',        value: criticalCount.value,                                           icon:'mdi-alert-circle',  color:'error'   },
  { label:'Resolved',        value: allAlerts.value.filter(a=>a.is_resolved).length,               icon:'mdi-check-circle',  color:'success' },
])

const severityTabs = computed(() => [
  { value:'',         label:'All',      icon:'mdi-bell',          color:'primary', count: allAlerts.value.length },
  { value:'critical', label:'Critical', icon:'mdi-alert-circle',  color:'error',   count: allAlerts.value.filter(a=>a.severity==='critical').length },
  { value:'warning',  label:'Warning',  icon:'mdi-alert',         color:'warning', count: allAlerts.value.filter(a=>a.severity==='warning').length  },
  { value:'info',     label:'Info',     icon:'mdi-information',   color:'info',    count: allAlerts.value.filter(a=>a.severity==='info').length     },
])

function severityColor(s) { return { info:'info', warning:'warning', critical:'error' }[s] ?? 'default' }
function severityIcon(s)  { return { info:'mdi-information', warning:'mdi-alert', critical:'mdi-alert-circle' }[s] ?? 'mdi-bell' }
function formatDateTime(d) {
  return new Date(d).toLocaleString('en-PH', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
}
function showSnack(text, color='success') { snack.value = { show:true, text, color } }

async function fetchAllAlerts() {
  try { const { data } = await axios.get('/api/alerts', { params:{ per_page:100 } }); allAlerts.value = data.data } catch {}
}

async function fetchAlerts() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      severity: activeSeverity.value || undefined,
      unread: showUnread.value || undefined,
    }
    const { data } = await axios.get('/api/alerts', { params })
    alerts.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
  } catch { showSnack('Failed to load alerts', 'error') }
  finally { loading.value = false }
}

async function markRead(alert) {
  markingId.value = alert.id
  try {
    await axios.patch(`/api/alerts/${alert.id}/read`)
    alert.is_read = true
    await fetchAllAlerts()
    showSnack('Alert marked as read.')
  } catch { showSnack('Failed to mark read.', 'error') }
  finally { markingId.value = null }
}

async function resolveAlert(alert) {
  resolvingId.value = alert.id
  try {
    await axios.patch(`/api/alerts/${alert.id}/resolve`)
    alert.is_resolved = true
    alert.is_read = true
    await fetchAllAlerts()
    showSnack('Alert resolved. ✓', 'success')
  } catch { showSnack('Failed to resolve alert.', 'error') }
  finally { resolvingId.value = null }
}

async function markAllRead() {
  markingAll.value = true
  try {
    await axios.patch('/api/alerts/read-all')
    alerts.value.forEach(a => a.is_read = true)
    await fetchAllAlerts()
    showSnack('All alerts marked as read.')
  } catch { showSnack('Failed.', 'error') }
  finally { markingAll.value = false }
}

watch(showUnread, () => { pagination.value.current_page = 1; fetchAlerts() })

onMounted(async () => {
  await fetchAllAlerts()
  await fetchAlerts()
})
</script>

<style scoped>
.page-heading { font-size:20px; font-weight:700; color:rgba(255,255,255,0.9); margin:0; }
.page-sub { font-size:12px; color:rgba(255,255,255,0.35); margin:2px 0 0; }
.stat-card { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.border-primary { border-left:3px solid #00bcd4 !important; }
.border-warning { border-left:3px solid #ff9800 !important; }
.border-error   { border-left:3px solid #f44336 !important; }
.border-success { border-left:3px solid #4caf50 !important; }
.stat-label { font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.5px; }
.stat-value { font-size:32px; font-weight:800; line-height:1.2; }
.filter-bar { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.severity-tabs { display:flex; gap:4px; flex-wrap:wrap; }
.sev-btn { padding:4px 10px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.4); font-size:11px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; }
.sev-btn.active.sev-primary { background:rgba(0,188,212,0.15); border-color:rgba(0,188,212,0.3); color:#00bcd4; font-weight:600; }
.sev-btn.active.sev-error   { background:rgba(244,67,54,0.15);  border-color:rgba(244,67,54,0.3);  color:#f44336; font-weight:600; }
.sev-btn.active.sev-warning { background:rgba(255,152,0,0.15);  border-color:rgba(255,152,0,0.3);  color:#ff9800; font-weight:600; }
.sev-btn.active.sev-info    { background:rgba(33,150,243,0.15); border-color:rgba(33,150,243,0.3); color:#2196f3; font-weight:600; }
.tab-count { font-size:10px; font-weight:700; background:rgba(255,255,255,0.08); border-radius:8px; padding:1px 5px; margin-left:4px; }
.alerts-list { display:flex; flex-direction:column; gap:8px; }
.alert-card { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; transition:all 0.2s; }
.alert-card.unread { background:rgba(255,255,255,0.05) !important; }
.alert-card.sev-critical { border-left:3px solid #f44336 !important; }
.alert-card.sev-warning  { border-left:3px solid #ff9800 !important; }
.alert-card.sev-info     { border-left:3px solid #2196f3 !important; }
.alert-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.icon-critical { background:rgba(244,67,54,0.12); }
.icon-warning  { background:rgba(255,152,0,0.12);  }
.icon-info     { background:rgba(33,150,243,0.12); }
.alert-title   { font-size:13px; font-weight:700; color:rgba(255,255,255,0.9); margin-bottom:3px; }
.alert-message { font-size:12px; color:rgba(255,255,255,0.55); line-height:1.5; }
.alert-meta    { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.alert-meta span { font-size:11px; color:rgba(255,255,255,0.35); display:flex; align-items:center; gap:3px; }
.unread-dot-label { font-size:9px; color:#ff9800; font-weight:700; letter-spacing:0.5px; }
.empty-state { text-align:center; padding:48px; color:rgba(255,255,255,0.3); font-size:13px; }
.empty-state p { margin:12px 0 0; }
</style>
