import axios from "axios"
import { defineStore } from "pinia"
import { ref } from "vue"

export const useAuthStore = defineStore("auth", () => {
  const token = ref(localStorage.getItem("token") || null)
  const user  = ref(JSON.parse(localStorage.getItem("user") || "null"))

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value  = newUser
    localStorage.setItem("token", newToken)
    localStorage.setItem("user", JSON.stringify(newUser))
    axios.defaults.headers.common["Authorization"] = `Bearer ${newToken}`
  }

  function clearAuth() {
    token.value = null
    user.value  = null
    localStorage.removeItem("token")
    localStorage.removeItem("user")
    delete axios.defaults.headers.common["Authorization"]
  }

  async function login(email, password) {
    const { data } = await axios.post("/api/login", { email, password })
    setAuth(data.token, data.user)
    return data
  }

  async function logout() {
    try { await axios.post("/api/logout") } catch {}
    clearAuth()
  }

  // Restore token on app load
  if (token.value) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${token.value}`
  }

  return { token, user, login, logout, setAuth, clearAuth }
})
