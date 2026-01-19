<script setup>
import { Quote } from 'lucide-vue-next'

const QuoteIcon = Quote

defineProps({
  name: {
    type: String,
    default: 'BetaEdge'
  }
})

const testimonials = [
  {
    name: "Tunde Adeyemi",
    role: "Founder, Bright Stars Academy",
    location: "Lagos, Nigeria",
    image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop",
    quote: "Teach helped me scale from 20 students to over 400 in just 8 months. The platform handles everything so I can focus on teaching.",
  },
  {
    name: "Amara Okafor",
    role: "CEO, Code Masters Academy",
    location: "Abuja, Nigeria",
    image: "https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=200&h=200&fit=crop",
    quote: "The multi-tenant architecture means I can manage 3 different academies from one dashboard. Game changer for my education business.",
  },
  {
    name: "Kwame Mensah",
    role: "Director, Excel Learning Hub",
    location: "Accra, Ghana",
    image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop",
    quote: "Finally, a platform built for African educators. Paystack integration works flawlessly, and my students love the mobile experience.",
  },
]
</script>

<template>
  <section class="py-20 md:py-32 bg-background">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-primary font-semibold text-sm uppercase tracking-wider mb-4 block">
          Success Stories
        </span>
        <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-6">
          Trusted by
          <span class="text-gradient-primary">African Educators</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Hear from school owners who transformed their education journey with {{ name }}.
        </p>
      </div>

      <!-- Testimonials Grid -->
      <div class="grid md:grid-cols-3 gap-6 mb-16">
        <div
          v-for="testimonial in testimonials"
          :key="testimonial.name"
          class="p-6 rounded-2xl bg-card border border-border hover:shadow-lg hover:border-primary/30 transition-all duration-300"
        >
          <div class="flex items-start gap-3 mb-4">
            <QuoteIcon class="w-8 h-8 text-primary/30 flex-shrink-0" />
            <p class="text-muted-foreground text-sm leading-relaxed italic">
              "{{ testimonial.quote }}"
            </p>
          </div>
          
          <div class="flex items-center gap-3 pt-4 border-t border-border">
            <img
              :src="testimonial.image"
              :alt="testimonial.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div>
              <h4 class="font-semibold text-foreground text-sm">
                {{ testimonial.name }}
              </h4>
              <p class="text-xs text-muted-foreground">
                {{ testimonial.role }} • {{ testimonial.location }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Trust Indicators -->
      <div class="pt-12 border-t border-border">
        <div class="flex flex-wrap items-center justify-center gap-8 text-muted-foreground">
          <div class="text-center">
            <div class="font-display text-3xl font-bold text-foreground">4.9/5</div>
            <div class="text-sm">Average Rating</div>
          </div>
          <div class="w-px h-12 bg-border hidden sm:block" />
          <div class="text-center">
            <div class="font-display text-3xl font-bold text-foreground">50,000+</div>
            <div class="text-sm">Course Completions</div>
          </div>
          <div class="w-px h-12 bg-border hidden sm:block" />
          <div class="text-center">
            <div class="font-display text-3xl font-bold text-foreground">98%</div>
            <div class="text-sm">Satisfaction Rate</div>
          </div>
          <div class="w-px h-12 bg-border hidden sm:block" />
          <div class="text-center">
            <div class="font-display text-3xl font-bold text-foreground">₦2.5B+</div>
            <div class="text-sm">Paid to Educators</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>