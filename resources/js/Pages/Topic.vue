<template>
  <Layout :topics="allTopics">
    <div>
      <!-- Topic Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ topic.name }}</h1>
            <p v-if="topic.description" class="mt-2 text-gray-600">{{ topic.description }}</p>
          </div>
        </div>

        <!-- Progress Bar -->
        <div v-if="progress" class="mt-6">
          <div class="flex items-center justify-between text-sm mb-2">
            <span class="text-gray-600">Progress</span>
            <span class="font-medium" :class="progress.completed === progress.total ? 'text-green-600' : 'text-gray-900'">
              {{ progress.completed }} / {{ progress.total }} completed
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div
              class="bg-indigo-600 h-3 rounded-full transition-all duration-500"
              :style="{ width: `${(progress.completed / progress.total) * 100}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Questions List -->
      <div class="space-y-4">
        <Link
          v-for="question in questions"
          :key="question.id"
          :href="`/question/${question.slug}`"
          class="block bg-white rounded-lg shadow-sm hover:shadow-md transition p-4"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <!-- Completion Checkbox -->
              <button
                v-if="$page.props.auth?.user"
                @click.prevent="toggleProgress(question.id)"
                class="flex-shrink-0"
              >
                <svg
                  v-if="question.is_completed"
                  class="w-6 h-6 text-green-500"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg
                  v-else
                  class="w-6 h-6 text-gray-300 hover:text-gray-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                </svg>
              </button>

              <span class="font-medium text-gray-900">{{ question.title }}</span>
            </div>

            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>
      </div>

      <!-- Empty State -->
      <div v-if="questions.length === 0" class="text-center py-12">
        <p class="text-gray-500">No questions in this topic yet.</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  topic: Object,
  questions: {
    type: Array,
    default: () => [],
  },
  progress: Object,
  topics: {
    type: Array,
    default: () => [],
  },
})

const allTopics = computed(() => {
  return props.topics || []
})

const toggleProgress = async (questionId) => {
  try {
    await fetch('/progress/toggle', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({ question_id: questionId }),
    })
    window.location.reload()
  } catch (error) {
    console.error('Failed to toggle progress:', error)
  }
}
</script>
