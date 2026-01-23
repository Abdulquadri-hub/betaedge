<script setup>
import { 
  GraduationCap, 
  Users, 
  CreditCard, 
  Globe, 
  Video, 
  BarChart,
  Shield,
  Smartphone
} from 'lucide-vue-next'

const features = [
  {
    icon: GraduationCap,
    title: "Course Management",
    description: "Create and sell courses with lessons, quizzes, assignments, and certificates. Support for video, documents, and live classes.",
    color: "primary",
  },
  {
    icon: Users,
    title: "Multi-Tenant Architecture",
    description: "Each school operates independently with its own branding, students, and instructors. Complete data isolation guaranteed.",
    color: "secondary",
  },
  {
    icon: CreditCard,
    title: "African Payments",
    description: "Accept payments via Paystack, bank transfer, and mobile money. Support for Naira, Cedis, and more African currencies.",
    color: "primary",
  },
  {
    icon: Globe,
    title: "Custom Domains",
    description: "Start with a free subdomain, upgrade to your own custom domain. Build your brand, your way.",
    color: "secondary",
  },
  {
    icon: Video,
    title: "Live Classes",
    description: "Built-in Google Meet integration for live sessions. Automatic attendance tracking and recording.",
    color: "primary",
  },
  {
    icon: BarChart,
    title: "Analytics Dashboard",
    description: "Track enrollments, revenue, student progress, and course performance. Make data-driven decisions.",
    color: "secondary",
  },
  {
    icon: Shield,
    title: "Parent Portal",
    description: "Built-in parent accounts for minors. Parents can track progress, grades, and make payments on behalf of children.",
    color: "primary",
  },
  {
    icon: Smartphone,
    title: "Mobile First",
    description: "Fully responsive design. Students can learn on any device, anywhere, anytime.",
    color: "secondary",
  },
]
</script>

<template>
  <section id="features" class="py-20 md:py-32 bg-background">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-primary font-semibold text-sm uppercase tracking-wider mb-4 block">
          Everything You Need
        </span>
        <h2 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-6">
          Powerful Features for
          <span class="text-primary">Modern Educators</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          From course creation to payment processing, we've built everything you need 
          to run a successful online school in Africa.
        </p>
      </div>

      <!-- Features Grid -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="(feature) in features"
          :key="feature.title"
          class="group p-6 rounded-2xl bg-card border border-border hover:shadow-lg hover:border-primary/30 transition-all duration-300"
        >
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110', feature.color === 'primary' ? 'bg-primary/10 text-primary' : 'bg-secondary/10 text-secondary']">
            <component :is="feature.icon" class="w-6 h-6" />
          </div>
          <h3 class="font-display font-bold text-lg text-foreground mb-2">
            {{ feature.title }}
          </h3>
          <p class="text-muted-foreground text-sm leading-relaxed">
            {{ feature.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
