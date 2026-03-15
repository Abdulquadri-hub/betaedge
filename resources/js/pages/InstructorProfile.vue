<script setup>
/**
 * InstructorProfilePublicPage.vue
 * Route: GET /tutors/{instructor}  → InstructorProfileController@public
 *
 * Public-facing instructor profile visible to school owners browsing the marketplace.
 * Only verified instructors are accessible at this URL.
 *
 * Laravel 12 Inertia props:
 *   instructor: {
 *     id, name, avatar, bio, location, gender,
 *     subjects: string[],
 *     levels: string[],
 *     years_experience: string,
 *     teaching_mode: string,
 *     verification_status: 'verified' | 'pending',
 *     rating: number,
 *     total_reviews: number,
 *     schools_count: number,
 *     batches_completed: number,
 *     students_taught: number,
 *     availability: Array<{ day, from, to }>,
 *     social: { linkedin, twitter, website },
 *     joined_at: string,
 *   }
 *   reviews: Array<{ school_name, rating, comment, date }>
 *   isOwner: boolean  (true = viewing own profile)
 *   canInvite: boolean (school owner viewing — can send invite)
 */
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  MapPin, Star, BookOpen, Users, GraduationCap,
  Clock, CheckCircle2, Send, Globe, Linkedin,
  Twitter, ArrowLeft, Calendar, Award, Briefcase,
  ChevronRight,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge  } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
  DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Label }    from '@/components/ui/label'
import { useToast } from '@/components/ui/toast/use-toast'
import VerificationBadge from '@/components/verification/VerificationBadge.vue'

const { toast } = useToast()

// ── Mock data ─────────────────────────────────────────────────────────────────
// TODO: replace with defineProps({ instructor, reviews, isOwner, canInvite })

const instructor = ref({
  id:                  'inst-001',
  name:                'Adebayo Johnson',
  avatar:              null,
  bio:                 'Experienced full-stack developer and educator with over 6 years of professional development experience and 4 years teaching web technologies. I specialise in making complex concepts simple, with a focus on practical, project-based learning. My students consistently achieve strong results and many have gone on to land junior developer roles within 3–6 months of completing my courses.',
  location:            'Lagos, Nigeria',
  gender:              'male',
  subjects:            ['Web Development', 'JavaScript', 'Vue.js', 'Laravel', 'Python'],
  levels:              ['SS 1–3', 'University Level', 'Adult / Professional'],
  years_experience:    '5-10',
  teaching_mode:       'online',
  verification_status: 'verified',
  rating:              4.8,
  total_reviews:       24,
  schools_count:       2,
  batches_completed:   7,
  students_taught:     142,
  availability: [
    { day: 'Monday',    from: '18:00', to: '21:00' },
    { day: 'Wednesday', from: '18:00', to: '21:00' },
    { day: 'Friday',    from: '18:00', to: '21:00' },
    { day: 'Saturday',  from: '10:00', to: '14:00' },
  ],
  social: { linkedin: 'linkedin.com/in/adebayojohnson', twitter: '', website: '' },
  joined_at: '2024-09-01',
})

const reviews = ref([
  { school_name: 'Bright Stars Academy',  rating: 5, date: '2026-02-15', comment: 'Adebayo is an exceptional instructor. His students consistently perform above average and his attendance record is perfect. Highly recommend.' },
  { school_name: 'Tech Academy Nigeria',  rating: 5, date: '2025-11-20', comment: 'Great communicator, very organised. Always delivers materials on time and keeps students engaged throughout every session.' },
  { school_name: 'Excel Learning Hub',    rating: 4, date: '2025-08-10', comment: 'Very knowledgeable teacher. Could improve on feedback turnaround time but overall a great instructor to work with.' },
])

// ── Invite dialog ─────────────────────────────────────────────────────────────
const showInviteDialog = ref(false)
const inviteMessage    = ref('')
const isSendingInvite  = ref(false)

