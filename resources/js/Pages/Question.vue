<template>
    <Head :title="question.title"/>
    <Layout :topics="topics">
        <div class="max-w-4xl">
            <!-- Breadcrumb -->
            <nav class="flex items-center text-sm text-gray-500 mb-6">
                <Link href="/" class="flex hover:text-gray-700">Home</Link>
                <span class="mx-2">/</span>
                <Link v-if="question.topic" :href="`/topic/${question.topic.slug}`" class="flex hover:text-gray-700">
                    {{ question.topic.name }}
                </Link>
                <span class="mx-2">/</span>
                <span class="text-gray-700">Question</span>
            </nav>

            <!-- Back Button -->
            <div class="mb-4">
                <button
                    @click="goBack"
                    class="flex items-center text-sm text-gray-500 hover:text-gray-700 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </button>
            </div>

            <!-- Question Header -->
            <div class="flex items-start justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900">{{ question.title }}</h1>

                <div class="flex items-center space-x-2">
                    <!-- Bookmark Button -->
                    <button
                        v-if="$page.props.auth?.user"
                        @click="toggleBookmark"
                        class="p-2 text-gray-400 hover:text-gray-600 transition"
                    >
                        <svg
                            v-if="question.is_bookmarked"
                            class="w-6 h-6 text-yellow-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                    </button>

                    <!-- Completion Toggle -->
                    <button
                        v-if="$page.props.auth?.user"
                        @click="toggleProgress"
                        class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition"
                        :class="question.is_completed
              ? 'bg-green-100 text-green-700 hover:bg-green-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                        <svg
                            v-if="question.is_completed"
                            class="w-4 h-4 mr-1"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd"
                                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                  clip-rule="evenodd"/>
                        </svg>
                        {{ question.is_completed ? 'Completed' : 'Mark Complete' }}
                    </button>
                </div>
            </div>

            <!-- Question Content -->
            <div class="bg-white rounded-lg shadow-sm p-8">
                <div
                    class="prose prose-indigo max-w-none"
                    v-html="renderedContent"
                ></div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8 pt-6 border-t">
                <Link
                    v-if="prevQuestion"
                    :href="`/question/${prevQuestion.slug}`"
                    class="flex items-center text-indigo-600 hover:text-indigo-700"
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ prevQuestion.title }}
                </Link>
                <div v-else></div>

                <Link
                    v-if="nextQuestion"
                    :href="`/question/${nextQuestion.slug}`"
                    class="flex items-center text-indigo-600 hover:text-indigo-700"
                >
                    {{ nextQuestion.title }}
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </Link>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from '@/Layouts/AppLayout.vue'
import {marked} from 'marked'
import {Head, Link, router} from '@inertiajs/vue3'
import {computed} from "vue";

const props = defineProps({
    question: Object,
    nextQuestion: Object,
    prevQuestion: Object,
    topics: {
        type: Array,
        default: () => [],
    },
})

const renderedContent = computed(() => {
    return marked(props.question.content || '')
})

const toggleProgress = () => {
    axios.post('/progress/toggle', {question_id: props.question.id})
        .then(() => router.reload())
        .catch(error => console.error('Failed to toggle progress:', error))
}

const toggleBookmark = () => {
    axios.post('/bookmark/toggle', {question_id: props.question.id})
        .then(() => router.reload())
        .catch(error => console.error('Failed to toggle bookmark:', error))
}

const goBack = () => {
    if (document.referrer && document.referrer.includes(window.location.host)) {
        router.visit(document.referrer)
    } else {
        router.visit('/')
    }
}
</script>

<style>
.prose pre {
    background-color: #1f2937;
    color: #e5e7eb;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
}

.prose code {
    background-color: #f3f4f6;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.prose pre code {
    background-color: transparent;
    padding: 0;
}

.prose table {
    width: 100%;
    border-collapse: collapse;
}

.prose th,
.prose td {
    border: 1px solid #e5e7eb;
    padding: 0.5rem 1rem;
    text-align: left;
}

.prose th {
    background-color: #f9fafb;
    font-weight: 600;
}
</style>
