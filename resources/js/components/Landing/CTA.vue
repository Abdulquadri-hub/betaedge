<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Sparkles, Check } from 'lucide-vue-next'

const ArrowRightIcon = ArrowRight
const SparklesIcon = Sparkles
</script>

<template>
  <section class="py-20 md:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-orange-100" />
    <div class="absolute top-0 left-1/4 w-64 h-64 bg-primary/10 rounded-full blur-3xl" />
    <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-primary/10 rounded-full blur-3xl" />

    <div class="container mx-auto px-4 relative z-10">
      <div class="max-w-3xl mx-auto text-center">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 border border-primary/30 text-primary text-sm font-medium mb-8">
          <SparklesIcon class="w-4 h-4" />
          Join 10,000+ educators across Africa
        </div>

        <!-- Headline -->
        <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
          Ready to Transform Your Teaching Business?
        </h2>

        <!-- Subheadline -->
        <p class="text-lg md:text-xl text-gray-700 mb-10 max-w-2xl mx-auto">
          Start your free school today. No credit card required. 
          Set up in minutes, grow to thousands of students.
        </p>

        <!-- CTAs -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <Link href="/onboarding">
            <button class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-md text-base font-medium h-12 px-6 bg-primary text-white hover:bg-primary/90 shadow-lg">
              Start Your Free School
              <ArrowRightIcon class="w-5 h-5" />
            </button>
          </Link>
          <button class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-md text-base font-medium h-12 px-6 border-2 border-primary text-primary hover:bg-primary/10">
            Schedule a Demo
          </button>
        </div>

        <!-- Trust indicators -->
        <p class="mt-8 text-sm text-gray-600">
          ✓ Free forever plan &nbsp;&nbsp; ✓ No setup fees &nbsp;&nbsp; ✓ Cancel anytime
        </p>
      </div>
    </div>
  </section>
</template>