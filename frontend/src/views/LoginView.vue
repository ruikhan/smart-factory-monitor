<template>
  <v-app theme="dark">
    <div class="login-root">
      <div class="bg-orb orb-1"/>
      <div class="bg-orb orb-2"/>
      <div class="bg-orb orb-3"/>

      <div class="login-card">
        <!-- Logo -->
        <div class="logo-wrap">
          <div class="logo-ring">
            <v-icon size="32" color="white">mdi-factory</v-icon>
          </div>
        </div>

        <div class="login-heading">
          <h1>Smart Factory Monitor</h1>
          <p>Industrial Intelligence Platform</p>
        </div>

        <transition name="shake">
          <div v-if="error" class="error-pill">
            <v-icon size="14" color="white" class="mr-1">mdi-alert-circle</v-icon>
            {{ error }}
          </div>
        </transition>

        <div class="form-group">
          <label class="field-label">Email address</label>
          <div class="field-wrap" :class="{ focused: focused === 'email' }">
            <v-icon size="16" class="field-icon">mdi-email-outline</v-icon>
            <input
              v-model="form.email"
              type="email"
              placeholder="admin@factory.com"
              autocomplete="email"
              @focus="focused = 'email'"
              @blur="focused = null"
              @keydown.enter="focusPassword"
              ref="emailRef"
            />
          </div>
        </div>

        <div class="form-group">
          <label class="field-label">Password</label>
          <div class="field-wrap" :class="{ focused: focused === 'password' }">
            <v-icon size="16" class="field-icon">mdi-lock-outline</v-icon>
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              @focus="focused = 'password'"
              @blur="focused = null"
              @keydown.enter="handleLogin"
              ref="passwordRef"
            />
            <button class="eye-btn" @click="showPassword = !showPassword" type="button">
              <v-icon size="15">{{ showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}</v-icon>
            </button>
          </div>
        </div>

        <button class="signin-btn" :class="{ loading }" :disabled="loading" @click="handleLogin">
          <span v-if="!loading">Sign In</span>
          <span v-else class="btn-spinner"/>
        </button>

        <div class="login-footer-row">
          <v-icon size="12">mdi-shield-check</v-icon>
          Secured Industrial System
        </div>
      </div>

      <div class="page-footer">Developer — Justine Philip Villarosa © {{ new Date().getFullYear() }}</div>
    </div>
  </v-app>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router       = useRouter()
const auth         = useAuthStore()
const loading      = ref(false)
const error        = ref(null)
const focused      = ref(null)
const showPassword = ref(false)
const emailRef     = ref(null)
const passwordRef  = ref(null)
const form         = ref({ email: '', password: '' })

function focusPassword() { passwordRef.value?.focus() }

async function handleLogin() {
  if (!form.value.email || !form.value.password) {
    error.value = 'Please enter your email and password.'
    return
  }
  loading.value = true
  error.value   = null
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'Invalid credentials. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-root {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #050914;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  padding: 24px 16px;
}
.bg-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  opacity: 0.25;
  animation: drift 14s ease-in-out infinite alternate;
}
.orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #00bcd4, #006064); top: -150px; left: -100px; }
.orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #0097a7, #004d40); bottom: -100px; right: -80px; animation-delay: -5s; }
.orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, #ff6f00, #e65100); top: 50%; left: 50%; animation-delay: -10s; opacity: 0.12; }
@keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(25px,-20px) scale(1.06); } }

.login-card {
  position: relative; z-index: 10;
  width: 100%; max-width: 400px;
  background: rgba(255,255,255,0.04);
  backdrop-filter: blur(40px);
  border: 1px solid rgba(0,188,212,0.15);
  border-radius: 20px;
  padding: 36px 32px;
  box-shadow: 0 32px 64px rgba(0,0,0,0.5), 0 0 0 1px rgba(0,188,212,0.08) inset;
}
.logo-wrap { display: flex; justify-content: center; margin-bottom: 20px; }
.logo-ring {
  width: 64px; height: 64px; border-radius: 18px;
  background: linear-gradient(135deg, #00bcd4, #006064);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 24px rgba(0,188,212,0.4), 0 0 0 1px rgba(255,255,255,0.1) inset;
}
.login-heading { text-align: center; margin-bottom: 24px; }
.login-heading h1 { font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.95); letter-spacing: -0.5px; margin: 0 0 4px; }
.login-heading p { font-size: 12px; color: rgba(0,188,212,0.7); margin: 0; letter-spacing: 1px; text-transform: uppercase; }

.error-pill {
  display: flex; align-items: center;
  background: rgba(244,67,54,0.15); border: 1px solid rgba(244,67,54,0.3);
  color: #ff8a80; font-size: 12px; padding: 8px 12px; border-radius: 10px; margin-bottom: 16px;
}
.form-group { margin-bottom: 14px; }
.field-label { display: block; font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.4); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.field-wrap {
  display: flex; align-items: center;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px; padding: 0 14px; height: 46px;
  transition: all 0.2s; gap: 10px;
}
.field-wrap.focused { background: rgba(0,188,212,0.08); border-color: rgba(0,188,212,0.5); box-shadow: 0 0 0 3px rgba(0,188,212,0.1); }
.field-icon { color: rgba(255,255,255,0.25) !important; flex-shrink: 0; }
.field-wrap.focused .field-icon { color: rgba(0,188,212,0.8) !important; }
.field-wrap input { flex: 1; background: none; border: none; outline: none; color: rgba(255,255,255,0.9); font-size: 14px; font-family: inherit; }
.field-wrap input::placeholder { color: rgba(255,255,255,0.2); }
.eye-btn { background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.3); display: flex; align-items: center; padding: 0; transition: color 0.2s; }
.eye-btn:hover { color: rgba(0,188,212,0.8); }

.signin-btn {
  width: 100%; height: 48px; margin-top: 20px; border: none; border-radius: 12px;
  background: linear-gradient(135deg, #00bcd4, #006064);
  color: white; font-size: 14px; font-weight: 700; font-family: inherit;
  cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(0,188,212,0.35); letter-spacing: 0.5px;
}
.signin-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,188,212,0.45); }
.signin-btn:active { transform: scale(0.98); }
.signin-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.login-footer-row { display: flex; align-items: center; justify-content: center; gap: 5px; margin-top: 20px; font-size: 11px; color: rgba(255,255,255,0.2); }
.page-footer { position: relative; z-index: 10; margin-top: 24px; font-size: 11px; color: rgba(255,255,255,0.15); }

.shake-enter-active { animation: shake 0.4s ease; }
@keyframes shake { 0%,100% { transform: translateX(0); } 20% { transform: translateX(-6px); } 40% { transform: translateX(6px); } 60% { transform: translateX(-4px); } 80% { transform: translateX(4px); } }
</style>
