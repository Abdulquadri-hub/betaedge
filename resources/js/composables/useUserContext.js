import { ref, computed } from 'vue'
// TODO (Laravel): import { usePage } from '@inertiajs/vue3'

/**
 * useUserContext
 * ─────────────────────────────────────────────────────────────
 * Single source of truth for the authenticated user, their role,
 * and the current tenant (school).
 *
 * Laravel Integration (when ready):
 *   const { auth } = usePage().props
 *   Replace mock refs below with:
 *     const user   = computed(() => auth.user)
 *     const tenant = computed(() => auth.tenant)
 *
 *   Backend shares data via HandleInertiaRequests middleware:
 *     'auth' => [
 *       'user'   => auth()->user(),
 *       'tenant' => currentTenant(),
 *     ]
 */

// ─── Mock Data ────────────────────────────────────────────────
const MOCK_USER = {
  id:     'user-001',
  name:   'Tunde Adeyemi',
  email:  'tunde@brightstars.teach.com',
  avatar: null,
  role:   'owner', // 'owner' | 'instructor' | 'student' | 'parent'
  phone:  '+234 801 234 5678',
  joinedAt: '2024-01-15',
}

const MOCK_TENANT = {
  id:        'tenant-001',
  name:      'Bright Stars Academy',
  subdomain: 'brightstars',
  logo:      null,
  plan:      'pro',          // 'starter' | 'pro' | 'enterprise'
  currency:  '₦',
}

// ─── Notifications mock ───────────────────────────────────────
const MOCK_NOTIFICATIONS = [
  { id: 1, title: 'New enrollment request', message: 'Ada Okonkwo enrolled in Web Dev Batch 3', time: '2 min ago', read: false, type: 'enrollment' },
  { id: 2, title: 'Session starting soon',  message: 'JavaScript Basics starts in 30 minutes', time: '28 min ago', read: false, type: 'session' },
  { id: 3, title: 'Payment received',       message: '₦20,000 received from Emeka Nwosu',      time: '1 hr ago',  read: false, type: 'payment' },
]
// ─────────────────────────────────────────────────────────────

export function useUserContext() {
  // TODO (Laravel): replace with usePage().props.auth.user
  const user   = ref(MOCK_USER)
  const tenant = ref(MOCK_TENANT)
  const notifications = ref(MOCK_NOTIFICATIONS)

  // ── Derived booleans ─────────────────────────────────────
  const isOwner      = computed(() => user.value?.role === 'owner')
  const isInstructor = computed(() => user.value?.role === 'instructor')
  const isStudent    = computed(() => user.value?.role === 'student')

  // ── Display helpers ───────────────────────────────────────
  /** "Tunde Adeyemi" → "TA" */
  const userInitials = computed(() => {
    if (!user.value?.name) return 'U'
    return user.value.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  })

  /** "Bright Stars Academy" → "BS" */
  const tenantInitials = computed(() => {
    if (!tenant.value?.name) return 'T'
    return tenant.value.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  })

  // ── Notification helpers ──────────────────────────────────
  const unreadCount = computed(
    () => notifications.value.filter(n => !n.read).length
  )

  function markAllRead() {
    notifications.value = notifications.value.map(n => ({ ...n, read: true }))
  }

  function markRead(id) {
    const n = notifications.value.find(n => n.id === id)
    if (n) n.read = true
  }

  return {
    user,
    tenant,
    notifications,
    isOwner,
    isInstructor,
    isStudent,
    userInitials,
    tenantInitials,
    unreadCount,
    markAllRead,
    markRead,
  }
}