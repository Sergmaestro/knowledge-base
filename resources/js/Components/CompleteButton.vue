<template>
    <button
        @click="$emit('toggle')"
        class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md transition"
        :class="is_completed
              ? 'bg-green-100 text-green-700 hover:bg-green-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
        <svg
            v-if="is_completed"
            class="w-4 h-4 mr-1"
            fill="currentColor"
            viewBox="0 0 20 20"
        >
            <path fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"/>
        </svg>
        {{ is_completed ? 'Completed' : 'Mark Complete' }}
    </button>
</template>

<script setup>
defineProps({
    is_completed: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['toggle'])
</script>
