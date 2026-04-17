import { usePage, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useUserProfile() {
  const { props } = usePage()

  const user = computed(() => props.auth?.user)
  const tenant = computed(() => props.tenant)
  const verification = computed(() => props.verification)

  const isVerified = computed(() => verification.value?.status === 'verified')
  const isPending = computed(() => verification.value?.status === 'pending')
  const isRejected = computed(() => verification.value?.status === 'rejected')

  const verificationStatusLabel = computed(() => {
    if (isVerified.value) return 'Verified'
    if (isPending.value) return 'Pending Review'
    if (isRejected.value) return 'Rejected'
    return 'Not Verified'
  })

  const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
    phone: user.value?.phone || '',
  })

  const updateProfile = async () => {
    await profileForm.post('/dashboard/profile', {
      onSuccess: () => {
        profileForm.reset()
      },
    })
  }

  return {
    user,
    tenant,
    verification,
    isVerified,
    isPending,
    isRejected,
    verificationStatusLabel,
    profileForm,
    updateProfile,
  }
}
