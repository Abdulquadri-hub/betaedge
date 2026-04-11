/**
 * Slug Validator - Validates slug against reserved keywords and uniqueness
 * Makes API call to backend to check reserved keywords and DB uniqueness
 */

export interface ValidationResult {
  valid: boolean
  errors: string[]
  truncated: boolean
}

/**
 * Client-side validation of slug format
 */
function validateSlugFormat(slug: string): ValidationResult {
  const errors: string[] = []

  // Check minimum length
  if (!slug || slug.trim().length === 0) {
    errors.push('Slug is required')
  }

  // Check maximum length
  if (slug.length > 7) {
    errors.push('Slug must be maximum 7 characters')
  }

  // Check valid characters (alphanumeric and hyphen only)
  if (!/^[a-z0-9-]+$/.test(slug)) {
    errors.push('Slug can only contain lowercase letters, numbers, and hyphens')
  }

  return {
    valid: errors.length === 0,
    errors,
    truncated: false
  }
}

/**
 * Full validation - client-side format + server-side reserved/uniqueness check
 */
export async function validateSlug(slug: string): Promise<ValidationResult> {
  // First do client-side validation
  const formatValidation = validateSlugFormat(slug)
  if (!formatValidation.valid) {
    return formatValidation
  }

  // Then check server-side (reserved keywords and uniqueness)
  try {
    const response = await fetch('/api/onboarding/validate-slug', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ slug })
    })

    if (!response.ok) {
      throw new Error('Failed to validate slug')
    }

    const data = await response.json()

    return {
      valid: data.valid,
      errors: data.errors || [],
      truncated: false
    }
  } catch (error) {
    console.error('Slug validation error:', error)
    // Return optimistic result if validation fails (allow user to proceed)
    return {
      valid: true,
      errors: [],
      truncated: false
    }
  }
}
