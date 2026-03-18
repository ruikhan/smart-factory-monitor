<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div>
        <h2 class="page-heading">Workers</h2>
        <p class="page-sub">Manage factory workforce and assignments</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openAddDialog" size="small">
        Add Worker
      </v-btn>
    </div>

    <!-- Stats -->
    <v-row class="mb-4">
      <v-col v-for="s in stats" :key="s.label" cols="6" md="3">
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

    <!-- Search & Filter -->
    <v-card class="filter-bar mb-4" rounded="lg">
      <v-card-text class="pa-3">
        <div class="d-flex gap-3 align-center flex-wrap">
          <div class="search-wrap">
            <v-icon size="16" class="search-icon">mdi-magnify</v-icon>
            <input v-model="search" placeholder="Search workers..." class="search-input"/>
          </div>
          <select v-model="filterRole" class="select-input">
            <option value="">All Roles</option>
            <option value="manager">Manager</option>
            <option value="supervisor">Supervisor</option>
            <option value="operator">Operator</option>
            <option value="maintenance">Maintenance</option>
          </select>
          <select v-model="filterDept" class="select-input">
            <option value="">All Departments</option>
            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
      </v-card-text>
    </v-card>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary"/>
    </div>

    <!-- Workers Grid -->
    <v-row v-else>
      <v-col v-for="w in filteredWorkers" :key="w.id" cols="12" sm="6" lg="4">
        <v-card class="worker-card" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center gap-3 mb-3">
              <v-avatar size="44" :color="roleColor(w.role)" class="worker-avatar">
                <span class="avatar-initials">{{ initials(w.name) }}</span>
              </v-avatar>
              <div class="flex-1">
                <div class="worker-name">{{ w.name }}</div>
                <div class="worker-email">{{ w.email }}</div>
              </div>
              <v-chip :color="roleColor(w.role)" size="x-small" variant="tonal">
                {{ w.role }}
              </v-chip>
            </div>

            <v-divider color="rgba(255,255,255,0.05)" class="mb-3"/>

            <div class="worker-details">
              <div class="detail-item">
                <v-icon size="13" color="rgba(255,255,255,0.3)">mdi-badge-account</v-icon>
                <span>{{ w.employee_id || 'No ID' }}</span>
              </div>
              <div class="detail-item">
                <v-icon size="13" color="rgba(255,255,255,0.3)">mdi-domain</v-icon>
                <span>{{ w.department || 'No Dept.' }}</span>
              </div>
              <div class="detail-item">
                <v-icon size="13" color="rgba(255,255,255,0.3)">mdi-calendar-plus</v-icon>
                <span>Joined {{ formatDate(w.created_at) }}</span>
              </div>
            </div>

            <div class="d-flex justify-end mt-3" @click.stop>
              <v-btn size="x-small" variant="text" color="error" @click="deactivateWorker(w)" :loading="deactivatingId === w.id">
                <v-icon size="12">mdi-account-off</v-icon>
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col v-if="!filteredWorkers.length" cols="12">
        <div class="empty-state">
          <v-icon size="48" color="rgba(255,255,255,0.1)">mdi-account-off-outline</v-icon>
          <p>No workers found</p>
        </div>
      </v-col>
    </v-row>

    <!-- Add Worker Dialog -->
    <v-dialog v-model="dialog" max-width="480" persistent>
      <v-card color="#111827" rounded="xl">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon color="primary">mdi-account-plus</v-icon>
          Add New Worker
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="dialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-row dense>
            <v-col cols="12">
              <v-text-field v-model="form.name" label="Full Name *" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="12">
              <v-text-field v-model="form.email" label="Email *" type="email" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.password" label="Password *" type="password" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-select v-model="form.role" label="Role *" :items="['manager','supervisor','operator','maintenance']" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.employee_id" label="Employee ID" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.department" label="Department" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
          </v-row>
          <v-alert v-if="formError" type="error" variant="tonal" density="compact" class="mt-2">{{ formError }}</v-alert>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer/>
          <v-btn variant="text" @click="dialog = false" color="rgba(255,255,255,0.4)">Cancel</v-btn>
          <v-btn color="primary" variant="tonal" @click="saveWorker" :loading="saving">Add Worker</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="bottom right" rounded="lg">
      {{ snack.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

const loading       = ref(false)
const saving        = ref(false)
const workers       = ref([])
const dialog        = ref(false)
const formError     = ref(null)
const search        = ref('')
const filterRole    = ref('')
const filterDept    = ref('')
const deactivatingId = ref(null)
const snack         = ref({ show: false, text: '', color: 'success' })
const form          = ref({ name:'', email:'', password:'', role:'operator', employee_id:'', department:'' })

const departments = computed(() => [...new Set(workers.value.map(w => w.department).filter(Boolean))])

const stats = computed(() => [
  { label:'Total Workers', value: workers.value.length, icon:'mdi-account-group', color:'primary' },
  { label:'Managers',      value: workers.value.filter(w=>w.role==='manager').length, icon:'mdi-account-tie', color:'info' },
  { label:'Operators',     value: workers.value.filter(w=>w.role==='operator').length, icon:'mdi-account-hard-hat', color:'success' },
  { label:'Maintenance',   value: workers.value.filter(w=>w.role==='maintenance').length, icon:'mdi-wrench-account', color:'warning' },
])

const filteredWorkers = computed(() => {
  let list = workers.value
  if (filterRole.value) list = list.filter(w => w.role === filterRole.value)
  if (filterDept.value) list = list.filter(w => w.department === filterDept.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(w => w.name.toLowerCase().includes(q) || w.email.toLowerCase().includes(q) || (w.employee_id??'').toLowerCase().includes(q))
  }
  return list
})

function roleColor(r) {
  return { super_admin:'error', manager:'purple', supervisor:'info', operator:'success', maintenance:'warning' }[r] ?? 'default'
}
function initials(name) {
  return name?.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2) ?? 'U'
}
function formatDate(d) {
  return new Date(d).toLocaleDateString('en-PH', { month:'short', year:'numeric' })
}
function showSnack(text, color='success') { snack.value = { show:true, text, color } }

async function fetchWorkers() {
  loading.value = true
  try { const { data } = await axios.get('/api/workers'); workers.value = data.workers }
  catch { showSnack('Failed to load workers', 'error') }
  finally { loading.value = false }
}

function openAddDialog() {
  formError.value = null
  form.value = { name:'', email:'', password:'', role:'operator', employee_id:'', department:'' }
  dialog.value = true
}

async function saveWorker() {
  formError.value = null
  if (!form.value.name || !form.value.email || !form.value.password || !form.value.role) {
    formError.value = 'Name, email, password and role are required.'
    return
  }
  saving.value = true
  try {
    await axios.post('/api/workers', form.value)
    showSnack('Worker added successfully.')
    dialog.value = false
    await fetchWorkers()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to add worker.'
  } finally { saving.value = false }
}

async function deactivateWorker(w) {
  if (!confirm(`Deactivate ${w.name}?`)) return
  deactivatingId.value = w.id
  try {
    await axios.delete(`/api/workers/${w.id}`)
    showSnack(`${w.name} deactivated.`, 'warning')
    await fetchWorkers()
  } catch { showSnack('Failed to deactivate worker.', 'error') }
  finally { deactivatingId.value = null }
}

onMounted(fetchWorkers)
</script>

<style scoped>
.page-heading { font-size:20px; font-weight:700; color:rgba(255,255,255,0.9); margin:0; }
.page-sub { font-size:12px; color:rgba(255,255,255,0.35); margin:2px 0 0; }
.stat-card { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.border-primary { border-left:3px solid #00bcd4 !important; }
.border-info    { border-left:3px solid #2196f3 !important; }
.border-success { border-left:3px solid #4caf50 !important; }
.border-warning { border-left:3px solid #ff9800 !important; }
.stat-label { font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.5px; }
.stat-value { font-size:32px; font-weight:800; line-height:1.2; }
.filter-bar { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; }
.search-wrap { display:flex; align-items:center; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:0 10px; height:32px; gap:6px; }
.search-icon { color:rgba(255,255,255,0.3); }
.search-input { background:none; border:none; outline:none; color:rgba(255,255,255,0.8); font-size:12px; width:160px; }
.search-input::placeholder { color:rgba(255,255,255,0.25); }
.select-input { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:6px 10px; color:rgba(255,255,255,0.7); font-size:12px; outline:none; cursor:pointer; }
.select-input option { background:#1a1a2e; }
.worker-card { background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.06) !important; transition:all 0.2s; }
.worker-card:hover { background:rgba(255,255,255,0.06) !important; }
.worker-avatar { font-size:14px; font-weight:700; }
.avatar-initials { font-size:14px; font-weight:700; color:white; }
.worker-name { font-size:14px; font-weight:700; color:rgba(255,255,255,0.9); }
.worker-email { font-size:11px; color:rgba(255,255,255,0.4); }
.worker-details { display:flex; flex-direction:column; gap:5px; }
.detail-item { display:flex; align-items:center; gap:6px; font-size:11px; color:rgba(255,255,255,0.5); }
.empty-state { text-align:center; padding:48px; color:rgba(255,255,255,0.3); font-size:13px; }
.empty-state p { margin:12px 0 0; }
</style>
