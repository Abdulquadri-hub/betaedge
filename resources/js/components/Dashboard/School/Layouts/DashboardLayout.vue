<script setup>
// import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
// TODO (Laravel): import { router } from '@inertiajs/vue3'
import { Bell, LogOut, Settings, User, CheckCheck, BookOpen } from 'lucide-vue-next'
import { SidebarProvider, SidebarTrigger, SidebarInset } from '@/components/ui/sidebar'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
// import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Popover, PopoverContent, PopoverTrigger,
} from '@/components/ui/popover'
import AppSidebar from '../AppSidebar.vue'
import { useUserContext } from '@/composables/useUserContext'
import { Toaster } from 'vue-sonner'

// ─── Composable ───────────────────────────────────────────────
const {
  user, userInitials,
  notifications, unreadCount,
  markAllRead, markRead,
} = useUserContext()

// ─── Notification icon color by type ─────────────────────────
const notifIcon = {
  enrollment: { icon: User,     bg: 'bg-primary/10',    color: 'text-primary'  },
  session:    { icon: BookOpen, bg: 'bg-secondary/10',  color: 'text-secondary' },
  payment:    { icon: CheckCheck, bg: 'bg-emerald-100', color: 'text-emerald-600' },
  complaint:  { icon: Bell,     bg: 'bg-red-100',       color: 'text-red-600'  },
}

// ─── Actions ─────────────────────────────────────────────────
function handleLogout() {
  // TODO (Laravel): router.post('/logout')
  window.location.href = '/login'
}

function handleMarkRead(id) {
  markRead(id)
}
</script>

<template>
  <SidebarProvider>
    <div class="min-h-screen flex w-full bg-background">

      <!-- Sidebar -->
      <AppSidebar />

      <!-- Main -->
      <SidebarInset class="flex flex-col">

        <!-- ── Topbar ── -->
        <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card/95 backdrop-blur px-4">

          <!-- Sidebar collapse toggle -->
          <SidebarTrigger class="text-muted-foreground hover:text-foreground" />

          <!-- Divider -->
          <div class="hidden sm:block h-5 w-px bg-border" />

          <!-- Breadcrumb slot — pages can fill this via teleport or prop -->
          <div class="flex-1 min-w-0">
            <slot name="breadcrumb" />
          </div>

          <!-- ── Right side actions ── -->
          <div class="flex items-center gap-1">

            <!-- Notifications popover -->
            <Popover>
              <PopoverTrigger as-child>
                <Button variant="ghost" size="icon" class="relative h-9 w-9 text-muted-foreground hover:text-foreground">
                  <Bell class="h-4 w-4" />
                  <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-primary-foreground shadow"
                  >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                  </span>
                </Button>
              </PopoverTrigger>

              <PopoverContent class="w-80 p-0" align="end">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-border px-4 py-3">
                  <div>
                    <p class="text-sm font-semibold text-foreground">Notifications</p>
                    <p v-if="unreadCount > 0" class="text-xs text-muted-foreground">{{ unreadCount }} unread</p>
                  </div>
                  <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs text-primary hover:text-primary"
                    @click="markAllRead"
                  >
                    Mark all read
                  </Button>
                </div>

                <!-- List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-border">
                  <button
                    v-for="notif in notifications"
                    :key="notif.id"
                    class="w-full text-left flex items-start gap-3 px-4 py-3 hover:bg-muted/50 transition-colors"
                    :class="!notif.read ? 'bg-primary/5' : ''"
                    @click="handleMarkRead(notif.id)"
                  >
                    <!-- Icon -->
                    <div :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-full mt-0.5', notifIcon[notif.type]?.bg ?? 'bg-muted']">
                      <component
                        :is="notifIcon[notif.type]?.icon ?? Bell"
                        :class="['h-3.5 w-3.5', notifIcon[notif.type]?.color ?? 'text-muted-foreground']"
                      />
                    </div>

                    <!-- Text -->
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-medium text-foreground leading-snug">{{ notif.title }}</p>
                      <p class="text-xs text-muted-foreground mt-0.5 leading-snug truncate">{{ notif.message }}</p>
                      <p class="text-[10px] text-muted-foreground/70 mt-1">{{ notif.time }}</p>
                    </div>

                    <!-- Unread dot -->
                    <div v-if="!notif.read" class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                  </button>

                  <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
                    No notifications yet
                  </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-border px-4 py-2">
                  <Link href="/dashboard/notifications" class="text-xs text-primary hover:underline">
                    View all notifications →
                  </Link>
                </div>
              </PopoverContent>
            </Popover>

            <!-- User dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="relative h-9 w-9 rounded-full p-0">
                  <Avatar class="h-8 w-8 ring-2 ring-border">
                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">{{ userInitials }}</AvatarFallback>
                  </Avatar>
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent class="w-56" align="end">
                <DropdownMenuLabel class="font-normal pb-2">
                  <div class="flex items-center gap-2.5">
                    <Avatar class="h-8 w-8">
                      <AvatarImage v-if="user.avatar" :src="user.avatar" />
                      <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">{{ userInitials }}</AvatarFallback>
                    </Avatar>
                    <div class="flex flex-col min-w-0">
                      <p class="text-sm font-semibold leading-none truncate">{{ user.name }}</p>
                      <p class="text-xs text-muted-foreground leading-none mt-1 truncate">{{ user.email }}</p>
                    </div>
                  </div>
                </DropdownMenuLabel>

                <DropdownMenuSeparator />

                <DropdownMenuItem as-child>
                  <Link href="/dashboard/settings" class="flex cursor-pointer items-center">
                    <User class="mr-2 h-4 w-4" />Profile
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuItem as-child>
                  <Link href="/dashboard/settings" class="flex cursor-pointer items-center">
                    <Settings class="mr-2 h-4 w-4" />Settings
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem class="cursor-pointer text-destructive focus:text-destructive" @click="handleLogout">
                  <LogOut class="mr-2 h-4 w-4" />
                  Log out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

          </div>
        </header>

        <!-- ── Page content ── -->
        <main class="flex-1 overflow-auto">
          <slot />
          
        </main>
<!-- <Toaster position="bottom-left" class="z-1000"/> -->
      </SidebarInset>
    </div>
  </SidebarProvider>
</template>