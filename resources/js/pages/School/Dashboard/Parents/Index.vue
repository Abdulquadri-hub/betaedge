<script setup>

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Baby, Search, Mail, Phone, MessageCircle,
  AlertCircle, TrendingUp, Users, ChevronRight,
  Filter, UserCheck,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Badge }    from '@/components/ui/badge'
import { Label }    from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
  DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import { useParents } from '@/composables/usePeopleManagement'
import { toast } from 'vue-sonner'
import DashboardLayout from '@/components/Dashboard/School/Layouts/DashboardLayout.vue'

const {
  filteredParents, isLoading, search, totalParents, totalChildren,
  initials, fmtDate, formatNaira, gradeColor, attendanceColor, sendMessage,
} = useParents()

const filterTab = ref('all')

const displayed = computed(() => {
  let list = filteredParents.value
  if (filterTab.value === 'concern') {
    list = list.filter(p => p.children.some(c => c.overall_grade < 60 || c.attendance_rate < 60))
  }
  return list
})

const concernCount = computed(() =>
  filteredParents.value.filter(p => p.children.some(c => c.overall_grade < 60 || c.attendance_rate < 60)).length
)

function totalChildrenForParent(p) { return p.children.length }
function hasAtRiskChild(p) { return p.children.some(c => c.overall_grade < 60 || c.attendance_rate < 60) }

// ── Message dialog ────────────────────────────────────────────────────────────
const showMessage  = ref(false)
const msgTarget    = ref(null)
const msgText      = ref('')
const isSending    = ref(false)

function openMessage(parent) {
  msgTarget.value = parent
  msgText.value   = ''
  showMessage.value = true
}

async function submitMessage() {
  if (!msgText.value.trim()) return
  isSending.value = true
  const result = await sendMessage(msgTarget.value.id, msgText.value)
  isSending.value = false
  if (result.success) {
    toast({ title: 'Message sent', description: `${msgTarget.value.name} will receive an email notification.` })
    showMessage.value = false
  }
}

function goToDetail(parent) {
  // TODO: router.visit(route('dashboard.parents.show', parent.id))
  router.visit(`/dashboard/parents/${parent.id}`)
}
</script>

