<script setup>

import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Bell, LogOut, Settings, Building2,
  ChevronDown, CheckCircle2, Clock, ArrowLeftRight, UsersRound,
} from 'lucide-vue-next'
import { SidebarProvider, SidebarTrigger, SidebarInset } from '@/components/ui/sidebar'
import { Button } from '@/components/ui/button'
import { Badge }  from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Popover, PopoverContent, PopoverTrigger,
} from '@/components/ui/popover'
import InstructorSidebar from '../InstructorSidebar.vue'
import { useUserContext } from '@/composables/useUserContext'
import { useInstructorDashboard } from '@/composables/useInstructorDashboard'
import { Toaster } from 'vue-sonner'

const { user, userInitials, unreadCount } = useUserContext()
const { schools, currentSchool, switchSchool } = useInstructorDashboard()

const topbarSwitcherOpen = ref(false)
const isSwitching        = ref(false)

async function handleSwitch(school) {
  if (school.tenant_id === currentSchool.value.tenant_id) {
    topbarSwitcherOpen.value = false
    return
  }
  isSwitching.value = true
  await switchSchool(school.tenant_id)
  // TODO (Laravel 12): router.post(route('instructor.switchSchool', school.tenant_id), {}, {
  //   onSuccess: () => { window.location.href = '/instructor' }
  // })
  isSwitching.value        = false
  topbarSwitcherOpen.value = false
}

function handleLogout() {
  // TODO (Laravel): router.post('/logout')
  window.location.href = '/login'
}
</script>

<template>
  <SidebarProvider>
    <div class="min-h-screen flex w-full bg-background">

      <InstructorSidebar />

      <SidebarInset class="flex flex-col">

        <!-- Topbar -->
        <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card/95 backdrop-blur px-4">

          <SidebarTrigger class="text-muted-foreground hover:text-foreground" />

          <div class="hidden sm:block h-5 w-px bg-border" />

          <!-- ── School switcher pill (topbar) ────────────────────────────── -->
          <!-- <Popover :open="topbarSwitcherOpen" @update:open="topbarSwitcherOpen = $event">
            <PopoverTrigger as-child>
              <button
                class="hidden sm:flex items-center gap-2 rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-left transition-all hover:border-secondary/40 hover:bg-secondary/5 focus:outline-none"
                :class="topbarSwitcherOpen ? 'border-secondary/40 bg-secondary/5' : ''"
              >
                <Building2 class="h-3.5 w-3.5 text-secondary shrink-0" />
                <span class="text-xs font-semibold text-foreground leading-none max-w-[130px] truncate">
                  {{ currentSchool.name }}
                </span>
                <ChevronDown
                  class="h-3 w-3 text-muted-foreground transition-transform shrink-0"
                  :class="topbarSwitcherOpen ? 'rotate-180' : ''"
                />
              </button>
            </PopoverTrigger>

            <PopoverContent class="w-72 p-2 shadow-xl" align="start" :side-offset="8">
              <div class="px-2 pb-2">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Switch School</p>
                <p class="text-[10px] text-muted-foreground mt-0.5">You teach at {{ schools.length }} school{{ schools.length !== 1 ? 's' : '' }}</p>
              </div>

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
                        {{ school.active_batches }} active batch{{ school.active_batches !== 1 ? 'es' : '' }}
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

              <div class="mt-2 border-t border-border pt-2 px-1">
                <p class="text-[10px] text-muted-foreground">
                  Switching shows that school's batches, students, and earnings.
                </p>
              </div>
            </PopoverContent>
          </Popover> -->

          <!-- Breadcrumb slot -->
          <div class="flex-1 min-w-0 hidden md:block">
            <slot name="breadcrumb" />
          </div>

          <div class="flex items-center gap-1 ml-auto">

            <!-- Notifications -->
            <Button variant="ghost" size="icon" class="relative h-9 w-9 text-muted-foreground hover:text-foreground">
              <Bell class="h-4 w-4" />
              <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-secondary text-[9px] font-bold text-secondary-foreground shadow"
              >
                {{ unreadCount }}
              </span>
            </Button>

            <!-- User dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="relative h-9 w-9 rounded-full p-0">
                  <Avatar class="h-8 w-8 ring-2 ring-border">
                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback class="bg-secondary/10 text-secondary text-xs font-bold">{{ userInitials }}</AvatarFallback>
                  </Avatar>
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent class="w-56" align="end">
                <DropdownMenuLabel class="font-normal pb-2">
                  <div class="flex items-center gap-2.5">
                    <Avatar class="h-8 w-8">
                      <AvatarFallback class="bg-secondary/10 text-secondary text-xs font-bold">{{ userInitials }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold truncate">{{ user.name }}</p>
                      <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                    </div>
                  </div>
                </DropdownMenuLabel>

                <!-- Current school indicator -->
                <div class="mx-2 mb-1 flex items-center gap-2 rounded-lg bg-secondary/10 px-2.5 py-1.5 text-xs">
                  <Building2 class="h-3.5 w-3.5 text-secondary shrink-0" />
                  <span class="font-medium text-secondary truncate">{{ currentSchool.name }}</span>
                </div>

                <DropdownMenuSeparator />

                <DropdownMenuItem as-child>
                  <Link href="/instructor/profile" class="flex cursor-pointer items-center">
                    <Settings class="mr-2 h-4 w-4" />My Profile
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <Link href="/instructor/earnings" class="flex cursor-pointer items-center">
                    <Building2 class="mr-2 h-4 w-4" />Earnings
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem class="cursor-pointer text-destructive focus:text-destructive" @click="handleLogout">
                  <LogOut class="mr-2 h-4 w-4" />Log out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

          </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-auto">
          <Toaster position="top-right" />
          <slot />
        </main>

      </SidebarInset>
    </div>
  </SidebarProvider>
</template>