<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div>
        <h2 class="page-heading">Machines</h2>
        <p class="page-sub">Monitor and manage all factory machines</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openAddDialog" size="small">
        Add Machine
      </v-btn>
    </div>

    <!-- Status Filter Chips -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
      <v-chip
        v-for="f in filters"
        :key="f.value"
        :color="selected === f.value ? f.color : 'default'"
        :variant="selected === f.value ? 'tonal' : 'outlined'"
        size="small"
        class="filter-chip"
        @click="selected = f.value"
      >
        <v-icon start size="12">{{ f.icon }}</v-icon>
        {{ f.label }}
        <span class="ml-1 chip-count">{{ statusCount(f.value) }}</span>
      </v-chip>

      <!-- Search -->
      <v-spacer/>
      <div class="search-wrap">
        <v-icon size="16" class="search-icon">mdi-magnify</v-icon>
        <input v-model="search" placeholder="Search machines..." class="search-input"/>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary"/>
    </div>

    <!-- Machine Grid -->
    <v-row v-else>
      <v-col v-for="m in filteredMachines" :key="m.id" cols="12" sm="6" lg="4">
        <v-card class="machine-card" rounded="lg" @click="openDetail(m)">
          <!-- Status bar -->
          <div class="status-bar" :class="`status-${m.status}`"/>

          <v-card-text class="pa-4">
            <!-- Top row -->
            <div class="d-flex align-start justify-space-between mb-3">
              <div class="machine-icon-wrap" :class="`icon-${m.status}`">
                <v-icon size="22" :color="statusColor(m.status)">{{ typeIcon(m.type) }}</v-icon>
              </div>
              <v-chip :color="statusColor(m.status)" size="x-small" variant="tonal" class="status-chip">
                <span class="status-dot-sm" :class="`dot-${m.status}`"/>
                {{ m.status }}
              </v-chip>
            </div>

            <!-- Machine info -->
            <div class="machine-name">{{ m.name }}</div>
            <div class="machine-meta">
              <span class="meta-tag">{{ m.code }}</span>
              <span class="meta-divider">·</span>
              <span class="meta-tag">{{ m.type }}</span>
            </div>
            <div class="machine-location mt-1" v-if="m.location">
              <v-icon size="11" class="mr-1">mdi-map-marker-outline</v-icon>
              {{ m.location }}
            </div>

            <v-divider class="my-3" color="rgba(255,255,255,0.05)"/>

            <!-- Stats row -->
            <div class="d-flex justify-space-between align-center">
              <div class="machine-stat">
                <div class="stat-val">{{ m.target_output_per_hour }}</div>
                <div class="stat-lbl">Target/hr</div>
              </div>
              <div class="machine-stat">
                <div class="stat-val text-primary">{{ m.today_output ?? 0 }}</div>
                <div class="stat-lbl">Today</div>
              </div>
              <div class="machine-stat">
                <div class="stat-val" :class="m.next_maintenance_date ? 'text-warning' : 'text-success'">
                  {{ m.next_maintenance_date ? formatDate(m.next_maintenance_date) : 'OK' }}
                </div>
                <div class="stat-lbl">Next Maint.</div>
              </div>
            </div>

            <!-- Quick status actions -->
            <div class="d-flex gap-1 mt-3" @click.stop>
              <v-btn
                v-for="s in quickStatuses"
                :key="s.value"
                :color="m.status === s.value ? s.color : 'default'"
                :variant="m.status === s.value ? 'tonal' : 'text'"
                size="x-small"
                @click="quickUpdateStatus(m, s.value)"
                :loading="updatingId === m.id && updatingStatus === s.value"
                :title="s.label"
              >
                <v-icon size="12">{{ s.icon }}</v-icon>
              </v-btn>
              <v-spacer/>
              <v-btn icon size="x-small" variant="text" color="rgba(255,255,255,0.3)" @click.stop="openEdit(m)">
                <v-icon size="14">mdi-pencil-outline</v-icon>
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Empty -->
      <v-col v-if="!filteredMachines.length" cols="12">
        <div class="empty-state">
          <v-icon size="48" color="rgba(255,255,255,0.1)">mdi-cog-off-outline</v-icon>
          <p>No machines found</p>
          <v-btn color="primary" variant="tonal" size="small" @click="openAddDialog" class="mt-2">
            Add First Machine
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- ── Add/Edit Dialog ── -->
    <v-dialog v-model="dialog" max-width="520" persistent>
      <v-card color="#111827" rounded="xl">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon color="primary">{{ editingMachine ? 'mdi-pencil' : 'mdi-plus-circle' }}</v-icon>
          {{ editingMachine ? 'Edit Machine' : 'Add New Machine' }}
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-row dense>
            <v-col cols="12">
              <v-text-field v-model="form.name" label="Machine Name *" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.code" label="Machine Code *" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)" :disabled="!!editingMachine"/>
            </v-col>
            <v-col cols="6">
              <v-select v-model="form.type" label="Type *" :items="machineTypes" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.manufacturer" label="Manufacturer" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.model" label="Model" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.location" label="Location" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model.number="form.target_output_per_hour" label="Target Output/hr" type="number" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.serial_number" label="Serial Number" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.installation_date" label="Installation Date" type="date" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)"/>
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.notes" label="Notes" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)" rows="2"/>
            </v-col>
          </v-row>
          <v-alert v-if="formError" type="error" variant="tonal" density="compact" class="mt-2">{{ formError }}</v-alert>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer/>
          <v-btn variant="text" @click="dialog = false" color="rgba(255,255,255,0.4)">Cancel</v-btn>
          <v-btn color="primary" variant="tonal" @click="saveMachine" :loading="saving" min-width="100">
            {{ editingMachine ? 'Save Changes' : 'Add Machine' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ── Detail / Log Dialog ── -->
    <v-dialog v-model="detailDialog" max-width="560">
      <v-card color="#111827" rounded="xl" v-if="detailMachine">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon :color="statusColor(detailMachine.status)">{{ typeIcon(detailMachine.type) }}</v-icon>
          {{ detailMachine.name }}
          <v-chip :color="statusColor(detailMachine.status)" size="x-small" variant="tonal" class="ml-1">{{ detailMachine.status }}</v-chip>
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="detailDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <!-- Info grid -->
          <v-row dense class="mb-3">
            <v-col cols="6" v-for="info in detailInfo" :key="info.label">
              <div class="detail-row">
                <div class="detail-label">{{ info.label }}</div>
                <div class="detail-value">{{ info.value || '—' }}</div>
              </div>
            </v-col>
          </v-row>
          <!-- Update status -->
          <div class="update-status-section">
            <div class="section-label mb-2">Update Status</div>
            <div class="d-flex gap-2 flex-wrap">
              <v-btn
                v-for="s in quickStatuses"
                :key="s.value"
                :color="detailMachine.status === s.value ? s.color : 'default'"
                :variant="detailMachine.status === s.value ? 'tonal' : 'outlined'"
                size="small"
                @click="quickUpdateStatus(detailMachine, s.value)"
                :loading="updatingId === detailMachine.id && updatingStatus === s.value"
              >
                <v-icon start size="14">{{ s.icon }}</v-icon>
                {{ s.label }}
              </v-btn>
            </div>
            <v-textarea v-model="statusNote" label="Status note (optional)" variant="outlined" density="compact" color="primary" bg-color="rgba(255,255,255,0.04)" rows="2" class="mt-3"/>
          </div>
        </v-card-text>
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

const loading       = ref(false)
const saving        = ref(false)
const machines      = ref([])
const selected      = ref('all')
const search        = ref('')
const dialog        = ref(false)
const detailDialog  = ref(false)
const editingMachine = ref(null)
const detailMachine  = ref(null)
const updatingId     = ref(null)
const updatingStatus = ref(null)
const statusNote     = ref('')
const formError      = ref(null)
const snack          = ref({ show: false, text: '', color: 'success' })

const form = ref({
  name: '', code: '', type: '', manufacturer: '', model: '',
  location: '', target_output_per_hour: 0, serial_number: '',
  installation_date: '', notes: ''
})

const machineTypes = ['CNC', 'Assembly', 'Welding', 'Conveyor', 'Drilling', 'Pressing', 'Cutting', 'Packaging', 'Inspection', 'Other']

const filters = [
  { value: 'all',         label: 'All',         icon: 'mdi-view-grid',       color: 'primary' },
  { value: 'online',      label: 'Online',       icon: 'mdi-check-circle',    color: 'success' },
  { value: 'offline',     label: 'Offline',      icon: 'mdi-minus-circle',    color: 'default' },
  { value: 'error',       label: 'Error',        icon: 'mdi-alert-circle',    color: 'error'   },
  { value: 'maintenance', label: 'Maintenance',  icon: 'mdi-wrench',          color: 'warning' },
]

const quickStatuses = [
  { value: 'online',      label: 'Online',      icon: 'mdi-check-circle',  color: 'success' },
  { value: 'offline',     label: 'Offline',     icon: 'mdi-minus-circle',  color: 'default' },
  { value: 'error',       label: 'Error',       icon: 'mdi-alert-circle',  color: 'error'   },
  { value: 'maintenance', label: 'Maintenance', icon: 'mdi-wrench',        color: 'warning' },
]

const filteredMachines = computed(() => {
  let list = machines.value
  if (selected.value !== 'all') list = list.filter(m => m.status === selected.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(m =>
      m.name.toLowerCase().includes(q) ||
      m.code.toLowerCase().includes(q) ||
      m.type.toLowerCase().includes(q) ||
      (m.location ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

const detailInfo = computed(() => {
  if (!detailMachine.value) return []
  const m = detailMachine.value
  return [
    { label: 'Code',            value: m.code },
    { label: 'Type',            value: m.type },
    { label: 'Manufacturer',    value: m.manufacturer },
    { label: 'Model',           value: m.model },
    { label: 'Serial Number',   value: m.serial_number },
    { label: 'Location',        value: m.location },
    { label: 'Target/hr',       value: m.target_output_per_hour },
    { label: 'Installation',    value: m.installation_date },
    { label: 'Last Maintenance',value: m.last_maintenance_date },
    { label: 'Next Maintenance',value: m.next_maintenance_date },
  ]
})

function statusCount(val) {
  if (val === 'all') return machines.value.length
  return machines.value.filter(m => m.status === val).length
}

function statusColor(s) {
  return { online: 'success', offline: 'grey', error: 'error', maintenance: 'warning' }[s] ?? 'grey'
}

function typeIcon(t) {
  const icons = {
    CNC: 'mdi-cog', Assembly: 'mdi-puzzle', Welding: 'mdi-fire',
    Conveyor: 'mdi-arrow-right-bold', Drilling: 'mdi-drill', Pressing: 'mdi-weight',
    Cutting: 'mdi-content-cut', Packaging: 'mdi-package', Inspection: 'mdi-magnify-scan',
  }
  return icons[t] ?? 'mdi-cog-outline'
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function showSnack(text, color = 'success') {
  snack.value = { show: true, text, color }
}

async function fetchMachines() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/machines')
    machines.value = data.machines
  } catch { showSnack('Failed to load machines', 'error') }
  finally { loading.value = false }
}

function openAddDialog() {
  editingMachine.value = null
  form.value = { name: '', code: '', type: '', manufacturer: '', model: '', location: '', target_output_per_hour: 0, serial_number: '', installation_date: '', notes: '' }
  formError.value = null
  dialog.value = true
}

function openEdit(m) {
  editingMachine.value = m
  form.value = { ...m }
  formError.value = null
  dialog.value = true
}

function openDetail(m) {
  detailMachine.value = { ...m }
  statusNote.value = ''
  detailDialog.value = true
}

async function saveMachine() {
  formError.value = null
  if (!form.value.name || !form.value.code || !form.value.type) {
    formError.value = 'Name, Code, and Type are required.'
    return
  }
  saving.value = true
  try {
    if (editingMachine.value) {
      await axios.put(`/api/machines/${editingMachine.value.id}`, form.value)
      showSnack('Machine updated successfully.')
    } else {
      await axios.post('/api/machines', form.value)
      showSnack('Machine added successfully.')
    }
    dialog.value = false
    await fetchMachines()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save machine.'
  } finally {
    saving.value = false
  }
}

async function quickUpdateStatus(machine, status) {
  if (machine.status === status) return
  updatingId.value   = machine.id
  updatingStatus.value = status
  try {
    await axios.patch(`/api/machines/${machine.id}/status`, {
      status,
      notes: statusNote.value || null,
    })
    machine.status = status
    if (detailMachine.value?.id === machine.id) detailMachine.value.status = status
    showSnack(`Status updated to ${status}.`, statusColor(status))
    statusNote.value = ''
    await fetchMachines()
  } catch {
    showSnack('Failed to update status.', 'error')
  } finally {
    updatingId.value   = null
    updatingStatus.value = null
  }
}

onMounted(fetchMachines)
</script>

<style scoped>
.page-heading { font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9); margin: 0; }
.page-sub { font-size: 12px; color: rgba(255,255,255,0.35); margin: 2px 0 0; }

.filter-chip { cursor: pointer; font-size: 11px; }
.chip-count { font-size: 10px; font-weight: 700; background: rgba(255,255,255,0.1); border-radius: 8px; padding: 1px 5px; }

.search-wrap { display: flex; align-items: center; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 0 10px; height: 32px; gap: 6px; }
.search-icon { color: rgba(255,255,255,0.3); }
.search-input { background: none; border: none; outline: none; color: rgba(255,255,255,0.8); font-size: 12px; width: 160px; }
.search-input::placeholder { color: rgba(255,255,255,0.25); }

.machine-card {
  background: rgba(255,255,255,0.03) !important;
  border: 1px solid rgba(255,255,255,0.06) !important;
  cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden;
}
.machine-card:hover { background: rgba(255,255,255,0.06) !important; border-color: rgba(0,188,212,0.2) !important; transform: translateY(-2px); }

.status-bar { height: 3px; width: 100%; }
.status-online      { background: #4caf50; }
.status-offline     { background: #757575; }
.status-error       { background: #f44336; }
.status-maintenance { background: #ff9800; }

.machine-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.icon-online      { background: rgba(76,175,80,0.12); }
.icon-offline     { background: rgba(117,117,117,0.12); }
.icon-error       { background: rgba(244,67,54,0.12); }
.icon-maintenance { background: rgba(255,152,0,0.12); }

.status-chip { font-size: 10px; font-weight: 600; text-transform: capitalize; }
.status-dot-sm { width: 6px; height: 6px; border-radius: 50%; margin-right: 4px; display: inline-block; }
.dot-online      { background: #4caf50; animation: pulse-dot 2s infinite; }
.dot-offline     { background: #757575; }
.dot-error       { background: #f44336; animation: pulse-dot 1s infinite; }
.dot-maintenance { background: #ff9800; }
@keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.machine-name { font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 4px; }
.machine-meta { display: flex; align-items: center; gap: 4px; }
.meta-tag { font-size: 11px; color: rgba(255,255,255,0.35); }
.meta-divider { color: rgba(255,255,255,0.2); }
.machine-location { font-size: 11px; color: rgba(255,255,255,0.3); display: flex; align-items: center; }

.machine-stat { text-align: center; }
.stat-val { font-size: 16px; font-weight: 700; color: rgba(255,255,255,0.85); line-height: 1.2; }
.stat-lbl { font-size: 9px; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

.empty-state { text-align: center; padding: 48px; color: rgba(255,255,255,0.3); font-size: 13px; }
.empty-state p { margin: 12px 0 0; }

.update-status-section { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 14px; border: 1px solid rgba(255,255,255,0.06); }
.section-label { font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px; }

.detail-row { margin-bottom: 10px; }
.detail-label { font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.5px; }
.detail-value { font-size: 13px; color: rgba(255,255,255,0.8); font-weight: 500; margin-top: 2px; }
</style>
