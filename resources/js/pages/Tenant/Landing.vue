<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  ArrowRight, 
  Users, 
  BookOpen, 
  Award,
  Clock,
  Mail,
  Phone,
  MapPin,
  Book,
  Video,
  CheckCircle,
  TrendingUp
} from 'lucide-vue-next'
import Navbar from '@/components/Landing/Tenant/Navbar.vue';
import Hero from '@/components/Landing/Tenant/Hero.vue';
import About from '@/components/Landing/Tenant/About.vue';
import FeaturedCourses from '@/components/Landing/Tenant/FeaturedCourses.vue';
import CTA from '@/components/Landing/Tenant/CTA.vue';
import Contact from '@/components/Landing/Tenant/Contact.vue';
import Footer from '@/components/Landing/Tenant/Footer.vue';

const ArrowRightIcon = ArrowRight
const UsersIcon = Users
const BookOpenIcon = BookOpen
const AwardIcon = Award
const ClockIcon = Clock
const MailIcon = Mail
const PhoneIcon = Phone
const MapPinIcon = MapPin

const props = defineProps({
  tenant: {
    type: Object,
    required: true
  },
  pageContent: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({
      students: 0,
      courses: 0,
      instructors: 0
    })
  },
  featuredCourses: {
    type: Array,
    default: () => []
  },
  contactInfo: {
    type: Object,
    default: () => ({})
  }
})

const mobileMenuOpen = ref(false)

</script>

<template>
    <div class="min-h-screen bg-background">
      <Navbar 
         :tenant="tenant" 
         :open="mobileMenuOpen"
      />

      <Hero
         :tenant="tenant"
         :stats="stats"
         :page-content="pageContent"
       />

      <About
         :tenant="tenant"
         :page-content="pageContent"
       />

      <FeaturedCourses
         :tenant="tenant"
         :featured-courses="FeaturedCourses"
      />

      <CTA
         :tenant="tenant"
         :page-content="pageContent"
      />

      <Contact
         :tenant="tenant"
         :contact-info="contactInfo"
      />

      <Footer
         :tenant="tenant"
         :contact-info="contactInfo"
      />

    </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>