<!-- BEFORE: Disorganized component with mixed imports and no clear structure -->
<template>
    <div>
        <h1>{{ title }}</h1>
        <div v-if="loading">Loading...</div>
        <div v-else>
            <input v-model="form.name" />
            <input v-model="form.email" />
            <button @click="submit">Submit</button>
        </div>
        <Modal v-if="showModal" @close="closeModal">
            <p>Modal content</p>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import Modal from '@/core/modals/Modal.vue'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useAuth } from '@/core/users/auth'

const auth = useAuth()
const loading = ref(false)
const showModal = ref(false)

interface Props {
    title: string
}
const props = defineProps<Props>()

const form = ref({
    name: '',
    email: ''
})

const submit = async () => {
    loading.value = true
    try {
        await axios.post('/api/submit', form.value)
    } finally {
        loading.value = false
    }
}

const closeModal = () => {
    showModal.value = false
}

const isValid = computed(() => {
    return form.value.name && form.value.email
})
</script>

<style scoped>
div {
    padding: 1rem;
}
</style>