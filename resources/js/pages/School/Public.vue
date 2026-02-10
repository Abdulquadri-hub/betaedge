<script setup>

import {ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { MapPin, Users, BookOpen, Star, Clock, GraduationCap, Phone, Mail, Globe, Calendar } from 'lucide-vue-next';
import { Separator } from '@/components/ui/separator';
import { mockCourses } from '@/data/mockCourses';
import {getBatchesByCourse } from '@/data/mockBatches';
import { formatDate, getSpotsRemaining } from '@/utils/batchHelpers';
import { route } from 'ziggy-js';


// props
defineProps({
  tenant: {
    type: Object,
    required: true
  },
  pageContent: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({
      students: 0,
      courses: 0,
      instructors: 0
    })
  },
  featuredCourses: {
    type: Array,
    default: () => []
  },
  contactInfo: {
    type: Object,
    default: () => ({})
  }
})

// reactive variables

const school = ref({
  id: '1',
  name: 'Bright Stars Academy',
  slug: 'elevate',
  logo: 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=200',
  coverImage: 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=1200',
  description: 'A premier learning institution dedicated to nurturing young minds with innovative teaching methods and a supportive learning environment.',
  location: 'Lagos, Nigeria',
  phone: '+234 801 234 5678',
  email: 'info@brightstars.edu',
  website: 'brightstars.betaedge.test',
  studentCount: 450,
  courseCount: 24,
  rating: 4.8,
  reviewCount: 156,
  established: 2015,
  categories: ['Mathematics', 'Sciences', 'Languages', 'Arts']
})
const instructors = ref([
  {
    id: '1',
    name: 'Dr. Adaora Nwosu',
    avatar: '',
    title: 'Mathematics Department Head',
    bio: '15+ years of experience teaching mathematics at various levels',
    coursesCount: 5
  },
  {
    id: '2',
    name: 'Mrs. Folake Adekunle',
    avatar: '',
    title: 'Senior English Instructor',
    bio: 'Certified TEFL instructor with a passion for literature',
    coursesCount: 3
  },
  {
    id: '3',
    name: 'Prof. Chinedu Obi',
    avatar: '',
    title: 'Physics & Science Lead',
    bio: 'PhD in Physics with research focus on renewable energy',
    coursesCount: 4
  }
])
const courses = ref(mockCourses)
const activeTab = ref('courses')

// methods

// const getNextBatchInfo = (courseId) => {
//   const nextBatch = getNextAvailableBatch(courseId)

//   if (!nextBatch) {
//     return {
//       available: false,
//       message: 'No batches available',
//       startDate: null,
//       spotsLeft: 0
//     }
//   }

//   return {
//     available: true,
//     message: `Next: ${formatDate(nextBatch.start_date)}`,
//     startDate: nextBatch.start_date,
//     spotsLeft: getSpotsRemaining(nextBatch),
//     batchId: nextBatch.id
//   }
// }

const getCurrentBatchInfo =   (courseId) => {
    const batches = getBatchesByCourse(courseId)

    // Filter only published batches that can accept enrollment
    const publishedBatches = batches.filter(batch =>
      batch.status === 'open' && batch.current_enrollment < batch.max_students
    )

    if (publishedBatches.length === 0) {
      return {
        available: false,
        message: 'No batches available',
        batchName: null,
        enrolled: 0,
        maxStudents: 0,
        spotsLeft: 0,
        startDate: null,
        batchId: null
      }
    }

    // Get the earliest starting published batch
    const currentBatch = publishedBatches.sort((a, b) =>
      new Date(a.start_date) - new Date(b.start_date)
    )[0]

    return {
      available: true,
      batchName: currentBatch.name,
      message: `${currentBatch.name} - Starts ${formatDate(currentBatch.start_date)}`,
      enrolled: currentBatch.current_enrollment,
      maxStudents: currentBatch.max_students,
      spotsLeft: getSpotsRemaining(currentBatch),
      startDate: currentBatch.start_date,
      batchId: currentBatch.id
    }
  }

// const viewCourse = (courseId) => {
//   if (!/^[a-zA-Z0-9_-]+$/.test(courseId)) {
//     console.warn('Invalid course ID format')
//     return
//   }

//   router.push(`/course/${courseId}`)
// }

// const enrollInCourse = (courseId, batchId = null) => {

//   if (!/^[a-zA-Z0-9_-]+$/.test(courseId)) {
//     console.warn('Invalid course ID')
//     return
//   }

//   const query = { course: courseId }
//   if (batchId && /^[a-zA-Z0-9_-]+$/.test(batchId)) {
//     query.batch = batchId
//   }

//   router.push({
//     path: `/enroll`,
//     query
//   })
// }

