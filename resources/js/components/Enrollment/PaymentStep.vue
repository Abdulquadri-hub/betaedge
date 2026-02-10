<script setup>
import { ref, computed } from 'vue'
import { CreditCard, Building2, Smartphone, Loader2, Mail, Lock } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Separator } from '@/components/ui/separator'
import { getCourseById } from '@/data/mockCourses'
import { getBatchById } from '@/data/mockBatches'

/**
 * PaymentStep - V3 with Batch Names
 * 
 * Changes:
 * - Display batch names with courses
 * - Show batch-specific pricing
 * - Remove learning type context
 * 
 * Laravel Integration:
 * - Process payment via Paystack
 * - Create enrollment records with batch_id
 * - Send confirmation emails
 */

const props = defineProps({
  enrollmentData: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['submit', 'back'])

const paymentMethod = ref('card')
const isProcessing = ref(false)
const cardData = ref({
  number: '',
  expiry: '',
  cvv: ''
})

// Computed: Selected course details with batches
const selectedCoursesWithBatches = computed(() => {
  return props.enrollmentData.selectedCourses.map(courseId => {
    const course = getCourseById(courseId)
    const batchId = props.enrollmentData.selectedBatches[courseId]
    const batch = getBatchById(batchId)
    
    return {
      courseId,
      courseName: course?.title || 'Unknown Course',
      batchName: batch?.name || 'Unknown Batch',
      price: course?.price || 0
    }
  })
})

// Computed: Subtotal
const subtotal = computed(() => {
  return selectedCoursesWithBatches.value.reduce((sum, item) => sum + item.price, 0)
})

// Computed: Processing fee (1.5%)
const processingFee = computed(() => {
  return Math.round(subtotal.value * 0.015)
})

// Computed: Total
const total = computed(() => {
  return subtotal.value + processingFee.value
})

// Handle payment submission
const handleSubmit = async () => {
  isProcessing.value = true

  try {
    // Laravel Inertia Integration:
    // 
    // 1. Initialize Paystack transaction
    // const response = await axios.post('/api/payment/initialize', {
    //   email: enrollmentData.adult?.email || enrollmentData.parent?.email,
    //   amount: total.value * 100, // Convert to kobo
    //   metadata: {
    //     courses: enrollmentData.selectedCourses,
    //     batches: enrollmentData.selectedBatches,
    //     type: enrollmentData.enrollmentType
    //   }
    // })
    // 
    // 2. Redirect to Paystack
    // window.location.href = response.data.authorization_url
    // 
    // 3. Handle callback at /payment/callback
    // Backend verifies payment and creates enrollment records

    // Mock payment processing
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    emit('submit', {
      paymentMethod: paymentMethod.value,
      paymentReference: `PAY-${Date.now()}`
    })
  } catch (error) {
    console.error('Payment error:', error)
  } finally {
    isProcessing.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <h2 class="text-2xl font-bold">Payment</h2>
      <p class="text-muted-foreground mt-2">
        Complete your enrollment payment
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Payment Method Selection -->
      <div class="space-y-4">
        <h3 class="font-semibold">Select Payment Method</h3>
        
        <RadioGroup v-model="paymentMethod" class="space-y-3">
          <div>
            <Label
              for="card"
              class="flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all"
              :class="paymentMethod === 'card' ? 'border-primary ring-2 ring-primary/20 bg-primary/5' : 'hover:bg-muted/50'"
            >
              <RadioGroupItem value="card" id="card" />
              <CreditCard class="h-5 w-5" />
              <div class="flex-1">
                <div class="font-medium">Card Payment</div>
                <div class="text-sm text-muted-foreground">Pay with Visa, Mastercard, or Verve</div>
              </div>
            </Label>
          </div>

          <div>
            <Label
              for="bank"
              class="flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all"
              :class="paymentMethod === 'bank' ? 'border-primary ring-2 ring-primary/20 bg-primary/5' : 'hover:bg-muted/50'"
            >
              <RadioGroupItem value="bank" id="bank" />
              <Building2 class="h-5 w-5" />
              <div class="flex-1">
                <div class="font-medium">Bank Transfer</div>
                <div class="text-sm text-muted-foreground">Pay directly from your bank account</div>
              </div>
            </Label>
          </div>

          <div>
            <Label
              for="ussd"
              class="flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all"
              :class="paymentMethod === 'ussd' ? 'border-primary ring-2 ring-primary/20 bg-primary/5' : 'hover:bg-muted/50'"
            >
              <RadioGroupItem value="ussd" id="ussd" />
              <Smartphone class="h-5 w-5" />
              <div class="flex-1">
                <div class="font-medium">USSD</div>
                <div class="text-sm text-muted-foreground">Pay using your phone's USSD code</div>
              </div>
            </Label>
          </div>
        </RadioGroup>

        <!-- Card Details (if card payment) -->
        <Card v-if="paymentMethod === 'card'" class="mt-4">
          <CardContent class="pt-6 space-y-4">
            <div>
              <Label>Card Number</Label>
              <div class="relative mt-1">
                <CreditCard class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input 
                  v-model="cardData.number"
                  placeholder="4000 0000 0000 0000" 
                  class="pl-10"
                />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label>Expiry Date</Label>
                <Input v-model="cardData.expiry" placeholder="MM/YY" class="mt-1" />
              </div>
              <div>
                <Label>CVV</Label>
                <Input v-model="cardData.cvv" placeholder="123" type="password" class="mt-1" />
              </div>
            </div>
            <p class="text-xs text-muted-foreground">
              Your card details are secured with Paystack. We don't store your card information.
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Order Summary -->
      <div>
        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Order Summary</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Course Items with Batch Names -->
            <div 
              v-for="item in selectedCoursesWithBatches" 
              :key="item.courseId"
              class="space-y-1"
            >
              <div class="flex justify-between text-sm">
                <div class="flex-1">
                  <div class="font-medium">{{ item.courseName }}</div>
                  <div class="text-xs text-muted-foreground">{{ item.batchName }}</div>
                </div>
                <span class="font-medium">₦{{ item.price.toLocaleString() }}</span>
              </div>
            </div>

            <Separator />

            <div class="flex justify-between text-sm">
              <span>Subtotal</span>
              <span>₦{{ subtotal.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-sm text-muted-foreground">
              <span>Processing Fee (1.5%)</span>
              <span>₦{{ processingFee.toLocaleString() }}</span>
            </div>
            
            <Separator />
            
            <div class="flex justify-between font-semibold text-lg">
              <span>Total</span>
              <span>₦{{ total.toLocaleString() }}</span>
            </div>
          </CardContent>
        </Card>

        <!-- Enrollee Info -->
        <Card class="mt-4">
          <CardHeader>
            <CardTitle class="text-lg">Enrollee Details</CardTitle>
          </CardHeader>
          <CardContent class="text-sm space-y-2">
            <template v-if="enrollmentData.enrollmentType === 'parent'">
              <div>
                <span class="text-muted-foreground">Parent: </span>
                {{ enrollmentData.parent.name }}
              </div>
              <div>
                <span class="text-muted-foreground">Email: </span>
                {{ enrollmentData.parent.email }}
              </div>
              <Separator class="my-2" />
              <div>
                <span class="text-muted-foreground">Student: </span>
                {{ enrollmentData.child.name }}
              </div>
              <div>
                <span class="text-muted-foreground">Grade: </span>
                {{ enrollmentData.child.grade }}
              </div>
            </template>
            <template v-else>
              <div>
                <span class="text-muted-foreground">Name: </span>
                {{ enrollmentData.adult.name }}
              </div>
              <div>
                <span class="text-muted-foreground">Email: </span>
                {{ enrollmentData.adult.email }}
              </div>
            </template>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between">
      <Button variant="outline" @click="$emit('back')" :disabled="isProcessing">
        Back
      </Button>
      <Button @click="handleSubmit" :disabled="isProcessing" size="lg">
        <template v-if="isProcessing">
          <Loader2 class="mr-2 h-4 w-4 animate-spin" />
          Processing...
        </template>
        <template v-else>
          Pay ₦{{ total.toLocaleString() }}
        </template>
      </Button>
    </div>
  </div>
</template>