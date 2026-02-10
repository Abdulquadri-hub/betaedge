<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowLeft, Clock, Users, Star, CheckCircle, Calendar,
  Video, MessageCircle, Download
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import BatchCard from '@/components/Batch/BatchCard.vue'
import { getCourseById } from '@/data/mockCourses'
import { useBatches } from '@/composables/useBatches'
import { route } from 'ziggy-js'




const props = defineProps({
  courseId: {
    type: String,
    required: true,
    validator: (value) => /^[a-zA-Z0-9_-]+$/.test(value)
  }
})


// Course data
const course = ref(null)
const { batches, loading: batchesLoading, fetchBatches, availableBatches, nextAvailableBatch } = useBatches()

// Mock modules
const modules = ref([
  {
    id: '1',
    title: 'Introduction to Calculus',
    lessons: [
      { id: '1', title: 'What is Calculus?', duration: '15 min', type: 'video' },
      { id: '2', title: 'Limits and Continuity', duration: '25 min', type: 'video' },
      { id: '3', title: 'Practice Problems', duration: '30 min', type: 'document' }
    ]
  },
  {
    id: '2',
    title: 'Differentiation',
    lessons: [
      { id: '4', title: 'Basic Derivatives', duration: '20 min', type: 'video' },
      { id: '5', title: 'Chain Rule', duration: '25 min', type: 'video' },
      { id: '6', title: 'Applications', duration: '30 min', type: 'video' },
      { id: '7', title: 'Quiz', duration: '15 min', type: 'quiz' }
    ]
  }
])

const school = ref({
  slug: 'brightstars',
  name: 'Bright Stars Academy'
})

const totalLessons = computed(() => {
  return modules.value.reduce((acc, mod) => acc + mod.lessons.length, 0)
})

const hasAvailableBatches = computed(() => {
  return availableBatches.value.length > 0
})

const enrollNow = (batchId = null) => {
  const query = { course: props.courseId}
 
  if (batchId && /^[a-zA-Z0-9_-]+$/.test(batchId)) {
    query.batch = batchId
  } else if (nextAvailableBatch.value) {
    query.batch = nextAvailableBatch.value.id
  }

  query.tenant = 'elevate'
  router.get(route('tenant.enroll', query))
}

const viewAllBatches = () => {
  const element = document.getElementById('batches-section')
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}

// Load course data
onMounted(async () => {
  // Fetch course
  course.value = getCourseById(props.courseId)
  
  if (!course.value) {
    console.error('Course not found')
    router.get(route('tenant.landing', {tenant: 'elevate'}))
    return
  }
  
  // Fetch batches
  await fetchBatches(props.courseId)
})

</script>

