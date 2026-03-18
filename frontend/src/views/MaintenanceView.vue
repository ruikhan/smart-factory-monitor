<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div>
        <h2 class="page-heading">Maintenance</h2>
        <p class="page-sub">Schedule, track and complete machine maintenance tasks</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openScheduleDialog" size="small">
        Schedule Maintenance
      </v-btn>
    </div>

    <!-- Summary Cards -->
    <v-row class="mb-4">
      <v-col v-for="s in summaryCards" :key="s.label" cols="6" md="3">
        <v-card class="summary-card" :class="`border-${s.color}`" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center gap-2 mb-1">
              <v-icon :color="s.color" size="18">{{ s.icon }}</v-icon>
              <span class="summary-label">{{ s.label }}</span>
            </div>
            <div class="summary-value" :class="`text-${s.color}`">{{ s.value }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-card class="filter-bar mb-4" rounded="lg">
      <v-card-text class="pa-3">
        <div class="d-flex gap-3 align-center flex-wrap">
          <!-- Status tabs -->
          <div class="status-tabs">
            <button
              v-for="t in statusTabs"
              :key="t.value"
              class="tab-btn"
              :class="{ active: activeTab === t.value, [`tab-${t.color}`]: activeTab === t.value }"
              @click="activeTab = t.value; fetchPlans()"
            >
              <v-icon size="12" class="mr-1">{{ t.icon }}</v-icon>
              {{ t.label }}
              <span class="tab-count">{{ tabCount(t.value) }}</span>
            </button>
          </div>
          <v-spacer/>
          <!-- Priority filter -->
          <select v-model="filterPriority" class="select-input" @change="fetchPlans">
            <option value="">All Priorities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
          <v-btn size="x-small" variant="tonal" color="primary" @click="fetchPlans" :loading="loading">
            <v-icon size="14">mdi-refresh</v-icon>
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Plans List -->
    <div v-if="loading" class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary"/>
    </div>

    <div v-else-if="!plans.length" class="empty-state">
      <v-icon size="48" color="rgba(255,255,255,0.1)">mdi-wrench-outline</v-icon>
      <p>No maintenance plans found</p>
      <v-btn color="primary" variant="tonal" size="small" @click="openScheduleDialog" class="mt-2">
        Schedule First Task
      </v-btn>
    </div>

    <v-row v-else>
      <v-col v-for="plan in plans" :key="plan.id" cols="12" md="6" lg="4">
        <v-card class="plan-card" rounded="lg">
          <!-- Priority bar -->
          <div class="priority-bar" :class="`priority-${plan.priority}`"/>

          <v-card-text class="pa-4">
            <!-- Top row -->
            <div class="d-flex align-start justify-space-between mb-3">
              <div class="plan-type-icon" :class="`type-${plan.type}`">
                <v-icon size="18" :color="typeColor(plan.type)">{{ typeIcon(plan.type) }}</v-icon>
              </div>
              <div class="d-flex gap-1">
                <v-chip :color="priorityColor(plan.priority)" size="x-small" variant="tonal">
                  {{ plan.priority }}
                </v-chip>
                <v-chip :color="statusColor(plan.status)" size="x-small" variant="tonal">
                  {{ plan.status.replace('_',' ') }}
                </v-chip>
              </div>
            </div>

            <!-- Title -->
            <div class="plan-title">{{ plan.title }}</div>
            <div class="plan-machine">
              <v-icon size="12" class="mr-1">mdi-cog-outline</v-icon>
              {{ plan.machine?.name ?? '—' }}
            </div>

            <v-divider class="my-3" color="rgba(255,255,255,0.05)"/>

            <!-- Meta info -->
            <div class="plan-meta">
              <div class="meta-item">
                <v-icon size="12" color="rgba(255,255,255,0.3)">mdi-calendar</v-icon>
                <span :class="isOverdue(plan) ? 'text-error' : ''">
                  {{ formatDateTime(plan.scheduled_at) }}
                  <span v-if="isOverdue(plan)" class="overdue-tag">OVERDUE</span>
                </span>
              </div>
              <div class="meta-item">
                <v-icon size="12" color="rgba(255,255,255,0.3)">mdi-clock-outline</v-icon>
                <span>{{ plan.estimated_duration_hours }}h estimated</span>
              </div>
              <div class="meta-item" v-if="plan.assigned_to">
                <v-icon size="12" color="rgba(255,255,255,0.3)">mdi-account</v-icon>
                <span>{{ plan.assigned_to?.name }}</span>
              </div>
              <div class="meta-item" v-if="plan.cost">
                <v-icon size="12" color="rgba(255,255,255,0.3)">mdi-currency-usd</v-icon>
                <span>₱{{ Number(plan.cost).toLocaleString() }}</span>
              </div>
            </div>

            <!-- Description -->
            <div v-if="plan.description" class="plan-desc">{{ plan.description }}</div>

            <v-divider class="mt-3 mb-2" color="rgba(255,255,255,0.05)"/>

            <!-- Action buttons -->
            <div class="d-flex gap-1 align-center">
              <template v-if="plan.status === 'scheduled'">
                <v-btn color="warning" variant="tonal" size="x-small" @click="updateStatus(plan, 'in_progress')" :loading="updatingId === plan.id">
                  <v-icon start size="12">mdi-play</v-icon> Start
                </v-btn>
                <v-btn color="error" variant="text" size="x-small" @click="updateStatus(plan, 'cancelled')" :loading="updatingId === plan.id">
                  Cancel
                </v-btn>
              </template>
              <template v-else-if="plan.status === 'in_progress'">
                <v-btn color="success" variant="tonal" size="x-small" @click="openCompleteDialog(plan)" :loading="updatingId === plan.id">
                  <v-icon start size="12">mdi-check</v-icon> Complete
                </v-btn>
              </template>
              <template v-else>
                <v-chip size="x-small" :color="statusColor(plan.status)" variant="tonal">
                  {{ plan.status === 'completed' ? '✓ Done' : '✗ Cancelled' }}
                </v-chip>
              </template>
              <v-spacer/>
              <v-btn icon size="x-small" variant="text" color="rgba(255,255,255,0.3)" @click="viewPlan(plan)">
                <v-icon size="14">mdi-eye-outline</v-icon>
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="d-flex justify-center mt-4">
      <v-pagination v-model="pagination.current_page" :length="pagination.last_page" density="compact" color="primary" @update:model-value="fetchPlans"/>
    </div>

    <!-- ── Schedule Dialog ── -->
    <v-dialog v-model="scheduleDialog" max-width="540" persistent>
      <v-card color="#111827" rounded="xl">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon color="primary">mdi-wrench-outline</v-icon>
          Schedule Maintenance
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="scheduleDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-row dense>
            <v-col cols="12">
              <v-text-field v-model="form.title" label="Task Title *" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="12">
              <v-select
                v-model="form.machine_id"
                label="Machine *"
                :items="machines"
                item-title="name"
                item-value="id"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="6">
              <v-select v-model="form.type" label="Type *" :items="['preventive','corrective','emergency']" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-select v-model="form.priority" label="Priority *" :items="['low','medium','high','critical']" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.scheduled_at" label="Scheduled Date/Time *" type="datetime-local" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model.number="form.estimated_duration_hours" label="Est. Duration (hrs)" type="number" min="1" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="12">
              <v-select
                v-model="form.assigned_to"
                label="Assign To"
                :items="workers"
                item-title="name"
                item-value="id"
                clearable
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.description" label="Description" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)" rows="2"/>
            </v-col>
          </v-row>
          <v-alert v-if="formError" type="error" variant="tonal" density="compact" class="mt-2">{{ formError }}</v-alert>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer/>
          <v-btn variant="text" @click="scheduleDialog = false" color="rgba(255,255,255,0.4)">Cancel</v-btn>
          <v-btn color="primary" variant="tonal" @click="submitPlan" :loading="saving" min-width="120">
            Schedule
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ── Complete Dialog ── -->
    <v-dialog v-model="completeDialog" max-width="420" persistent>
      <v-card color="#111827" rounded="xl">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon color="success">mdi-check-circle</v-icon>
          Complete Maintenance
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="completeDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-textarea v-model="completeForm.completion_notes" label="Completion Notes" variant="outlined" density="compact" color="success" bg-color="rgba(255,255,255,0.04)" rows="3" class="mb-3"/>
          <v-text-field v-model.number="completeForm.cost" label="Cost (₱)" type="number" min="0" variant="outlined" density="compact" color="success" bg-color="rgba(255,255,255,0.04)"/>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer/>
          <v-btn variant="text" @click="completeDialog = false" color="rgba(255,255,255,0.4)">Cancel</v-btn>
          <v-btn color="success" variant="tonal" @click="submitComplete" :loading="saving" min-width="120">
            Mark Complete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="bottom right" rounded="lg">
      {{ snack.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

const loading         = ref(false)
const saving          = ref(false)
const plans           = ref([])
const machines        = ref([])
const workers         = ref([])
const allPlans        = ref([])
const activeTab       = ref('')
const filterPriority  = ref('')
const scheduleDialog  = ref(false)
const completeDialog  = ref(false)
const completingPlan  = ref(null)
const updatingId      = ref(null)
const formError       = ref(null)
const pagination      = ref({ current_page: 1, last_page: 1 })
const snack           = ref({ show: false, text: '', color: 'success' })

const form = ref({
  machine_id: null, title: '', type: 'preventive', priority: 'medium',
  scheduled_at: '', estimated_duration_hours: 2,
  assigned_to: null, description: ''
})

const completeForm = ref({ completion_notes: '', cost: null })

const statusTabs = [
  { value: '',            label: 'All',         icon: 'mdi-view-grid',    color: 'primary' },
  { value: 'scheduled',   label: 'Scheduled',   icon: 'mdi-calendar',     color: 'info'    },
  { value: 'in_progress', label: 'In Progress', icon: 'mdi-play-circle',  color: 'warning' },
  { value: 'completed',   label: 'Completed',   icon: 'mdi-check-circle', color: 'success' },
  { value: 'cancelled',   label: 'Cancelled',   icon: 'mdi-close-circle', color: 'error'   },
]

const summaryCards = computed(() => [
  { label: 'Overdue',     value: allPlans.value.filter(p => isOverdue(p)).length, icon: 'mdi-alert-circle', color: 'error'   },
  { label: 'Scheduled',   value: allPlans.value.filter(p => p.status === 'scheduled').length,   icon: 'mdi-calendar',    color: 'info'    },
  { label: 'In Progress', value: allPlans.value.filter(p => p.status === 'in_progress').length, icon: 'mdi-play-circle', color: 'warning' },
  { label: 'Completed',   value: allPlans.value.filter(p => p.status === 'completed').length,   icon: 'mdi-check-circle',color: 'success' },
])

function tabCount(val) {
  if (val === '') return allPlans.value.length
  return allPlans.value.filter(p => p.status === val).length
}

function isOverdue(plan) {
  return plan.status === 'scheduled' && new Date(plan.scheduled_at) < new Date()
}

function statusColor(s) {
  return { scheduled: 'info', in_progress: 'warning', completed: 'success', cancelled: 'error' }[s] ?? 'default'
}
function priorityColor(p) {
  return { low: 'success', medium: 'info', high: 'warning', critical: 'error' }[p] ?? 'default'
}
function typeColor(t) {
  return { preventive: 'info', corrective: 'warning', emergency: 'error' }[t] ?? 'default'
}
function typeIcon(t) {
  return { preventive: 'mdi-shield-check', corrective: 'mdi-wrench', emergency: 'mdi-alert' }[t] ?? 'mdi-wrench'
}

function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function showSnack(text, color = 'success') {
  snack.value = { show: true, text, color }
}

async function fetchMachines() {
  try { const { data } = await axios.get('/api/machines'); machines.value = data.machines } catch {}
}
async function fetchWorkers() {
  try { const { data } = await axios.get('/api/workers'); workers.value = data.workers } catch {}
}
async function fetchAllPlans() {
  try { const { data } = await axios.get('/api/maintenance', { params: { per_page: 100 } }); allPlans.value = data.data } catch {}
}

async function fetchPlans() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      status: activeTab.value || undefined,
      priority: filterPriority.value || undefined,
    }
    const { data } = await axios.get('/api/maintenance', { params })
    plans.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
  } catch { showSnack('Failed to load plans', 'error') }
  finally { loading.value = false }
}

