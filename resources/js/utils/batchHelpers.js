
/**
 * Sanitize and validate batch ID
*/
export const sanitizeBatchId = (batchId) => {
    if (!batchId || typeof batchId !== 'string') return null

    const sanitized = batchId.replace(/[^a-zA-Z0-9_-]/g, '');
  
    if (sanitized.length > 100 || sanitized.length === 0) return null;
    
    return sanitized;
}

/**
 * Format date to readable string
*/
export const formatDate = (dateString) => {
    if (!dateString) return ''

    const date = new Date(dateString)
    if (isNaN(date.getTime())) return ''

    const options = {year: 'numeric',month: 'short',day: 'numeric'}
    return date.toLocaleDateString(undefined, options)
}

/**
 * Calculate spots remaining in a batch
 */
export const getSpotsRemaining = (batch) => {
    if (!batch || typeof batch.max_students !== 'number')
        return 0
    const remaining = (
        batch.max_students - (batch.current_enrollment || 0)
    )
    return Math.max(0, remaining)
}

/**
 * Check if batch is almost full (< 5 spots or > 80% capacity)
 */
export const isAlmostFull = (batch) => {
  if (!batch) return false;
  
  const spotsRemaining = getSpotsRemaining(batch);
  const percentFilled = (batch.current_enrollment / batch.max_students) * 100;
  
  return spotsRemaining < 5 || percentFilled > 80;
};

/**
 * Check if batch is full
 */
export const isFull = (batch) => {
  if (!batch) return false;
  return batch.current_enrollment >= batch.max_students;
};

/**
 * calculate days until batch starts
 */

export const getDaysUntilStart = (startDate) => {
    if (!startDate) return null

    const start = new Date(startDate)
    const now = new Date()

    // validate date
    if (isNaN(start.getTime()))  return null

    const diffTime = start - now // this return in milliseconds
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    return diffDays
}

/**
 * Check if batch start soons (< 14 days)
 */
export const startsSoon = (startDate) => {
    const days = getDaysUntilStart(startDate)
    return days !== null && days > 0 && days <= 12
}

/**
 * Format time from 24-hour to 12-hour with AM/PM
 */
export const formatTime = (time24) => {
    if (!time24 || typeof time24 !== 'string') return ''

    const [hours, minutes] = time24.split(':').map(Number)

    if (isNaN(hours)  || isNaN(minutes)) return ''

    const period = hours >= 12 ? 'PM' : 'AM'
    const hour12 = hours % 12 || 12
    
    return `${hour12}:${minutes.toString().padStart(2, '0')} ${period}`;
}

/**
 * Format day of week to tittle case
*/

export const formatDayOfWeek = (day) => {
  if (!day || typeof day !== 'string') return '';
  return day.charAt(0).toUpperCase() + day.slice(1).toLowerCase();
};


/**
 * Get batch status badge info
 */
export const getBatchStatusBadge = (batch) => {
  if (!batch) return { variant: 'secondary', text: 'Unknown', icon: null };
  
  // Full batch
  if (isFull(batch)) {
    return {
      variant: 'destructive',
      text: 'Full',
      icon: 'users-x'
    };
  }
  
  // Almost full
  if (isAlmostFull(batch) && batch.status === 'open') {
    return {
      variant: 'warning',
      text: 'Almost Full',
      icon: 'alert-circle'
    };
  }
  
  // Starts soon
  if (startsSoon(batch.start_date) && batch.status === 'open') {
    return {
      variant: 'info',
      text: 'Starts Soon',
      icon: 'clock'
    };
  }
  
  // Open for enrollment
  if (batch.status === 'open') {
    return {
      variant: 'success',
      text: 'Enrolling Now',
      icon: 'check-circle'
    };
  }
  
  // Upcoming (not yet open)
  if (batch.status === 'upcoming') {
    return {
      variant: 'secondary',
      text: 'Upcoming',
      icon: 'calendar'
    };
  }
  
  // Closed
  if (batch.status === 'closed') {
    return {
      variant: 'secondary',
      text: 'Closed',
      icon: 'lock'
    };
  }
  
  // Completed
  if (batch.status === 'completed') {
    return {
      variant: 'secondary',
      text: 'Completed',
      icon: 'check'
    };
  }
  
  // Default
  return {
    variant: 'secondary',
    text: 'Unknown',
    icon: null
  };
};

/**
 * Get urgency message for a batch
 */
export const getUrgencyMessage = (batch) => {
  if (!batch || batch.status !== 'open') return null;
  
  const spotsRemaining = getSpotsRemaining(batch);
  
  if (spotsRemaining === 0) {
    return 'This batch is now full';
  }
  
  if (spotsRemaining === 1) {
    return 'Only 1 spot left!';
  }
  
  if (spotsRemaining <= 3) {
    return `Only ${spotsRemaining} spots left!`;
  }
  
  if (spotsRemaining <= 5) {
    return `Only ${spotsRemaining} spots remaining`;
  }
  
  const daysUntil = getDaysUntilStart(batch.start_date);
  if (daysUntil !== null && daysUntil > 0 && daysUntil <= 7) {
    return `Starts in ${daysUntil} day${daysUntil === 1 ? '' : 's'}!`;
  }
  
  return null;
};

/**
 * Check if batch can be enrolled in
 */
export const canEnrollInBatch = (batch) => {
  if (!batch) return false;
  
  return batch.status === 'open' && !isFull(batch);
};

/**
 * Detect schedule conflicts between selected batches
*/
export const detectScheduleConflicts = (batches) => {
  if (!Array.isArray(batches) || batches.length < 2) return [];
  
  const conflicts = [];
  
  for (let i = 0; i < batches.length; i++) {
    for (let j = i + 1; j < batches.length; j++) {
      const batch1 = batches[i];
      const batch2 = batches[j];
      
      // Check if same day and overlapping time
      if (batch1.schedule?.day_of_week === batch2.schedule?.day_of_week) {
        conflicts.push({
          batch1: batch1.name,
          batch2: batch2.name,
          conflict: `Both classes on ${formatDayOfWeek(batch1.schedule.day_of_week)}`
        });
      }
    }
  }
  
  return conflicts;
};

/**
 * Validate batch selection object
 * Security check for malicious data injection
 */
export const validateBatchSelections = (selections) => {
  if (!selections || typeof selections !== 'object') return false;
  
  // Check all keys and values are valid strings
  for (const [courseId, batchId] of Object.entries(selections)) {
    if (!sanitizeBatchId(courseId) || !sanitizeBatchId(batchId)) {
      return false;
    }
  }
  
  return true;
};