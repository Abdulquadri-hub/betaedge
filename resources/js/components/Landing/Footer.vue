<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { GraduationCap, Twitter, Linkedin, Instagram, Youtube } from 'lucide-vue-next'

const GraduationCapIcon = GraduationCap

defineProps({
  name: {
    type: String,
    default: 'BetaEdge'
  }
})

const currentYear = computed(() => new Date().getFullYear())

const socialLinks = [
  { icon: Twitter, href: '#', label: 'Twitter' },
  { icon: Linkedin, href: '#', label: 'LinkedIn' },
  { icon: Instagram, href: '#', label: 'Instagram' },
  { icon: Youtube, href: '#', label: 'YouTube' },
]

const footerSections = [
  {
    title: 'Product',
    links: [
      { label: 'Features', href: '#features' },
      { label: 'Pricing', href: '#pricing' },
      { label: 'Marketplace', href: '/marketplace' },
      { label: 'API', href: '#' },
    ]
  },
  {
    title: 'Company',
    links: [
      { label: 'About Us', href: '#about' },
      { label: 'Careers', href: '#' },
      { label: 'Press', href: '#' },
      { label: 'Contact', href: '#' },
    ]
  },
  {
    title: 'Resources',
    links: [
      { label: 'Blog', href: '#' },
      { label: 'Help Center', href: '#' },
      { label: 'Community', href: '#' },
      { label: 'Webinars', href: '#' },
    ]
  },
  {
    title: 'Legal',
    links: [
      { label: 'Privacy Policy', href: '#' },
      { label: 'Terms of Service', href: '#' },
      { label: 'Cookie Policy', href: '#' },
    ]
  }
]
</script>

<template>
  <footer id="about" class="bg-foreground text-background">
    <div class="container mx-auto px-4 py-16">
      <div class="grid grid-cols-2 md:grid-cols-6 gap-8 mb-12">
        <!-- Brand -->
        <div class="col-span-2">
          <Link href="/" class="flex items-center gap-2 mb-4">
            <div class="w-10 h-10 rounded-xl gradient-hero flex items-center justify-center">
              <GraduationCapIcon class="w-6 h-6 text-primary-foreground" />
            </div>
            <span class="font-display font-bold text-xl">{{ name }}</span>
          </Link>
          <p class="text-background/60 text-sm mb-6 max-w-xs">
            The all-in-one platform for African educators to create, manage, and grow 
            thriving online schools.
          </p>
          <div class="flex items-center gap-3">
            <a
              v-for="social in socialLinks"
              :key="social.label"
              :href="social.href"
              :aria-label="social.label"
              class="w-10 h-10 rounded-lg bg-background/10 flex items-center justify-center hover:bg-primary hover:text-primary-foreground transition-colors duration-200"
            >
              <component :is="social.icon" class="w-5 h-5" />
            </a>
          </div>
        </div>

        <!-- Links -->
        <div v-for="section in footerSections" :key="section.title">
          <h4 class="font-semibold text-sm uppercase tracking-wider mb-4">
            {{ section.title }}
          </h4>
          <ul class="space-y-3">
            <li v-for="link in section.links" :key="link.label">
              <a
                :href="link.href"
                class="text-background/60 hover:text-background text-sm transition-colors duration-200"
              >
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom -->
      <div class="pt-8 border-t border-background/10 flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-background/40 text-sm">
          © {{ currentYear }} {{ name }}. All rights reserved.
        </p>
        <p class="text-background/40 text-sm">
          Made with ❤️ for African Educators
        </p>
      </div>
    </div>
  </footer>
</template>