<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="show"
                class="fixed top-4 left-0 right-0 w-fit mx-auto z-50 flex items-center gap-3 rounded-lg px-4 py-3 shadow-lg"
                :class="typeClasses"
            >
                <span class="text-sm font-medium">{{ message }}</span>
                <button
                    @click="close"
                    class="rounded p-1 hover:bg-black/10"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const show = ref(false)
const message = ref('')
const type = ref('error')

const typeClasses = computed(() => {
    switch (type.value) {
        case 'success':
            return 'bg-green-600 text-white'
        case 'error':
            return 'bg-red-600 text-white'
        case 'warning':
            return 'bg-yellow-500 text-white'
        default:
            return 'bg-gray-800 text-white'
    }
})

const close = () => {
    show.value = false
}

const showToast = (msg, toastType = 'error') => {
    message.value = msg || 'An error occurred. Please try again.'
    type.value = toastType
    show.value = true

    setTimeout(() => {
        close()
    }, 5000)
}

const handleError = (event) => {
    showToast(event.detail?.message || 'An error occurred. Please try again.')
}

onMounted(() => {
    window.addEventListener('show-toast', handleError)
})

onUnmounted(() => {
    window.removeEventListener('show-toast', handleError)
})
</script>
