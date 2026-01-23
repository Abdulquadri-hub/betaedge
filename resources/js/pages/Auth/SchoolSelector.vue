<script setup >
import { ref } from 'vue';
import { GraduationCap, ArrowRight, Loader2, Copyright } from 'lucide-vue-next'
import { toast } from 'vue-sonner';
import { Link } from '@inertiajs/vue3';

// props
defineProps({
    name: {
        type: String,
        default: 'BetaEdge'
    },
    copyrightText: {
        type: String,
        default: '2026 BetaEdge Platform. All rights reserved'
    }
})

// reactive variables
const isLoading = ref(false)
const selectedSchool = ref(null)
// Mock schools data - Replace with props from backend
const schools = ref([
  {
    id: 1,
    name: 'Elevate Academy',
    subdomain: 'elevate.betaedge.test',
    logo: null,
    studentCount: 1250,
    courseCount: 45
  },
  {
    id: 2,
    name: 'Beta Learning School',
    subdomain: 'beta.betaedge.test',
    logo: null,
    studentCount: 850,
    courseCount: 32
  },
  {
    id: 3,
    name: 'Tech Institute',
    subdomain: 'tech.betaedge.test',
    logo: null,
    studentCount: 2100,
    courseCount: 67
  }
])

// methods
const handleSchoolSelect = async (school) => {
  if (isLoading.value) return

  selectedSchool.value = school.id
  isLoading.value = true

  try {
    // Mock API call - Replace with actual backend call
    // Laravel Inertia.js: Use router.post('/auth/select-school', { school_id: school.id })
    
    console.log('Selected school:', school)

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))

    toast.success('Success', {
      description: `Switched to ${school.name}`,
    })

    // Redirect to instructor dashboard with selected school context
    console.log('Redirecting to: /instructor/dashboard')
    // window.location.href = `/instructor/dashboard?school=${school.subdomain}`

  } catch (error) {
    console.log(error);
    
    toast.error('Error',{
      description: 'Failed to select school',
      variant: 'destructive',
    })
    selectedSchool.value = null
  } finally {
    isLoading.value = false
  }
}

const getSchoolInitials = (name) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

// lifecycle hooks

</script>


<template>
  <div class="min-h-screen flex">
    <!-- Left side - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-primary relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-primary/80" />
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-20 w-72 h-72 bg-white rounded-full blur-3xl" />
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-white rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 flex flex-col justify-between p-12 text-primary-foreground">
        <a href="/" class="flex items-center gap-2">
          <GraduationCap class="h-8 w-8" />
          <span class="text-2xl font-bold">{{ name }}</span>
        </a>
        
        <div class="space-y-6">
          <h1 class="text-4xl font-bold leading-tight">
            Choose your school
          </h1>
          <p class="text-lg opacity-90">
            You're associated with multiple schools. Select which one you'd like to access.
          </p>
        </div>

        <p class="flex items-center gap-2 text-sm opacity-70">
          <Copyright class="w-4 h-4"/> {{ copyrightText }}
        </p>
      </div>
    </div>

    <!-- Right side - School Selection -->
    <div class="flex-1 flex items-center justify-center p-8">
      <div class="w-full max-w-md space-y-8">
        <div class="lg:hidden flex justify-center">
          <a href="/" class="flex items-center gap-2 text-primary">
            <GraduationCap class="h-8 w-8" />
            <span class="text-2xl font-bold">{{ name }}</span>
          </a>
        </div>

        <div class="text-center space-y-2">
          <h2 class="text-2xl font-bold">Select a school</h2>
          <p class="text-muted-foreground">
            You teach at {{ schools.length }} schools
          </p>
        </div>

        <div class="space-y-3">
          <button
            v-for="school in schools"
            :key="school.id"
            @click="handleSchoolSelect(school)"
            :disabled="isLoading"
            class="w-full flex items-center gap-4 p-4 rounded-lg border-2 text-left transition-all hover:border-primary hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <!-- School Logo/Avatar -->
            <div class="h-14 w-14 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
              <span v-if="!school.logo" class="text-lg font-bold text-primary">
                {{ getSchoolInitials(school.name) }}
              </span>
              <img v-else :src="school.logo" :alt="school.name" class="h-full w-full object-cover rounded-lg" />
            </div>

            <!-- School Info -->
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-base truncate">{{ school.name }}</p>
              <p class="text-sm text-muted-foreground truncate">{{ school.subdomain }}</p>
              <div class="flex items-center gap-4 mt-1">
                <span class="text-xs text-muted-foreground">
                  {{ school.studentCount }} students
                </span>
                <span class="text-xs text-muted-foreground">
                  {{ school.courseCount }} courses
                </span>
              </div>
            </div>

            <!-- Loading/Arrow Icon -->
            <div class="flex-shrink-0">
              <Loader2 
                v-if="isLoading && selectedSchool === school.id" 
                class="h-5 w-5 animate-spin text-primary" 
              />
              <ArrowRight 
                v-else 
                class="h-5 w-5 text-muted-foreground group-hover:text-primary" 
              />
            </div>
          </button>
        </div>

        <div class="text-center pt-4">
          <Link 
            href="/auth/logout"
            class="text-sm text-muted-foreground hover:text-foreground"
          >
            Sign out instead
        </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="css" scoped></style>