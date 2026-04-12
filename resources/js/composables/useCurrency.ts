import { ref, computed } from 'vue'

interface CurrencyData {
  country_code: string
  country_name: string
  currency_code: string
  currency_symbol: string
  currency_name: string
  exchange_rate: number
  ip: string | null
}

// Global currency state
const currencyData = ref<CurrencyData | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

/**
 * Composable for accessing and managing user currency based on IP location
 */
export function useCurrency() {
  
  /**
   * Fetch currency from backend based on IP
   */
  const detectCurrency = async () => {
    if (currencyData.value) {
      return currencyData.value // Already detected
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/currency/detect', {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })

      if (!response.ok) {
        throw new Error('Failed to detect currency')
      }

      currencyData.value = await response.json()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      console.error('Currency detection error:', err)
      
      // Use default (NGN)
      currencyData.value = {
        country_code: 'NG',
        country_name: 'Nigeria',
        currency_code: 'NGN',
        currency_symbol: '₦',
        currency_name: 'Nigerian Naira',
        exchange_rate: 1.0,
        ip: null,
      }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Format amount for display with currency symbol
   */
  const formatAmount = (amount: number): string => {
    if (!currencyData.value) return ''
    
    const symbol = currencyData.value.currency_symbol
    const rate = currencyData.value.exchange_rate
    
    // Convert from base (NGN) to target currency
    const converted = Math.round(amount * rate)
    
    return `${symbol}${converted.toLocaleString()}`
  }

  /**
   * Get currency symbol
   */
  const getSymbol = (): string => {
    return currencyData.value?.currency_symbol ?? '₦'
  }

  /**
   * Get currency code (e.g., 'NGN', 'KES')
   */
  const getCurrencyCode = (): string => {
    return currencyData.value?.currency_code ?? 'NGN'
  }

  /**
   * Get country code
   */
  const getCountryCode = (): string => {
    return currencyData.value?.country_code ?? 'NG'
  }

  /**
   * Get exchange rate
   */
  const getExchangeRate = (): number => {
    return currencyData.value?.exchange_rate ?? 1.0
  }

  return {
    currencyData: computed(() => currencyData.value),
    isLoading: computed(() => isLoading.value),
    error: computed(() => error.value),
    detectCurrency,
    formatAmount,
    getSymbol,
    getCurrencyCode,
    getCountryCode,
    getExchangeRate,
  }
}
