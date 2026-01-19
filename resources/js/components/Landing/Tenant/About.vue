<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js'
import { 
  Book,
  Video,
  CheckCircle,
  TrendingUp
} from 'lucide-vue-next'


defineProps({
    tenant: {
        type: Object,
        default: () => ({})
    },
    pageContent: {
        type: Object,
        default: () => ({})
    }
})

const getFeatureIcon = (iconName) => {
  const icons = {
    'book': Book,
    'video': Video,
    'check': CheckCircle,
    'trending': TrendingUp
  }
  return icons[iconName] || Book
}

</script>

<template>
    <section id="about" class="py-20 md:py-32 bg-muted/30">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
                        About {{ tenant.name }}
                    </h2>
                    <div class="w-20 h-1 mx-auto rounded-full" :style="{ backgroundColor: tenant.primary_color }" />
                </div>

                <div class="prose prose-lg max-w-none">
                    <p class="text-muted-foreground leading-relaxed text-center">
                        {{ pageContent.about_preview || tenant.description }}
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mt-12">
                    <div v-for="(feature, index) in pageContent.features" :key="index"
                        class="p-6 rounded-xl bg-card border border-border hover:shadow-lg transition-all">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                            :style="{ backgroundColor: `${tenant.primary_color}20` }">
                            <component :is="getFeatureIcon(feature.icon)" class="w-6 h-6"
                                :style="{ color: tenant.primary_color }" />
                        </div>
                        <h3 class="font-semibold text-foreground mb-2">
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>