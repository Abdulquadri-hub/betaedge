<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SchoolModal from '@/components/Landing/SchoolModal.vue';
import { Button } from '@/components/ui/button';
import {
    ArrowLeft,
    Search,
    Star,
    Users,
    MapPin,
    ChevronLeft,
    ChevronRight,
    GraduationCap
} from 'lucide-vue-next';
import Footer from '@/components/Landing/Footer.vue';

const categories = [
    "All Categories",
    "Primary Education",
    "Secondary Education",
    "Web Development",
    "Mobile Development",
    "Data Science",
    "Business & Finance",
    "Languages",
    "STEM",
    "Arts & Design",
    "Health & Wellness",
];

const locations = [
    "All Locations",
    "Lagos, Nigeria",
    "Abuja, Nigeria",
    "Accra, Ghana",
    "Nairobi, Kenya",
    "Cape Town, SA",
    "Johannesburg, SA",
    "Dar es Salaam, TZ",
];

const allSchools = [
    {
        name: "Bright Stars Academy",
        category: "Primary Education",
        location: "Lagos, Nigeria",
        students: 450,
        rating: 4.9,
        courses: 24,
        image: "https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400&h=250&fit=crop",
        description: "Premier primary education institution dedicated to nurturing young minds.",
        instructors: 15,
        established: "2019",
    },
    {
        name: "Tech Academy Nigeria",
        category: "Web Development",
        location: "Abuja, Nigeria",
        students: 1200,
        rating: 4.8,
        courses: 18,
        image: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=250&fit=crop",
        description: "Leading coding bootcamp transforming beginners into job-ready developers.",
        instructors: 22,
        established: "2018",
    },
    {
        name: "Excel Learning Hub",
        category: "Business & Finance",
        location: "Accra, Ghana",
        students: 680,
        rating: 4.7,
        courses: 32,
        image: "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&h=250&fit=crop",
        description: "Empowering entrepreneurs with practical business skills.",
        instructors: 18,
        established: "2020",
    },
    {
        name: "Lingua Africa",
        category: "Languages",
        location: "Nairobi, Kenya",
        students: 890,
        rating: 4.9,
        courses: 15,
        image: "https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=400&h=250&fit=crop",
        description: "Immersive language courses connecting cultures through communication.",
        instructors: 25,
        established: "2017",
    },
    {
        name: "STEM Excellence Hub",
        category: "STEM",
        location: "Lagos, Nigeria",
        students: 560,
        rating: 4.8,
        courses: 28,
        image: "https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=400&h=250&fit=crop",
        description: "Preparing the next generation of African scientists and engineers.",
        instructors: 20,
        established: "2019",
    },
    {
        name: "Creative Arts Academy",
        category: "Arts & Design",
        location: "Cape Town, SA",
        students: 340,
        rating: 4.6,
        courses: 21,
        image: "https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&h=250&fit=crop",
        description: "Nurturing artistic talent across graphic design and digital art.",
        instructors: 14,
        established: "2021",
    },
    {
        name: "Code Masters Academy",
        category: "Mobile Development",
        location: "Abuja, Nigeria",
        students: 780,
        rating: 4.7,
        courses: 14,
        image: "https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&h=250&fit=crop",
        description: "Specialized in iOS and Android app development training.",
        instructors: 16,
        established: "2020",
    },
    {
        name: "Data Insights Africa",
        category: "Data Science",
        location: "Johannesburg, SA",
        students: 520,
        rating: 4.8,
        courses: 22,
        image: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop",
        description: "Comprehensive data science and analytics training programs.",
        instructors: 19,
        established: "2019",
    },
    {
        name: "Future Leaders Academy",
        category: "Secondary Education",
        location: "Lagos, Nigeria",
        students: 920,
        rating: 4.9,
        courses: 45,
        image: "https://images.unsplash.com/photo-1509062522246-3755977927d7?w=400&h=250&fit=crop",
        description: "Preparing students for university and beyond with excellence.",
        instructors: 35,
        established: "2016",
    },
    {
        name: "Wellness Institute Africa",
        category: "Health & Wellness",
        location: "Nairobi, Kenya",
        students: 410,
        rating: 4.5,
        courses: 18,
        image: "https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=250&fit=crop",
        description: "Holistic health and wellness certification programs.",
        instructors: 12,
        established: "2021",
    },
    {
        name: "African Business School",
        category: "Business & Finance",
        location: "Dar es Salaam, TZ",
        students: 650,
        rating: 4.6,
        courses: 26,
        image: "https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=400&h=250&fit=crop",
        description: "Executive education and MBA programs for African leaders.",
        instructors: 21,
        established: "2018",
    },
    {
        name: "Digital Arts Studio",
        category: "Arts & Design",
        location: "Lagos, Nigeria",
        students: 380,
        rating: 4.7,
        courses: 16,
        image: "https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400&h=250&fit=crop",
        description: "Animation, 3D modeling, and digital illustration courses.",
        instructors: 11,
        established: "2020",
    },
];

