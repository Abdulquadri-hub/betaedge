/**
 * useBatches Composable
 * 
 * Advanced Vue 3 Composition API pattern for batch management
 * Demonstrates:
 * - Reactive state management
 * - Computed properties with memoization
 * - Side effect handling
 * - Reusable business logic
 * - Security-first approach
 * 
 * Laravel Inertia Integration:
 * Replace mockBatches with Inertia page props
 * Example: const { batches } = usePage().props
 */

import { ref, computed, watch } from 'vue';
import {getBatchesByCourse, getBatchById } from '@/data/mockBatches';
import {
  canEnrollInBatch,
  detectScheduleConflicts,
  sanitizeBatchId,
  validateBatchSelections
} from '@/utils/batchHelpers';

export function useBatches(courseId = null) {
  // Reactive state
  const batches = ref([]);
  const loading = ref(false);
  const error = ref(null);

  /**
   * Fetch batches for a specific course
   * In production: This would be an API call
   */
  const fetchBatches = async (cId) => {
    // Security: Sanitize course ID
    const sanitizedCourseId = sanitizeBatchId(cId);
    if (!sanitizedCourseId) {
      error.value = 'Invalid course ID';
      return;
    }

    loading.value = true;
    error.value = null;

    try {
      // Simulate API delay (remove in production)
      await new Promise(resolve => setTimeout(resolve, 300));

      // Laravel Inertia Integration:
      // const response = await axios.get(`/api/courses/${sanitizedCourseId}/batches`);
      // batches.value = response.data;

      // Mock data for now
      batches.value = getBatchesByCourse(sanitizedCourseId);

      batches.value = batches.value.filter(batch => {
        return batch &&
          typeof batch.id === 'string' &&
          typeof batch.name === 'string' &&
          typeof batch.status === 'string';
      });

    } catch (err) {
      error.value = 'Failed to load batches';
      console.error('Batch fetch error:', err);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Computed: Available (enrollable) batches
   */
  const availableBatches = computed(() => {
    return batches.value.filter(batch => canEnrollInBatch(batch));
  });

  /**
   * Computed: Upcoming batches (not yet open)
   */
  const upcomingBatches = computed(() => {
    return batches.value.filter(batch => batch.status === 'upcoming');
  });

  /**
   * Computed: Full batches
   */
  const fullBatches = computed(() => {
    return batches.value.filter(batch => 
      batch.current_enrollment >= batch.max_students
    );
  });

  /**
   * Computed: Next available batch (earliest start date)
   */
  const nextAvailableBatch = computed(() => {
    if (availableBatches.value.length === 0) return null;

    return availableBatches.value.reduce((earliest, batch) => {
      const batchDate = new Date(batch.start_date);
      const earliestDate = new Date(earliest.start_date);
      return batchDate < earliestDate ? batch : earliest;
    });
  });

  /**
   * Computed: Has available batches
   */
  const hasAvailableBatches = computed(() => {
    return availableBatches.value.length > 0;
  });

  // Auto-fetch when courseId changes
  if (courseId) {
    watch(
      () => courseId,
      (newCourseId) => {
        if (newCourseId) {
          fetchBatches(newCourseId);
        }
      },
      { immediate: true }
    );
  }

  return {
    // State
    batches,
    loading,
    error,

    // Computed
    availableBatches,
    upcomingBatches,
    fullBatches,
    nextAvailableBatch,
    hasAvailableBatches,

    // Methods
    fetchBatches
  };
}

/**
 * useBatchSelection Composable
 * Manages batch selection state across multiple courses
 */
export function useBatchSelection(selectedCourses = []) {
  // Reactive state: { courseId: batchId }
  const selectedBatches = ref({});
  const conflicts = ref([]);

  /**
   * Select a batch for a course
   * @param {string} courseId - The course ID
   * @param {string} batchId - The batch ID
   */
  const selectBatch = (courseId, batchId) => {
    // Security: Sanitize inputs
    const sanitizedCourseId = sanitizeBatchId(courseId);
    const sanitizedBatchId = sanitizeBatchId(batchId);

    if (!sanitizedCourseId || !sanitizedBatchId) {
      console.warn('Invalid course or batch ID');
      return;
    }

    // Update selection
    selectedBatches.value = {
      ...selectedBatches.value,
      [sanitizedCourseId]: sanitizedBatchId
    };

    // Check for conflicts
    updateConflicts();
  };

  /**
   * Deselect batch for a course
   * @param {string} courseId - The course ID
   */
  const deselectBatch = (courseId) => {
    const sanitizedCourseId = sanitizeBatchId(courseId);
    if (!sanitizedCourseId) return;

    const { [sanitizedCourseId]: _, ...rest } = selectedBatches.value;
    selectedBatches.value = rest;

    updateConflicts();
  };

  /**
   * Check if a batch is selected for a course
   * @param {string} courseId - The course ID
   * @param {string} batchId - The batch ID
   * @returns {boolean} True if selected
   */
  const isSelected = (courseId, batchId) => {
    return selectedBatches.value[courseId] === batchId;
  };

  /**
   * Get selected batch ID for a course
   * @param {string} courseId - The course ID
   * @returns {string|null} Batch ID or null
   */
  const getSelectedBatch = (courseId) => {
    return selectedBatches.value[courseId] || null;
  };

  /**
   * Update schedule conflicts
   */
  const updateConflicts = () => {
    const selectedBatchObjects = Object.values(selectedBatches.value)
      .map(batchId => getBatchById(batchId))
      .filter(Boolean);

    conflicts.value = detectScheduleConflicts(selectedBatchObjects);
  };

  /**
   * Computed: All courses have batches selected
   */
  const allCoursesHaveBatches = computed(() => {
    if (!Array.isArray(selectedCourses) || selectedCourses.length === 0) {
      return false;
    }

    return selectedCourses.every(courseId => 
      selectedBatches.value[courseId]
    );
  });

  /**
   * Computed: Has schedule conflicts
   */
  const hasConflicts = computed(() => {
    return conflicts.value.length > 0;
  });

  /**
   * Computed: Selection count
   */
  const selectionCount = computed(() => {
    return Object.keys(selectedBatches.value).length;
  });

  /**
   * Validate selections before submission
   * @returns {boolean} True if valid
   */
  const validateSelections = () => {
    return validateBatchSelections(selectedBatches.value);
  };

  /**
   * Clear all selections
   */
  const clearSelections = () => {
    selectedBatches.value = {};
    conflicts.value = [];
  };

  /**
   * Reset to specific selections (useful for pre-filling from URL)
   * @param {Object} selections - { courseId: batchId }
   */
  const resetSelections = (selections) => {
    if (!validateBatchSelections(selections)) {
      console.warn('Invalid selections object');
      return;
    }

    selectedBatches.value = { ...selections };
    updateConflicts();
  };

  return {
    // State
    selectedBatches,
    conflicts,

    // Computed
    allCoursesHaveBatches,
    hasConflicts,
    selectionCount,

    // Methods
    selectBatch,
    deselectBatch,
    isSelected,
    getSelectedBatch,
    validateSelections,
    clearSelections,
    resetSelections
  };
}