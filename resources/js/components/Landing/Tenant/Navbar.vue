<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js'
import { 
  School, 
  Menu,
  X, 
} from 'lucide-vue-next'

const SchoolIcon = School
const MenuIcon = Menu
const XIcon = X


defineProps({
    tenant: {
        type: Object,
        default: () => ({})
    }
})

const open = ref(false)
</script>

<template>
    <nav class="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-lg border-b border-border">
      <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16 md:h-20">
          <!-- Logo -->
          <div class="flex items-center gap-3">
            <img 
              v-if="tenant.logo" 
              :src="tenant.logo" 
              :alt="tenant.name"
              class="w-10 h-10 rounded-lg object-cover"
            />
            <div v-else class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: tenant.primary_color }">
              <SchoolIcon class="w-6 h-6 text-white" />
            </div>
            <span class="font-display font-bold text-xl text-foreground">
              {{ tenant.name }}
            </span>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center gap-8">
            <a href="#about" class="text-muted-foreground hover:text-foreground font-medium transition-colors">
              About
            </a>
            <a href="#courses" class="text-muted-foreground hover:text-foreground font-medium transition-colors">
              Courses
            </a>
            <a href="#contact" class="text-muted-foreground hover:text-foreground font-medium transition-colors">
              Contact
            </a>
          </div>

          <!-- Desktop CTA -->
          <div class="hidden md:flex items-center gap-3">
            <Link 
              :href="route('tenant.register.student', { tenant: tenant.slug || tenant.id })"
              class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors"
            >
              Enroll as Student
            </Link>
            <Link 
              :href="route('tenant.register.parent', { tenant: tenant.slug || tenant.id })"
              class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium text-white transition-colors"
              :style="{ backgroundColor: tenant.primary_color }"
            >
              Register as Parent
            </Link>
          </div>

          <!-- Mobile Menu Button -->
          <button
            @click="open = !open"
            class="md:hidden p-2 text-foreground"
          >
            <MenuIcon v-if="!open" class="w-6 h-6" />
            <XIcon v-else class="w-6 h-6" />
          </button>
        </div>

        <!-- Mobile Menu -->
        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div v-if="open" class="md:hidden py-4 border-t border-border">
            <div class="flex flex-col gap-4">
              <a href="#about" @click="open = false" class="text-muted-foreground hover:text-foreground font-medium py-2">
                About
              </a>
              <a href="#courses" @click="open = false" class="text-muted-foreground hover:text-foreground font-medium py-2">
                Courses
              </a>
              <a href="#contact" @click="open = false" class="text-muted-foreground hover:text-foreground font-medium py-2">
                Contact
              </a>
              <div class="flex flex-col gap-2 pt-4 border-t border-border">
                <Link 
                  :href="route('tenant.register.student', { tenant: tenant.slug || tenant.id })"
                  class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium border border-input bg-background hover:bg-accent"
                >
                  Enroll as Student
                </Link>
                <Link 
                  :href="route('tenant.register.parent', { tenant: tenant.slug || tenant.id })"
                  class="inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium text-white"
                  :style="{ backgroundColor: tenant.primary_color }"
                >
                  Register as Parent
                </Link>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </nav>
</template>