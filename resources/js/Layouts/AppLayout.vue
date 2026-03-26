<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <Link href="/" class="flex items-center">
                            <span class="text-xl font-bold text-gray-900">Knowledge Base</span>
                        </Link>
                    </div>

                    <div class="flex items-center space-x-2 lg:space-x-4">
                        <!-- Mobile Menu Button -->
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="lg:hidden p-2 text-gray-600 hover:text-gray-900"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <!-- Search Button -->
                        <button
                            @click="showSearch = true"
                            class="flex items-center px-3 py-1.5 text-sm text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search...
                        </button>

                        <!-- Auth Links (desktop) -->
                        <template v-if="$page.props.auth?.user">
                            <Link href="/bookmarks" class="hidden lg:inline-block text-sm text-gray-600 hover:text-gray-900">
                                Bookmarks
                            </Link>
                            <Link :href="route('profile.edit')" class="hidden lg:inline-block text-sm text-gray-600 hover:text-gray-900">
                                {{ $page.props.auth.user.name }}
                            </Link>
                            <Link href="/logout" method="post" as="button"
                                  class="hidden lg:inline-block text-sm text-red-600 hover:text-red-700">
                                Log out
                            </Link>
                        </template>
                        <template v-else>
                            <Link href="/login" class="hidden lg:inline-block text-sm text-gray-600 hover:text-gray-900">Log in</Link>
                            <Link href="/register" class="hidden lg:inline-block text-sm text-gray-600 hover:text-gray-900">Register</Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Menu -->
        <div
            v-if="showMobileMenu"
            class="fixed inset-0 z-50 lg:hidden"
        >
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="showMobileMenu = false"></div>
            <div class="fixed inset-y-0 left-0 w-72 bg-white shadow-xl">
                <div class="p-4 border-b flex items-center justify-between">
                    <span class="font-semibold text-gray-900">Topics</span>
                    <button @click="showMobileMenu = false" class="p-1 text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <nav class="p-4 space-y-1 overflow-y-auto h-full pb-20">
                    <Link
                        v-for="topic in topics"
                        :key="topic.slug"
                        :href="`/topic/${topic.slug}`"
                        class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition"
                        :class="[
                            isActive(topic.slug)
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                        ]"
                        @click="showMobileMenu = false"
                    >
                        <span>{{ topic.name }}</span>
                        <span
                            v-if="topic.progress"
                            class="text-xs px-2 py-0.5 rounded-full"
                            :class="topic.progress.completed === topic.progress.total ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ topic.progress.completed }}/{{ topic.progress.total }}
                        </span>
                    </Link>

                    <!-- Mobile Auth Links -->
                    <div class="border-t pt-4 mt-4">
                        <template v-if="$page.props.auth?.user">
                            <Link
                                href="/bookmarks"
                                class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                                @click="showMobileMenu = false"
                            >
                                Bookmarks
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                                @click="showMobileMenu = false"
                            >
                                {{ $page.props.auth.user.name }}
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="flex items-center w-full px-3 py-2 text-sm font-medium rounded-md text-red-600 hover:bg-red-50"
                                @click="showMobileMenu = false"
                            >
                                Log out
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                                @click="showMobileMenu = false"
                            >
                                Log in
                            </Link>
                            <Link
                                href="/register"
                                class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                                @click="showMobileMenu = false"
                            >
                                Register
                            </Link>
                        </template>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex gap-6">
                <!-- Sidebar -->
                <Sidebar :topics="topics"/>

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    <slot/>
                </main>
            </div>
        </div>

        <!-- Search Modal -->
        <div v-if="showSearch" class="fixed inset-0 z-50 overflow-y-auto" @close="showSearch = false">
            <div class="flex items-start justify-center min-h-screen pt-16 px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showSearch = false"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                    <div class="p-4 border-b">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                type="text"
                                class="w-full border-0 focus:ring-0 text-lg"
                                placeholder="Search questions..."
                                @keydown.enter="handleSearch"
                                @keydown.escape="showSearch = false"
                            />
                        </div>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        <div v-if="searchResults.length > 0" class="divide-y">
                            <Link
                                v-for="result in searchResults"
                                :key="result.id"
                                :href="`/question/${result.slug}`"
                                class="block p-4 hover:bg-gray-50"
                                @click="showSearch = false"
                            >
                                <div class="font-medium text-gray-900">{{ result.title }}</div>
                                <div class="text-sm text-gray-500 mt-1">{{ result.topic.name }}</div>
                                <div class="text-sm text-gray-600 mt-1 line-clamp-2">{{ result.excerpt }}</div>
                            </Link>
                        </div>
                        <div v-else-if="searchQuery.length >= 2" class="p-8 text-center text-gray-500">
                            No results found for "{{ searchQuery }}"
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Toast />
    </div>
</template>

<script setup>
import {ref, watch, nextTick} from 'vue'
import {Link} from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar.vue'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
    topics: {
        type: Array,
        default: () => [],
    },
})

const showSearch = ref(false)
const showMobileMenu = ref(false)
const searchQuery = ref('')
const searchResults = ref([])
const searchInput = ref(null)

const isActive = (slug) => {
    return window.location.pathname.startsWith(`/topic/${slug}`)
}

function debounce(fn, delay) {
    let timeoutId
    return (...args) => {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => fn(...args), delay)
    }
}

const performSearch = debounce((query) => {
    if (query.length >= 2) {
        axios.get(`/search?q=${encodeURIComponent(query)}`)
            .then(({data}) => searchResults.value = data.results || [])
            .catch(() => window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Search failed' } })))
    } else {
        searchResults.value = []
    }
}, 400)

watch(showSearch, async (value) => {
    if (value) {
        await nextTick()
        searchInput.value?.focus()
    } else {
        searchQuery.value = ''
        searchResults.value = []
    }
})

watch(searchQuery, (query) => {
    performSearch(query)
})

const handleSearch = () => {
    if (searchResults.value.length > 0) {
        window.location.href = `/question/${searchResults.value[0].slug}`
    }
}
</script>
