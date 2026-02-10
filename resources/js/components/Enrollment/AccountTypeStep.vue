<script setup>
import { GraduationCap, Users } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'

/**
 * Props
 */
defineProps({
    selectedType: {
        type: String,
        default: null,
        validator: (value) => value === null || ['adult', 'parent'].includes(value)
    }
})

/**
 * Emit select event
 */
const emit = defineEmits(['select'])
const handleSelect = (type) => {
    emit('select', type)
}

/**
 * Account types array of objects
 */
const accountTypes = [
  {
    value: 'adult',
    icon: GraduationCap,
    label: "I'm an Adult Student",
    description: "I'm 18 years or older and enrolling for myself"
  },
  {
    value: 'parent',
    icon: Users,
    label: "I'm a Parent/Guardian",
    description: "I'm enrolling my child (under 18)"
  }
]

</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold">Who is enrolling</h2>
            <p class="text-muted-foreground mt-2">Select the type of account you need</p>
        </div>

        <!-- content -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card 
               v-for="type in accountTypes"
               :key="type.value"
               class="cursor-pointer transition-all hover:shadow-lg hover:-translate-y-1"
               :class="{
                 'border-2 border-primary ring-2 ring-primary/20 bg-primary/5' : selectedType === type.value, 
                 'hover:border-primary/50' : selectedType !== type.value
               }"
               @click="handleSelect(type.value)"
            >
                <CardContent class="">
                    <component 
                      :is="type.icon"
                      class="h-12 w-12 mx-auto mb-4"
                      :class="selectedType === type.value ? 'text-primary' : 'text-muted-foreground'"
                    />
                    <h3 class="font-semibold text-lg mb-2">{{ type.label }}</h3>
                    <p class="text-sm text-muted-foreground">{{ type.description }}</p>
                </CardContent>
            </Card>
        </div>

    </div>
</template>