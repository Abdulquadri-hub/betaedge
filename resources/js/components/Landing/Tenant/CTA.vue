<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js'
import { 
  ArrowRight, 
} from 'lucide-vue-next'

const ArrowRightIcon = ArrowRight

defineProps({
    tenant: {
        type: Object,
        default: () => ({})
    },
    pageContent: {
        type: Object,
        default: () => ({})
    }
})

</script>

<template>
    <section class="py-20 md:py-32 relative overflow-hidden">
      <div 
        class="absolute inset-0 opacity-95"
        :style="{ background: `linear-gradient(135deg, ${tenant.primary_color} 0%, ${tenant.secondary_color} 100%)` }"
      />
      
      <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-3xl mx-auto text-center text-white">
          <h2 class="font-display text-3xl md:text-4xl font-bold mb-6">
            {{ pageContent.cta_section?.heading || 'Ready to Start Learning?' }}
          </h2>
          <p class="text-lg mb-10 opacity-90">
            {{ pageContent.cta_section?.subheading || `Join thousands of students already learning at ${tenant.name}` }}
          </p>
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link 
              :href="route('tenant.register.student', {tenant: tenant.slug || tenant.id})"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-lg bg-white font-semibold text-lg shadow-lg hover:shadow-xl transition-all"
              :style="{ color: tenant.primary_color }"
            >
              Enroll Now
              <ArrowRightIcon class="w-5 h-5" />
            </Link>
          </div>
        </div>
      </div>
    </section>
</template>