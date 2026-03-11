<script setup>

import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  User, Camera, Save, RefreshCw, Bell,
  Clock, CheckCircle2, Phone, Mail,
  MapPin, BookOpen, Plus, Trash2,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { Badge }    from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import InstructorLayout from '@/components/Dashboard/Instrutor/Layouts/InstructorLayout.vue'

const { updateProfile, initials } = useInstructorDashboard()

const activeTab   = ref('profile')
const isSaving    = ref(false)

// ── Profile form ──────────────────────────────────────────────────────────────
const form = ref({
  first_name:    'Adebayo',
  last_name:     'Johnson',
  email:         'adebayo.johnson@gmail.com',
  phone:         '+234 803 456 7890',
  location:      'Lagos, Nigeria',
  bio:           'Experienced software engineer and educator with 6+ years of full-stack development experience. Passionate about making tech accessible to everyone. MSc in Computer Science from University of Lagos.',
  specialties:   ['JavaScript', 'Vue.js', 'Laravel', 'Python', 'Data Science'],
  years_experience: 6,
  linkedin:      'https://linkedin.com/in/adebayojohnson',
  twitter:       '',
  website:       '',
  profile_photo: null,
})

const newSpecialty = ref('')

function addSpecialty() {
  const s = newSpecialty.value.trim()
  if (s && !form.value.specialties.includes(s)) {
    form.value.specialties.push(s)
    newSpecialty.value = ''
  }
}

function removeSpecialty(s) {
  form.value.specialties = form.value.specialties.filter(x => x !== s)
}

async function saveProfile() {
  isSaving.value = true
  try {
    await updateProfile(form.value)
    toast({ title: 'Profile updated', description: 'Your changes have been saved.' })
  } finally {
    isSaving.value = false
  }
}

// ── Availability ──────────────────────────────────────────────────────────────
const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const availability = ref({
  Monday:    { available: true,  from: '09:00', to: '18:00' },
  Tuesday:   { available: true,  from: '09:00', to: '18:00' },
  Wednesday: { available: true,  from: '09:00', to: '21:00' },
  Thursday:  { available: false, from: '09:00', to: '18:00' },
  Friday:    { available: true,  from: '16:00', to: '21:00' },
  Saturday:  { available: true,  from: '08:00', to: '14:00' },
  Sunday:    { available: false, from: '09:00', to: '18:00' },
})

const isSavingAvail = ref(false)

async function saveAvailability() {
  isSavingAvail.value = true
  await new Promise(r => setTimeout(r, 700))
  // TODO: router.put(route('instructor.profile.availability'), availability.value)
  isSavingAvail.value = false
  toast({ title: 'Availability saved', description: 'Schools can now see your schedule.' })
}

// ── Notifications ─────────────────────────────────────────────────────────────
const notifPrefs = ref({
  email_new_assignment:   true,
  email_batch_start:      true,
  email_payment_received: true,
  email_application_update: true,
  email_weekly_summary:   false,
  sms_new_assignment:     false,
  sms_batch_reminder:     true,
})

const isSavingNotif = ref(false)

async function saveNotifications() {
  isSavingNotif.value = true
  await new Promise(r => setTimeout(r, 600))
  // TODO: router.put(route('instructor.profile.notifications'), notifPrefs.value)
  isSavingNotif.value = false
  toast({ title: 'Notification preferences saved' })
}
</script>

