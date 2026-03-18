<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
      <div>
        <h2 class="page-heading">Production Tracking</h2>
        <p class="page-sub">Log and monitor factory output per machine</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openLogDialog" size="small">
        Log Production
      </v-btn>
    </div>

    <!-- Summary Cards -->
    <v-row class="mb-4">
      <v-col v-for="s in summaryCards" :key="s.label" cols="6" md="3">
        <v-card class="summary-card" :class="`border-${s.color}`" rounded="lg">
          <v-card-text class="pa-4">
            <div class="d-flex align-center gap-2 mb-2">
              <v-icon :color="s.color" size="18">{{ s.icon }}</v-icon>
              <span class="summary-label">{{ s.label }}</span>
            </div>
            <div class="summary-value" :class="`text-${s.color}`">{{ s.value }}</div>
            <div class="summary-sub">{{ s.sub }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-card class="filter-bar mb-4" rounded="lg">
      <v-card-text class="pa-3">
        <div class="d-flex gap-3 align-center flex-wrap">
          <!-- Period tabs -->
          <div class="period-tabs">
            <button
              v-for="p in periods"
              :key="p.value"
              class="period-btn"
              :class="{ active: period === p.value }"
              @click="setPeriod(p.value)"
            >{{ p.label }}</button>
          </div>

          <v-divider vertical color="rgba(255,255,255,0.08)" class="mx-1"/>

          <!-- Date picker -->
          <input v-model="filterDate" type="date" class="date-input"/>

          <!-- Machine filter -->
          <select v-model="filterMachine" class="select-input">
            <option value="">All Machines</option>
            <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>

          <!-- Shift filter -->
          <select v-model="filterShift" class="select-input">
            <option value="">All Shifts</option>
            <option value="day">Day</option>
            <option value="afternoon">Afternoon</option>
            <option value="night">Night</option>
          </select>

          <v-spacer/>
          <v-btn size="x-small" variant="tonal" color="primary" @click="fetchRecords" :loading="loading">
            <v-icon size="14">mdi-refresh</v-icon>
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- Production Table -->
    <v-card class="data-card" rounded="lg">
      <v-card-text class="pa-0">
        <!-- Table header -->
        <div class="table-header">
          <div class="th col-machine">Machine</div>
          <div class="th col-product">Product</div>
          <div class="th col-shift">Shift</div>
          <div class="th col-date">Date</div>
          <div class="th col-produced">Produced</div>
          <div class="th col-target">Target</div>
          <div class="th col-efficiency">Efficiency</div>
          <div class="th col-rejected">Rejected</div>
          <div class="th col-actions">Actions</div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="d-flex justify-center pa-8">
          <v-progress-circular indeterminate color="primary" size="28"/>
        </div>

        <!-- Empty -->
        <div v-else-if="!records.length" class="empty-state">
          <v-icon size="40" color="rgba(255,255,255,0.1)">mdi-chart-bar</v-icon>
          <p>No production records found</p>
          <v-btn color="primary" variant="tonal" size="small" @click="openLogDialog" class="mt-2">
            Log First Record
          </v-btn>
        </div>

        <!-- Rows -->
        <div v-else>
          <div v-for="r in records" :key="r.id" class="table-row">
            <div class="td col-machine">
              <div class="machine-cell">
                <v-icon size="14" color="primary" class="mr-1">mdi-cog</v-icon>
                <div>
                  <div class="cell-main">{{ r.machine?.name }}</div>
                  <div class="cell-sub">{{ r.machine?.code }}</div>
                </div>
              </div>
            </div>
            <div class="td col-product">
              <span class="cell-main">{{ r.product_name || '—' }}</span>
            </div>
            <div class="td col-shift">
              <v-chip :color="shiftColor(r.shift)" size="x-small" variant="tonal">{{ r.shift }}</v-chip>
            </div>
            <div class="td col-date">
              <span class="cell-sub">{{ formatDate(r.production_date) }}</span>
            </div>
            <div class="td col-produced">
              <span class="cell-main text-success">{{ r.units_produced }}</span>
            </div>
            <div class="td col-target">
              <span class="cell-sub">{{ r.target_units }}</span>
            </div>
            <div class="td col-efficiency">
              <div class="efficiency-wrap">
                <v-progress-linear
                  :model-value="efficiency(r)"
                  :color="efficiencyColor(efficiency(r))"
                  bg-color="rgba(255,255,255,0.05)"
                  rounded height="4"
                  class="mb-1"
                />
                <span class="efficiency-pct" :class="`text-${efficiencyColor(efficiency(r))}`">
                  {{ efficiency(r) }}%
                </span>
              </div>
            </div>
            <div class="td col-rejected">
              <span :class="r.units_rejected > 0 ? 'text-error' : 'text-success'" class="cell-main">
                {{ r.units_rejected }}
              </span>
            </div>
            <div class="td col-actions">
              <v-btn icon size="x-small" variant="text" color="rgba(255,255,255,0.3)" @click="viewRecord(r)">
                <v-icon size="14">mdi-eye-outline</v-icon>
              </v-btn>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="d-flex justify-center pa-3">
          <v-pagination
            v-model="pagination.current_page"
            :length="pagination.last_page"
            density="compact"
            color="primary"
            @update:model-value="fetchRecords"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- ── Log Production Dialog ── -->
    <v-dialog v-model="logDialog" max-width="520" persistent>
      <v-card color="#111827" rounded="xl">
        <v-card-title class="pa-5 pb-3 d-flex align-center gap-2">
          <v-icon color="primary">mdi-chart-bar</v-icon>
          Log Production Record
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="logDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-row dense>
            <v-col cols="12">
              <v-select
                v-model="logForm.machine_id"
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
              <v-select
                v-model="logForm.shift"
                label="Shift *"
                :items="['day','afternoon','night']"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="logForm.production_date"
                label="Date *"
                type="date"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="logForm.start_time"
                label="Start Time *"
                type="time"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="logForm.end_time"
                label="End Time"
                type="time"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="logForm.product_name"
                label="Product Name"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="4">
              <v-text-field
                v-model.number="logForm.target_units"
                label="Target Units *"
                type="number"
                min="1"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="4">
              <v-text-field
                v-model.number="logForm.units_produced"
                label="Units Produced *"
                type="number"
                min="0"
                variant="outlined"
                density="compact"
                color="success"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>
            <v-col cols="4">
              <v-text-field
                v-model.number="logForm.units_rejected"
                label="Rejected"
                type="number"
                min="0"
                variant="outlined"
                density="compact"
                color="error"
                bg-color="rgba(255,255,255,0.04)"
              />
            </v-col>

            <!-- Live efficiency preview -->
            <v-col cols="12" v-if="logForm.target_units && logForm.units_produced">
              <div class="efficiency-preview">
                <div class="d-flex justify-space-between mb-1">
                  <span class="preview-label">Efficiency Preview</span>
                  <span :class="`text-${efficiencyColor(previewEfficiency)}`" class="preview-pct">
                    {{ previewEfficiency }}%
                  </span>
                </div>
                <v-progress-linear
                  :model-value="previewEfficiency"
                  :color="efficiencyColor(previewEfficiency)"
                  bg-color="rgba(255,255,255,0.08)"
                  rounded height="6"
                />
              </div>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="logForm.notes"
                label="Notes"
                variant="outlined"
                density="compact"
                color="primary"
                bg-color="rgba(255,255,255,0.04)"
                rows="2"
              />
            </v-col>
          </v-row>
          <v-alert v-if="logError" type="error" variant="tonal" density="compact" class="mt-2">
            {{ logError }}
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer/>
          <v-btn variant="text" @click="logDialog = false" color="rgba(255,255,255,0.4)">Cancel</v-btn>
          <v-btn color="primary" variant="tonal" @click="submitLog" :loading="saving" min-width="120">
            Save Record
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ── View Record Dialog ── -->
    <v-dialog v-model="viewDialog" max-width="420">
      <v-card color="#111827" rounded="xl" v-if="viewingRecord">
        <v-card-title class="pa-5 pb-3 d-flex align-center">
          <v-icon color="primary" class="mr-2">mdi-chart-bar</v-icon>
          Production Record
          <v-spacer/>
          <v-btn icon size="small" variant="text" @click="viewDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider color="rgba(255,255,255,0.06)"/>
        <v-card-text class="pa-5">
          <v-row dense>
            <v-col cols="6" v-for="row in viewRows" :key="row.label">
              <div class="detail-row">
                <div class="detail-label">{{ row.label }}</div>
                <div class="detail-value" :class="row.class">{{ row.value }}</div>
              </div>
            </v-col>
          </v-row>
          <div v-if="viewingRecord.notes" class="mt-3">
            <div class="detail-label mb-1">Notes</div>
            <div class="notes-box">{{ viewingRecord.notes }}</div>
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

const loading     = ref(false)
const saving      = ref(false)
const machines    = ref([])
const records     = ref([])
const summary     = ref({})
const logDialog   = ref(false)
const viewDialog  = ref(false)
const viewingRecord = ref(null)
const logError    = ref(null)
const period      = ref('today')
const filterDate  = ref(new Date().toISOString().split('T')[0])
const filterMachine = ref('')
const filterShift   = ref('')
const pagination    = ref({ current_page: 1, last_page: 1 })
const snack         = ref({ show: false, text: '', color: 'success' })

const logForm = ref({
  machine_id: null, shift: 'day',
  production_date: new Date().toISOString().split('T')[0],
  start_time: '', end_time: '',
  product_name: '', target_units: 0,
  units_produced: 0, units_rejected: 0, notes: ''
})

const periods = [
  { label: 'Today',  value: 'today' },
  { label: 'Week',   value: 'week'  },
  { label: 'Month',  value: 'month' },
]

const summaryCards = computed(() => [
  { label: 'Units Produced', value: summary.value.total_produced ?? 0,  icon: 'mdi-package-variant', color: 'success', sub: 'Total output' },
  { label: 'Target Units',   value: summary.value.total_target   ?? 0,  icon: 'mdi-target',           color: 'primary', sub: 'Goal set'    },
  { label: 'Units Rejected', value: summary.value.total_rejected ?? 0,  icon: 'mdi-close-circle',     color: 'error',   sub: 'Defects'     },
  { label: 'Records Logged', value: summary.value.total_records  ?? 0,  icon: 'mdi-clipboard-list',   color: 'info',    sub: 'Entries'     },
])

const previewEfficiency = computed(() => {
  if (!logForm.value.target_units || logForm.value.target_units <= 0) return 0
  return Math.min(100, Math.round((logForm.value.units_produced / logForm.value.target_units) * 100))
})

const viewRows = computed(() => {
  if (!viewingRecord.value) return []
  const r = viewingRecord.value
  return [
    { label: 'Machine',        value: r.machine?.name },
    { label: 'Date',           value: formatDate(r.production_date) },
    { label: 'Shift',          value: r.shift },
    { label: 'Product',        value: r.product_name || '—' },
    { label: 'Units Produced', value: r.units_produced, class: 'text-success' },
    { label: 'Target Units',   value: r.target_units },
    { label: 'Units Rejected', value: r.units_rejected, class: r.units_rejected > 0 ? 'text-error' : '' },
    { label: 'Efficiency',     value: efficiency(r) + '%', class: `text-${efficiencyColor(efficiency(r))}` },
    { label: 'Start Time',     value: r.start_time },
    { label: 'End Time',       value: r.end_time || '—' },
  ]
})

function efficiency(r) {
  if (!r.target_units || r.target_units <= 0) return 0
  return Math.min(100, Math.round((r.units_produced / r.target_units) * 100))
}
function efficiencyColor(pct) {
  if (pct >= 90) return 'success'
  if (pct >= 70) return 'warning'
  return 'error'
}
function shiftColor(s) {
  return { day: 'primary', afternoon: 'warning', night: 'info' }[s] ?? 'default'
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}
function showSnack(text, color = 'success') {
  snack.value = { show: true, text, color }
}

async function fetchMachines() {
  try {
    const { data } = await axios.get('/api/machines')
    machines.value = data.machines
  } catch {}
}

async function fetchSummary() {
  try {
    const { data } = await axios.get('/api/production/summary', { params: { period: period.value } })
    summary.value = data.summary
  } catch {}
}

async function fetchRecords() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      date: filterDate.value || undefined,
      machine_id: filterMachine.value || undefined,
      shift: filterShift.value || undefined,
    }
    const { data } = await axios.get('/api/production', { params })
    records.value = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
  } catch { showSnack('Failed to load records', 'error') }
  finally { loading.value = false }
}

