<script setup>
defineOptions({
  name: 'AppTextarea',
  inheritAttrs: false,
})


// const { class: _class, label, variant: _, ...restAttrs } = useAttrs()
const elementId = computed(() => {
  const attrs = useAttrs()
  const _elementIdToken = attrs.id
  const _id = useId()
  
  return _elementIdToken ? `app-textarea-${ _elementIdToken }` : _id
})

const label = computed(() => useAttrs().label)
</script>

<template>
  <div
    class="app-textarea flex-grow-1"
    :class="$attrs.class"
  >
    <VLabel
      v-if="label"
      :for="elementId"
      class="mb-1 text-body-2"
      :text="label"
    />
    <VTextarea
      v-bind="{
        ...$attrs,
        class: null,
        label: undefined,
        variant: 'outlined',
        id: elementId,
      }"
    >
      <template
        v-for="(_, name) in $slots"
        #[name]="slotProps"
      >
        <slot
          :name="name"
          v-bind="slotProps || {}"
        />
      </template>
    </VTextarea>
  </div>
</template>
