<?php

/**
 * BETAEDGE - COMPREHENSIVE ELOQUENT MODELS SPECIFICATION
 * 
 * Multi-Tenant EdTech Platform
 * Laravel 12 with MySQL Database
 * 
 * This file documents all 40+ Eloquent Models with their:
 * - Properties and fillables
 * - Relationships
 * - Query scopes
 * - Helper methods
 * - Casts
 * 
 * Location: app/Models/
 */

// ============================================================================
// CORE ACADEMIC MODELS (13 MODELS)
// ============================================================================

/**
 * 1. STUDENT Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, user_id, student_id, date_of_birth, gender, address, city, state
 * - country, emergency_contact_name, emergency_contact_phone, enrollment_date
 * - enrollment_status, academic_level_id, gpa, notes
 * 
 * Relationships:
 * - belongsTo(User), belongsTo(AcademicLevel)
 * - belongsToMany(ParentModel) via student_parent [with pivot data]
 * - hasMany(Enrollment), hasMany(Attendance), hasMany(Submission), hasMany(ChildLinkingRequest)
 * - hasManyThrough(Grade, Submission)
 * 
 * Scopes:
 * - active(), inactive(), suspended(), byLevel($id), byGender($gender)
 * 
 * Helpers:
 * - calculateGPA(), updateGPA(), getAttendancePercentage(), isInGoodStanding()
 * - getPerformanceSummary(), canEnrollInCourse(), getRecommendedCourses()
 * - promote(), canBePromoted(), getNextGradeLevel()
 */

/**
 * 2. INSTRUCTOR Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, user_id, instructor_id, qualification, specialization
 * - years_of_experience, hire_date, status, bio, linkedin_url, hourly_rate
 * 
 * Relationships:
 * - belongsTo(User)
 * - belongsToMany(Course) via instructor_course [with pivot: assigned_date, is_primary]
 * - hasMany(ClassSession), hasMany(Assignment), hasMany(InstructorAttendance), hasMany(Grade)
 * - hasManyThrough(Student, Enrollment)
 * 
 * Scopes:
 * - active(), inactive(), fullTime(), partTime(), bySpecialization()
 * 
 * Helpers:
 * - calculateMonthlyHours(), calculateMonthlyEarnings()
 * - getStudentCount(), getAverageRating(), getTotalCoursesTeaching()
 */

/**
 * 3. COURSE Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, academic_level_id, course_code, title, description
 * - category, level, duration_weeks, credit_hours, price, thumbnail
 * - learning_objectives[], prerequisites[], status, max_students
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(AcademicLevel)
 * - belongsToMany(Instructor) via instructor_course
 * - hasMany(Material), hasMany(Assignment), hasMany(ClassSession)
 * - hasMany(Enrollment)
 * 
 * Scopes:
 * - active(), published(), byLevel(), byCategory(), popular(), featured()
 * 
 * Helpers:
 * - getEnrollmentCount(), getActiveStudentCount%, getAveragePrice()
 * - hasCapacity(), isFull(), getMaterialCount(), getAssignmentCount()
 */

/**
 * 4. ACADEMIC LEVEL Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, name (e.g., "Grade 9"), code, level_number, description, is_active
 * 
 * Relationships:
 * - belongsTo(Tenant)
 * - hasMany(Course), hasMany(Student)
 * 
 * Scopes:
 * - active(), byCode()
 * 
 * Helpers:
 * - getNextLevel(), getPreviousLevel(), getStudentCount(), getCourseCount()
 * - isHighestLevel(), isLowestLevel()
 */

/**
 * 5. ENROLLMENT Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, student_id, course_id, enrollment_date, status
 * - progress_percentage, final_grade
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Student), belongsTo(Course)
 * - hasMany(Submission), hasOne(Grade)
 * 
 * Scopes:
 * - active(), completed(), pending(), withdrawn(), failed()
 * 
 * Helpers:
 * - getAttendanceRate(), getAverageSubmissionScore(), updateProgress()
 * - markCompleted(), markWithdrawn(), isPassing(), getPerformanceData()
 */

