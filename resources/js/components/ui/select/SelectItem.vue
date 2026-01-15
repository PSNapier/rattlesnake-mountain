<template>
  <button
    type="button"
    class="w-full px-3 py-2 text-left hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
    @click="$emit('select', value)"
  >
    <slot>{{ label }}</slot>
  </button>
</template>

<script setup lang="ts">
interface Props {
  value: any
  label?: string
}

defineProps<Props>()

defineEmits<{
  select: [value: any]
}>()
</script>
