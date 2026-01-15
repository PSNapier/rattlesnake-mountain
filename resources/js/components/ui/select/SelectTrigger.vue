<template>
  <button
    ref="trigger"
    type="button"
    :class="cn(buttonVariants({ variant, size }), className)"
    v-bind="$attrs"
  >
    <span v-if="placeholder && !modelValue" class="text-muted-foreground">
      {{ placeholder }}
    </span>
    <slot v-else />
    <ChevronDownIcon class="ml-2 h-4 w-4 opacity-50" />
  </button>
</template>

<script setup lang="ts">
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { cn } from '@/lib/utils'
import { buttonVariants } from '@/components/ui/button'

interface Props {
  modelValue?: any
  placeholder?: string
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

withDefaults(defineProps<Props>(), {
  variant: 'outline',
  size: 'default',
})
</script>
