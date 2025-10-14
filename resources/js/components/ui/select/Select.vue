<template>
  <div class="relative">
    <button
      ref="trigger"
      type="button"
      :class="cn(buttonVariants({ variant, size }), className)"
      v-bind="$attrs"
      @click="open = !open"
    >
      <span v-if="placeholder && !modelValue" class="text-muted-foreground">
        {{ placeholder }}
      </span>
      <span v-else-if="selectedOption">
        {{ selectedOption.label }}
      </span>
      <ChevronDownIcon class="ml-2 h-4 w-4 opacity-50" />
    </button>

    <div
      v-if="open"
      class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg"
    >
      <div class="py-1">
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          class="w-full px-3 py-2 text-left hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
          @click="selectOption(option)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { cn } from '@/lib/utils'
import { buttonVariants } from '@/components/ui/button'

interface Option {
  value: any
  label: string
}

interface Props {
  modelValue?: any
  options?: Option[]
  placeholder?: string
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
  className?: string
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [],
  variant: 'outline',
  size: 'default',
})

const emit = defineEmits<{
  'update:modelValue': [value: any]
}>()

const trigger = ref<HTMLButtonElement>()
const open = ref(false)

const selectedOption = computed(() => {
  return props.options.find(option => option.value === props.modelValue)
})

const selectOption = (option: Option) => {
  emit('update:modelValue', option.value)
  open.value = false
}

const handleClickOutside = (event: Event) => {
  if (trigger.value && !trigger.value.contains(event.target as Node)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
