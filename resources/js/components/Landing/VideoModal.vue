<script setup>
import { X } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogClose, DialogDescription, DialogTitle} from '../ui/dialog';
import { VisuallyHidden } from 'reka-ui';

defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    videoUrl: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['close'])

const handleClose = (value) => {
    if (!value) {
        emit('close')
    }
}

</script>

<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-4xl p-0 bg-background border-border overflow-hidden">
      <VisuallyHidden>
        <DialogTitle>Featured School</DialogTitle>
          <DialogDescription>
            Learn more about this school’s programs, ratings, and enrollment details.
          </DialogDescription>
      </VisuallyHidden>
      <DialogClose class="absolute right-4 top-4 z-10 rounded-full bg-background/80 p-2 hover:bg-background transition-colors">
        <X class="h-5 w-5 text-foreground" />
        <span class="sr-only">Close</span>
      </DialogClose>
      
      <div class="aspect-video w-full">
        <iframe
          :src="videoUrl"
          :title="title"
          class="w-full h-full"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        />
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
.text-gradient-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.gradient-hero {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-secondary {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.shadow-glow {
  box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
}

@keyframes scroll {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.animate-scroll {
  animation: scroll 30s linear infinite;
}

.animate-scroll:hover {
  animation-play-state: paused;
}
</style>