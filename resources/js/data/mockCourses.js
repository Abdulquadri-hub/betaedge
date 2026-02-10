/**
 * Mock Course Data - V3 (Live Classes Only)
 * 
 * Changes from V2:
 * - Removed: learningType (hybrid/self_paced/live_classes)
 * - Removed: selfPacedPrice, liveClassPrice
 * - Added: Single price field
 * - All courses are live classes with batch system
 * 
 * Laravel Inertia Integration:
 * Backend should sanitize all HTML content in descriptions
 * Use htmlspecialchars() or Purifier for XSS prevention
 */

export const mockCourses = [
  {
    id: '1',
    title: 'Advanced Mathematics',
    description: 'Master calculus, algebra, and geometry with practical applications',
    price: 25000, // Single price - all courses are live classes
    duration: '12 weeks',
    level: 'Advanced',
    enrolledCount: 45,
    rating: 4.8,
    reviewCount: 32,
    image: 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=400',
    instructor: {
      id: '1',
      name: 'Dr. Adaora Nwosu',
      avatar: '',
      title: 'Mathematics Department Head',
      bio: '15+ years of experience teaching mathematics at various levels. PhD in Applied Mathematics from University of Lagos.'
    },
    features: [
      '12 weeks of live classes',
      'Weekly interactive sessions',
      'Personalized feedback on assignments',
      'Certificate upon completion',
      'Lifetime access to recordings',
      'WhatsApp study group support'
    ],
    requirements: [
      'Basic understanding of algebra',
      'Access to a computer or tablet',
      'Commitment to 5-6 hours per week',
      'Stable internet connection for live classes'
    ]
  },
  {
    id: '2',
    title: 'English Language & Literature',
    description: 'Improve your English proficiency with comprehensive lessons',
    price: 20000,
    duration: '10 weeks',
    level: 'Intermediate',
    enrolledCount: 62,
    rating: 4.7,
    reviewCount: 28,
    image: 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=400',
    instructor: {
      id: '2',
      name: 'Mrs. Folake Adekunle',
      avatar: '',
      title: 'Senior English Instructor',
      bio: 'Certified TEFL instructor with a passion for literature and language development.'
    },
    features: [
      '10 weeks of live classes',
      'Speaking and writing practice',
      'Literary analysis sessions',
      'Grammar and vocabulary building',
      'Certificate upon completion'
    ],
    requirements: [
      'Basic English proficiency',
      'Willingness to participate actively',
      'Commitment to 4-5 hours per week'
    ]
  },
  {
    id: '3',
    title: 'Physics Fundamentals',
    description: 'Explore the laws of nature through experiments and theory',
    price: 22000,
    duration: '12 weeks',
    level: 'Beginner',
    enrolledCount: 38,
    rating: 4.9,
    reviewCount: 19,
    image: 'https://images.unsplash.com/photo-1636466497217-26a8cbeaf0aa?w=400',
    instructor: {
      id: '3',
      name: 'Prof. Chinedu Obi',
      avatar: '',
      title: 'Physics & Science Lead',
      bio: 'PhD in Physics with research focus on renewable energy and practical applications.'
    },
    features: [
      '12 weeks of live classes',
      'Virtual lab experiments',
      'Real-world problem solving',
      'Interactive demonstrations',
      'Certificate upon completion'
    ],
    requirements: [
      'Basic mathematics knowledge',
      'Scientific curiosity',
      'Commitment to 6-7 hours per week'
    ]
  },
  {
    id: '4',
    title: 'Chemistry Basics',
    description: 'Learn the fundamentals of chemistry and chemical reactions',
    price: 18000,
    duration: '10 weeks',
    level: 'Beginner',
    enrolledCount: 41,
    rating: 4.6,
    reviewCount: 24,
    image: 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=400',
    instructor: {
      id: '4',
      name: 'Dr. Amina Bello',
      avatar: '',
      title: 'Chemistry Specialist',
      bio: 'Industrial chemist with 10+ years teaching experience in secondary education.'
    },
    features: [
      '10 weeks of live classes',
      'Chemical equation practice',
      'Lab safety training',
      'Periodic table mastery',
      'Certificate upon completion'
    ],
    requirements: [
      'Basic science background',
      'Calculator for stoichiometry',
      'Commitment to 4-5 hours per week'
    ]
  },
  {
    id: '5',
    title: 'Biology for Beginners',
    description: 'Discover the science of life and living organisms',
    price: 20000,
    duration: '10 weeks',
    level: 'Beginner',
    enrolledCount: 55,
    rating: 4.8,
    reviewCount: 31,
    image: 'https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=400',
    instructor: {
      id: '5',
      name: 'Dr. Ngozi Ekwueme',
      avatar: '',
      title: 'Biology Department Lead',
      bio: 'Medical doctor and biology educator passionate about life sciences education.'
    },
    features: [
      '10 weeks of live classes',
      'Cell biology exploration',
      'Genetics and evolution',
      'Human anatomy basics',
      'Certificate upon completion'
    ],
    requirements: [
      'Interest in life sciences',
      'No prior biology knowledge needed',
      'Commitment to 4-5 hours per week'
    ]
  }
];

// get course by Id
export const getCourseById = (courseId) => {
    if (!courseId || typeof courseId !== 'string') return null;
  
    // Prevent injection: courseId should be alphanumeric
    if (!/^[a-zA-Z0-9_-]+$/.test(courseId)) {
      console.warn('Invalid course ID format');
      return null;
    }

    return mockCourses.find(course => course.id === courseId) || null
}


// get multiple courses by Ids
export const getCourseByIds = (courseIds) => {
    if (!Array.isArray(courseIds)) return []

    return courseIds
    .filter(
        (id) => typeof id === 'string' && /^[a-zA-Z0-9_-]+$/.test(id)
    ).map(
        (id) => getCourseById(id)
    ).filter(Boolean)
}