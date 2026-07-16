<template>
  <div class="relative">
    <button
      ref="trigger"
      type="button"
      :class="cn(buttonVariants({ variant, size }), className)"
      v-bind="$attrs"
      @click="toggleOpen"
    >
      <span v-if="placeholder && !modelValue" class="text-muted-foreground">
        {{ placeholder }}
      </span>
      <span v-else-if="selectedOption">
        {{ selectedOption.label }}
      </span>
      <ChevronDownIcon class="ml-2 h-4 w-4 opacity-50" />
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="dropdown"
        class="fixed z-[100] min-w-[var(--select-width)] rounded-md border border-gray-200 bg-white py-1 shadow-lg"
        :style="dropdownStyle"
      >
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
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
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
const dropdown = ref<HTMLDivElement>()
const open = ref(false)
const dropdownStyle = ref({ top: '0px', left: '0px', '--select-width': '8rem' })

const selectedOption = computed(() => {
  return props.options.find(option => option.value === props.modelValue)
})

const optionHeight = 40
const dropdownPadding = 8

function updatePosition() {
  if (!trigger.value || !open.value) return

  const rect = trigger.value.getBoundingClientRect()
  const gap = 4
  const estimatedHeight = props.options.length * optionHeight + dropdownPadding
  const spaceBelow = window.innerHeight - rect.bottom - gap
  const spaceAbove = rect.top - gap
  const placeAbove = estimatedHeight > spaceBelow && spaceAbove > spaceBelow

  dropdownStyle.value = {
    left: `${rect.left}px`,
    '--select-width': `${rect.width}px`,
    top: placeAbove
      ? `${rect.top - estimatedHeight - gap}px`
      : `${rect.bottom + gap}px`,
  }
}

function toggleOpen() {
  open.value = !open.value
  if (open.value) {
    updatePosition()
  }
}

const selectOption = (option: Option) => {
  emit('update:modelValue', option.value)
  open.value = false
}

const handleClickOutside = (event: Event) => {
  const target = event.target as Node
  if (
    open.value &&
    trigger.value &&
    !trigger.value.contains(target) &&
    dropdown.value &&
    !dropdown.value.contains(target)
  ) {
    open.value = false
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    requestAnimationFrame(updatePosition)
    window.addEventListener('scroll', updatePosition, true)
    window.addEventListener('resize', updatePosition)
  } else {
    window.removeEventListener('scroll', updatePosition, true)
    window.removeEventListener('resize', updatePosition)
  }
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
})
</script>
