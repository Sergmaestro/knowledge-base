<template>
    <Head :title="question.title"/>
    <Layout :topics="topics" :current-topic-slug="question.topic?.slug">
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

                <div class="flex items-center space-x-2"
                     v-if="$page.props.auth?.user">
                    <!-- Bookmark Button -->
                    <BookmarkButton
                        :is_bookmarked="question.is_bookmarked"
                        @toggle="toggleBookmark"
                    />

                    <!-- Completion Toggle -->
                    <CompleteButton
                        :is_completed="question.is_completed"
                        @toggle="toggleProgress"
                    />
                </div>
            </div>

            <!-- Question Content -->
            <div class="bg-white rounded-lg shadow-sm p-8">
                <div
                    class="prose prose-indigo max-w-none"
                    v-html="renderedContent"
                ></div>
            </div>

            <div class="flex items-start justify-end mt-6"
                 v-if="$page.props.auth?.user">
                <div class="flex items-center space-x-2">
                    <BookmarkButton
                        :is_bookmarked="question.is_bookmarked"
                        @toggle="toggleBookmark"/>

                    <CompleteButton
                        :is_completed="question.is_completed"
                        @toggle="toggleProgress"/>
                </div>
            </div>

            <!-- Notes Section -->
            <div v-if="$page.props.auth?.user" class="mt-8 pt-6 border-t">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">My Notes</h3>

                <!-- Existing Notes -->
                <div v-if="question.notes && question.notes.length > 0" class="space-y-4 mb-6">
                    <div
                        v-for="note in question.notes"
                        :key="note.id"
                        class="bg-white rounded-lg shadow-sm p-4"
                    >
                        <div v-if="editingNoteId === note.id" class="space-y-3">
                            <textarea
                                v-model="editNoteText"
                                class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                rows="3"
                            ></textarea>
                            <div class="flex space-x-2">
                                <button
                                    @click="updateNote(note.id)"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
                                >
                                    Save
                                </button>
                                <button
                                    @click="cancelEdit"
                                    class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ note.note }}</p>
                            <div class="flex items-center justify-between mt-2 text-sm text-gray-500">
                                <span>{{ formatDate(note.created_at) }}</span>
                                <div class="flex space-x-2">
                                    <button
                                        @click="startEdit(note)"
                                        class="text-indigo-600 hover:text-indigo-800"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="confirmDeleteNote(note.id)"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Note Form -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Add a note</h4>
                    <textarea
                        v-model="newNote"
                        class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        rows="3"
                        placeholder="Write your note here..."
                    ></textarea>
                    <div class="mt-2 flex justify-end">
                        <button
                            @click="addNote"
                            :disabled="!newNote.trim()"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Add Note
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8 pt-6 border-t">
                <Link
                    v-if="prev_question"
                    :href="`/question/${prev_question.slug}`"
                    class="flex items-center text-indigo-600 hover:text-indigo-700"
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ prev_question.title }}
                </Link>
                <div v-else></div>

                <Link
                    v-if="next_question"
                    :href="`/question/${next_question.slug}`"
                    class="flex items-center text-indigo-600 hover:text-indigo-700"
                >
                    {{ next_question.title }}
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </Link>
            </div>

        </div>
        <ConfirmDialog
            :show="showDeleteConfirm"
            title="Delete Note"
            message="Are you sure you want to delete this note?"
            confirm-text="Delete"
            @confirm="deleteNote"
            @close="showDeleteConfirm = false; noteToDelete = null"
        />
    </Layout>
</template>

<script setup>
import Layout from '@/Layouts/AppLayout.vue'
import {marked} from 'marked'
import {Head, Link, router} from '@inertiajs/vue3'
import {computed, ref} from "vue";
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import CompleteButton from '@/Components/CompleteButton.vue';
import BookmarkButton from '@/Components/BookmarkButton.vue';

const props = defineProps({
    question: Object,
    next_question: Object,
    prev_question: Object,
    topics: {
        type: Array,
        default: () => [],
    },
})

const newNote = ref('')
const editingNoteId = ref(null)
const editNoteText = ref('')
const showDeleteConfirm = ref(false)
const noteToDelete = ref(null)

const renderedContent = computed(() => {
    return marked(props.question.content || '')
})

const toggleProgress = () => {
    axios.post('/progress/toggle', {question_id: props.question.id})
        .then(() => router.reload())
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to toggle progress'}})))
}

const toggleBookmark = () => {
    axios.post('/bookmark/toggle', {question_id: props.question.id})
        .then(() => router.reload())
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to toggle bookmark'}})))
}

const addNote = () => {
    if (!newNote.value.trim()) return

    axios.post('/notes', {
        question_id: props.question.id,
        note: newNote.value,
    })
        .then(() => {
            newNote.value = ''
            router.reload()
        })
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to add note'}})))
}

const startEdit = (note) => {
    editingNoteId.value = note.id
    editNoteText.value = note.note
}

const cancelEdit = () => {
    editingNoteId.value = null
    editNoteText.value = ''
}

const updateNote = (noteId) => {
    if (!editNoteText.value.trim()) return

    axios.patch(`/notes/${noteId}`, {
        note: editNoteText.value,
    })
        .then(() => {
            cancelEdit()
            router.reload()
        })
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to update note'}})))
}

const confirmDeleteNote = (noteId) => {
    noteToDelete.value = noteId
    showDeleteConfirm.value = true
}

const deleteNote = () => {
    if (!noteToDelete.value) return

    axios.delete(`/notes/${noteToDelete.value}`)
        .then(() => router.reload())
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to delete note'}})))
    showDeleteConfirm.value = false
    noteToDelete.value = null
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

const goBack = () => {
    if (window.history.length) {
        window.history.back()
    } else {
        router.visit('/')
    }
}
</script>
