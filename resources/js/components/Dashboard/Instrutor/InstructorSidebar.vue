<script setup>

import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Sidebar, SidebarContent, SidebarFooter, SidebarGroup,
  SidebarGroupContent, SidebarGroupLabel, SidebarHeader,
  SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarSeparator,
} from '@/components/ui/sidebar'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
  Popover, PopoverContent, PopoverTrigger,
} from '@/components/ui/popover'
import {
  LayoutDashboard, UsersRound, ClipboardCheck,
  Video, Wallet, Briefcase, User, LogOut, GraduationCap,
  Building2, ChevronDown, CheckCircle2, Clock, Users,
  ShieldCheck, ShieldX, ShieldAlert,
} from 'lucide-vue-next'
import { useUserContext } from '@/composables/useUserContext'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'

const { user, userInitials } = useUserContext()
const { schools, currentSchool, switchSchool } = useInstructorDashboard()

// ── School switcher ───────────────────────────────────────────────────────────
const switcherOpen = ref(false)
const isSwitching  = ref(false)

async function handleSwitch(school) {
  if (school.tenant_id === currentSchool.value.tenant_id) {
    switcherOpen.value = false
    return
  }
  isSwitching.value = true
  await switchSchool(school.tenant_id)
  // TODO (Laravel 12): router.post(route('instructor.switchSchool', school.tenant_id), {}, {
  //   onSuccess: () => { window.location.href = '/instructor' }
  // })
  isSwitching.value  = false
  switcherOpen.value = false
}

// ── KYC status config ─────────────────────────────────────────────────────────
// TODO: replace mock with user.kyc_status from useUserContext Inertia prop
const kycStatus = computed(() => user.value?.kyc_status ?? 'unverified')

const kycConfig = computed(() => ({
  verified: {
    icon:  ShieldCheck,
    label: 'Verified',
    badge: 'text-emerald-600 bg-emerald-100 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/30',
  },
  pending: {
    icon:  Clock,
    label: 'Under Review',
    badge: 'text-amber-600 bg-amber-100 border-amber-200 dark:text-amber-400 dark:bg-amber-950/30',
  },
  rejected: {
    icon:  ShieldX,
    label: 'Action Needed',
    badge: 'text-destructive bg-destructive/10 border-destructive/20',
  },
  unverified: {
    icon:  ShieldAlert,
    label: 'Not Verified',
    badge: 'text-muted-foreground bg-muted border-border',
  },
}[kycStatus.value]))

// ── Menu definitions ──────────────────────────────────────────────────────────
const teachingMenu = [
  { title: 'Hub',           href: '/instructor',            icon: LayoutDashboard, exact: true },
  { title: 'My Batches',    href: '/instructor/batches',    icon: UsersRound },
  { title: 'My Students',   href: '/instructor/students',   icon: Users },
  { title: 'Grading',       href: '/instructor/grading',    icon: ClipboardCheck },
  { title: 'Live Sessions', href: '/instructor/sessions',   icon: Video },
  { title: 'Earnings',      href: '/instructor/earnings',   icon: Wallet },
]

const careerMenu = [
  { title: 'Job Portal',  href: '/instructor/applications', icon: Briefcase },
  { title: 'My Profile',  href: '/instructor/profile',      icon: User },
]

const page = usePage()

function isActive(href, exact = false) {
  const current = page.url
  if (exact) return current === href || current === href + '/'
  return current.startsWith(href)
}
</script>

