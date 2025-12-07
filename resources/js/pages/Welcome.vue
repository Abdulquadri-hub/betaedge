<script setup lang="ts">
import { Switch } from '@/components/ui/switch';
import { Camera, GraduationCap } from 'lucide-vue-next';

</script>

<template>
  <div class="border p-10 max-w-full bg-linear-to-br from-teal-300 to-gray-500">
    <Switch />
    <Camera 
       color="red"
       :size="100"
    />
    <GraduationCap
        color="orange"
        :size="100" />
  </div>
</template>
