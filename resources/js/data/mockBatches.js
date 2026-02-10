/**
 * Mock Batch Data
 * 
 * Laravel Inertia Integration:
 * This will be replaced by backend data via Inertia props
 * Backend should implement:
 * - Batch availability filtering (open, upcoming, full, closed)
 * - Real-time enrollment counting
 * - Schedule conflict detection
 * - Date validation (start_date < end_date)
 */

export const mockBatches = [
  // Primary 4 Mathematics Batches
  {
    id: 'batch_pm4_jan_2025',
    course_id: '1',
    name: 'January 2025 Batch',
    start_date: '2025-01-15',
    end_date: '2025-04-07',
    max_students: 30,
    current_enrollment: 23,
    status: 'open', // 'open' | 'upcoming' | 'full' | 'closed' | 'completed'
    schedule: {
      day_of_week: 'friday',
      time: '16:00', // 24-hour format
      duration_minutes: 90
    },
    opens_at: null, // When registration opens (null = already open)
    whatsapp_group: null // Will be populated after enrollment
  },
  {
    id: 'batch_pm4_apr_2025',
    course_id: '1',
    name: 'April 2025 Batch',
    start_date: '2025-04-14',
    end_date: '2025-07-06',
    max_students: 30,
    current_enrollment: 8,
    status: 'open',
    schedule: {
      day_of_week: 'friday',
      time: '16:00',
      duration_minutes: 90
    },
    opens_at: null
  },
  {
    id: 'batch_pm4_jul_2025',
    course_id: '1',
    name: 'July 2025 Batch',
    start_date: '2025-07-14',
    end_date: '2025-10-05',
    max_students: 30,
    current_enrollment: 0,
    status: 'upcoming',
    schedule: {
      day_of_week: 'friday',
      time: '16:00',
      duration_minutes: 90
    },
    opens_at: '2025-06-01'
  },
  {
    id: 'batch_pm4_oct_2025',
    course_id: '1',
    name: 'October 2025 Batch',
    start_date: '2025-10-13',
    end_date: '2026-01-04',
    max_students: 30,
    current_enrollment: 0,
    status: 'upcoming',
    schedule: {
      day_of_week: 'friday',
      time: '16:00',
      duration_minutes: 90
    },
    opens_at: '2025-09-01'
  },

  // English Language Batches
  {
    id: 'batch_eng_feb_2025',
    course_id: '2',
    name: 'February 2025 Batch',
    start_date: '2025-02-01',
    end_date: '2025-04-20',
    max_students: 25,
    current_enrollment: 25,
    status: 'full',
    schedule: {
      day_of_week: 'saturday',
      time: '10:00',
      duration_minutes: 90
    },
    opens_at: null
  },
  {
    id: 'batch_eng_may_2025',
    course_id: '2',
    name: 'May 2025 Batch',
    start_date: '2025-05-03',
    end_date: '2025-07-26',
    max_students: 25,
    current_enrollment: 12,
    status: 'open',
    schedule: {
      day_of_week: 'saturday',
      time: '10:00',
      duration_minutes: 90
    },
    opens_at: null
  },
  {
    id: 'batch_eng_aug_2025',
    course_id: '2',
    name: 'August 2025 Batch',
    start_date: '2025-08-02',
    end_date: '2025-10-25',
    max_students: 25,
    current_enrollment: 0,
    status: 'upcoming',
    schedule: {
      day_of_week: 'saturday',
      time: '10:00',
      duration_minutes: 90
    },
    opens_at: '2025-07-01'
  },

  // Physics Fundamentals Batches
  {
    id: 'batch_phy_mar_2025',
    course_id: '3',
    name: 'March 2025 Batch',
    start_date: '2025-03-10',
    end_date: '2025-06-02',
    max_students: 20,
    current_enrollment: 18,
    status: 'open',
    schedule: {
      day_of_week: 'monday',
      time: '18:00',
      duration_minutes: 120
    },
    opens_at: null
  },
  {
    id: 'batch_phy_jun_2025',
    course_id: '3',
    name: 'June 2025 Batch',
    start_date: '2025-06-09',
    end_date: '2025-09-01',
    max_students: 20,
    current_enrollment: 5,
    status: 'open',
    schedule: {
      day_of_week: 'monday',
      time: '18:00',
      duration_minutes: 120
    },
    opens_at: null
  },

  // Chemistry Basics Batches
  {
    id: 'batch_chem_jan_2025',
    course_id: '4',
    name: 'January 2025 Batch',
    start_date: '2025-01-20',
    end_date: '2025-04-14',
    max_students: 25,
    current_enrollment: 16,
    status: 'open',
    schedule: {
      day_of_week: 'wednesday',
      time: '17:00',
      duration_minutes: 90
    },
    opens_at: null
  },
  {
    id: 'batch_chem_may_2025',
    course_id: '4',
    name: 'May 2025 Batch',
    start_date: '2025-05-05',
    end_date: '2025-07-28',
    max_students: 25,
    current_enrollment: 3,
    status: 'open',
    schedule: {
      day_of_week: 'wednesday',
      time: '17:00',
      duration_minutes: 90
    },
    opens_at: null
  },

  // Biology Batches
  {
    id: 'batch_bio_feb_2025',
    course_id: '5',
    name: 'February 2025 Batch',
    start_date: '2025-02-10',
    end_date: '2025-05-05',
    max_students: 30,
    current_enrollment: 27,
    status: 'open',
    schedule: {
      day_of_week: 'thursday',
      time: '16:30',
      duration_minutes: 90
    },
    opens_at: null
  },
  {
    id: 'batch_bio_jun_2025',
    course_id: '5',
    name: 'June 2025 Batch',
    start_date: '2025-06-12',
    end_date: '2025-09-04',
    max_students: 30,
    current_enrollment: 0,
    status: 'upcoming',
    schedule: {
      day_of_week: 'thursday',
      time: '16:30',
      duration_minutes: 90
    },
    opens_at: '2025-05-15'
  }
];

// Get batches for a specific course
export const getBatchesByCourse = (courseId) => {
    if (!courseId) return [];
    return mockBatches.filter(batch => batch.course_id === courseId);
}

// get a single batch by ID
export const getBatchById = (batchId) => {
    if (!batchId) return null
    return mockBatches.find(batch => batch.id === batchId) || null
}

// Get available (open) batches for a course
export const getAvailableBatches = (courseId) => {
    if (!courseId) return []
    return mockBatches.filter(
        batch => batch.status === 'open' && 
        batch.current_enrollment < batch.max_students
    )
}

// get available batch for a course 
export const getNextAvailableBatch = (courseId) => {
    if (!courseId) return null
    
    const available = getAvailableBatches(courseId)
    if (available.length  === 0) return null

    return available.sort(
        (a, b) => new Date(a.start_date) - new Date(b.start_date)
    )[0]
}

export const getCurrentActiveBatch = (courseId) => {
  const batches = getBatchesByCourse(courseId);
  
  // Get published batches that can accept enrollment
  const publishedBatches = batches.filter(
    batch => batch.status === 'open' && batch.current_enrollment < batch.max_students
  );
  
  if (publishedBatches.length === 0) return null;
  
  // Return earliest starting batch
  return publishedBatches.sort((a, b) => 
    new Date(a.start_date) - new Date(b.start_date)
  )[0];
};
