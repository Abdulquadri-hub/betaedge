/**
 * Slug Generator - Creates a URL-safe slug from a school name
 * Strategy: Take first word of school name, convert to lowercase, max 7 characters
 * 
 * Examples:
 * - "Harvard University" → "harvard"
 * - "Campus Academy" → "campus"
 * - "AI Lab" → "ai"
 * - "MIT Media Lab" → "mit"
 */

export function generateSlugFromSchoolName(schoolName: string): string {
  if (!schoolName || typeof schoolName !== 'string') {
    return ''
  }

  // Trim whitespace
  const trimmed = schoolName.trim()
  
  if (!trimmed) {
    return ''
  }

  // Extract first word (split by space and take first part)
  const firstWord = trimmed.split(/\s+/)[0]

  if (!firstWord) {
    return ''
  }

  // Convert to lowercase and remove special characters (keep alphanumeric and hyphen)
  let slug = firstWord
    .toLowerCase()
    .replace(/[^a-z0-9-]/g, '') // Remove all non-alphanumeric and non-hyphen characters

  if (!slug) {
    return ''
  }

  // Truncate to max 7 characters
  slug = slug.substring(0, 7)

  return slug
}

/**
 * Check if slug generation would result in truncation
 * This is used to show a warning to the user
 */
export function isSlugTruncated(schoolName: string, generatedSlug: string): boolean {
  if (!schoolName || !generatedSlug) {
    return false
  }

  const firstWord = schoolName.trim().split(/\s+/)[0]
  const cleanedWord = firstWord
    .toLowerCase()
    .replace(/[^a-z0-9-]/g, '')

  return cleanedWord.length > 7
}
