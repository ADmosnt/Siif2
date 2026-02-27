<script lang="ts">
import { defineComponent } from 'vue';

const isNaN = Number.isNaN || window.isNaN;
const REGEXP_NUMBER = /^-?(?:\d+|\d+\.\d+|\.\d+)(?:[eE][-+]?\d+)?$/;
const REGEXP_DECIMALS = /\.\d*(?:0|9){10}\d*$/;
const normalizeDecimalNumber = (value: number, times = 100000000000) => (
  REGEXP_DECIMALS.test(String(value)) ? (Math.round(value * times) / times) : value
);

export default defineComponent({
  name: 'VueNumberInput',

  props: {
    attrs: {
      type: Object,
      default: undefined,
    },

    center: Boolean,
    controls: Boolean,
    disabled: Boolean,

    inputtable: {
      type: Boolean,
      default: true,
    },

    inline: Boolean,

    max: {
      type: Number,
      default: Infinity,
    },

    min: {
      type: Number,
      default: -Infinity,
    },

    name: {
      type: String,
      default: undefined,
    },

    placeholder: {
      type: String,
      default: undefined,
    },

    readonly: Boolean,
    rounded: Boolean,

    size: {
      type: String,
      default: undefined,
    },

    step: {
      type: Number,
      default: 1,
    },

    modelValue: {
      type: Number,
      default: NaN,
    },
  },

  emits: [
    'update:modelValue',
  ],

  data() {
    return {
      value: NaN,
    };
  },

  computed: {
    /**
     * Indicate if the value is increasable.
     * @returns {boolean} Return `true` if it is decreasable, else `false`.
     */
    increasable(): boolean {
      return isNaN(this.value) || this.value < this.max;
    },

    /**
     * Indicate if the value is decreasable.
     * @returns {boolean} Return `true` if it is decreasable, else `false`.
     */
    decreasable(): boolean {
      return isNaN(this.value) || this.value > this.min;
    },
  },

  watch: {
    modelValue: {
      immediate: true,
      handler(newValue, oldValue) {
        if (
          // Avoid triggering change event when created
          !(isNaN(newValue) && typeof oldValue === 'undefined')

          // Avoid infinite loop
          && newValue !== this.value
        ) {
          this.setValue(newValue);
        }
      },
    },
  },

  methods: {
    isNaN,

    /**
     * Change event handler.
     * @param {string} value - The new value.
     */
    change(event: any) {
      this.setValue(event.target.value);
    },

    /**
     * Paste event handler.
     * @param {Event} event - Event object.
     */
    paste(event: ClipboardEvent) {
      const clipboardData = event.clipboardData || (window as any).clipboardData;

      if (clipboardData && !REGEXP_NUMBER.test(clipboardData.getData('text'))) {
        event.preventDefault();
      }
    },

    /**
     * Decrease the value.
     */
    decrease() {
      if (this.decreasable) {
        let { value } = this;

        if (isNaN(value)) {
          value = 0;
        }

        this.setValue(normalizeDecimalNumber(value - this.step));
      }
    },

    /**
     * Increase the value.
     */
    increase() {
      if (this.increasable) {
        let { value } = this;

        if (isNaN(value)) {
          value = 0;
        }

        this.setValue(normalizeDecimalNumber(value + this.step));
      }
    },

    /**
     * Set new value and dispatch change event.
     * @param {number} value - The new value to set.
     */
    setValue(value: number) {
      const oldValue = this.value;
      let newValue = typeof value !== 'number' ? parseFloat(value) : value;

      if (!isNaN(newValue)) {
        if (this.min <= this.max) {
          newValue = Math.min(this.max, Math.max(this.min, newValue));
        }

        if (this.rounded) {
          newValue = Math.round(newValue);
        }
      }

      this.value = newValue;

      if (newValue === oldValue) {
        // Force to override the number in the input box (#13).
        (this.$refs.input as HTMLInputElement).value = String(newValue);
      }

      this.$emit('update:modelValue', newValue, oldValue);
    },
  },
});
</script>

<template>
  <div
    class="relative max-w-full overflow-hidden"
    :class="{
      'inline-block': inline,
      'inline-block w-24': inline && size === 'small',
      'inline-block w-60': inline && size === 'large',
      'inline-block w-[12.5rem]': inline && !size,
    }"
  >
    <button
      v-if="controls"
      class="vue-number-input__button vue-number-input__button--minus absolute top-px bottom-px left-px z-10 border-r border-gray-300 rounded-l disabled:opacity-65 focus:outline-none"
      :class="[
        size === 'small' ? 'w-8' : size === 'large' ? 'w-12' : 'w-10',
        'bg-white dark:bg-gray-700'
      ]"
      type="button"
      tabindex="-1"
      :disabled="disabled || readonly || !decreasable"
      @click.prevent="decrease"
    />


    <input
    ref="input"
    class="block min-w-[3rem] max-w-full min-h-[1.5rem] border transition-colors focus:outline-none"
    :class="[
      'border-gray-300 focus:border-blue-600 bg-white text-black',
      'dark:border-gray-600 dark:bg-black dark:text-white',
      center && 'text-center',
      controls && !size && 'px-14',
      controls && size === 'small' && 'px-10',
      controls && size === 'large' && 'px-16',
      !controls && !size && 'px-3.5 py-1.75',
      size === 'small' && 'text-sm rounded-sm py-1',
      size === 'large' && 'text-xl rounded-md py-2',
      inline && size === 'small' && 'w-24',
      inline && !size && 'w-48',
      inline && size === 'large' && 'w-60',
      !inline && 'w-full'
    ]"
    v-bind="attrs"
    type="number"
    :name="name"
    :value="isNaN(value) ? '' : value"
    :min="min"
    :max="max"
    :step="step"
    :readonly="readonly || !inputtable"
    :disabled="disabled || (!decreasable && !increasable)"
    :placeholder="placeholder"
    autocomplete="off"
    @change="change"
    @paste="paste"
    />

    <button
      v-if="controls"
      class="vue-number-input__button absolute top-px bottom-px right-px z-10 border-l border-gray-300 rounded-r disabled:opacity-65 focus:outline-none"
      :class="[
        size === 'small' ? 'w-8' : size === 'large' ? 'w-12' : 'w-10',
        'bg-white dark:bg-gray-700'
      ]"
      type="button"
      tabindex="-1"
      :disabled="disabled || readonly || !increasable"
      @click.prevent="increase"
    />


  </div>
</template>

<style>


.vue-number-input__button::before {
  height: 1px;
  width: 50%;
}

.vue-number-input__button::after {
  height: 50%;
  width: 1px;
}

.vue-number-input__button--minus::after {
  visibility: hidden;
}

.vue-number-input__button::before,
.vue-number-input__button::after {
  content: "";
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  transition: background-color 0.15s;
  background-color: #111; /* Claro por defecto */
}

.dark .vue-number-input__button::before,
.dark .vue-number-input__button::after {
  background-color: #fff; /* Blanco en modo oscuro */
}

/* Quitar flechas en inputs number */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}
</style>