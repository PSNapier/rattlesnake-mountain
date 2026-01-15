<template>
  <span v-if="placeholder && !modelValue" class="text-muted-foreground">
    {{ placeholder }}
  </span>
  <slot v-else />
</template>

<script setup lang="ts">
interface Props {
  placeholder?: string
  modelValue?: any
}

defineProps<Props>()
</script>
