<template>
    <Layout :topics="topics">
        <div class="space-y-8">
            <!-- Hero Section -->
            <div class="text-center py-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Knowledge Base</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    A comprehensive collection of interview questions and answers for senior software engineers.
                </p>
            </div>

            <!-- Overall Progress -->
            <div v-if="overallProgress" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Your Progress</h2>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div
                        class="bg-indigo-600 h-4 rounded-full transition-all duration-500"
                        :style="{ width: `${overallProgress.percentage}%` }"
                    ></div>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    {{ overallProgress.completed }} of {{ overallProgress.total }} questions completed
                    ({{ overallProgress.percentage }}%)
                </p>
            </div>

            <!-- Topics Grid -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="topic in topics"
                    :key="topic.slug"
                    :href="`/topic/${topic.slug}`"
                    class="bg-white rounded-lg shadow hover:shadow-md transition p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <Icon :name="topic.icon" class="w-6 h-6 text-indigo-600" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ topic.name }}</h3>
                        </div>
                        <span
                            v-if="topic.questions_count"
                            class="text-sm text-gray-500">
                        {{ topic.questions_count }} questions
                        </span>
                    </div>

                    <p v-if="topic.description" class="text-sm text-gray-600 mb-4">
                        {{ topic.description }}
                    </p>

                    <div v-if="topic.progress" class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-500">Progress</span>
                            <span class="font-medium"
                                  :class="topic.progress.completed === topic.progress.total ? 'text-green-600' : 'text-gray-900'">
                                {{ topic.progress.completed }}/{{ topic.progress.total }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-indigo-600 h-2 rounded-full"
                                :style="{ width: `${(topic.progress.completed / topic.progress.total) * 100}%` }"></div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="topics.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No topics yet</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Run <code class="bg-gray-100 px-2 py-1 rounded">php artisan markdown:load</code> to load content.
                </p>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from '@/Layouts/AppLayout.vue'
import {computed} from 'vue'
import {Link} from "@inertiajs/vue3";
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    topics: {
        type: Array,
        default: () => [],
    },
})

const overallProgress = computed(() => {
    const topicsWithProgress = props.topics.filter(t => t.progress)
    if (topicsWithProgress.length === 0) return null

    const total = topicsWithProgress.reduce((sum, t) => sum + t.progress.total, 0)
    const completed = topicsWithProgress.reduce((sum, t) => sum + t.progress.completed, 0)

    return {
        total,
        completed,
        percentage: total > 0 ? Math.round((completed / total) * 100) : 0,
    }
})
</script>
