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
            <div class="flex items-center gap-2 mb-2" v-if="question.tag">
                <Tag :label="question.tag" />
            </div>
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ question.title }}</h1>
                </div>

                <div class="flex items-center space-x-2"
                     v-if="$page.props.auth?.user">
                    <!-- Bookmark Button -->
                    <BookmarkButton
                        :is_bookmarked="question.is_bookmarked"
                        :loading="isTogglingBookmark"
                        @toggle="toggleBookmark"
                    />

                    <!-- Completion Toggle -->
                    <CompleteButton
                        :is_completed="question.is_completed"
                        :loading="isTogglingProgress"
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
                        :loading="isTogglingBookmark"
                        @toggle="toggleBookmark"/>

                    <CompleteButton
                        :is_completed="question.is_completed"
                        :loading="isTogglingProgress"
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
                                    :disabled="isUpdatingNote"
                                    class="flex items-center px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <svg v-if="isUpdatingNote" class="w-4 h-4 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
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
                            :disabled="!newNote.trim() || isAddingNote"
                            class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isAddingNote" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Add Note
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between gap-2 mt-8 pt-6 border-t">
                <Link
                    v-if="question.navigation.prev"
                    :href="`/question/${question.navigation.prev.slug}`"
                    class="flex items-center text-indigo-600 hover:text-indigo-700"
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ question.navigation.prev.title }}
                </Link>
                <div v-else></div>

                <Link
                    v-if="question.navigation.next"
                    :href="`/question/${question.navigation.next.slug}`"
                    class="flex items-center text-indigo-600 hover:text-indigo-700"
                >
                    {{ question.navigation.next.title }}
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
import Tag from '@/Components/Tag.vue';

const props = defineProps({
    question: Object,
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

const isTogglingProgress = ref(false)
const isTogglingBookmark = ref(false)
const isAddingNote = ref(false)
const isUpdatingNote = ref(false)
const isDeletingNote = ref(false)

const renderedContent = computed(() => {
    return marked(props.question.content || '')
})

const toggleProgress = () => {
    if (isTogglingProgress.value) return
    isTogglingProgress.value = true
    axios.post(route('progress.toggle'), {question_id: props.question.id})
        .then(() => router.reload())
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to toggle progress'}})))
        .finally(() => isTogglingProgress.value = false)
}

const toggleBookmark = () => {
    if (isTogglingBookmark.value) return
    isTogglingBookmark.value = true
    axios.post(route('bookmarks.toggle'), {question_id: props.question.id})
        .then(() => router.reload())
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to toggle bookmark'}})))
        .finally(() => isTogglingBookmark.value = false)
}

const addNote = () => {
    if (!newNote.value.trim() || isAddingNote.value) return
    isAddingNote.value = true

    axios.post(route('notes.store'), {
        question_id: props.question.id,
        note: newNote.value,
    })
        .then(() => {
            newNote.value = ''
            router.reload()
        })
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to add note'}})))
        .finally(() => isAddingNote.value = false)
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
    if (!editNoteText.value.trim() || isUpdatingNote.value) return
    isUpdatingNote.value = true

    axios.patch(route('notes.update', noteId), {
        note: editNoteText.value,
    })
        .then(() => {
            cancelEdit()
            router.reload()
        })
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to update note'}})))
        .finally(() => isUpdatingNote.value = false)
}

const confirmDeleteNote = (noteId) => {
    noteToDelete.value = noteId
    showDeleteConfirm.value = true
}

const deleteNote = () => {
    if (!noteToDelete.value || isDeletingNote.value) return
    isDeletingNote.value = true

    axios.delete(route('notes.destroy'), noteToDelete.value)
        .then(() => router.reload())
        .catch(() => window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Failed to delete note'}})))
        .finally(() => {
            isDeletingNote.value = false
            showDeleteConfirm.value = false
            noteToDelete.value = null
        })
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
