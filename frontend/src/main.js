import axios from "axios"
import { createPinia } from "pinia"
import { createApp } from "vue"
import { createVuetify } from "vuetify"
import * as components from "vuetify/components"
import * as directives from "vuetify/directives"
import App from "./App.vue"
import router from "./router"
import "@mdi/font/css/materialdesignicons.css"
import "vuetify/styles"

axios.defaults.baseURL = import.meta.env.PROD ? "https://api.factory.com" : ""
axios.defaults.headers.common["Accept"] = "application/json"

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: "dark",
    themes: {
      dark: {
        colors: {
          primary:   "#00BCD4",
          secondary: "#0097A7",
          accent:    "#FF6F00",
          success:   "#4CAF50",
          warning:   "#FF9800",
          error:     "#F44336",
          info:      "#2196F3",
          background:"#0A0E1A",
          surface:   "#111827",
        },
      },
    },
  },
})

document.title = "Smart Factory Monitor"

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(vuetify)
app.mount("#app")
