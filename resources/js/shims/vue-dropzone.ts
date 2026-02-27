import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'VueDropzone',
  props: {
    options: { type: Object, default: () => ({}) }
  },
  emits: ['vdropzone-success', 'vdropzone-error', 'vdropzone-added-file'],
  setup(_, { emit, slots }) {
    const onChange = (e: Event) => {
      const files = (e.target as HTMLInputElement).files;
      if (!files) return;
      // disparamos eventos mínimos para que no reviente el código que los escucha
      Array.from(files).forEach((f) => emit('vdropzone-added-file', f));
    };
    return () =>
      h('div', { class: 'dropzone-shim' }, [
        h('input', { type: 'file', multiple: true, onChange }),
        slots.default?.()
      ]);
  }
});