async function sendInvite() {
  if (!inviteMessage.value.trim()) return
  isSendingInvite.value = true
  await new Promise(r => setTimeout(r, 800))
  // TODO: router.post(route('instructors.invite'), {
  //   instructor_id: instructor.value.id,
  //   message: inviteMessage.value,
  // }, { onSuccess: () => { showInviteDialog.value = false; ... } })
  isSendingInvite.value  = false
  showInviteDialog.value = false
  toast({
    title: 'Invite sent!',
    description: `${instructor.value.name} will receive your invite via email.`,
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function initials(name) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function fmtDate(iso) {
  return new Date(iso).toLocaleDateString('en-NG', { month: 'long', year: 'numeric' })
}

function formatTime(t) {
  const [h, m] = t.split(':').map(Number)
  const p  = h >= 12 ? 'PM' : 'AM'
  const hr = h === 0 ? 12 : h > 12 ? h - 12 : h
  return `${hr}:${String(m).padStart(2, '0')} ${p}`
}

const experienceLabel = {
  '0-1':  'Less than 1 year',
  '1-3':  '1–3 years',
  '3-5':  '3–5 years',
  '5-10': '5–10 years',
  '10+':  '10+ years',
}

const teachingModeLabel = {
  'online':     'Online',
  'in-person':  'In-person',
  'hybrid':     'Online & In-person',
}

const filledStars = computed(() => Math.round(instructor.value.rating))
</script>

<template>
  <div class="min-h-screen bg-background">

    <!-- Back nav -->
    <div class="sticky top-0 z-10 border-b border-border bg-background/95 backdrop-blur">
      <div class="container mx-auto px-4 h-14 flex items-center gap-3">
        <Link href="/marketplace" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors">
          <ArrowLeft class="h-4 w-4" />Back to Tutors
        </Link>
      </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
      <div class="grid lg:grid-cols-3 gap-8">

        <!-- ── LEFT COLUMN ── -->
        <div class="lg:col-span-1 space-y-5">

          <!-- Profile card -->
          <div class="rounded-2xl border border-border bg-card p-6 text-center space-y-4">

            <Avatar class="h-24 w-24 mx-auto ring-4 ring-border">
              <AvatarImage v-if="instructor.avatar" :src="instructor.avatar" :alt="instructor.name" />
              <AvatarFallback class="text-2xl font-black bg-secondary/10 text-secondary">
                {{ initials(instructor.name) }}
              </AvatarFallback>
            </Avatar>

            <div class="space-y-1">
              <h1 class="text-xl font-black text-foreground">{{ instructor.name }}</h1>
              <div class="flex items-center justify-center gap-1.5 text-sm text-muted-foreground">
                <MapPin class="h-3.5 w-3.5" />{{ instructor.location }}
              </div>
            </div>

            <!-- Verification badge -->
            <div class="flex items-center justify-center">
              <VerificationBadge :status="instructor.verification_status" size="md" />
            </div>

            <!-- Rating -->
            <div class="flex items-center justify-center gap-1.5">
              <div class="flex items-center gap-0.5">
                <Star
                  v-for="i in 5" :key="i"
                  class="h-4 w-4"
                  :class="i <= filledStars
                    ? 'text-amber-400 fill-amber-400'
                    : 'text-muted-foreground/30'"
                />
              </div>
              <span class="text-sm font-bold text-foreground">{{ instructor.rating }}</span>
              <span class="text-xs text-muted-foreground">({{ instructor.total_reviews }} reviews)</span>
            </div>

            <!-- Invite CTA -->
            <Button class="w-full gap-2" @click="showInviteDialog = true">
              <Send class="h-4 w-4" />Invite to My School
            </Button>

            <p class="text-xs text-muted-foreground">
              Member since {{ fmtDate(instructor.joined_at) }}
            </p>
          </div>

          <!-- Stats -->
          <div class="rounded-2xl border border-border bg-card p-5 space-y-3">
            <p class="text-sm font-semibold text-foreground">Teaching Stats</p>
            <div class="space-y-2">
              <div v-for="stat in [
                { icon: GraduationCap, label: 'Batches completed', value: instructor.batches_completed, color: 'text-primary' },
                { icon: Users,         label: 'Students taught',    value: instructor.students_taught,   color: 'text-secondary' },
                { icon: Briefcase,     label: 'Schools teaching at', value: instructor.schools_count,    color: 'text-foreground' },
              ]" :key="stat.label"
                class="flex items-center justify-between text-sm"
              >
                <span class="flex items-center gap-2 text-muted-foreground">
                  <component :is="stat.icon" class="h-3.5 w-3.5" />{{ stat.label }}
                </span>
                <span :class="['font-bold', stat.color]">{{ stat.value }}</span>
              </div>
            </div>
          </div>

          <!-- Teaching details -->
          <div class="rounded-2xl border border-border bg-card p-5 space-y-3">
            <p class="text-sm font-semibold text-foreground">Details</p>
            <div class="space-y-2 text-xs">
              <div class="flex items-start justify-between gap-2">
                <span class="text-muted-foreground">Experience</span>
                <span class="font-medium text-foreground text-right">{{ experienceLabel[instructor.years_experience] }}</span>
              </div>
              <div class="flex items-start justify-between gap-2">
                <span class="text-muted-foreground">Mode</span>
                <span class="font-medium text-foreground text-right">{{ teachingModeLabel[instructor.teaching_mode] }}</span>
              </div>
            </div>
          </div>

          <!-- Social links -->
          <div
            v-if="instructor.social.linkedin || instructor.social.twitter || instructor.social.website"
            class="rounded-2xl border border-border bg-card p-5 space-y-2"
          >
            <p class="text-sm font-semibold text-foreground">Links</p>
            <a v-if="instructor.social.linkedin" :href="`https://${instructor.social.linkedin}`" target="_blank"
              class="flex items-center gap-2 text-xs text-muted-foreground hover:text-primary transition-colors"
            >
              <Linkedin class="h-3.5 w-3.5" />LinkedIn
              <ChevronRight class="h-3 w-3 ml-auto" />
            </a>
            <a v-if="instructor.social.twitter" :href="`https://twitter.com/${instructor.social.twitter}`" target="_blank"
              class="flex items-center gap-2 text-xs text-muted-foreground hover:text-primary transition-colors"
            >
              <Twitter class="h-3.5 w-3.5" />Twitter
              <ChevronRight class="h-3 w-3 ml-auto" />
            </a>
            <a v-if="instructor.social.website" :href="instructor.social.website" target="_blank"
              class="flex items-center gap-2 text-xs text-muted-foreground hover:text-primary transition-colors"
            >
              <Globe class="h-3.5 w-3.5" />Website
              <ChevronRight class="h-3 w-3 ml-auto" />
            </a>
          </div>

        </div>

        <!-- ── RIGHT COLUMN ── -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Bio -->
          <div class="rounded-2xl border border-border bg-card p-6 space-y-3">
            <h2 class="text-base font-bold text-foreground">About</h2>
            <p class="text-sm text-muted-foreground leading-relaxed">{{ instructor.bio }}</p>
          </div>

          <!-- Subjects -->
          <div class="rounded-2xl border border-border bg-card p-6 space-y-4">
            <h2 class="text-base font-bold text-foreground flex items-center gap-2">
              <BookOpen class="h-4 w-4 text-primary" />Subjects & Levels
            </h2>
            <div>
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Subjects</p>
              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="subject in instructor.subjects"
                  :key="subject"
                  variant="secondary"
                  class="text-xs"
                >
                  {{ subject }}
                </Badge>
              </div>
            </div>
            <div>
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Academic Levels</p>
              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="level in instructor.levels"
                  :key="level"
                  variant="outline"
                  class="text-xs"
                >
                  {{ level }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Availability -->
          <div class="rounded-2xl border border-border bg-card p-6 space-y-4">
            <h2 class="text-base font-bold text-foreground flex items-center gap-2">
              <Calendar class="h-4 w-4 text-primary" />Weekly Availability
            </h2>
            <div class="grid sm:grid-cols-2 gap-2">
              <div
                v-for="slot in instructor.availability"
                :key="slot.day"
                class="flex items-center justify-between rounded-lg border border-border bg-muted/30 px-4 py-2.5 text-sm"
              >
                <span class="font-medium text-foreground">{{ slot.day }}</span>
                <span class="text-xs text-muted-foreground">
                  {{ formatTime(slot.from) }} – {{ formatTime(slot.to) }}
                </span>
              </div>
            </div>
          </div>

          <!-- School reviews -->
          <div class="rounded-2xl border border-border bg-card p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-bold text-foreground flex items-center gap-2">
                <Award class="h-4 w-4 text-primary" />School Reviews
              </h2>
              <div class="flex items-center gap-1.5 text-sm font-bold text-amber-500">
                <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                {{ instructor.rating }} / 5
              </div>
            </div>

            <div class="space-y-4">
              <div
                v-for="review in reviews"
                :key="review.school_name + review.date"
                class="rounded-xl border border-border bg-muted/30 p-4 space-y-2"
              >
                <div class="flex items-center justify-between gap-2">
                  <p class="text-sm font-semibold text-foreground">{{ review.school_name }}</p>
                  <div class="flex items-center gap-0.5 shrink-0">
                    <Star
                      v-for="i in 5" :key="i"
                      class="h-3.5 w-3.5"
                      :class="i <= review.rating
                        ? 'text-amber-400 fill-amber-400'
                        : 'text-muted-foreground/30'"
                    />
                  </div>
                </div>
                <p class="text-sm text-muted-foreground leading-relaxed">{{ review.comment }}</p>
                <p class="text-xs text-muted-foreground">{{ fmtDate(review.date) }}</p>
              </div>

              <p v-if="!reviews.length" class="text-sm text-muted-foreground text-center py-4">
                No reviews yet.
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Invite dialog -->
    <Dialog :open="showInviteDialog" @update:open="showInviteDialog = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Send class="h-5 w-5 text-primary" />Invite {{ instructor.name }}
          </DialogTitle>
          <DialogDescription class="text-sm">
            Send a teaching invite to {{ instructor.name }}. They will receive it by email
            and can accept or decline from their dashboard.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-3 py-2">
          <div class="space-y-1.5">
            <Label class="font-semibold">Your Message</Label>
            <Textarea
              v-model="inviteMessage"
              placeholder="Introduce your school and the teaching opportunity. Include the course, schedule, and payment details so they can make an informed decision..."
              :rows="5"
            />
            <p class="text-xs text-muted-foreground">
              Your school profile and the invite will be visible to the instructor.
            </p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" :disabled="isSendingInvite" @click="showInviteDialog = false">
            Cancel
          </Button>
          <Button
            :disabled="isSendingInvite || !inviteMessage.trim()"
            class="gap-2"
            @click="sendInvite"
          >
            <span v-if="isSendingInvite" class="h-4 w-4 rounded-full border-2 border-primary-foreground border-t-transparent animate-spin" />
            <Send v-else class="h-4 w-4" />
            {{ isSendingInvite ? 'Sending...' : 'Send Invite' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
</template>