</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Cover Image -->
    <div class="relative h-64 md:h-80 overflow-hidden">
      <img :src="school.coverImage" :alt="school.name" class="w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent" />
    </div>

    <!-- School Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="relative -mt-20 mb-8">
        <div class="flex flex-col md:flex-row gap-6 items-start">
          <Avatar class="h-32 w-32 border-4 border-background shadow-xl">
            <AvatarImage :src="school.logo" :alt="school.name" />
            <AvatarFallback class="text-3xl bg-primary text-primary-foreground">
              {{ school.name.charAt(0) }}
            </AvatarFallback>
          </Avatar>

          <div class="flex-1 pt-4">
            <h1 class="text-3xl font-bold lg:text-primary-foreground md:text-primary-foreground">{{ school.name }}</h1>
            <div class="flex flex-wrap items-center gap-4 lg:text-primary-foreground md:text-primary-foreground">
              <span class="flex items-center gap-1.5">
                <MapPin class="h-4 w-4" />
                {{ school.location }}
              </span>
              <span class="flex items-center gap-1.5">
                <Users class="h-4 w-4" />
                {{ school.studentCount }} students
              </span>
              <span class="flex items-center gap-1.5">
                <BookOpen class="h-4 w-4" />
                {{ school.courseCount }} courses
              </span>
              <span class="flex items-center gap-1.5">
                <Star class="h-4 w-4 text-yellow-500 fill-yellow-500" />
                {{ school.rating }} ({{ school.reviewCount }} reviews)
              </span>
            </div>
            <p class="mt-4 text-muted-foreground max-w-2xl">
              {{ school.description }}
            </p>
          </div>

          <div class="flex flex-col gap-2">
            <Link 
              :href="route('tenant.enroll', { tenant: tenant.slug})"
            >
              <Button size="lg" class="cursor-pointer">
                Enroll Now
              </Button>
            </Link>
 
            <a href="">
              <Button variant="outline" size="lg" class="cursor-pointer">
                Contact School
              </Button>
            </a>

          </div>
        </div>
      </div>

      <!-- Contact Info -->
      <Card class="mb-8">
        <CardContent class="py-4">
          <div class="flex flex-wrap gap-6">
            <a :href="`tel:${school.phone}`"
              class="flex items-center gap-2 text-sm hover:text-primary transition-colors">
              <Phone class="h-4 w-4 text-muted-foreground" />
              {{ school.phone }}
            </a>
            <a :href="`mailto:${school.email}`"
              class="flex items-center gap-2 text-sm hover:text-primary transition-colors">
              <Mail class="h-4 w-4 text-muted-foreground" />
              {{ school.email }}
            </a>
            <a :href="`https://${school.website}`" target="_blank" rel="noopener noreferrer"
              class="flex items-center gap-2 text-sm hover:text-primary transition-colors">
              <Globe class="h-4 w-4 text-muted-foreground" />
              {{ school.website }}
            </a>
            <span class="flex items-center gap-2 text-sm">
              <Clock class="h-4 w-4 text-muted-foreground" />
              Established {{ school.established }}
            </span>
          </div>
        </CardContent>
      </Card>

      <!-- Categories -->
      <div class="flex flex-wrap gap-2 mb-8">
        <Badge v-for="category in school.categories" :key="category" class="bg-primary">
          {{ category }}
        </Badge>
      </div>

      <!-- Tabs -->
      <Tabs v-model="activeTab" class="mb-12">
        <TabsList class="grid w-full grid-cols-3 max-w-md">
          <TabsTrigger class="cursor-pointer" value="courses">Courses</TabsTrigger>
          <TabsTrigger class="cursor-pointer" value="instructors">Instructors</TabsTrigger>
          <TabsTrigger class="cursor-pointer" value="about">About</TabsTrigger>
        </TabsList>

        <!-- Courses Tab -->
        <TabsContent value="courses" class="mt-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card v-for="course in courses" :key="course.id"
              class="overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1 group">
              <div class="relative h-48 overflow-hidden">  
                <img :src="course.image" :alt="course.title"
                  class="h-full w-full object-cover transition-transform duration-300" />
                <!-- Urgency Badge -->
                <!-- <div v-if="getNextBatchInfo(course.id).available && getNextBatchInfo(course.id).spotsLeft <= 5"
                  class="absolute top-3 right-3">
                  <Badge variant="destructive" class="shadow-lg">
                    <AlertCircle class="h-3 w-3 mr-1" />
                    Only {{ getNextBatchInfo(course.id).spotsLeft }} spots left
                  </Badge>
                </div> -->
              </div>

              <CardHeader>
                <div class="flex items-center justify-between mb-2">
                  <Badge variant="outline">{{ course.level }}</Badge>
                  <span class="text-sm text-muted-foreground flex items-center gap-1">
                    <Clock class="h-3.5 w-3.5" />
                    {{ course.duration }}
                  </span>
                </div>
                <CardTitle class="text-lg group-hover:text-primary transition-colors">
                  {{ course.title }}
                </CardTitle>
              </CardHeader>

              <CardContent>
                <p class="text-sm text-muted-foreground mb-4 line-clamp-2">
                  {{ course.description }}
                </p>

                <Separator class="my-4" />

                <!-- Next Batch Info -->
                <div class="space-y-3">
                  <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-1.5 text-muted-foreground">
                      <Calendar class="h-4 w-4" />
                      Next available Batch
                    </span>
                    <span class="font-medium"
                      :class="getCurrentBatchInfo(course.id).available ? 'text-green-600' : 'text-destructive'">
                      {{ getCurrentBatchInfo(course.id).message }}
                    </span>
                  </div>

                   <!-- Enrollment Progress Bar -->
                  <div v-if="getCurrentBatchInfo(course.id).available" class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-muted-foreground">
                        {{ getCurrentBatchInfo(course.id).enrolled }} enrolled / {{ getCurrentBatchInfo(course.id).maxStudents }} spots
                      </span>
                      <span class="font-semibold" :class="getCurrentBatchInfo(course.id).spotsLeft <= 5 ? 'text-destructive' : 'text-muted-foreground'">
                        {{ getCurrentBatchInfo(course.id).spotsLeft }} left
                      </span>
                    </div>
                    <div class="w-full bg-muted rounded-full h-2">
                      <div 
                        class="h-2 rounded-full transition-all"
                        :class="getCurrentBatchInfo(course.id).spotsLeft <= 5 ? 'bg-destructive' : 'bg-primary'"
                        :style="{ width: `${(getCurrentBatchInfo(course.id).enrolled / getCurrentBatchInfo(course.id).maxStudents) * 100}%` }"
                      ></div>
                    </div>
                  </div>

                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <Avatar class="h-6 w-6">
                        <AvatarImage :src="course.instructor.avatar" />
                        <AvatarFallback class="text-xs">
                          {{ course.instructor.name.charAt(0) }}
                        </AvatarFallback>
                      </Avatar>
                      <span class="text-sm">{{ course.instructor.name }}</span>
                    </div>
                    <span class="font-bold text-lg">₦{{ course.price.toLocaleString() }}</span>
                  </div>