<template>
    <InstructorLayout>
  <div class="p-6 max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-foreground tracking-tight">My Profile</h1>
      <p class="text-sm text-muted-foreground mt-1">Update your profile, availability, and notification preferences.</p>
    </div>

    <!-- Profile snapshot -->
    <Card>
      <CardContent class="p-5">
        <div class="flex items-center gap-4">
          <div class="relative shrink-0">
            <Avatar class="h-16 w-16">
              <AvatarFallback class="text-xl font-black bg-primary/10 text-primary">
                {{ initials(form.first_name + ' ' + form.last_name) }}
              </AvatarFallback>
            </Avatar>
            <button class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-background bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
              @click="toast({ title: 'Photo upload', description: 'Photo upload via file input coming in backend integration.' })"
            >
              <Camera class="h-3 w-3" />
            </button>
          </div>
          <div>
            <p class="text-lg font-bold text-foreground">{{ form.first_name }} {{ form.last_name }}</p>
            <p class="text-sm text-muted-foreground">{{ form.email }}</p>
            <div class="flex flex-wrap gap-1.5 mt-2">
              <Badge v-for="s in form.specialties.slice(0, 4)" :key="s" variant="secondary" class="text-xs">{{ s }}</Badge>
              <Badge v-if="form.specialties.length > 4" variant="outline" class="text-xs">+{{ form.specialties.length - 4 }}</Badge>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <Tabs v-model="activeTab">
      <TabsList>
        <TabsTrigger value="profile"><User class="mr-1.5 h-3.5 w-3.5" />Profile</TabsTrigger>
        <TabsTrigger value="availability"><Clock class="mr-1.5 h-3.5 w-3.5" />Availability</TabsTrigger>
        <TabsTrigger value="notifications"><Bell class="mr-1.5 h-3.5 w-3.5" />Notifications</TabsTrigger>
      </TabsList>

      <!-- Profile tab -->
      <TabsContent value="profile" class="mt-4 space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">Personal Information</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div class="space-y-1.5"><Label>First Name</Label><Input v-model="form.first_name" /></div>
              <div class="space-y-1.5"><Label>Last Name</Label><Input v-model="form.last_name" /></div>
              <div class="space-y-1.5">
                <Label>Email <span class="text-muted-foreground text-xs">(cannot change)</span></Label>
                <Input :value="form.email" disabled class="bg-muted/50 text-muted-foreground" />
              </div>
              <div class="space-y-1.5">
                <Label>Phone</Label>
                <div class="relative">
                  <Phone class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input v-model="form.phone" class="pl-9" placeholder="+234 8XX XXX XXXX" />
                </div>
              </div>
              <div class="space-y-1.5">
                <Label>Location</Label>
                <div class="relative">
                  <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input v-model="form.location" class="pl-9" placeholder="City, Country" />
                </div>
              </div>
              <div class="space-y-1.5">
                <Label>Years of Experience</Label>
                <Input v-model.number="form.years_experience" type="number" min="0" />
              </div>
            </div>

            <div class="space-y-1.5">
              <Label>Bio / About Me</Label>
              <Textarea v-model="form.bio" placeholder="Tell schools and students about your background and teaching style..." :rows="4" />
              <p class="text-xs text-muted-foreground">This appears on your public instructor profile.</p>
            </div>

            <!-- Specialties -->
            <div class="space-y-2">
              <Label>Specialties / Skills</Label>
              <div class="flex flex-wrap gap-2">
                <Badge v-for="s in form.specialties" :key="s" variant="secondary" class="gap-1.5 pr-1">
                  {{ s }}
                  <button @click="removeSpecialty(s)" class="hover:text-destructive transition-colors">
                    <Trash2 class="h-3 w-3" />
                  </button>
                </Badge>
              </div>
              <div class="flex gap-2 mt-2">
                <Input v-model="newSpecialty" placeholder="Add a skill or subject..." class="max-w-xs" @keydown.enter.prevent="addSpecialty" />
                <Button variant="outline" size="sm" class="gap-1.5" :disabled="!newSpecialty.trim()" @click="addSpecialty">
                  <Plus class="h-4 w-4" />Add
                </Button>
              </div>
            </div>

            <!-- Social links -->
            <div class="space-y-3 pt-2 border-t border-border">
              <p class="text-sm font-semibold">Links <span class="text-xs font-normal text-muted-foreground">(optional)</span></p>
              <div class="grid sm:grid-cols-2 gap-3">
                <div class="space-y-1.5"><Label>LinkedIn</Label><Input v-model="form.linkedin" placeholder="https://linkedin.com/in/..." /></div>
                <div class="space-y-1.5"><Label>Twitter / X</Label><Input v-model="form.twitter" placeholder="https://x.com/..." /></div>
                <div class="space-y-1.5"><Label>Website</Label><Input v-model="form.website" placeholder="https://yourwebsite.com" /></div>
              </div>
            </div>
          </CardContent>
        </Card>
        <Button :disabled="isSaving" @click="saveProfile">
          <RefreshCw v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
          <Save v-else class="mr-2 h-4 w-4" />
          {{ isSaving ? 'Saving...' : 'Save Profile' }}
        </Button>
      </TabsContent>

      <!-- Availability tab -->
      <TabsContent value="availability" class="mt-4 space-y-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm">Weekly Availability</CardTitle>
            <p class="text-xs text-muted-foreground mt-1">Schools see this when scheduling your classes. Toggle a day to mark it available.</p>
          </CardHeader>
          <CardContent class="space-y-3 pt-0">
            <div v-for="day in days" :key="day" class="flex items-center gap-4 rounded-lg border border-border p-3"
              :class="availability[day].available ? 'bg-primary/5 border-primary/20' : 'opacity-60'"
            >
              <Checkbox
                :checked="availability[day].available"
                @update:checked="availability[day].available = $event"
              />
              <span class="w-24 text-sm font-medium text-foreground shrink-0">{{ day }}</span>
              <template v-if="availability[day].available">
                <div class="flex items-center gap-2 flex-1 flex-wrap">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs text-muted-foreground">From</span>
                    <Input v-model="availability[day].from" type="time" class="h-7 w-24 text-xs px-2" />
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs text-muted-foreground">To</span>
                    <Input v-model="availability[day].to" type="time" class="h-7 w-24 text-xs px-2" />
                  </div>
                </div>
              </template>
              <span v-else class="text-xs text-muted-foreground italic">Not available</span>
            </div>
          </CardContent>
        </Card>
        <Button :disabled="isSavingAvail" @click="saveAvailability">
          <RefreshCw v-if="isSavingAvail" class="mr-2 h-4 w-4 animate-spin" />
          <Save v-else class="mr-2 h-4 w-4" />
          {{ isSavingAvail ? 'Saving...' : 'Save Availability' }}
        </Button>
      </TabsContent>

      <!-- Notifications tab -->
      <TabsContent value="notifications" class="mt-4 space-y-4">
        <Card>
          <CardHeader><CardTitle class="text-sm">Email Notifications</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <label
              v-for="(label, key) in {
                email_new_assignment:     'New assignment submission',
                email_batch_start:        'New batch starting soon',
                email_payment_received:   'Payment received from school',
                email_application_update: 'Job application update',
                email_weekly_summary:     'Weekly teaching summary',
              }"
              :key="key"
              class="flex items-center justify-between rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40 transition-colors"
            >
              <span class="text-sm text-foreground">{{ label }}</span>
              <Checkbox :checked="notifPrefs[key]" @update:checked="notifPrefs[key] = $event" />
            </label>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle class="text-sm">SMS Notifications</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <label
              v-for="(label, key) in {
                sms_new_assignment:  'New assignment submitted',
                sms_batch_reminder:  'Class reminder (1 hour before)',
              }"
              :key="key"
              class="flex items-center justify-between rounded-lg border border-border p-3 cursor-pointer hover:bg-muted/40 transition-colors"
            >
              <span class="text-sm text-foreground">{{ label }}</span>
              <Checkbox :checked="notifPrefs[key]" @update:checked="notifPrefs[key] = $event" />
            </label>
          </CardContent>
        </Card>

        <Button :disabled="isSavingNotif" @click="saveNotifications">
          <RefreshCw v-if="isSavingNotif" class="mr-2 h-4 w-4 animate-spin" />
          <Save v-else class="mr-2 h-4 w-4" />
          {{ isSavingNotif ? 'Saving...' : 'Save Preferences' }}
        </Button>
      </TabsContent>
    </Tabs>

  </div>
  </InstructorLayout>
</template>