const props = defineProps({
    name: {
        type: String,
        default: 'BetaEdge'
    }
})

const ITEMS_PER_PAGE = 6

const searchQuery = ref("")
const categoryFilter = ref("All Categories")
const locationFilter = ref("All Locations")
const sortBy = ref("rating")
const currentPage = ref(1)
const selectedSchool = ref(null)

const filteredAndSortedSchools = computed(() => {
    let result = [...allSchools]

    // search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(
            (school) =>
                school.name.toLowerCase().includes(query) ||
                school.category.toLowerCase().includes(query) ||
                school.location.toLowerCase().includes(query)
        )
    }

    // category filter
    if (categoryFilter.value !== "All Categories") {
        result = result.filter(
            (school) => school.category === categoryFilter.value
        )
    }

    // location filter
    if (locationFilter.value != "All Locations") {
        result = result.filter(
            (school) => school.location === locationFilter.value
        )
    }

    // sort
    if (sortBy.value) {
        switch (sortBy.value) {
            case "rating":
                result.sort((a, b) => b.rating - a.rating);
                break;
            case "students":
                result.sort((a, b) => b.students - a.students);
                break;
            case "courses":
                result.sort((a, b) => b.courses - a.courses);
                break;
            case "name":
                result.sort((a, b) => a.name.localeCompare(b.name));
                break;
        }
    }

    return result
})

const totalPages = computed(
    () => Math.ceil(filteredAndSortedSchools.value.length / ITEMS_PER_PAGE)
)

const paginatedSchools = computed(() =>
    filteredAndSortedSchools.value.slice(
        (currentPage.value - 1) * ITEMS_PER_PAGE,
        currentPage.value * ITEMS_PER_PAGE
    )
);