<!-- 
                  <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Users class="h-4 w-4" />
                    {{ course.enrolledCount }} students enrolled
                  </div> -->
                </div>

                <!-- <div class="flex items-center gap-3 justify-between"> -->
                  <!-- <Button class="mt-4 cursor-pointer w-full"
                    @click.stop="enrollInCourse(course.id, getCurrentBatchInfo(course.id).batchId)"
                    :disabled="!getCurrentBatchInfo(course.id).available">
                    {{ getCurrentBatchInfo(course.id).available ? 'Enroll Now' : 'No Batches Available' }}
                  </Button> -->
                  <Link 
                    :href="route('tenant.course', { tenant: tenant.slug, course: course.id })"
                  >
                    <Button
                      class="mt-4 cursor-pointer w-full hover:scale-105 transition-transform duration-300"
                    >
                      View Course
                    </Button>
                  </Link>
                  
                <!-- </div> -->


              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Instructors Tab -->
        <TabsContent value="instructors" class="mt-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card v-for="instructor in instructors" :key="instructor.id">
              <CardContent class="pt-6">
                <div class="flex flex-col items-center text-center">
                  <Avatar class="h-20 w-20 mb-4">
                    <AvatarImage :src="instructor.avatar" />
                    <AvatarFallback class="text-xl bg-primary text-primary-foreground">
                      {{ instructor.name.charAt(0) }}
                    </AvatarFallback>
                  </Avatar>
                  <h3 class="font-semibold text-lg">{{ instructor.name }}</h3>
                  <p class="text-sm text-muted-foreground">{{ instructor.title }}</p>
                  <p class="text-sm mt-2">{{ instructor.bio }}</p>
                  <Badge variant="secondary" class="mt-4">
                    <GraduationCap class="h-3 w-3 mr-1" />
                    {{ instructor.coursesCount }} courses
                  </Badge>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- About Tab -->
        <TabsContent value="about" class="mt-6">
          <Card>
            <CardContent class="py-6">
              <h3 class="text-xl font-semibold mb-4">About {{ school.name }}</h3>
              <p class="text-muted-foreground mb-8">{{ school.description }}</p>

              <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                  <div class="text-4xl font-bold text-primary">{{ school.studentCount }}</div>
                  <div class="text-sm text-muted-foreground mt-1">Students</div>
                </div>
                <div class="text-center">
                  <div class="text-4xl font-bold text-primary">{{ school.courseCount }}</div>
                  <div class="text-sm text-muted-foreground mt-1">Courses</div>
                </div>
                <div class="text-center">
                  <div class="text-4xl font-bold text-primary">{{ instructors.length }}</div>
                  <div class="text-sm text-muted-foreground mt-1">Instructors</div>
                </div>
                <div class="text-center">
                  <div class="text-4xl font-bold text-primary">{{ new Date().getFullYear() - school.established }}+
                  </div>
                  <div class="text-sm text-muted-foreground mt-1">Years</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </div>
</template>

<style lang="css" scoped></style>