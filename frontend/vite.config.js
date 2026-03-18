import { defineConfig } from "vite"
import vue from "@vitejs/plugin-vue"
import vuetify from "vite-plugin-vuetify"

export default defineConfig({
  plugins: [
    vue(),
    vuetify({ autoImport: true }),
  ],
  resolve: {
    dedupe: ["vue", "vue-router", "pinia", "vuetify"],
  },
  server: {
    host: "0.0.0.0",
    port: 5173,
    hmr: {
      clientPort: 5174,
    },
    allowedHosts: ["factory.my-yca.com"],
    proxy: {
      "/api": {
        target: "http://nginx:80",
        changeOrigin: true,
      },
    },
  },
})
