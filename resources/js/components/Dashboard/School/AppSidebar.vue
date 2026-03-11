<script setup>
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
} from 'lucide-vue-next'
import { useUserContext } from '@/composables/useUserContext'

const { user, tenant, tenantInitials, userInitials, 
  //unreadCount
 } = useUserContext()

const mainMenu = [
  { title: 'Dashboard',     href: '/dashboard',               icon: LayoutDashboard, exact: true },
  { title: 'Courses',       href: '/dashboard/courses',       icon: BookOpen },
  { title: 'Batches',       href: '/dashboard/batches',       icon: UsersRound },
  { title: 'Students',      href: '/dashboard/students',      icon: Users },
  { title: 'Parents',       href: '/dashboard/parents',       icon: Baby },
  { title: 'Instructors',   href: '/dashboard/instructors',   icon: GraduationCap },
  { title: 'Live Sessions', href: '/dashboard/live-sessions', icon: Video },
  { title: 'Enrollments',   href: '/dashboard/enrollments',   icon: ClipboardList },
]

const managementMenu = [
  { title: 'Complaints',   href: '/dashboard/complaints',   icon: MessageSquareWarning },
  { title: 'Certificates', href: '/dashboard/certificates', icon: Award },
  { title: 'Reports',      href: '/dashboard/reports',      icon: BarChart3 },
  { title: 'Financials',   href: '/dashboard/financials',   icon: Wallet },
]

const page = usePage()

function isActive(item) {
  const current = page.url
  if (item.exact) return current === item.href || current === item.href + '/'
  return current.startsWith(item.href)
}
</script>

<template>
  <Sidebar collapsible="icon">

    <!-- Header -->
    <SidebarHeader class="p-3">
      <Link href="/dashboard" class="flex items-center gap-3 rounded-lg p-1 transition-colors hover:bg-sidebar-accent">
        <!-- Tenant logo / initials -->
        <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary shadow-sm">
          <img v-if="tenant.logo" :src="tenant.logo" :alt="tenant.name" class="h-full w-full rounded-lg object-cover" />
          <span v-else class="text-xs font-bold text-primary-foreground tracking-wide">{{ tenantInitials }}</span>
        </div>

        <!-- Name — hidden when collapsed -->
        <div class="group-data-[collapsible=icon]:hidden flex-1 min-w-0">
          <p class="truncate text-sm font-semibold text-foreground leading-snug">{{ tenant.name }}</p>
          <div class="flex items-center gap-1 mt-0.5">
            <span class="text-[10px] text-muted-foreground">School Dashboard</span>
            <Badge v-if="tenant.plan === 'pro'" variant="secondary" class="h-3.5 px-1 text-[9px] leading-none">PRO</Badge>
          </div>
        </div>
      </Link>
    </SidebarHeader>

    <SidebarSeparator />

    <SidebarContent class="gap-0">

      <!-- Main Menu -->
      <SidebarGroup class="pt-2">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">Main</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in mainMenu" :key="item.title">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive(item)
                    ? 'bg-primary/10 text-primary font-semibold shadow-sm'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent'
                ]"
              >
                <Link :href="item.href" class="flex items-center gap-3 px-3 py-2">
                  <component
                    :is="item.icon"
                    :class="['h-4 w-4 shrink-0', isActive(item) ? 'text-primary' : '']"
                  />
                  <span class="text-sm">{{ item.title }}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarSeparator class="my-1 mx-3" />

      <!-- Management Menu -->
      <SidebarGroup class="pt-1">
        <SidebarGroupLabel class="text-[10px] uppercase tracking-widest text-muted-foreground/60 px-3">Management</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in managementMenu" :key="item.title">
              <SidebarMenuButton
                as-child
                :tooltip="item.title"
                :class="[
                  'transition-all duration-150 rounded-lg mx-1',
                  isActive(item)
                    ? 'bg-primary/10 text-primary font-semibold shadow-sm'
                    : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent'
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

      <!-- Settings -->
      <SidebarGroup class="pt-1">
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              as-child
              tooltip="Settings"
              :class="[
                'transition-all duration-150 rounded-lg mx-1',
                isActive({ href: '/dashboard/settings' })
                  ? 'bg-primary/10 text-primary font-semibold'
                  : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent'
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
    <SidebarFooter class="p-3">
      <SidebarSeparator class="mb-2" />
      <div class="flex items-center gap-3 rounded-lg p-2 group-data-[collapsible=icon]:justify-center">
        <Avatar class="h-8 w-8 shrink-0 ring-2 ring-primary/20">
          <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
          <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">{{ userInitials }}</AvatarFallback>
        </Avatar>
        <div class="group-data-[collapsible=icon]:hidden flex-1 min-w-0">
          <p class="text-sm font-medium text-foreground truncate leading-snug">{{ user.name }}</p>
          <p class="text-[11px] text-muted-foreground truncate">{{ user.email }}</p>
        </div>
        <!-- TODO (Laravel): replace with <Link href="/logout" method="post" as="button"> -->
        <a href="/logout" class="group-data-[collapsible=icon]:hidden text-muted-foreground hover:text-destructive transition-colors shrink-0" title="Logout">
          <LogOut class="h-4 w-4" />
        </a>
      </div>
    </SidebarFooter>

  </Sidebar>
</template>