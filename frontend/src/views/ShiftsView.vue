<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div>
        <h2 class="page-heading">Shifts</h2>
        <p class="page-sub">Manage worker shift assignments</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog" size="small">Assign Shift</v-btn>
    </div>

    <!-- Today's Summary -->
    <v-row class="mb-4">
      <v-col v-for="s in shiftStats" :key="s.label" cols="6" md="3">
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

    <!-- Date filter -->
    <v-card class="filter-bar mb-4" rounded="lg">
      <v-card-text class="pa-3">
        <div class="d-flex gap-3 align-center flex-wrap">
          <input v-model="filterDate" type="date" class="date-input" @change="fetchShifts"/>
          <select v-model="filterType" class="select-input" @change="fetchShifts">
            <option value="">All Shifts</option>
            <option value="day">Day</option>
            <option value="afternoon">Afternoon</option>
            <option value="night">Night</option>
          </select>
          <v-btn size="x-small" color="primary" variant="tonal" @click="filterDate = today; fetchShifts()">Today</v-btn>
          <v-spacer/>
          <v-btn size="x-small" variant="tonal" color="primary" @click="fetchShifts" :loading="loading">
            <v-icon size="14">mdi-refresh</v-icon>
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Shifts Table -->
    <v-card class="data-card" rounded="lg">
      <v-card-text class="pa-0">
        <div class="table-header">
          <div class="th">Worker</div>
          <div class="th">Machine</div>
          <div class="th">Shift</div>
          <div class="th">Time</div>
          <div class="th">Date</div>
          <div class="th">Status</div>
          <div class="th">Actions</div>
        </div>

        <div v-if="loading" class="d-flex justify-center pa-8">
          <v-progress-circular indeterminate color="primary" size="28"/>
        </div>

        <div v-else-if="!shifts.length" class="empty-state">
          <v-icon size="40" color="rgba(255,255,255,0.1)">mdi-clock-outline</v-icon>
          <p>No shifts found for this date</p>
          <v-btn color="primary" variant="tonal" size="small" @click="openDialog" class="mt-2">Assign Shift</v-btn>
        </div>

        <div v-else>
          <div v-for="s in shifts" :key="s.id" class="table-row">
            <div class="td">
              <div class="d-flex align-center gap-2">
                <v-avatar size="28" :color="roleColor(s.worker?.role)" class="flex-shrink-0">
                  <span style="font-size:10px;font-weight:700;color:white">{{ initials(s.worker?.name) }}</span>
                </v-avatar>
                <div>
                  <div class="cell-main">{{ s.worker?.name }}</div>
                  <div class="cell-sub">{{ s.worker?.employee_id || s.worker?.role }}</div>
                </div>
              </div>
            </div>
            <div class="td">
              <div class="cell-main">{{ s.machine?.name || '—' }}</div>
              <div class="cell-sub">{{ s.machine?.code }}</div>
            </div>
            <div class="td">
              <v-chip :color="shiftColor(s.shift_type)" size="x-small" variant="tonal">
                {{ s.shift_type }}
              </v-chip>
            </div>
            <div class="td">
              <span class="cell-sub">{{ s.start_time }} — {{ s.end_time }}</span>
            </div>
            <div class="td">
              <span class="cell-sub">{{ formatDate(s.shift_date) }}</span>
            </div>
            <div class="td">
              <v-chip :color="shiftStatusColor(s.status)" size="x-small" variant="tonal">{{ s.status }}</v-chip>
            </div>
            <div class="td">
              <div class="d-flex gap-1">
                <v-btn v-if="s.status === 'scheduled'" size="x-small" color="success" variant="tonal" @click="updateShiftStatus(s,'active')" :loading="updatingId===s.id">Start</v-btn>
                <v-btn v-if="s.status === 'active'" size="x-small" color="primary" variant="tonal" @click="updateShiftStatus(s,'completed')" :loading="updatingId===s.id">End</v-btn>
              </div>
            </div>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Assign Dialog -->
    <v-dialog v-model="dialog" max-width="480" persistent>
      <v-card color="#111827" rounded="xl">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon color="primary">mdi-clock-plus</v-icon>
          Assign Shift
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="dialog=false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-row dense>
            <v-col cols="12">
              <v-select v-model="form.user_id" label="Worker *" :items="workers" item-title="name" item-value="id" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="12">
              <v-select v-model="form.machine_id" label="Machine" :items="machines" item-title="name" item-value="id" clearable variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.shift_name" label="Shift Name *" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-select v-model="form.shift_type" label="Shift Type *" :items="['day','afternoon','night']" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="4">
              <v-text-field v-model="form.shift_date" label="Date *" type="date" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="4">
              <v-text-field v-model="form.start_time" label="Start *" type="time" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="4">
              <v-text-field v-model="form.end_time" label="End *" type="time" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
          </v-row>
          <v-alert v-if="formError" type="error" variant="tonal" density="compact" class="mt-2">{{ formError }}</v-alert>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer/>
          <v-btn variant="text" @click="dialog=false" color="rgba(255,255,255,0.4)">Cancel</v-btn>
          <v-btn color="primary" variant="tonal" @click="saveShift" :loading="saving">Assign</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="bottom right" rounded="lg">{{ snack.text }}</v-snackbar>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

