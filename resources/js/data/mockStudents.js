/**
 * mockStudents.js
 * Mock student data for development.
 * TODO (Laravel): Replace with API call → /dashboard/students (Inertia shared props)
 */

export const mockStudents = [
  {
    id: 'student-1',
    name: 'Amara Okafor',
    email: 'amara.okafor@gmail.com',
    phone: '+2348012345678',
    avatar: null,
    type: 'adult', // 'adult' | 'child'
    parentId: null,
    enrolledCourses: ['course-1', 'course-2'],
    enrolledBatches: ['batch-1'],
    joinedAt: '2025-01-10T08:00:00Z',
    status: 'active', // 'active' | 'inactive' | 'suspended'
  },
  {
    id: 'student-2',
    name: 'Chidi Nwosu',
    email: 'chidi.nwosu@gmail.com',
    phone: '+2348023456789',
    avatar: null,
    type: 'adult',
    parentId: null,
    enrolledCourses: ['course-1'],
    enrolledBatches: ['batch-1', 'batch-2'],
    joinedAt: '2025-01-15T08:00:00Z',
    status: 'active',
  },
  {
    id: 'student-3',
    name: 'Zara Ibrahim',
    email: null,
    phone: null,
    avatar: null,
    type: 'child',
    parentId: 'parent-1',
    enrolledCourses: ['course-2'],
    enrolledBatches: ['batch-2'],
    joinedAt: '2025-02-01T08:00:00Z',
    status: 'active',
  },
  {
    id: 'student-4',
    name: 'Emeka Eze',
    email: 'emeka.eze@gmail.com',
    phone: '+2348034567890',
    avatar: null,
    type: 'adult',
    parentId: null,
    enrolledCourses: ['course-1', 'course-3'],
    enrolledBatches: ['batch-3'],
    joinedAt: '2025-02-10T08:00:00Z',
    status: 'inactive',
  },
  {
    id: 'student-5',
    name: 'Fatima Bello',
    email: null,
    phone: null,
    avatar: null,
    type: 'child',
    parentId: 'parent-2',
    enrolledCourses: ['course-3'],
    enrolledBatches: ['batch-3'],
    joinedAt: '2025-02-12T08:00:00Z',
    status: 'active',
  },
]

export const mockParents = [
  {
    id: 'parent-1',
    name: 'Mrs. Hauwa Ibrahim',
    email: 'hauwa.ibrahim@gmail.com',
    phone: '+2348045678901',
    children: ['student-3'],
  },
  {
    id: 'parent-2',
    name: 'Mr. Usman Bello',
    email: 'usman.bello@gmail.com',
    phone: '+2348056789012',
    children: ['student-5'],
  },
]