<script setup>
import { ref } from 'vue';
import Modal from './Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm',
    },
    message: {
        type: String,
        default: 'Are you sure?',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const handleConfirm = () => {
    emit('confirm');
    emit('close');
};

const handleCancel = () => {
    emit('cancel');
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-4 text-center">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ title }}
            </h3>
            <p class="mt-2 text-sm text-gray-600">
                {{ message }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="handleCancel"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
