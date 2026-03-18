<template>
  <div id="app">
    <component :is="routerView" />
  </div>
</template>

<script setup>
import { RouterView } from "vue-router"
const routerView = RouterView
</script>