<template>
  <Sidebar collapsible="icon">

    <!-- School switcher header -->
    <SidebarHeader class="p-3">
      <Popover :open="switcherOpen" @update:open="switcherOpen = $event">
        <PopoverTrigger as-child>
          <button
            class="group flex w-full items-center gap-3 rounded-xl border border-border bg-card px-3 py-2.5 text-left shadow-sm transition-all hover:border-secondary/40 hover:bg-secondary/5 focus:outline-none"
            :class="switcherOpen ? 'border-secondary/40 bg-secondary/5' : ''"
          >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-secondary/15 text-secondary">
              <Building2 class="h-4 w-4" />
            </div>
            <div class="group-data-[collapsible=icon]:hidden flex-1 min-w-0">
              <p class="text-sm font-bold text-foreground leading-tight truncate">
                {{ currentSchool.name }}
              </p>
              <p class="text-[10px] text-muted-foreground leading-tight">
                {{ currentSchool.subdomain }}.teach.com
              </p>
            </div>
            <ChevronDown
              class="group-data-[collapsible=icon]:hidden h-4 w-4 text-muted-foreground transition-transform shrink-0"
              :class="switcherOpen ? 'rotate-180' : ''"
            />
          </button>
        </PopoverTrigger>

        <PopoverContent class="w-72 p-2 shadow-xl" side="right" align="start" :side-offset="8">
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-2 pb-2">
            Switch School
          </p>
          <div class="space-y-1">
            <button
              v-for="school in schools"
              :key="school.tenant_id"
              class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-all focus:outline-none"
              :class="school.tenant_id === currentSchool.tenant_id
                ? 'bg-secondary/10 ring-1 ring-secondary/30'
                : 'hover:bg-muted/50'"
              :disabled="isSwitching"
              @click="handleSwitch(school)"
            >
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                :class="school.tenant_id === currentSchool.tenant_id
                  ? 'bg-secondary text-secondary-foreground'
                  : 'bg-muted text-muted-foreground'"
              >
                {{ school.name.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                  <p class="text-sm font-semibold text-foreground truncate">{{ school.name }}</p>
                  <CheckCircle2
                    v-if="school.tenant_id === currentSchool.tenant_id"
                    class="h-3.5 w-3.5 text-secondary shrink-0"
                  />
                </div>
                <div class="flex items-center gap-3 mt-0.5 text-[10px] text-muted-foreground">
                  <span class="flex items-center gap-0.5">
                    <UsersRound class="h-2.5 w-2.5" />
                    {{ school.active_batches }} batch{{ school.active_batches !== 1 ? 'es' : '' }}
                  </span>
                  <span v-if="school.next_class" class="flex items-center gap-0.5">
                    <Clock class="h-2.5 w-2.5" />{{ school.next_class }}
                  </span>
                </div>
              </div>
              <span
                v-if="isSwitching && school.tenant_id !== currentSchool.tenant_id"
                class="h-4 w-4 shrink-0 rounded-full border-2 border-secondary border-t-transparent animate-spin"
              />
            </button>
          </div>
          <div class="mt-2 border-t border-border pt-2 px-2">
            <p class="text-[10px] text-muted-foreground leading-relaxed">
              Switching school reloads your dashboard to show that school's data.
            </p>
          </div>
        </PopoverContent>
      </Popover>
    </SidebarHeader>

    <!-- <SidebarSeparator /> -->

    <SidebarContent class="gap-0 overflow-hidden">

      <!-- Teaching -->
      <SidebarGroup class="pt-2">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">
          Teaching
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in teachingMenu" :key="item.title">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive(item.href, item.exact)
                    ? 'bg-secondary/15 text-secondary font-semibold shadow-sm'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent',
                ]"
              >
                <Link :href="item.href" class="flex items-center gap-3 px-3 py-2">
                  <component
                    :is="item.icon"
                    :class="['h-4 w-4 shrink-0', isActive(item.href, item.exact) ? 'text-secondary' : '']"
                  />
                  <span class="text-sm">{{ item.title }}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarSeparator class="my-1 mx-3" />

      <!-- KYC / Verification -->
      <SidebarGroup class="pt-1">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">
          KYC
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                tooltip="Identity Verification"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive('/instructor/verification')
                    ? 'bg-secondary/15 text-secondary font-semibold shadow-sm'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent',
                ]"
              >
                <Link href="/instructor/verification" class="flex items-center gap-3 px-3 py-2">
                  <component
                    :is="kycConfig.icon"
                    class="h-4 w-4 shrink-0"
                    :class="{
                      'text-emerald-500': kycStatus === 'verified',
                      'text-amber-500':   kycStatus === 'pending',
                      'text-destructive': kycStatus === 'rejected',
                    }"
                  />
                  <span class="text-sm group-data-[collapsible=icon]:hidden">Verification</span>
                  <!-- Live status badge — hidden when sidebar collapsed -->
                  <span
                    class="group-data-[collapsible=icon]:hidden ml-auto inline-flex items-center rounded-full border px-1.5 py-0.5 text-[9px] font-semibold leading-none"
                    :class="kycConfig.badge"
                  >
                    {{ kycConfig.label }}
                  </span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarSeparator class="my-1 mx-3" />

      <!-- Career -->
      <SidebarGroup class="pt-1 pb-2">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">
          Career
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in careerMenu" :key="item.title">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive(item.href)
                    ? 'bg-secondary/15 text-secondary font-semibold'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent',
                ]"
              >
                <Link :href="item.href" class="flex items-center gap-3 px-3 py-2">
                  <component :is="item.icon" class="h-4 w-4 shrink-0" />
                  <span class="text-sm">{{ item.title }}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

    </SidebarContent>

    <!-- Footer: user info + logout -->
    <!-- <SidebarFooter class="p-3">
      <SidebarSeparator class="mb-2" />
      <div class="flex items-center gap-3 rounded-lg p-2 group-data-[collapsible=icon]:justify-center">
        <Avatar class="h-8 w-8 shrink-0 ring-2 ring-secondary/20">
          <AvatarFallback class="bg-secondary/10 text-secondary text-xs font-bold">
            {{ userInitials }}
          </AvatarFallback>
        </Avatar>
        <div class="group-data-[collapsible=icon]:hidden flex-1 min-w-0">
          <p class="text-sm font-medium text-foreground truncate leading-snug">{{ user.name }}</p>
          <p class="text-[11px] text-muted-foreground truncate">{{ user.email }}</p>
        </div>
        <a
          href="/logout"
          class="group-data-[collapsible=icon]:hidden text-muted-foreground hover:text-destructive transition-colors shrink-0"
          title="Logout"
        >
          <LogOut class="h-4 w-4" />
        </a>
      </div>
    </SidebarFooter> -->

  </Sidebar>
</template>