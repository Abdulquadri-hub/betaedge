<script setup>

import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Sidebar, SidebarContent, SidebarFooter, SidebarGroup,
  SidebarGroupContent, SidebarGroupLabel, SidebarHeader,
  SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarSeparator,
} from '@/components/ui/sidebar'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
  LayoutDashboard, Users, BookOpen, GraduationCap, Video,
  BarChart3, Settings, LogOut, UsersRound, ClipboardList,
  MessageSquareWarning, Award, Wallet, Baby,
  ShieldCheck, Clock, ShieldX, ShieldAlert,
} from 'lucide-vue-next'
import { useUserContext } from '@/composables/useUserContext'

const { user, tenant, tenantInitials, userInitials } = useUserContext()

// ── KYC status config ─────────────────────────────────────────────────────────
const kycConfig = computed(() => ({
  verified: {
    icon:    ShieldCheck,
    label:   'Verified',
    badge:   'text-emerald-600 bg-emerald-100 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/30',
    menuBg:  'bg-emerald-50/50 dark:bg-emerald-950/10',
  },
  pending: {
    icon:    Clock,
    label:   'Under Review',
    badge:   'text-amber-600 bg-amber-100 border-amber-200 dark:text-amber-400 dark:bg-amber-950/30',
    menuBg:  'bg-amber-50/50 dark:bg-amber-950/10',
  },
  rejected: {
    icon:    ShieldX,
    label:   'Action Needed',
    badge:   'text-destructive bg-destructive/10 border-destructive/20',
    menuBg:  'bg-destructive/5',
  },
  unverified: {
    icon:    ShieldAlert,
    label:   'Not Verified',
    badge:   'text-muted-foreground bg-muted border-border',
    menuBg:  '',
  },
}[tenant.value?.kyc_status ?? 'unverified']))

// ── Menu definitions ──────────────────────────────────────────────────────────
const mainMenu = [
  { title: 'Dashboard',     href: '/dashboard',               icon: LayoutDashboard, exact: true },
  { title: 'Courses',       href: '/dashboard/courses',       icon: BookOpen },
  { title: 'Batches',       href: '/dashboard/batches',       icon: UsersRound },
  { title: 'Students',      href: '/dashboard/students',      icon: Users },
  // { title: 'Parents',       href: '/dashboard/parents',       icon: Baby },
  { title: 'Instructors',   href: '/dashboard/instructors',   icon: GraduationCap },
  { title: 'Live Sessions', href: '/dashboard/live-sessions', icon: Video },
  // { title: 'Enrollments',   href: '/dashboard/enrollments',   icon: ClipboardList },
]

const managementMenu = [
  // { title: 'Complaints',   href: '/dashboard/complaints',   icon: MessageSquareWarning },
  { title: 'Certificates', href: '/dashboard/certificates', icon: Award },
  // { title: 'Reports',      href: '/dashboard/reports',      icon: BarChart3 },
  { title: 'Financials',   href: '/dashboard/financials',   icon: Wallet },
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
    <SidebarHeader class="p-3">
      <Link
        href="/dashboard"
        class="flex items-center gap-3 rounded-lg p-1 transition-colors hover:bg-sidebar-accent"
      >
        <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary shadow-sm">
          <img
            v-if="tenant.logo"
            :src="tenant.logo"
            :alt="tenant.name"
            class="h-full w-full rounded-lg object-cover"
          />
          <span v-else class="text-xs font-bold text-primary-foreground tracking-wide">
            {{ tenantInitials }}
          </span>
        </div>
        <div class="group-data-[collapsible=icon]:hidden flex-1 min-w-0">
          <p class="truncate text-sm font-semibold text-foreground leading-snug">{{ tenant.name }}</p>
          <div class="flex items-center gap-1 mt-0.5">
            <span class="text-[10px] text-muted-foreground">School Dashboard</span>
            <Badge
              v-if="tenant.plan === 'pro'"
              variant="secondary"
              class="h-3.5 px-1 text-[9px] leading-none"
            >PRO</Badge>
          </div>
        </div>
      </Link>
    </SidebarHeader>

    <!-- <SidebarSeparator /> -->

    <SidebarContent class="gap-0 overflow-hidden">

      <!-- Main -->
      <SidebarGroup class="pt-2">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">
          Main
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in mainMenu" :key="item.title">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive(item.href, item.exact)
                    ? 'bg-primary/10 text-primary font-semibold shadow-sm'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent',
                ]"
              >
                <Link :href="item.href" class="flex items-center gap-3 px-3 py-2">
                  <component
                    :is="item.icon"
                    :class="['h-4 w-4 shrink-0', isActive(item.href, item.exact) ? 'text-primary' : '']"
                  />
                  <span class="text-sm">{{ item.title }}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarSeparator class="my-1 mx-3" />

      <!-- Management -->
      <SidebarGroup class="pt-1">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">
          Management
        </SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in managementMenu" :key="item.title">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive(item.href)
                    ? 'bg-primary/10 text-primary font-semibold shadow-sm'
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
                tooltip="School Verification"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive('/dashboard/verification')
                    ? 'bg-primary/10 text-primary font-semibold shadow-sm'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent',
                ]"
              >
                <Link href="/dashboard/verification" class="flex items-center gap-3 px-3 py-2">
                  <component
                    :is="kycConfig.icon"
                    class="h-4 w-4 shrink-0"
                    :class="{
                      'text-emerald-500': tenant.kyc_status === 'verified',
                      'text-amber-500':   tenant.kyc_status === 'pending',
                      'text-destructive': tenant.kyc_status === 'rejected',
                    }"
                  />
                  <span class="text-sm group-data-[collapsible=icon]:hidden">Verification</span>
                  <!-- Status badge — hidden when sidebar collapsed -->
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

      <!-- Settings -->
      <SidebarGroup class="pt-1 pb-2">
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              as-child
              tooltip="Settings"
              :class="[
                'transition-all duration-150 rounded-lg mx-1',
                isActive('/dashboard/settings')
                  ? 'bg-primary/10 text-primary font-semibold'
                  : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent',
              ]"
            >
              <Link href="/dashboard/settings" class="flex items-center gap-3 px-3 py-2">
                <Settings class="h-4 w-4 shrink-0" />
                <span class="text-sm">Settings</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

    </SidebarContent>

    <!-- Footer: User info + logout -->
    <!-- <SidebarFooter class="p-3">
      <SidebarSeparator class="mb-2" />
      <div class="flex items-center gap-3 rounded-lg p-2 group-data-[collapsible=icon]:justify-center">
        <Avatar class="h-8 w-8 shrink-0 ring-2 ring-primary/20">
          <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
          <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">
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