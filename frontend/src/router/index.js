import { createRouter, createWebHistory } from "vue-router"
import { useAuthStore } from "../stores/auth"

const routes = [
  {
    path: "/login",
    name: "Login",
    component: () => import("../views/LoginView.vue"),
    meta: { guest: true },
  },
  {
    path: "/",
    component: () => import("../layouts/MainLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      { path: "",           redirect: "/dashboard" },
      { path: "dashboard",   name: "Dashboard",   component: () => import("../views/DashboardView.vue")   },
      { path: "machines",    name: "Machines",    component: () => import("../views/MachinesView.vue")    },
      { path: "production",  name: "Production",  component: () => import("../views/ProductionView.vue")  },
      { path: "maintenance", name: "Maintenance", component: () => import("../views/MaintenanceView.vue") },
      { path: "shifts",      name: "Shifts",      component: () => import("../views/ShiftsView.vue")      },
      { path: "workers",     name: "Workers",     component: () => import("../views/WorkersView.vue")     },
      { path: "alerts",      name: "Alerts",      component: () => import("../views/AlertsView.vue")      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.token) return "/login"
  if (to.meta.guest && auth.token) return "/dashboard"
})

export default router
