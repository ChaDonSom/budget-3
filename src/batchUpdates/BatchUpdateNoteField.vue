<template>
  <transition
      name="opacity-0-scale-097-150ms"
      mode="out-in"
  >
    <div class="my-7 flex justify-center">
      <OutlinedTextarea v-model="modelValue">
        Note
      </OutlinedTextarea>
    </div>
  </transition>
</template>

<script lang="ts" setup>
defineProps({
  modelValue: {
    type: String,
    required: true,
  }
})
</script>

<style lang="scss" scoped></style>