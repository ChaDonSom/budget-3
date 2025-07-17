<!-- AFTER: Well-organized component following standards -->
<template>
  <div class="organized-component">
    <!-- Header -->
    <header class="component-header">
      <h1>{{ title }}</h1>
    </header>

    <!-- Main content -->
    <main class="component-content">
      <div v-if="loading" class="loading-state">
        Loading...
      </div>
      
      <form v-else class="form-section" @submit.prevent="handleSubmit">
        <div class="field-group">
          <input 
            v-model="form.name" 
            type="text"
            placeholder="Name"
            class="form-input"
          />
        </div>
        
        <div class="field-group">
          <input 
            v-model="form.email" 
            type="email"
            placeholder="Email"
            class="form-input"
          />
        </div>
        
        <div class="form-actions">
          <button 
            type="submit" 
            :disabled="!isValid || loading"
            class="submit-button"
          >
            Submit
          </button>
        </div>
      </form>
    </main>

    <!-- Modal -->
    <Teleport to="body">
      <Modal v-if="showModal" @close="closeModal">
        <p>Modal content</p>
      </Modal>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
// ==========================================
// IMPORTS
// ==========================================
// Vue core imports
import { ref, computed } from 'vue'

// Third-party imports
import axios from 'axios'

// Local imports - composables
import { useAuth } from '@/core/users/auth'

// Local imports - components
import Modal from '@/core/modals/Modal.vue'

// ==========================================
// COMPONENT DEFINITION
// ==========================================
interface Props {
  title: string
}
const props = defineProps<Props>()

interface Emits {
  'submit-success': [data: any]
  'submit-error': [error: Error]
}
const emit = defineEmits<Emits>()

// ==========================================
// COMPOSABLES
// ==========================================
const auth = useAuth()

// ==========================================
// REACTIVE DATA
// ==========================================
const loading = ref(false)
const showModal = ref(false)
const form = ref({
  name: '',
  email: ''
})

// ==========================================
// COMPUTED PROPERTIES
// ==========================================
const isValid = computed(() => {
  return form.value.name.trim() && form.value.email.trim()
})

// ==========================================
// METHODS
// ==========================================
async function handleSubmit() {
  if (!isValid.value) return
  
  loading.value = true
  
  try {
    const response = await axios.post('/api/submit', form.value)
    emit('submit-success', response.data)
    resetForm()
  } catch (error) {
    emit('submit-error', error as Error)
  } finally {
    loading.value = false
  }
}

function closeModal() {
  showModal.value = false
}

function resetForm() {
  form.value = {
    name: '',
    email: ''
  }
}
</script>

<style scoped lang="scss">
.organized-component {
  padding: 1rem;
}

.component-header {
  margin-bottom: 1.5rem;
  
  h1 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
  }
}

.component-content {
  .loading-state {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
  }
}

.form-section {
  max-width: 400px;
}

.field-group {
  margin-bottom: 1rem;
}

.form-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  
  &:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
}

.form-actions {
  margin-top: 1.5rem;
}

.submit-button {
  background-color: #3b82f6;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  
  &:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
  }
  
  &:hover:not(:disabled) {
    background-color: #2563eb;
  }
}
</style>