function openScheduleDialog() {
  formError.value = null
  form.value = {
    machine_id: null, title: '', type: 'preventive', priority: 'medium',
    scheduled_at: '', estimated_duration_hours: 2,
    assigned_to: null, description: ''
  }
  scheduleDialog.value = true
}

function openCompleteDialog(plan) {
  completingPlan.value = plan
  completeForm.value = { completion_notes: '', cost: null }
  completeDialog.value = true
}

function viewPlan(plan) {
  openCompleteDialog(plan)
}

async function submitPlan() {
  formError.value = null
  if (!form.value.title || !form.value.machine_id || !form.value.type || !form.value.priority || !form.value.scheduled_at) {
    formError.value = 'Please fill in all required fields.'
    return
  }
  saving.value = true
  try {
    await axios.post('/api/maintenance', form.value)
    showSnack('Maintenance scheduled successfully.')
    scheduleDialog.value = false
    await fetchPlans()
    await fetchAllPlans()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to schedule maintenance.'
  } finally { saving.value = false }
}

async function updateStatus(plan, status) {
  updatingId.value = plan.id
  try {
    await axios.patch(`/api/maintenance/${plan.id}/status`, { status })
    showSnack(`Status updated to ${status.replace('_',' ')}.`)
    await fetchPlans()
    await fetchAllPlans()
  } catch { showSnack('Failed to update status.', 'error') }
  finally { updatingId.value = null }
}

