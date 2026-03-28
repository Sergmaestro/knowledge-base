<template>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="colorClasses">
        {{ label }}
    </span>
</template>

<script setup>
import {computed} from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
});

const colorClasses = computed(() => {
    const colors = {
        fundamentals: 'bg-green-100 text-green-800',
        advanced: 'bg-purple-100 text-purple-800',
        oop: 'bg-blue-100 text-blue-800',
        postgres: 'bg-yellow-100 text-yellow-800',
        mysql: 'bg-orange-100 text-orange-800',
        architecture: 'bg-cyan-100 text-cyan-800',
        security: 'bg-red-100 text-red-800',
        default: 'bg-gray-100 text-gray-800',
    };

    return colors[props.label.toLowerCase()] || colors.default;
});
</script>
