<script setup>
import { GraduationCap, Users, BookOpen, School } from 'lucide-vue-next'


defineProps({
    name: {
        type: String,
        default: 'BetaEdge'
    }
})
const emit = defineEmits(['roleSelected'])

const roles = [
  {
    value: 'student',
    label: 'Student',
    description: 'Enroll in courses and learn',
    icon: GraduationCap,
  },
  {
    value: 'parent',
    label: 'Parent',
    description: "Manage your children's education",
    icon: Users,
  },
  {
    value: 'instructor',
    label: 'Instructor',
    description: 'Teach and create courses',
    icon: BookOpen,
  },
  {
    value: 'school_owner',
    label: 'School Owner',
    description: 'Create and manage your school',
    icon: School,
  },
]

const handleRoleSelect = (role) => {
  emit('roleSelected', role)
}
</script>

<template>
  <div class="space-y-6">
    <div class="text-center space-y-2">
      <h3 class="text-xl font-semibold">Choose your role</h3>
      <p class="text-sm text-muted-foreground">
        Select how you'll be using {{ name }}
      </p>
    </div>

    <div class="grid gap-3">
      <button
        v-for="role in roles"
        :key="role.value"
        type="button"
        @click="handleRoleSelect(role)"
        class="flex items-center gap-4 p-4 rounded-lg border-2 text-left transition-all hover:border-primary hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 hover:cursor-pointer"
      >
        <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
          <component :is="role.icon" class="h-6 w-6 text-primary" />
        </div>
        <div class="flex-1">
          <p class="font-medium">{{ role.label }}</p>
          <p class="text-sm text-muted-foreground">{{ role.description }}</p>
        </div>
      </button>
    </div>

    <!-- <p class="text-center text-sm text-muted-foreground">
      Don't have an account?
      <a href="/auth/register" class="text-primary hover:underline font-medium">
        Create one
      </a>
    </p> -->
  </div>
</template>