async function submitComplete() {
  saving.value = true
  try {
    await axios.patch(`/api/maintenance/${completingPlan.value.id}/status`, {
      status: 'completed',
      completion_notes: completeForm.value.completion_notes,
      cost: completeForm.value.cost,
    })
    showSnack('Maintenance marked as completed! ✓', 'success')
    completeDialog.value = false
    await fetchPlans()
    await fetchAllPlans()
  } catch { showSnack('Failed to complete maintenance.', 'error') }
  finally { saving.value = false }
}

onMounted(async () => {
  await fetchMachines()
  await fetchWorkers()
  await fetchAllPlans()
  await fetchPlans()
})
</script>

<style scoped>
.page-heading { font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9); margin: 0; }
.page-sub { font-size: 12px; color: rgba(255,255,255,0.35); margin: 2px 0 0; }

.summary-card { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; }
.border-error   { border-left: 3px solid #f44336 !important; }
.border-info    { border-left: 3px solid #2196f3 !important; }
.border-warning { border-left: 3px solid #ff9800 !important; }
.border-success { border-left: 3px solid #4caf50 !important; }
.summary-label { font-size: 11px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px; }
.summary-value { font-size: 32px; font-weight: 800; line-height: 1.2; }

.filter-bar { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; }
.status-tabs { display: flex; background: rgba(255,255,255,0.04); border-radius: 8px; padding: 2px; gap: 2px; flex-wrap: wrap; }
.tab-btn { padding: 4px 10px; border-radius: 6px; border: none; background: none; color: rgba(255,255,255,0.4); font-size: 11px; cursor: pointer; transition: all 0.2s; white-space: nowrap; display: flex; align-items: center; }
.tab-btn.active { font-weight: 600; }
.tab-btn.tab-primary { background: rgba(0,188,212,0.15); color: #00bcd4; }
.tab-btn.tab-info    { background: rgba(33,150,243,0.15); color: #2196f3; }
.tab-btn.tab-warning { background: rgba(255,152,0,0.15);  color: #ff9800; }
.tab-btn.tab-success { background: rgba(76,175,80,0.15);  color: #4caf50; }
.tab-btn.tab-error   { background: rgba(244,67,54,0.15);  color: #f44336; }
.tab-count { font-size: 10px; font-weight: 700; background: rgba(255,255,255,0.08); border-radius: 8px; padding: 1px 5px; margin-left: 4px; }

.select-input {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px; padding: 6px 10px; color: rgba(255,255,255,0.7);
  font-size: 12px; outline: none; cursor: pointer;
}
.select-input option { background: #1a1a2e; }

.plan-card { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; transition: all 0.2s; position: relative; overflow: hidden; }
.plan-card:hover { background: rgba(255,255,255,0.05) !important; }

.priority-bar { height: 3px; width: 100%; }
.priority-low      { background: #4caf50; }
.priority-medium   { background: #2196f3; }
.priority-high     { background: #ff9800; }
.priority-critical { background: #f44336; animation: blink-bar 1s infinite; }
@keyframes blink-bar { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.plan-type-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.type-preventive { background: rgba(33,150,243,0.12); }
.type-corrective  { background: rgba(255,152,0,0.12); }
.type-emergency   { background: rgba(244,67,54,0.12); }

.plan-title { font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 4px; }
.plan-machine { font-size: 11px; color: rgba(255,255,255,0.4); display: flex; align-items: center; }
.plan-meta { display: flex; flex-direction: column; gap: 5px; }
.meta-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: rgba(255,255,255,0.5); }
.overdue-tag { background: rgba(244,67,54,0.2); color: #f44336; font-size: 9px; font-weight: 700; padding: 1px 5px; border-radius: 4px; margin-left: 4px; }
.plan-desc { font-size: 11px; color: rgba(255,255,255,0.35); margin-top: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.empty-state { text-align: center; padding: 48px; color: rgba(255,255,255,0.3); font-size: 13px; }
.empty-state p { margin: 12px 0 0; }
</style>
