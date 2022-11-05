<template>
  <div class="flex flex-col items-center relative">
    <h1 class="my-6 text-2xl">{{ form.name ? form.name : '??' }}'s Profile</h1>
    <Textfield v-model="form.name" :error="form.errors.name" autoselect>Name</Textfield>
    <Textfield v-model="form.email" :error="form.errors.email" autoselect>Email</Textfield>
    <SaveButton @click="submit" :disabled="form.processing" :loading="form.processing" />

    <Button @click="changePassword">Change password</Button>

    <h3 class="text-2xl mb-4 mt-8">Share your budget with others</h3>
    <SearchForUserTextfield
        v-model="addUserForm.search"
        autoselect
        :error="addUserForm.errors.search"
        :loading="addUserForm.processing"
        @icon-click="addUser"
        @keypress-enter="addUser"
    >
      Email or name
    </SearchForUserTextfield>
    <h3 class="text-xl mb-4 mt-4" v-if="form.shared_users?.length">Currently shared with</h3>
    <div v-for="user of form.shared_users" class="flex items-center gap-3">
      <p>{{ user.name }}: {{ user.email }}</p>
      <IconButton v-tooltip="'Remove user'" @click="removeUser(user.id)">close</IconButton>
    </div>
    <h3 class="text-l mb-4 mt-4" v-if="form.users_who_shared_to_me?.length">Currently sharing with you</h3>
    <div v-for="user of form.users_who_shared_to_me">{{ user.name }}: {{ user.email }}</div>

    <transition name="error-message">
      <p
          v-if="form.errors.message || addUserForm.errors.message"
          class="bg-red-200 rounded-3xl py-3 px-4 absolute text-red-800" style="bottom: -4rem;"
      >
        {{ form.errors.message ?? (
          addUserForm.errors.message == 'No query results for model [App\\Models\\User].'
            ? "Sorry, we couldn't find a user by that name or email."
            : addUserForm.errors.message
        ) }}
      </p>
    </transition>
  </div>
</template>

<script setup lang="ts">
import SaveButton from '@/core/buttons/SaveButton.vue';
import { useAuth, type User } from '@/core/users/auth';
import { useForm } from '@/store/forms';
import Textfield from '@/core/fields/OutlinedTextfield.vue';
import Button from '@/core/buttons/Button.vue';
import { markRaw, ref } from 'vue';
import SearchForUserTextfield from '@/core/users/SearchForUserTextfield.vue';
import IconButton from '@/core/buttons/IconButton.vue';
import { useModals } from '@/store/modals';
import PasswordModalVue from '@/core/users/auth/PasswordModal.vue';

const auth = useAuth()

const form = useForm('/user/profile-information', JSON.parse(JSON.stringify(auth.user)) as User)

const addUserForm = useForm('/user/share-by-search', { search: '' })
async function addUser() {
  // @ts-ignore
  let result = await addUserForm.submit('post') as User
  form.reset(result)
}
async function removeUser(userId: number) {
  form.shared_users = form.shared_users?.filter(user => user.id != userId)
  await form.submit('post', '/user/remove-shared-user')
}
async function submit() {
  await form.submit('put')
  auth.user = JSON.parse(JSON.stringify(form.internalForm))
}

const modals = useModals()
function changePassword() {
  modals.open({
    modal: markRaw(PasswordModalVue),
    props: {
      password: ''
    }
  })
}
</script>

<style scoped lang="scss">
</style>