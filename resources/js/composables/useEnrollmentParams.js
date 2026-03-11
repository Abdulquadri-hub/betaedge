import { ref, computed, watch } from "vue";
import { sanitizeBatchId } from "@/utils/batchHelpers";
import { usePage, router } from "@inertiajs/vue3";


export function useEnrollmentParams() {

    const { props } = usePage()


    const courseParam = ref(null)
    const batchParam = ref(null)

    /**
     * Get and validate url params
     */
    const parseParams = () => {
        // course param
        const courseFromUrl = props.query?.course
        if (courseFromUrl && typeof courseFromUrl === 'string') {
            courseParam.value = sanitizeBatchId(courseFromUrl)
        } else {
          courseParam.value = null
        }

        // batch param
        const batchFromUrl = props.query?.batch
        if (batchFromUrl && typeof batchFromUrl === 'string') {
            batchParam.value = batchFromUrl
        } else {
          batchParam.value = null
        }
    }

    /**
     * Update url with new parameters
     * maintain clean URLs without page reload
     */
    const updateParams = (params) => {
        const newQuery = {...props.query}


        // update course param
        if (params.course) {
            const sanitized = sanitizeBatchId(params.course)
            if (sanitized) {
                newQuery.course = sanitized
            }
        } else {
            delete newQuery.course
        }

        if (params.batch) {
            const sanitized = sanitizeBatchId(params.batch)
            if (sanitized) {
                newQuery.batch = sanitized
            }
        } else {
            delete newQuery.batch
        }

        const queryString = Object.keys(newQuery).length > 0 ? '?' + new URLSearchParams(newQuery).toString() : ''

        // update the URL without page reload
        router.replace(window.location.pathname + queryString,{
          preserveState: true,
          preserveScroll: true,
          only: ['query']
        })
        console.log(newQuery);
        

    }

    /**
     * Clear all enrollment from the URL
     */
    const clearParams = () => {
        const newQuery = {...props.query}
        delete newQuery.course
        delete newQuery.batch

        const queryString = Object.keys(newQuery).length > 0 ? '?' + new URLSearchParams(newQuery).toString() : ''

        router.replace(window.location.pathname + queryString,{
          preserveState: true,
          preserveScroll: true,
          only: ['query']
        })
    }

    /**
     * Generate shareable enrollment link
     */
    const generateEnrollmentLink = (courseId, batchId = null) => {
        const sanitizedCourse = sanitizeBatchId(courseId)
        if (!sanitizedCourse) return null

        const baseUrl = window.location.origin
        const currentPath = window.location.pathname
        const path =  currentPath.includes('/enroll') ? currentPath : `${currentPath}/enroll`

        let url = `${baseUrl}${path}?course=${sanitizedCourse}`

        if (batchId) {
            const sanitizeBatch = sanitizeBatchId(batchId)
            if (sanitizeBatch) {
                url += `&batch=${sanitizeBatch}`
            }
        }

        return url
    }

    /**
     * Copy Enrollement link to clipboard
     */
    const copyEnrollmentLink = async (courseId, batchId = null) => {
        const link = generateEnrollmentLink(courseId, batchId)
        if (!link) return false

        try {
            await navigator.clipboard.writeText(link)
            return true
        } catch(error) {
            console.log('Failed to copy link:', error);
            return false
        }
    }

    /**
     * Computed methods to check preselected course
     */
    const hasPreselectedCourse = computed(() => {
        return courseParam.value !== null
    })

    /**
     * Computed methods to check preselected batch
     */
    const hasPreselectedBatch = computed(() => {
        return batchParam.value  !== null
    })

    /**
     * Computed methods to check both course and batch preselected
     */
    const hasFullPreselection = computed(() => {
        return hasPreselectedCourse.value && hasPreselectedBatch.value
    })

      // Watch for route changes
  watch(
    () => props.query,
    () => {
      parseParams()
    },
    { immediate: true }
  )

  // Parse on mount
  parseParams()

  return {
    // State
    preselectedCourse: courseParam,
    preselectedBatch: batchParam,

    // Computed
    hasPreselectedCourse,
    hasPreselectedBatch,
    hasFullPreselection,

    // Methods
    updateParams,
    clearParams,
    generateEnrollmentLink,
    copyEnrollmentLink
  }
}

/**
 * UseEnrollmentTracking Composable
 * Tracking Enrollment step, conversions and more.
 */
export function useEnrollmentTracking() {
  /**
   * Track enrollment step
   */
  const trackStep = (step, data = {}) => {
    // In production: Send to analytics service
    // Example: Google Analytics, Mixpanel, etc.
    
    const event = {
      event: 'enrollment_step',
      step,
      timestamp: Date.now(),
      ...data
    }

    // Laravel Inertia Integration:
    // axios.post('/api/analytics/track', event)

    console.log('[Analytics] Enrollment Step:', event)
  }

  /**
   * Track enrollment conversion
   */
  const trackConversion = (data = {}) => {
    const event = {
      event: 'enrollment_completed',
      timestamp: Date.now(),
      ...data
    }

    // Laravel Inertia Integration:
    // axios.post('/api/analytics/conversion', event)

    console.log('[Analytics] Conversion:', event)
  }

  /**
   * Track batch selection
   */
  const trackBatchSelection = (courseId, batchId) => {
    trackStep('batch_selected', {
      course_id: courseId,
      batch_id: batchId
    })
  }

  /**
   * Track enrollment abandonment
   * lastStep - Last completed step
   */
  const trackAbandonment = (lastStep) => {
    const event = {
      event: 'enrollment_abandoned',
      last_step: lastStep,
      timestamp: Date.now()
    }

    console.log('[Analytics] Abandonment:', event)
  }

  return {
    trackStep,
    trackConversion,
    trackBatchSelection,
    trackAbandonment
  }
}