const handlePageChange = (page) => {
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const resetFilters = () => {
    searchQuery.value = "";
    categoryFilter.value = "All Categories";
    locationFilter.value = "All Locations";
    sortBy.value = "rating";
    currentPage.value = 1;
};

</script>

<template>
    <div>

        <Head title="Marketplace - Discover Schools | BetaEdge LMS" />

        <header class="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-lg border-b border-border">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between h-16 md:h-20">
                    <Link href="/" class="flex items-center gap-2 group">
                        <div
                            class="w-10 h-10 rounded-xl gradient-hero flex items-center justify-center shadow-md group-hover:shadow-glow transition-shadow duration-300">
                            <GraduationCap class="w-6 h-6 text-primary-foreground" />
                        </div>
                        <span class="font-display font-bold tex-xl text-foreground">
                            {{ props.name }}
                        </span>
                    </Link>
                    <Link href="/">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Home
                        </Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16 min-h-screen bg-background">
            <div class="container mx-auto px-4">
                <!-- Page Header -->
                <div class="mb-10">
                    <h1 class="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-foreground mb-4">
                        Explore the <span class="bg-secondary bg-clip-text text-transparent">Marketplace</span>
                    </h1>
                    <p class="text-lg text-muted-foreground max-w-2xl">
                        Discover {{ allSchools.length }}+ schools offering world-class education across Africa.
                        Find your perfect learning path today.
                    </p>
                </div>

                <!-- Filters & Search -->
                <div class="bg-card border border-border rounded-2xl p-4 md:p-6 mb-8">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                            <input v-model="searchQuery" @input="currentPage = 1" type="text"
                                placeholder="Search schools, categories, or locations..."
                                class="w-full pl-10 h-12 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" />
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap gap-3">
                            <select v-model="categoryFilter" @change="currentPage = 1"
                                class="w-[180px] h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </option>
                            </select>

                            <select v-model="locationFilter" @change="currentPage = 1"
                                class="w-[180px] h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option v-for="loc in locations" :key="loc" :value="loc">
                                    {{ loc }}
                                </option>
                            </select>

                            <select v-model="sortBy"
                                class="w-[160px] h-12 bg-background border border-border rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="rating">Highest Rated</option>
                                <option value="students">Most Students</option>
                                <option value="courses">Most Courses</option>
                                <option value="name">Name (A-Z)</option>
                            </select>

                            <button @click="resetFilters"
                                class="h-12 px-6 rounded-lg border-2 border-primary text-primary bg-background hover:bg-primary hover:text-white transition-colors">
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="mt-4 pt-4 border-t border-border">
                        <p class="text-sm text-muted-foreground">
                            Showing <span class="font-medium text-foreground">{{ paginatedSchools.length }}</span>
                            of <span class="font-medium text-foreground">{{ filteredAndSortedSchools.length }}</span>
                            schools
                        </p>
                    </div>
                </div>

                <!-- Schools Grid -->
                <div v-if="paginatedSchools.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    <div v-for="school in paginatedSchools" :key="school.name" @click="selectedSchool = school"
                        class="group rounded-2xl bg-card border border-border overflow-hidden hover:shadow-lg hover:border-purple-600/30 transition-all duration-300 cursor-pointer">
                        <!-- Image -->
                        <div class="relative h-48 overflow-hidden">
                            <img :src="school.image" :alt="school.name"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                            <div class="absolute bottom-4 left-4 right-4">
                                <span
                                    class="inline-block px-3 py-1 rounded-full bg-secondary text-white text-xs font-medium">
                                    {{ school.category }}
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <h3
                                class="font-display font-bold text-xl text-foreground mb-2 group-hover:text-secondary transition-colors">
                                {{ school.name }}
                            </h3>

                            <div class="flex items-center gap-4 text-sm text-muted-foreground mb-4">
                                <div class="flex items-center gap-1">
                                    <MapPin class="w-4 h-4"  />
                                    {{ school.location }}
                                </div>
                                <div class="flex items-center gap-1">
                                    <Star class="w-4 h-4 text-yellow-500 fill-yellow-500"/>
                                    {{ school.rating }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-border">
                                <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                    <Users class="w-4 h-4" />
                                    {{ school.students.toLocaleString() }} students
                                </div>
                                <span class="text-sm font-medium text-foreground">
                                    {{ school.courses }} courses
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 bg-card border border-border rounded-2xl">
                    <p class="text-xl text-muted-foreground mb-4">
                        No schools found matching your criteria
                    </p>
                    <button @click="resetFilters"
                        class="px-6 py-3 rounded-lg bg-secondary text-white font-semibold hover:opacity-90 transition-opacity">
                        Clear Filters
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="flex items-center justify-center gap-2">
                    <button @click="handlePageChange(currentPage - 1)" :disabled="currentPage === 1" :class="[
                        'p-2 rounded-lg border transition-colors',
                        currentPage === 1
                            ? 'border-border bg-muted text-muted-foreground cursor-not-allowed'
                            : 'border-border bg-background hover:bg-muted'
                    ]">
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <button v-for="page in totalPages" :key="page" @click="handlePageChange(page)" :class="[
                        'w-10 h-10 rounded-lg font-medium transition-all',
                        currentPage === page
                            ? 'bg-primary text-white'
                            : 'border border-border bg-background hover:bg-muted'
                    ]">
                        {{ page }}
                    </button>

                    <button @click="handlePageChange(currentPage + 1)" :disabled="currentPage === totalPages" :class="[
                        'p-2 rounded-lg border transition-colors',
                        currentPage === totalPages
                            ? 'border-border bg-muted text-muted-foreground cursor-not-allowed'
                            : 'border-border bg-background hover:bg-muted'
                    ]">
                        <ChevronRight class="w-4 h-4"/>
                    </button>
                </div>
            </div>
        </main>

        <SchoolModal :is-open="!!selectedSchool" :school="selectedSchool" @close="selectedSchool = null" />
    </div>

    <Footer />
</template>

<style scoped></style>