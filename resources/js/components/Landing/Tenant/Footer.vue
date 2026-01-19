<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js'


defineProps({
    tenant: {
        type: Object,
        default: () => ({})
    },
    contactInfo: {
        type: Object,
        default: () => ({})
    }
})

const currentYear = computed(() => new Date().getFullYear())

</script>

<template>
    <footer class="bg-foreground text-background py-12">
      <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
          <!-- Brand -->
          <div class="col-span-2">
            <div class="flex items-center gap-3 mb-4">
              <img 
                v-if="tenant.logo" 
                :src="tenant.logo" 
                :alt="tenant.name"
                class="w-10 h-10 rounded-lg object-cover"
              />
              <span class="font-display font-bold text-xl">
                {{ tenant.name }}
              </span>
            </div>
            <p class="text-background/60 text-sm max-w-sm">
              {{ tenant.description }}
            </p>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="font-semibold text-sm uppercase tracking-wider mb-4">
              Quick Links
            </h4>
            <ul class="space-y-2">
              <li><a href="#about" class="text-background/60 hover:text-background text-sm">About Us</a></li>
              <li><a href="#courses" class="text-background/60 hover:text-background text-sm">Courses</a></li>
              <li><Link :href="route('tenant.register.student', {tenant: tenant.slug || tenant.id})" class="text-background/60 hover:text-background text-sm">Register</Link></li>
              <li><a href="#contact" class="text-background/60 hover:text-background text-sm">Contact</a></li>
            </ul>
          </div>

          <!-- Contact -->
          <div>
            <h4 class="font-semibold text-sm uppercase tracking-wider mb-4">
              Contact
            </h4>
            <ul class="space-y-2 text-sm text-background/60">
              <li>{{ tenant.owner_email }}</li>
              <li>{{ contactInfo.city }}, {{ contactInfo.country }}</li>
            </ul>
          </div>
        </div>

        <div class="pt-8 border-t border-background/10 text-center">
          <p class="text-background/40 text-sm">
            © {{ currentYear }} {{ tenant.name }}. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
</template>