function setPeriod(val) {
  period.value = val
  fetchSummary()
}

function openLogDialog() {
  logError.value = null
  logForm.value = {
    machine_id: null, shift: 'day',
    production_date: new Date().toISOString().split('T')[0],
    start_time: '', end_time: '',
    product_name: '', target_units: 0,
    units_produced: 0, units_rejected: 0, notes: ''
  }
  logDialog.value = true
}

function viewRecord(r) {
  viewingRecord.value = r
  viewDialog.value = true
}

async function submitLog() {
  logError.value = null
  const f = logForm.value
  if (!f.machine_id || !f.shift || !f.production_date || !f.start_time || !f.target_units || f.units_produced === null) {
    logError.value = 'Please fill in all required fields.'
    return
  }
  saving.value = true
  try {
    await axios.post('/api/production', f)
    showSnack('Production record saved successfully.')
    logDialog.value = false
    await fetchRecords()
    await fetchSummary()
  } catch (e) {
    logError.value = e.response?.data?.message ?? 'Failed to save record.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchMachines()
  await fetchSummary()
  await fetchRecords()
})
</script>

<style scoped>
.page-heading { font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9); margin: 0; }
.page-sub { font-size: 12px; color: rgba(255,255,255,0.35); margin: 2px 0 0; }

.summary-card { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; }
.border-success { border-left: 3px solid #4caf50 !important; }
.border-primary { border-left: 3px solid #00bcd4 !important; }
.border-error   { border-left: 3px solid #f44336 !important; }
.border-info    { border-left: 3px solid #2196f3 !important; }
.summary-label { font-size: 11px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px; }
.summary-value { font-size: 32px; font-weight: 800; line-height: 1.1; }
.summary-sub   { font-size: 10px; color: rgba(255,255,255,0.25); margin-top: 2px; }

.filter-bar { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; }
.period-tabs { display: flex; background: rgba(255,255,255,0.05); border-radius: 8px; padding: 2px; gap: 2px; }
.period-btn { padding: 4px 12px; border-radius: 6px; border: none; background: none; color: rgba(255,255,255,0.4); font-size: 12px; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.period-btn.active { background: rgba(0,188,212,0.2); color: #00bcd4; font-weight: 600; }
.date-input, .select-input {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px; padding: 6px 10px; color: rgba(255,255,255,0.7);
  font-size: 12px; outline: none; cursor: pointer;
}
.select-input option { background: #1a1a2e; }

.data-card { background: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.06) !important; }
.table-header {
  display: grid;
  grid-template-columns: 2fr 1.5fr 80px 100px 80px 80px 120px 80px 60px;
  padding: 10px 16px; border-bottom: 1px solid rgba(255,255,255,0.06);
}
.th { font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.5px; }
.table-row {
  display: grid;
  grid-template-columns: 2fr 1.5fr 80px 100px 80px 80px 120px 80px 60px;
  padding: 10px 16px; border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: background 0.15s; align-items: center;
}
.table-row:hover { background: rgba(255,255,255,0.03); }
.table-row:last-child { border-bottom: none; }
.td { font-size: 12px; }
.machine-cell { display: flex; align-items: center; gap: 6px; }
.cell-main { font-size: 12px; color: rgba(255,255,255,0.8); font-weight: 500; }
.cell-sub  { font-size: 10px; color: rgba(255,255,255,0.35); }
.efficiency-wrap { min-width: 80px; }
.efficiency-pct { font-size: 10px; font-weight: 600; }

.empty-state { text-align: center; padding: 48px; color: rgba(255,255,255,0.3); font-size: 13px; }
.empty-state p { margin: 12px 0 0; }

.efficiency-preview { background: rgba(255,255,255,0.04); border-radius: 10px; padding: 10px 12px; border: 1px solid rgba(255,255,255,0.06); }
.preview-label { font-size: 11px; color: rgba(255,255,255,0.4); }
.preview-pct { font-size: 13px; font-weight: 700; }

.detail-row { margin-bottom: 10px; }
.detail-label { font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.5px; }
.detail-value { font-size: 13px; color: rgba(255,255,255,0.8); font-weight: 500; margin-top: 2px; }
.notes-box { background: rgba(255,255,255,0.04); border-radius: 8px; padding: 10px 12px; font-size: 12px; color: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.06); }
</style>
