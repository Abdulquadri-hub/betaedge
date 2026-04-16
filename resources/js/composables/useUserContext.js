import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useUserContext() {
  const { props } = usePage()

  const user = computed(() => props.auth?.user)
  const tenant = computed(() => props.tenant)
  const notifications = computed(() => props.notifications || [])

  const isOwner = computed(() => user.value?.user_type === 'school_owner')
  const isInstructor = computed(() => user.value?.user_type === 'instructor')
  const isStudent = computed(() => user.value?.user_type === 'student')

  const userInitials = computed(() => {
    if (!user.value?.name) return 'U'
    return user.value.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  })

  const tenantInitials = computed(() => {
    if (!tenant.value?.name) return 'T'
    return tenant.value.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  })

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