/**
 * 6. CLASS SESSION Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, course_id, instructor_id, title, session_type
 * - scheduled_start, scheduled_end, status, location, meeting_link
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Course), belongsTo(Instructor)
 * - hasMany(Attendance)
 * 
 * Scopes:
 * - scheduled(), completed(), cancelled(), upcoming(), today()
 * 
 * Helpers:
 * - getDurationInMinutes(), getAttendanceCount(), getAbsenceCount()
 * - getAttendanceRate(), isUpcoming(), hasStarted(), isCompleted()
 */

/**
 * 7. MATERIAL Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, course_id, title, material_type (video/pdf/document/link)
 * - file_url, description, is_published, order, upload_date
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Course)
 * 
 * Scopes:
 * - published(), byType(), ordered()
 * 
 * Helpers:
 * - publish(), unpublish(), getDownloadCount()
 */

/**
 * 8. ASSIGNMENT Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, course_id, instructor_id, title, description
 * - due_at, max_score, status, rubric[], specifications[]
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Course), belongsTo(Instructor)
 * - hasMany(Submission)
 * 
 * Scopes:
 * - active(), published(), drafted(), overdue()
 * 
 * Helpers:
 * - getSubmissionCount(), getSubmissionRate(), getAverageScore()
 * - isOverdue(), isPublished(), extendDeadline()
 */

/**
 * 9. SUBMISSION Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, assignment_id, student_id, file_url, submitted_at
 * - submission_text, status, is_late, submitted_by_id
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Assignment), belongsTo(Student)
 * - hasOne(Grade)
 * 
 * Scopes:
 * - submitted(), graded(), ungraded(), late(), ontime()
 * 
 * Helpers:
 * - markGraded(), isOnTime(), isLate(), daysLate(), getGrade()
 */

/**
 * 10. GRADE Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, submission_id, instructor_id, score, percentage
 * - letter_grade (A, B, C, etc), feedback, is_published, graded_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Submission), belongsTo(Instructor)
 * 
 * Scopes:
 * - published(), unpublished()
 * 
 * Helpers:
 * - publish(), unpublish(), caculateLetterGrade(), getFeedback()
 * - isExcellent(), isGood(), isPassing(), isFailing()
 */

/**
 * 11. ATTENDANCE Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, student_id, course_id, class_session_id, attendance_date
 * - status (present/absent/late/excused), check_in_time, check_out_time
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Student), belongsTo(Course), belongsTo(ClassSession)
 * 
 * Scopes:
 * - present(), absent(), late(), excused(), byDate(), byStudent()
 * 
 * Helpers:
 * - markPresent(), markAbsent(), markLate(), markExcused()
 * - getDurationInMinutes()
 */

/**
 * 12. INSTRUCTOR ATTENDANCE Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, instructor_id, attendance_date, status
 * - check_in_time, check_out_time, notes
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Instructor)
 * 
 * Scopes:
 * - present(), absent(), late(), byDate(), byMonth()
 * 
 * Helpers:
 * - getWorkingHours(), markPresent(), markAbsent()
 */

/**
 * 13. REPORT Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, generated_by, report_type, title, report_data[]
 * - status, parameters[], generated_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User, 'generated_by')
 * 
 * Scopes:
 * - byType(), completed(), failed()
 */

// ============================================================================
// PARENT/GUARDIAN MODELS (5 MODELS)
// ============================================================================

/**
 * 14. PARENT MODEL Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, user_id, parent_id, occupation, address, city, state, country
 * - phone, emergency_relation
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User)
 * - belongsToMany(Student) via student_parent [with pivot: relationship, is_primary, permissions]
 * - hasMany(ChildLinkingRequest), hasMany(ParentAssignment)
 */

/**
 * 15. STUDENT PARENT Pivot Model
 * @package App\Models
 * 
 * Properties (in pivot table):
 * - student_id, parent_id, relationship (father/mother/guardian)
 * - is_primary_contact (boolean), can_view_grades, can_view_attendance
 * 
 * Used in: Student belongsToMany(ParentModel)
 */