const loading    = ref(false)
const saving     = ref(false)
const shifts     = ref([])
const workers    = ref([])
const machines   = ref([])
const dialog     = ref(false)
const formError  = ref(null)
const updatingId = ref(null)
const snack      = ref({ show:false, text:'', color:'success' })
const today      = new Date().toISOString().split('T')[0]
const filterDate = ref(today)
const filterType = ref('')

const form = ref({ user_id:null, machine_id:null, shift_name:'', shift_type:'day', shift_date:today, start_time:'06:00', end_time:'14:00' })

const shiftStats = computed(() => [
  { label:'Total Today', value: shifts.value.length, icon:'mdi-clock-outline', color:'primary' },
  { label:'Active',      value: shifts.value.filter(s=>s.status==='active').length, icon:'mdi-play-circle', color:'success' },
  { label:'Scheduled',   value: shifts.value.filter(s=>s.status==='scheduled').length, icon:'mdi-calendar', color:'info' },
  { label:'Completed',   value: shifts.value.filter(s=>s.status==='completed').length, icon:'mdi-check-circle', color:'warning' },
])

function shiftColor(t)       { return { day:'primary', afternoon:'warning', night:'info' }[t] ?? 'default' }
function shiftStatusColor(s) { return { scheduled:'info', active:'success', completed:'default', absent:'error' }[s] ?? 'default' }
function roleColor(r)        { return { manager:'purple', supervisor:'info', operator:'success', maintenance:'warning' }[r] ?? 'default' }
function initials(n)         { return n?.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2) ?? 'U' }
function formatDate(d)       { return new Date(d).toLocaleDateString('en-PH', { month:'short', day:'numeric' }) }
function showSnack(t,c='success') { snack.value = { show:true, text:t, color:c } }

async function fetchShifts() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/shifts', {
      params: { date: filterDate.value, shift_type: filterType.value || undefined }
    })
    shifts.value = data.data
  } catch { showSnack('Failed to load shifts','error') }
  finally { loading.value = false }
}

function openDialog() {
  formError.value = null
  form.value = { user_id:null, machine_id:null, shift_name:'', shift_type:'day', shift_date:today, start_time:'06:00', end_time:'14:00' }
  dialog.value = true
}

async function saveShift() {
  formError.value = null
  if (!form.value.user_id || !form.value.shift_name || !form.value.shift_date) {
    formError.value = 'Worker, shift name and date are required.'
    return
  }
  saving.value = true
  try {
    await axios.post('/api/shifts', form.value)
    showSnack('Shift assigned successfully.')
    dialog.value = false
    await fetchShifts()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to assign shift.'
  } finally { saving.value = false }
}

async function updateShiftStatus(shift, status) {
  updatingId.value = shift.id
  try {
    await axios.patch(`/api/shifts/${shift.id}/status`, { status })
    shift.status = status
    showSnack(`Shift ${status}.`)
  } catch { showSnack('Failed to update shift.','error') }
  finally { updatingId.value = null }
}

onMounted(async () => {
  try { const [m, w] = await Promise.all([axios.get('/api/machines'), axios.get('/api/workers')]); machines.value = m.data.machines; workers.value = w.data.workers } catch {}
  await fetchShifts()
})
</script>

<style scoped>
.page-heading { font-size:20px; font-weight:700; color:rgba(255,255,255,0.9); margin:0; }
.page-sub { font-size:12px; color:rgba(255,255,255,0.35); margin:2px 0 0; }
.stat-card { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.border-primary { border-left:3px solid #00bcd4 !important; }
.border-success { border-left:3px solid #4caf50 !important; }
.border-info    { border-left:3px solid #2196f3 !important; }
.border-warning { border-left:3px solid #ff9800 !important; }
.stat-label { font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.5px; }
.stat-value { font-size:32px; font-weight:800; line-height:1.2; }
.filter-bar { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.date-input,.select-input { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:6px 10px; color:rgba(255,255,255,0.7); font-size:12px; outline:none; cursor:pointer; }
.select-input option { background:#1a1a2e; }
.data-card { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.table-header { display:grid; grid-template-columns:2fr 1.5fr 90px 140px 90px 90px 100px; padding:10px 16px; border-bottom:1px solid rgba(255,255,255,0.06); }
.th { font-size:10px; font-weight:600; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.5px; }
.table-row { display:grid; grid-template-columns:2fr 1.5fr 90px 140px 90px 90px 100px; padding:10px 16px; border-bottom:1px solid rgba(255,255,255,0.04); align-items:center; transition:background 0.15s; }
.table-row:hover { background:rgba(255,255,255,0.03); }
.table-row:last-child { border-bottom:none; }
.td { font-size:12px; }
.cell-main { font-size:12px; color:rgba(255,255,255,0.8); font-weight:500; }
.cell-sub  { font-size:10px; color:rgba(255,255,255,0.35); }
.empty-state { text-align:center; padding:48px; color:rgba(255,255,255,0.3); font-size:13px; }
.empty-state p { margin:12px 0 0; }
</style>
