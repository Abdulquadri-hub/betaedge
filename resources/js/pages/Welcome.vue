<script setup lang="ts">
import CTA from '@/components/Landing/CTA.vue';
import FeaturedSchools from '@/components/Landing/FeaturedSchools.vue';
import Features from '@/components/Landing/Features.vue';
import Footer from '@/components/Landing/Footer.vue';
import Hero from '@/components/Landing/Hero.vue';
import Navbar from '@/components/Landing/Navbar.vue';
import Pricing from '@/components/Landing/Pricing.vue';
import Testimonials from '@/components/Landing/Testimonials.vue';


defineProps({
  featuredSchools: {
    type: Array,
    default: () => []
  },
  plans: {
    type: Array, 
    default: () => []
  },
  stats: {
    type: Object, 
    default: () => ({
      total_schools: 0,
      total_students: 0,
      countries: 0
    })
  }
})

</script>

<template>
  <div class="min-h-screen bg-background">
    <Navbar />
    <Hero />
    <Features />
    <FeaturedSchools :schools="featuredSchools" />
    <Pricing :plans="plans" />
    <Testimonials />
    <CTA />
    <Footer />
  </div>
</template>

<style scoped>
@keyframes pulse-soft {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse-soft {
  animation: pulse-soft 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slide-up {
  animation: slide-up 0.6s ease-out;
}

.animate-slide-up-delayed {
  animation: slide-up 0.6s ease-out 0.2s both;
}

@keyframes scale-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-scale-in {
  animation: scale-in 0.6s ease-out 0.4s both;
}

.gradient-hero {
  background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--secondary)) 100%);
}

.gradient-surface {
  background: linear-gradient(to bottom, hsl(var(--background)) 0%, hsl(var(--muted) / 0.3) 100%);
}

.text-gradient-primary {
  background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--secondary)) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.text-gradient-secondary {
  background: linear-gradient(135deg, hsl(var(--secondary)) 0%, hsl(var(--accent)) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.shadow-glow {
  box-shadow: 0 0 20px hsl(var(--primary) / 0.5);
}
</style>
