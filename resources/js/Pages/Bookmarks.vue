<template>
  <Layout :topics="topics">
    <div class="space-y-8">
      <!-- Hero Section -->
      <div class="text-center py-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Bookmarks</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Your saved questions for quick reference during interview preparation.
        </p>
      </div>

      <div v-if="bookmarks.length > 0" class="space-y-4">
        <Link
          v-for="bookmark in bookmarks"
          :key="bookmark.id"
          :href="`/question/${bookmark.slug}`"
          class="block bg-white rounded-lg shadow-sm hover:shadow-md transition p-6"
        >
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ bookmark.title }}</h2>
              <span class="text-sm text-indigo-600">{{ bookmark.topic?.name }}</span>
            </div>
            <button
              @click.prevent="removeBookmark(bookmark.id)"
              class="p-2 text-gray-400 hover:text-red-500 transition"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </Link>
      </div>

      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No bookmarks yet</h3>
        <p class="mt-1 text-sm text-gray-500">Bookmark questions to find them quickly later.</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  bookmarks: {
    type: Array,
    default: () => [],
  },
  topics: {
    type: Array,
    default: () => [],
  },
})

const removeBookmark = async (questionId) => {
  try {
    await router.post('/bookmark/toggle', { question_id: questionId })
    window.location.reload()
  } catch (error) {
    console.error('Failed to remove bookmark:', error)
  }
}
</script>
