<script setup>
import { Quote, Play, Star, Trophy } from 'lucide-vue-next'
import VideoModal from './VideoModal.vue';
import { ref } from 'vue';

const QuoteIcon = Quote

defineProps({
  name: {
    type: String,
    default: 'BetaEdge'
  },
  // videoTestimonials: {
  //   type: Array,
  //   default: () => []
  // },
  // successStories: {
  //   type: Array,
  //   default: () => []
  // },
  // trustMetrics: {
  //   type: Object,
  //   default: () => ({})
  // }
})


// const testimonials = [
//   {
//     name: "Tunde Adeyemi",
//     role: "Founder, Bright Stars Academy",
//     location: "Lagos, Nigeria",
//     image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop",
//     quote: "Teach helped me scale from 20 students to over 400 in just 8 months. The platform handles everything so I can focus on teaching.",
//   },
//   {
//     name: "Amara Okafor",
//     role: "CEO, Code Masters Academy",
//     location: "Abuja, Nigeria",
//     image: "https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=200&h=200&fit=crop",
//     quote: "The multi-tenant architecture means I can manage 3 different academies from one dashboard. Game changer for my education business.",
//   },
//   {
//     name: "Kwame Mensah",
//     role: "Director, Excel Learning Hub",
//     location: "Accra, Ghana",
//     image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop",
//     quote: "Finally, a platform built for African educators. Paystack integration works flawlessly, and my students love the mobile experience.",
//   },
// ]

const videoTestimonials = ref([
  {
    id: 1,
    name: "Tunde Adeyemi",
    role: "Founder, Bright Stars Academy",
    location: "Lagos, Nigeria",
    thumbnail: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=300&fit=crop&crop=face",
    videoUrl: "https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1",
    quote: "Teach helped me scale from 20 students to over 400 in just 8 months. The platform handles everything so I can focus on teaching.",
    stats: { students: 450, revenue: "₦2.5M/month" },
  },
  {
    id: 2,
    name: "Amara Okafor",
    role: "CEO, Code Masters Academy",
    location: "Abuja, Nigeria",
    thumbnail: "https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=400&h=300&fit=crop&crop=face",
    videoUrl: "https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1",
    quote: "The multi-tenant architecture means I can manage 3 different academies from one dashboard. Game changer for my education business.",
    stats: { students: 1200, revenue: "₦5.2M/month" },
  },
  {
    id: 3,
    name: "Kwame Mensah",
    role: "Director, Excel Learning Hub",
    location: "Accra, Ghana",
    thumbnail: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=300&fit=crop&crop=face",
    videoUrl: "https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1",
    quote: "Finally, a platform built for African educators. Paystack integration works flawlessly, and my students love the mobile experience.",
    stats: { students: 680, revenue: "GH₵45K/month" },
  },
])

const successStories = ref([
  {
    name: "Chiamaka Eze",
    age: 14,
    achievement: "Scored 95% in WAEC Mathematics",
    story: "Started struggling with fractions in Primary 5. After joining Bright Stars Academy on Teach, I went from failing to top of my class.",
    image: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=200&h=200&fit=crop&crop=face",
    school: "Bright Stars Academy",
  },
  {
    name: "Ibrahim Suleiman",
    age: 24,
    achievement: "Landed junior developer job at Paystack",
    story: "Completed the Full-Stack Web Development course at Tech Academy Nigeria. Within 3 months of graduating, I got my dream job.",
    image: "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=200&h=200&fit=crop&crop=face",
    school: "Tech Academy Nigeria",
  },
  {
    name: "Fatima Bello",
    age: 32,
    achievement: "Started her own tutoring business",
    story: "Learned digital marketing on Teach, now running a successful online tutoring business with 50+ students of my own.",
    image: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&crop=face",
    school: "Business Mastery Academy",
  },
  {
    name: "David Okonkwo",
    age: 17,
    achievement: "Won National Science Competition",
    story: "The interactive physics lessons and live classes helped me understand concepts I struggled with for years. Now I want to study engineering.",
    image: "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=200&h=200&fit=crop&crop=face",
    school: "STEM Excellence Hub",
  },
])

const activeVideo = ref(null)
const isHovered  = ref(false)

const openVideo = (testimonial) => {
  activeVideo.value = testimonial
  console.log(activeVideo);
}

const closeVideo = () => {
  activeVideo.value = null
}

