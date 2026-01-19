<script setup>
import { Toaster } from 'vue-sonner'
</script>

<template>
  <div>
    <Toaster position="top-right" />
    <slot />
  </div>
</template>