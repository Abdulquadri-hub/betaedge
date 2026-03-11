/**
 * mockInstructors.js
 * Mock instructor data for development.
 * TODO (Laravel): Replace with Inertia shared props
 */

export const mockInstructors = [
  {
    id: 'instructor-1',
    name: 'Dr. Seun Alade',
    email: 'seun.alade@gmail.com',
    phone: '+2348067890123',
    avatar: null,
    specialization: 'Mathematics',
    bio: 'PhD in Applied Mathematics. 8 years teaching experience.',
    status: 'active', // 'active' | 'inactive' | 'pending'
    joinedAt: '2024-11-01T08:00:00Z',
    assignedBatches: ['batch-1', 'batch-3'],
    // Payment agreement
    paymentTerms: {
      structure: 'per_batch', // 'per_batch' | 'per_student' | 'monthly' | 'custom'
      perBatchAmount: 25000,
      notes: 'Paid within 5 days of batch completion.',
    },
    // Permissions granted by school owner
    permissions: [
      'host_live_sessions',
      'grade_assignments',
      'view_analytics',
      'message_students',
    ],
  },
  {
    id: 'instructor-2',
    name: 'Miss Ngozi Obi',
    email: 'ngozi.obi@gmail.com',
    phone: '+2348078901234',
    avatar: null,
    specialization: 'English Language',
    bio: 'B.Ed English. Passionate about literacy and communication.',
    status: 'active',
    joinedAt: '2024-12-05T08:00:00Z',
    assignedBatches: ['batch-2'],
    paymentTerms: {
      structure: 'per_student',
      perStudentAmount: 2500,
      notes: '',
    },
    permissions: [
      'host_live_sessions',
      'grade_assignments',
      'upload_content',
      'message_students',
    ],
  },
  {
    id: 'instructor-3',
    name: 'Mr. Biodun Taiwo',
    email: 'biodun.taiwo@gmail.com',
    phone: '+2348089012345',
    avatar: null,
    specialization: 'Basic Science',
    bio: 'BSc Chemistry. Junior secondary specialist.',
    status: 'pending', // Invite sent, not yet accepted
    joinedAt: null,
    assignedBatches: [],
    paymentTerms: {
      structure: 'monthly',
      monthlyAmount: 120000,
      notes: 'Probation period: 3 months.',
    },
    permissions: [
      'host_live_sessions',
      'grade_assignments',
    ],
  },
]