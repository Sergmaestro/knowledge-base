<template>
    <aside class="w-64 flex-shrink-0 hidden lg:block">
        <nav class="sticky top-6 space-y-1">
            <Link
                v-for="topic in topics"
                :key="topic.slug"
                :href="`/topic/${topic.slug}`"
                class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition"
                :class="[
                    isActive(topic.slug)
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                ]">
                <span>{{ topic.name }}</span>
                <span
                    v-if="topic.progress"
                    class="text-xs px-2 py-0.5 rounded-full"
                    :class="topic.progress.completed === topic.progress.total ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                    {{ topic.progress.completed }}/{{ topic.progress.total }}
                </span>
            </Link>
        </nav>
    </aside>
</template>

<script setup>
import {Link} from '@inertiajs/vue3'

const props = defineProps({
    topics: {
        type: Array,
        default: () => [],
    },
})

const isActive = (slug) => {
    return window.location.pathname.startsWith(`/topic/${slug}`)
}
</script>