/**
 * 16. CHILD LINKING REQUEST Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, parent_id, student_id, relationship, verification_token
 * - status (pending/approved/rejected), notes, created_at, responded_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(ParentModel), belongsTo(Student)
 * 
 * Scopes:
 * - pending(), approved(), rejected()
 * 
 * Helpers:
 * - approve(), reject(), sendVerificationEmail()
 */

/**
 * 17. PARENT REGISTRATION Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, email, first_name, last_name, phone
 * - verification_token, status (pending/verified/rejected), expires_at
 * 
 * Relationships:
 * - belongsTo(Tenant)
 * 
 * Scopes:
 * - pending(), verified(), expired()
 */

/**
 * 18. PARENT ASSIGNMENT Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, parent_id, assignment_id, student_id, last_viewed_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(ParentModel), belongsTo(Assignment), belongsTo(Student)
 */

// ============================================================================
// MULTI-TENANCY MODELS (6 MODELS)
// ============================================================================

/**
 * 19. TENANT USER Model [Already exists]
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, user_id, role, permissions[], status
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User)
 */

/**
 * 20. TENANT INVITATION Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, email, role, token, status (pending/accepted/rejected), expires_at
 * 
 * Relationships:
 * - belongsTo(Tenant)
 * 
 * Scopes:
 * - pending(), accepted(), expired()
 */

/**
 * 21. DOMAIN VERIFICATION Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, domain, verification_type (dns/file), token
 * - is_verified, verified_at
 * 
 * Relationships:
 * - belongsTo(Tenant)
 */

/**
 * 22. TENANT PAGE Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, slug, title, content, meta_description[], is_published
 * 
 * Relationships:
 * - belongsTo(Tenant)
 */

/**
 * 23. TENANT FILE Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, uploaded_by, original_name, path, mime_type
 * - size_bytes, storage_disk (local/s3), access_level
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User, 'uploaded_by')
 */

/**
 * 24. ONBOARDING PROCESS Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, user_id, completed_steps[], current_step, status
 * - progress_percentage, completed_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User)
 */

// ============================================================================
// BILLING MODELS (7 MODELS)
// ============================================================================

/**
 * 25. SUBSCRIPTION PLAN Model
 * @package App\Models
 * 
 * Properties:
 * - name, slug, description, price (decimal), billing_cycle (monthly/yearly)
 * - features[], limits[], is_active, max_users, max_courses
 * 
 * Relationships:
 * - hasMany(Subscription)
 * 
 * Note: NO tenant_id (global plans)
 */

/**
 * 26. SUBSCRIPTION Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, subscription_plan_id, status (active/cancelled/suspended)
 * - amount, billing_cycle, auto_renew, subscribed_at, renews_at, cancelled_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(SubscriptionPlan)
 * - hasMany(TenantPayment)
 * 
 * Scopes:
 * - active(), cancelled(), expiring()
 */

/**
 * 27. TENANT PAYMENT CONFIG Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, payment_provider (stripe/paystack/others)
 * - api_key_public, api_key_secret (encrypted), webhook_secret (encrypted)
 * - is_active, configured_at
 * 
 * Relationships:
 * - belongsTo(Tenant)
 */

/**
 * 28. TENANT PAYMENT Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, subscription_id, invoice_number, amount (decimal)
 * - status (pending/completed/failed), payment_method, reference_code
 * - paid_at, verified_by
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Subscription), belongsTo(User, 'verified_by')
 */

/**
 * 29. PAYMENT Model
 * @package App\Models
 * @uses BelongsToTenant, SoftDeletes
 * 
 * Properties:
 * - tenant_id, student_id, parent_id, course_id, amount (decimal)
 * - purpose (enrollment/tuition/materials), status, reference_code
 * - payment_date, verified_by, notes
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Student), belongsTo(ParentModel)
 * - belongsTo(Course), belongsTo(User, 'verified_by')
 */

