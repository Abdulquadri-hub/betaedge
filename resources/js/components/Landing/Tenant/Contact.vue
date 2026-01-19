<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js'
import { 
  Mail,
  Phone,
  MapPin,
} from 'lucide-vue-next'

const MailIcon = Mail
const PhoneIcon = Phone
const MapPinIcon = MapPin

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

</script>

<template>
    <section id="contact" class="py-20 md:py-32 bg-muted/30">
      <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
          <div class="text-center mb-12">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
              Get in Touch
            </h2>
            <p class="text-muted-foreground">
              Have questions? We'd love to hear from you
            </p>
          </div>

          <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center p-6 rounded-xl bg-card border border-border">
              <div class="w-12 h-12 rounded-full mx-auto mb-4 flex items-center justify-center" :style="{ backgroundColor: `${tenant.primary_color}20` }">
                <MailIcon class="w-6 h-6" :style="{ color: tenant.primary_color }" />
              </div>
              <h3 class="font-semibold text-foreground mb-2">Email</h3>
              <a :href="`mailto:${tenant.owner_email}`" class="text-muted-foreground hover:text-foreground">
                {{ tenant.owner_email }}
              </a>
            </div>

            <div class="text-center p-6 rounded-xl bg-card border border-border">
              <div class="w-12 h-12 rounded-full mx-auto mb-4 flex items-center justify-center" :style="{ backgroundColor: `${tenant.primary_color}20` }">
                <PhoneIcon class="w-6 h-6" :style="{ color: tenant.primary_color }" />
              </div>
              <h3 class="font-semibold text-foreground mb-2">Phone</h3>
              <a :href="`tel:${contactInfo.phone}`" class="text-muted-foreground hover:text-foreground">
                {{ contactInfo.phone }}
              </a>
            </div>

            <div class="text-center p-6 rounded-xl bg-card border border-border">
              <div class="w-12 h-12 rounded-full mx-auto mb-4 flex items-center justify-center" :style="{ backgroundColor: `${tenant.primary_color}20` }">
                <MapPinIcon class="w-6 h-6" :style="{ color: tenant.primary_color }" />
              </div>
              <h3 class="font-semibold text-foreground mb-2">Location</h3>
              <p class="text-muted-foreground">
                {{ contactInfo.city }}, {{ contactInfo.country }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
</template>