<template>
  <div class="m-2 mt-4">
    <label class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon" ref="mainRef">
      <span class="mdc-notched-outline">
        <span class="mdc-notched-outline__leading"></span>
        <span class="mdc-notched-outline__notch">
          <span class="mdc-floating-label" :id="`textfield-label-${id}`"><slot /></span>
        </span>
        <span class="mdc-notched-outline__trailing"></span>
      </span>
      <input
          :id="`textfield-input-${id}`"
          :type="type ?? 'text'"
          class="mdc-text-field__input"
          :style="{ 'margin-right': loading ? '32px' : 'inherit' }"
          :aria-labelledby="`textfield-label-${id}`"
          :value="modelValue"
          :autofocus="autofocus"
          @focus="autoselect ? ($event.target as HTMLInputElement).select() : null"
          @input="$emit('update:modelValue', ($event?.target as HTMLInputElement)?.value)"
          @keypress.enter="$emit('keypress-enter', $event)"
      >
      <TextfieldIcon
          v-if="!loading"
          v-tooltip="'Find user'"
          @click="$emit('icon-click', $event)"
          trailing
      >send</TextfieldIcon>
      <Circular v-if="loading" style="position: absolute; right: 0;"></Circular>
    </label>
    <div class="mdc-text-field-helper-line max-w-fit">
      <div 
          id="my-helper-id" 
          class="mdc-text-field-helper-text"
          aria-hidden="true"
          :style="{ opacity: 1, color: error ? 'rgb(181 30 30)' : undefined }"
      >
        {{ error ? error : (helper ? helper : '') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { MDCTextField } from '@material/textfield'
import { onMounted, ref } from 'vue'
import TextfieldIcon from '@/core/fields/TextfieldIcon.vue';
import Circular from '@/core/loaders/Circular.vue';

const emit = defineEmits(['update:modelValue', 'icon-click', 'keypress-enter'])

const props = defineProps({
  modelValue: [String, Number],
  type: String,
  error: String,
  helper: String,
  autoselect: Boolean,
  autofocus: Boolean,
  loading: Boolean,
})

const id = ref(Math.floor(Math.random() * 10000000))
const mainRef = ref<Element | null>(null)
const mdcTextfield = ref<MDCTextField | null>(null)
onMounted(() => {
  if (mainRef.value) mdcTextfield.value = new MDCTextField(mainRef.value)
  if (props.autofocus) mainRef.value?.querySelector('input')?.focus()
})
</script>

<style scoped lang="scss">
@use "@/css/mdc-theme";
@use "@material/floating-label/mdc-floating-label";
@use "@material/line-ripple/mdc-line-ripple";
@use "@material/notched-outline/mdc-notched-outline";
@use "@material/textfield";

@include textfield.core-styles;

.mdc-text-field {
  @include textfield.outline-shape-radius(mdc-theme.$textfield-shape-radius);
  // @include textfield.outlined-density(-4);
  @include textfield.outline-color(var(--color-border));
  @include textfield.label-color(var(--color-text));
  @include textfield.ink-color(var(--color-text));
  @include textfield.hover-outline-color(var(--color-border-hover));
}

// Tailwind 'undo' RE MDC mess-up, recommended by Adam Wathan (fixes tons of borders showing up on focus in MDC)
*,
*::before,
*::after {
  border-width: 1px;
  border-style: none;
}

// For some reason, if we don't have this, date-type inputs don't show the calendar icon
input[type="date"]::-webkit-calendar-picker-indicator {
    display: block;
    -webkit-appearance: button;
}
</style>