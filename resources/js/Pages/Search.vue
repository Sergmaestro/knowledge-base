<template>
    <Layout>
        <div class="max-w-4xl">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Search Results</h1>

            <div v-if="query" class="mb-6">
                <p class="text-gray-600">
                    Results for "{{ query }}"
                </p>
            </div>

            <div v-if="results.length > 0" class="space-y-4">
                <Link
                    v-for="result in results"
                    :key="result.id"
                    :href="`/question/${result.slug}`"
                    class="block bg-white rounded-lg shadow-sm hover:shadow-md transition p-6"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ result.title }}</h2>
                            <span class="text-sm text-indigo-600">{{ result.topic.name }}</span>
                        </div>
                    </div>
                    <p class="text-gray-600 mt-2">{{ result.excerpt }}</p>
                </Link>
            </div>

            <div v-else-if="query && query.length >= 2" class="text-center py-12">
                <p class="text-gray-500">No results found for "{{ query }}"</p>
            </div>

            <div v-else class="text-center py-12">
                <p class="text-gray-500">Enter a search term to find questions.</p>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from '@/Layouts/AppLayout.vue'
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    query: String,
    results: {
        type: Array,
        default: () => [],
    },
})
</script>
