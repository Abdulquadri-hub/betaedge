<script setup>
import { computed } from 'vue';
import { 
  Dialog, 
  DialogContent, 
  DialogClose,
  DialogTitle,
  DialogDescription
} from '@/components/ui/dialog';
import { VisuallyHidden } from 'reka-ui';
import { Button } from '@/components/ui/button';
import { 
  X, 
  Star, 
  Users, 
  MapPin, 
  BookOpen, 
  Clock, 
  Award, 
  CheckCircle 
} from 'lucide-vue-next';


const props = defineProps({
  isOpen: Boolean,
  school: Object
});

// const emit = defineEmits(['close'])

// // const handleClose = (value) => {
// //     if (!value) {
// //         emit('close')
// //     }
// // }

const defaultFeatures = [
    "Live interactive classes",
    "Downloadable course materials",
    "Certificate upon completion",
    "24/7 student support",
    "Mobile learning app",
    "Progress tracking dashboard"
]

const defaultDescription = computed(() => {
  if (!props.school) return '';
  return `${props.school.name} is a leading educational institution offering high-quality ${props.school.category.toLowerCase()} programs. With a strong focus on practical skills and student success, we've helped thousands of students achieve their learning goals.`;
});

</script>

<template>
  <Dialog v-if="school" :open="isOpen" @update:open="(val) => !val && $emit('close')">
    <DialogContent class="max-w-2xl p-0 bg-card border-border overflow-hidden max-h-[90vh] overflow-y-auto">
      <VisuallyHidden>
        <DialogTitle>Featured School</DialogTitle>
          <DialogDescription>
            Learn more about this school’s programs, ratings, and enrollment details.
          </DialogDescription>
      </VisuallyHidden>
      <DialogClose class="absolute right-4 top-4 z-10 rounded-full bg-background/80 p-2 hover:bg-background transition-colors">
        <X class="h-5 w-5 text-foreground" />
        <span class="sr-only">Close</span>
      </DialogClose>

      <!-- Hero Image -->
      <div class="relative h-56">
        <img
          :src="school.image"
          :alt="school.name"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-card via-transparent to-transparent" />
        <div class="absolute bottom-4 left-6 right-6">
          <span class="inline-block px-3 py-1 rounded-full bg-secondary text-secondary-foreground text-sm font-medium mb-2">
            {{ school.category }}
          </span>
          <h2 class="font-display text-2xl md:text-3xl font-bold text-foreground">
            {{ school.name }}
          </h2>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-primary mb-1">
              <Star class="w-4 h-4 fill-primary" />
              <span class="font-bold text-lg">{{ school.rating }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Rating</span>
          </div>
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-secondary mb-1">
              <Users class="w-4 h-4" />
              <span class="font-bold text-lg">{{ school.students.toLocaleString() }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Students</span>
          </div>
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-accent-foreground mb-1">
              <BookOpen class="w-4 h-4" />
              <span class="font-bold text-lg">{{ school.courses }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Courses</span>
          </div>
          <div class="p-3 rounded-xl bg-muted/50 text-center">
            <div class="flex items-center justify-center gap-1 text-foreground mb-1">
              <Award class="w-4 h-4" />
              <span class="font-bold text-lg">{{ school.instructors || 12 }}</span>
            </div>
            <span class="text-xs text-muted-foreground">Instructors</span>
          </div>
        </div>

        <!-- Location -->
        <div class="flex items-center gap-2 text-muted-foreground">
          <MapPin class="w-5 h-5 text-primary" />
          <span>{{ school.location }}</span>
          <template v-if="school.established">
            <span class="mx-2">•</span>
            <Clock class="w-4 h-4" />
            <span>Est. {{ school.established }}</span>
          </template>
        </div>

        <!-- Description -->
        <div>
          <h3 class="font-semibold text-foreground mb-2">About</h3>
          <p class="text-muted-foreground leading-relaxed">
            {{ school.description || defaultDescription }}
          </p>
        </div>

        <!-- Features -->
        <div>
          <h3 class="font-semibold text-foreground mb-3">What You'll Get</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div
              v-for="(feature, index) in (school.features || defaultFeatures)"
              :key="index"
              class="flex items-center gap-2 text-sm text-muted-foreground"
            >
              <CheckCircle class="w-4 h-4 text-secondary flex-shrink-0" />
              <span>{{ feature }}</span>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <div class="pt-4 border-t border-border flex flex-col sm:flex-row gap-3">
          <Button size="lg" class="flex-1 gradient-hero text-primary-foreground font-semibold">
            Enroll Now
          </Button>
          <Button variant="outline" size="lg" class="flex-1">
            View Courses
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style lang="css" scoped></style>