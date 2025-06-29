<script setup>
const props = defineProps({
  selectedCheckbox: {
    type: Array,
    required: true,
  },
  checkboxContent: {
    type: Array,
    required: true,
  },
  gridColumn: {
    type: null,
    required: false,
  },
})

const emit = defineEmits(['update:selectedCheckbox'])

const updateSelectedOption = value => {
  if (typeof value !== 'boolean' && value !== null)
    emit('update:selectedCheckbox', value)
}
</script>

<template>
  <VRow
    v-if="props.checkboxContent && props.selectedCheckbox"
    class="custom-input-wrapper"
  >
    <VCol
      v-for="item in props.checkboxContent"
      :key="item.value"
      v-bind="gridColumn"
    >
      <VLabel
        class="custom-input custom-checkbox rounded cursor-pointer w-100"
        :class="props.selectedCheckbox.includes(item.value) ? 'active' : ''"
      >
        <div>
          <VCheckbox
            :id="`custom-checkbox-with-img-${item.value}`"
            :model-value="props.selectedCheckbox"
            :value="item.value"
            @update:model-value="updateSelectedOption"
          />
        </div>
        <img
          :src="item.bgImage"
          alt="bg-img"
          class="custom-checkbox-image"
        >
      </VLabel>

      <VLabel
        v-if="item.label || $slots.label"
        :for="`custom-checkbox-with-img-${item.value}`"
        class="cursor-pointer"
      >
        <slot
          name="label"
          :label="item.label"
        >
          {{ item.label }}
        </slot>
      </VLabel>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
.custom-checkbox {
  position: relative;
  padding: 0;

  .custom-checkbox-image {
    block-size: 100%;
    inline-size: 100%;
    min-inline-size: 100%;
  }

  .v-checkbox {
    position: absolute;
    inset-block-start: 0;
    inset-inline-end: 0;
    visibility: hidden;
  }

  &:hover,
  &.active {
    .v-checkbox {
      visibility: visible;
    }
  }
}
</style>