/**
 * 30. NOTIFICATION Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, title, body, type (info/warning/alert/success)
 * - priority (low/normal/high), icon_class, data[]
 * - created_by, is_broadcast, scheduled_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User, 'created_by')
 * - belongsToMany(User) via user_notifications [with pivot: read_at, is_archived]
 */

/**
 * 31. USER NOTIFICATION Pivot Model
 * @package App\Models
 * 
 * Properties (in pivot table):
 * - user_id, notification_id, read_at, is_archived
 * 
 * Used in: Notification belongsToMany(User)
 * Methods: markAsRead(), markAsUnread(), archive(), unarchive()
 */

// ============================================================================
// MARKETPLACE MODELS (3 MODELS)
// ============================================================================

/**
 * 32. MARKETPLACE LISTING Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, title, description, logo, category
 * - rating (decimal), review_count, is_featured, click_count, created_at
 * 
 * Relationships:
 * - belongsTo(Tenant)
 * - hasMany(MarketplaceReview), hasMany(MarketplaceClick)
 */

/**
 * 33. MARKETPLACE REVIEW Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, marketplace_listing_id, reviewer_id, rating (1-5)
 * - review_text, status (pending/approved/rejected), created_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(MarketplaceListing), belongsTo(User, 'reviewer_id')
 */

/**
 * 34. MARKETPLACE CLICK Model
 * @package App\Models
 * 
 * Properties:
 * - marketplace_listing_id, ip_address, user_agent, country
 * - clicked_at
 * 
 * Relationships:
 * - belongsTo(MarketplaceListing)
 */

// ============================================================================
// ENROLLMENT & PROGRESSION MODELS (2 MODELS)
// ============================================================================

/**
 * 35. ENROLLMENT REQUEST Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, course_id, student_id, email, status
 * - request_type (student/parent), parent_notification_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Course), belongsTo(Student)
 */

/**
 * 36. STUDENT PROMOTION Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, student_id, from_academic_level_id, to_academic_level_id
 * - status (pending/completed), promotion_type (regular/summer/remedial)
 * - promotion_date, final_gpa, promotion_notes, promoted_by
 * - academic_year
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(Student)
 * - belongsTo(AcademicLevel, 'from_academic_level_id')
 * - belongsTo(AcademicLevel, 'to_academic_level_id')
 * - belongsTo(User, 'promoted_by')
 */

// ============================================================================
// ADMIN & PLATFORM MODELS (1 MODEL)
// ============================================================================

/**
 * 37. ADMIN Model [May exist]
 * @package App\Models
 * 
 * Properties:
 * - user_id, role (super_admin/admin/moderator), permissions[]
 * 
 * Relationships:
 * - belongsTo(User)
 */

// ============================================================================
// ANALYTICS MODELS (1 MODEL)
// ============================================================================

/**
 * 38. TENANT ANALYTIC Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, analytics_date, active_users, new_enrollments
 * - revenue (decimal), course_completions, average_score
 * 
 * Relationships:
 * - belongsTo(Tenant)
 */

// ============================================================================
// INFRASTRUCTURE MODELS (2 MODELS)
// ============================================================================

/**
 * 39. JOB PROGRESS Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, job_name, status (pending/processing/completed/failed)
 * - progress_percentage, started_at, completed_at, error_message
 * 
 * Relationships:
 * - belongsTo(Tenant)
 */

/**
 * 40. ACTIVITY LOG Model
 * @package App\Models
 * @uses BelongsToTenant
 * 
 * Properties:
 * - tenant_id, user_id, event, log_name, subject_type, subject_id
 * - ip_address, old_values[], new_values[], description, created_at
 * 
 * Relationships:
 * - belongsTo(Tenant), belongsTo(User)
 * 
 * Scopes:
 * - byEvent(), forSubject(), byUser(), recent()
 */

// ============================================================================
// TOTAL MODELS: 40+
// ============================================================================
