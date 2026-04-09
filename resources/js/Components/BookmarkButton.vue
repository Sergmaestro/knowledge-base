<template>
    <button
        v-if="$page.props.auth?.user"
        @click="$emit('toggle')"
        :disabled="loading"
        class="p-2 text-gray-400 hover:text-gray-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <svg
            v-if="is_bookmarked"
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
</template>

<script setup>
defineProps({
    is_bookmarked: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['toggle'])
</script>