<template>
    <DashboardLayout>
  <div class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Parents & Guardians</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Parent accounts linked to minor students enrolled at your school.
        </p>
      </div>
    </div>

    <!-- Info banner -->
    <div class="flex items-start gap-3 rounded-xl border border-secondary/30 bg-secondary/5 p-4">
      <Baby class="h-5 w-5 text-secondary shrink-0 mt-0.5" />
      <div class="text-xs">
        <p class="font-semibold text-foreground">How parent accounts work</p>
        <p class="text-muted-foreground mt-0.5">
          When a student under 18 enrolls, the system requires a parent or guardian account. The parent registers,
          pays for enrollment, and receives a separate login to monitor their child's progress at
          <span class="font-medium text-foreground">yourschool.teach.com/parent</span>.
          Multiple children can be linked to one parent account.
        </p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <UserCheck class="h-3.5 w-3.5" />Total Parents
          </p>
          <p class="text-2xl font-bold text-foreground mt-1">{{ totalParents }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <Baby class="h-3.5 w-3.5 text-secondary" />Minor Students
          </p>
          <p class="text-2xl font-bold text-secondary mt-1">{{ totalChildren }}</p>
          <p class="text-xs text-muted-foreground">under 18, parent enrolled</p>
        </CardContent>
      </Card>
      <Card class="border-destructive/20 bg-destructive/5">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <AlertCircle class="h-3.5 w-3.5 text-destructive" />Needs Attention
          </p>
          <p class="text-2xl font-bold text-destructive mt-1">{{ concernCount }}</p>
          <p class="text-xs text-muted-foreground">child grade or attendance &lt;60%</p>
        </CardContent>
      </Card>
      <Card class="border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20 dark:border-emerald-800">
        <CardContent class="p-4">
          <p class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
            <TrendingUp class="h-3.5 w-3.5 text-emerald-600" />Avg Children / Parent
          </p>
          <p class="text-2xl font-bold text-emerald-600 mt-1">
            {{ totalParents ? (totalChildren / totalParents).toFixed(1) : '0' }}
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input v-model="search" placeholder="Search parents or children..." class="pl-9" />
      </div>
      <Tabs :model-value="filterTab" @update:model-value="filterTab = $event">
        <TabsList>
          <TabsTrigger value="all">All Parents</TabsTrigger>
          <TabsTrigger value="concern">
            Needs Attention
            <Badge v-if="concernCount" variant="destructive" class="ml-1.5 h-4 px-1 text-[10px]">
              {{ concernCount }}
            </Badge>
          </TabsTrigger>
        </TabsList>
      </Tabs>
    </div>

    <!-- Empty -->
    <div v-if="!displayed.length" class="py-16 text-center rounded-xl border border-dashed border-border">
      <Baby class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
      <p class="text-sm font-medium text-foreground">No parent accounts yet</p>
      <p class="text-xs text-muted-foreground mt-1">
        Parents are automatically created when a minor student enrolls.
      </p>
    </div>

    <!-- Parent cards -->
    <div v-else class="space-y-4">
      <Card
        v-for="parent in displayed"
        :key="parent.id"
        class="group hover:border-primary/20 hover:shadow-sm transition-all cursor-pointer"
        @click="goToDetail(parent)"
      >
        <CardContent class="p-5">
          <div class="flex items-start gap-4">

            <!-- Avatar -->
            <Avatar class="h-11 w-11 shrink-0">
              <AvatarFallback
                :class="hasAtRiskChild(parent)
                  ? 'bg-destructive/10 text-destructive'
                  : 'bg-secondary/10 text-secondary'"
                class="font-bold text-sm"
              >{{ initials(parent.name) }}</AvatarFallback>
            </Avatar>

            <!-- Parent info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <p class="text-sm font-bold text-foreground">{{ parent.name }}</p>
                <Badge v-if="hasAtRiskChild(parent)" variant="destructive" class="text-[10px]">
                  Child needs attention
                </Badge>
              </div>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <span class="flex items-center gap-1">
                  <Mail class="h-3 w-3" />{{ parent.email }}
                </span>
                <span class="flex items-center gap-1">
                  <Phone class="h-3 w-3" />{{ parent.phone }}
                </span>
                <span class="flex items-center gap-1">
                  <Baby class="h-3 w-3" />
                  {{ parent.children.length }} child{{ parent.children.length !== 1 ? 'ren' : '' }} enrolled
                </span>
                <span>Joined {{ fmtDate(parent.joined_at) }}</span>
              </div>

              <!-- Children row -->
              <div class="mt-3 flex flex-wrap gap-2">
                <div
                  v-for="child in parent.children"
                  :key="child.id"
                  class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-1.5 text-xs"
                  :class="child.overall_grade < 60 || child.attendance_rate < 60
                    ? 'border-destructive/30 bg-destructive/5'
                    : ''"
                >
                  <Baby class="h-3 w-3 text-secondary shrink-0" />
                  <span class="font-medium text-foreground">{{ child.name }}</span>
                  <span class="text-muted-foreground">age {{ child.age }}</span>
                  <span class="text-border">·</span>
                  <span :class="gradeColor(child.overall_grade)" class="font-bold">{{ child.overall_grade }}%</span>
                  <span class="text-border">·</span>
                  <span :class="attendanceColor(child.attendance_rate)" class="font-medium">{{ child.attendance_rate }}% attend.</span>
                  <Badge
                    v-if="child.overall_grade < 60 || child.attendance_rate < 60"
                    variant="destructive"
                    class="text-[9px] h-3.5 px-1 ml-0.5"
                  >⚠</Badge>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 shrink-0" @click.stop>
              <Button
                variant="outline"
                size="sm"
                class="gap-1.5 text-xs h-8"
                @click="openMessage(parent)"
              >
                <MessageCircle class="h-3.5 w-3.5" />Message
              </Button>
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity"
                @click="goToDetail(parent)"
              >
                <ChevronRight class="h-4 w-4" />
              </Button>
            </div>

          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Message dialog -->
    <Dialog :open="showMessage" @update:open="showMessage = $event">
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <MessageCircle class="h-5 w-5 text-secondary" />Message {{ msgTarget?.name }}
          </DialogTitle>
          <DialogDescription class="text-xs">
            This will be sent to {{ msgTarget?.email }}. The parent will receive an email notification.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-3 py-2">
          <Label>Message</Label>
          <Textarea
            v-model="msgText"
            placeholder="e.g., Hi, I wanted to update you on your child's recent performance..."
            :rows="5"
          />
        </div>
        <DialogFooter>
          <Button variant="outline" :disabled="isSending" @click="showMessage = false">Cancel</Button>
          <Button :disabled="!msgText.trim() || isSending" @click="submitMessage">
            <span v-if="isSending" class="flex items-center gap-2">
              <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
              Sending...
            </span>
            <span v-else class="flex items-center gap-2">
              <Mail class="h-4 w-4" />Send Message
            </span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </div>
  </DashboardLayout>
</template>