// run backend inertia login here

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
          <span class="text-primary">African Educators</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Hear from school owners who transformed their education journey with {{ name }}.
        </p>
      </div>

      <!-- Testimonials Grid -->
      <!-- <div class="grid md:grid-cols-3 gap-6 mb-16">
        <div v-for="testimonial in testimonials" :key="testimonial.name"
          class="p-6 rounded-2xl bg-card border border-border hover:shadow-lg hover:border-primary/30 transition-all duration-300">
          <div class="flex items-start gap-3 mb-4">
            <QuoteIcon class="w-8 h-8 text-primary/30 flex-shrink-0" />
            <p class="text-muted-foreground text-sm leading-relaxed italic">
              "{{ testimonial.quote }}"
            </p>
          </div>

          <div class="flex items-center gap-3 pt-4 border-t border-border">
            <img :src="testimonial.image" :alt="testimonial.name" class="w-10 h-10 rounded-full object-cover" />
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
      </div> -->

      <!-- videos testimonials Grid -->
      <div class="relative overflow-hidden mb-20">
        <div
          class="flex gap-6"
          :class="{ 'animate-scroll': !isHovered}"
          @mouseenter="isHovered = true"
          @mouseleave="isHovered = false"
        >

          <!-- first set of testimonials -->
          <div
            v-for="testimonial in [...videoTestimonials, ...videoTestimonials]"
            :key="`${testimonial.id}-${Math.random()}`"
            class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-primary/30 transition-all duration-300 flex-shrink-0 w-[350px]"
          >
            <!-- video thumbnail -->
            <div 
              class="relative h-48 overflow-hidden cursor-pointer "
              @click="openVideo(testimonial)"
            >
              <img 
                :src="testimonial.thumbnail"
                :alt="testimonial.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
              />

              <div class="absolute inset-0 bg-foreground/40 flex items-center justify-center group-hover:bg-foreground/50 transition-colors">
                <div class="w-16 h-16 rounded-full gradient-hero flex items-center justify-center shadow-glow transform group-hover:scale-110 transition-transform">
                  <Play class="w-7 h-7 text-primary-foreground ml-1" :fill="'currentColor'" />
                </div>
              </div>

              <!-- stats badge -->
              <div class="absolute bottom-3 left-3 right-3 flex gap-2">
                <span class="px-2 py-1 rounded-full bg-secondary/90 text-secondary-foreground text-xs font-medium">
                  {{ testimonial.stats.students }} students
                </span>
                <span class="px-2 py-1 rounded-full bg-primary/90 text-primary-foreground text-xs font-medium">
                  {{ testimonial.stats.revenue }}
                </span>
              </div>
            </div>

            <!-- content -->
            <div class="p-6">
              <!-- qoute section -->
              <div class="flex items-start gap-3 mb-4">
                <QuoteIcon class="w-8 h-8 text-primary/30 flex-shrink-0" />
                <p class="text-muted-foreground text-sm leading-relaxed italic">
                  {{ testimonial.quote }}
                </p>
              </div>

              <div class="flex items-center gap-3 pt-4 border-t border-border">
                <img 
                  :src="testimonial.thumbnail" 
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
          
        </div>
      </div>

      <!-- Student Success Stories -->
      <div class="relative">
        <div class="text-center mb-12">
          <h3 class="font-display text-2xl md:text-3xl font-bold text-foreground mb-4">
            Student Success Stories
          </h3>
          <p class="text-muted-foreground">
            Real results from students learning on Teach-powered schools
          </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="(story, index) in successStories"
            :key="story.name"
            class="group p-6 rounded-2xl bg-gradient-to-br from-card to-muted/30 border border-border hover:shadow-lg hover:border-secondary/30 transition-all duration-300"
            :style="{ animationDelay: `${index * 100}ms` }"
          >
            <!-- Avatar & Rating -->
            <div class="flex items-center gap-4 mb-4">
              <div class="relative">
                <img
                  :src="story.image"
                  :alt="story.name"
                  class="w-14 h-14 rounded-full object-cover border-2 border-secondary/30"
                />
                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                  <Star class="w-3 h-3 text-secondary-foreground" :fill="'currentColor'" />
                </div>
              </div>
              <div>
                <h4 class="font-semibold text-foreground">
                  {{ story.name }}
                </h4>
                <p class="text-xs text-muted-foreground">
                  Age {{ story.age }} • {{ story.school }}
                </p>
              </div>
            </div>

            <!-- Achievement Badge -->
            <div class="mb-4">
              <span class="inline-block px-3 py-1.5 rounded-lg bg-accent text-accent-foreground text-xs font-semibold">
                <div class="flex items-center">
                  <Trophy class="w-4 h-4 mr-1"/> {{ story.achievement }}
                </div>

              </span>
            </div>

            <!-- Story -->
            <p class="text-sm text-muted-foreground leading-relaxed">
              "{{ story.story }}"
            </p>
          </div>
        </div>
      </div>

      <!-- Trust Indicators -->
      <div class="mt-16 pt-12 border-t border-border">
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

      <!-- video modal -->
      <VideoModal
      :is-open="!!activeVideo"
      :video-url="activeVideo?.videoUrl || ''"
      :title="activeVideo?.name || ''"
      @close="closeVideo"
      />

    </div>
  </section>
</template>

<style scoped>
@keyframes scroll {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.animate-scroll {
  animation: scroll 10s linear infinite;
}

.animate-scroll:hover {
  animation-play-state: paused;
}
</style>