<template>
  <div v-if="course" class="min-h-screen bg-background">
    <!-- Header -->
    <div class="bg-muted/50 border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <Link :href="route('tenant.landing', {tenant: 'elevate'})">
          <button
            
            class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
          >
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back to {{ school.name }}
          </button>
      </Link>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- main content -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Course Image -->
          <div class="rounded-xl overflow-hidden shadow-lg">
            <img 
               :src="course.image"
               :alt="course.title"
               class="w-full h-64 md:h-80 object-cover"
            />
          </div>

          <!-- Course Info -->
          <div>
            <div class="flex flex-wrap items-center gap-3 mb-4">
              <Badge variant="outline" class="text-sm">{{ course.level }}</Badge>
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Star class="h-4 w-4 text-yellow-500 fill-yellow-500"/>
                {{ course.rating }} ({{ course.reviewCount }}
                Reviews)
              </span>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ course.title }}</h1>
            <p class="text-lg text-muted-foreground">{{ course.description }}</p>
          </div>

          <!-- CourseInfo -->
          <div class="flex flex-wrap gap-6">
            <div class="flex items-center gap-2">
              <Clock class="w-5 h-5 text-muted-foreground"/>
              <span class="font-medium">{{ course.duration }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Video class="w-5 h-5 text-muted-foreground"/>
              <span class="font-medium">
                {{ totalLessons }} live sessions
              </span>
            </div>
            <div class="flex items-center gap-2">
              <Users class="w-5 h-5 text-muted-foreground"/>
              <span class="font-medium">
                {{ course.enrolledCount }} enrolled
              </span>
            </div>
          </div>

          <Separator />

          <!-- Instructor -->
          <Card>
           <CardHeader>
             <CardTitle class="text-lg">Your Instructor</CardTitle>
           </CardHeader>
           <CardContent>
             <div class="flex items-start gap-4">
               <Avatar class="h-16 w-16">
                 <AvatarImage :src="course.instructor.avatar"/>
                 <AvatarFallback class="text-lg bg-primary text-primary-foreground">
                   {{ course.instructor.name.charAt(0) }}
                 </AvatarFallback>
               </Avatar>
               <div>
                 <h3 class="font-semi-bold text-lg">
                   {{ course.instructor.name }}
                 </h3>
                 <p class="text-sm text-muted-foreground">{{ course.instructor.title }}</p>
                 <p class="text-sm mt-2">{{ course.instructor.bio }}</p>
               </div>
             </div>
           </CardContent>
          </Card>

          <!-- Curicullum -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Curriculum</CardTitle>
            </CardHeader>
            <CardContent>
              <Accordion type="multiple" class="w-full">
                <AccordionItem
                    v-for="(module, index) in modules"
                    :key="module.id"
                    :value="module.id"
                >
                  <AccordionTrigger class="hover:no-underline cursor-pointer">
                    <div class="flex items-center gap-3 text-left">
                      <span class="flex items-center justify-center h-8 w-8 bg-primary/10 rounded-full text-primary text-sm font-semibold flex-shrink-0">
                        {{ index + 1 }}
                      </span>
                      <div>
                        <div class="font-medium">
                          {{ module.title }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                          {{ module.lessons.length }} lessons
                        </div>
                      </div>
                    </div>
                  </AccordionTrigger>
                  <AccordionContent>
                    <div class="space-y-2 pl-11 mt-2">
                      <div
                        v-for="lesson in module.lessons"
                        :key="lesson.id"
                        class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-muted/50 transition-colors"
                      >
                        <div class="flex items-center gap-3">
                          <Video v-if="lesson.type === 'video'" class="h-4 w-4 text-muted-foreground" />
                          <Download v-else-if="lesson.type === 'document'" class="h-4 w-4 text-muted-foreground" />
                          <CheckCircle v-else class="h-4 w-4 text-muted-foreground" />
                          <span class="text-sm">{{ lesson.title }}</span>
                        </div>
                        <span class="text-sm text-muted-foreground">{{ lesson.duration }}</span>
                      </div>
                    </div>
                  </AccordionContent>
                </AccordionItem>
              </Accordion>
            </CardContent>
          </Card>
          
          <!-- Requirements -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Requirements</CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-3">
                <li v-for="(req, index) in course.requirements" :key="index" class="flex items-start gap-3">
                  <CheckCircle class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
                  <span>{{ req }}</span>
                </li>
              </ul>
            </CardContent>
          </Card>

          <div id="batches-section">
            <Card>
              <CardHeader>
                <CardTitle class="text-lg">Upcoming Batches</CardTitle>
                <p class="text-sm text-muted-foreground mt-1">
                  Select a batch to join this course
                </p>
              </CardHeader>
              <CardContent>
                <div class="py-12 text-center" v-if="batchesLoading">
                  <div class="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-3"></div>
                  <p>Loading batches...</p>
                </div>

                <Alert v-else-if="!hasAvailableBatches" variant="destructive">
                  <AlertDescription>
                    No batches are currently available for enrollment. Please check back later or contact the school.
                  </AlertDescription>
                </Alert>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-else>
                  <BatchCard
                    v-for="batch in batches"
                    :key="batch.id"
                    :batch="batch"
                    :selectable="false"
                  />
                </div>

              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="sticky top-8 space-y-6">
            <!-- Enrollment Card -->
            <Card class="overflow-hidden border-2 border-primary/20">
              <CardContent class="pt-6">
                <div class="text-center mb-6">
                  <div class="text-sm text-muted-foreground mb-2">
                    Course Price
                  </div>
                  <div class="text-4xl font-bold">
                    ₦{{ course.price.toLocaleString() }}
                  </div>
                </div>

                <div class="mb-6 p-4 rounded-lg bg-primary/5 border border-primary/20" v-if="nextAvailableBatch">
                  <div class="text-sm font-semibold text-primary mb-2">
                    Next Available Batch
                  </div>
                  <div class="text-sm text-muted-foreground">
                    {{ nextAvailableBatch.name }}
                  </div>
                  <div class="flex items-center gap-2 mt-2 text-sm">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                      <span>
                        Starts {{ new Date(nextAvailableBatch.start_date).toLocaleDateString('en-NG', { month: 'short', day: 'numeric', year: 'numeric'}) }}
                      </span>
                  </div>
                </div>

                <Button 
                  class="w-full cursor-pointer mb-3" 
                  size="lg" 
                  @click="enrollNow()"
                  :disabled="!hasAvailableBatches"
                >
                  {{ hasAvailableBatches ? 'Enroll Now' : 'No Batches Available' }}
                </Button>

                <Button 
                  variant="outline" 
                  class="w-full cursor-pointer" 
                  size="lg"
                  @click="viewAllBatches"
                >
                  View All Batches
                </Button>

                <p class="text-center text-sm text-muted-foreground mt-4">
                  30-day money-back guarantee
                </p>
              </CardContent>
            </Card>

            <!-- Live Classes Info -->
            <Card class="border-primary/20 bg-primary/5">
              <CardHeader class="pb-3">
                <CardTitle class="text-lg flex items-center gap-2">
                  <Video class="h-5 w-5 text-primary" />
                  Live Classes Include
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="flex items-center gap-2.5 text-sm">
                  <Calendar class="h-4 w-4 text-muted-foreground" />
                  <span>Weekly live sessions</span>
                </div>
                <div class="flex items-center gap-2.5 text-sm">
                  <MessageCircle class="h-4 w-4 text-muted-foreground" />
                  <span>WhatsApp study group</span>
                </div>
                <div class="flex items-center gap-2.5 text-sm">
                  <Users class="h-4 w-4 text-muted-foreground" />
                  <span>Real-time Q&A with instructor</span>
                </div>
                <div class="flex items-center gap-2.5 text-sm">
                  <CheckCircle class="h-4 w-4 text-muted-foreground" />
                  <span>Certificate upon completion</span>
                </div>
              </CardContent>
            </Card>

            <!-- What's Included -->
            <Card>
              <CardHeader>
                <CardTitle class="text-lg">What's Included</CardTitle>
              </CardHeader>
              <CardContent>
                <ul class="space-y-3">
                  <li v-for="(feature, index) in course.features" :key="index" class="flex items-start gap-2">
                    <CheckCircle class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" />
                    <span class="text-sm">{{ feature }}</span>
                  </li>
                </ul>
              </CardContent>
            </Card>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>