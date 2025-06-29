<script setup>
const props = defineProps({
  selectedRadio: {
    type: String,
    required: true,
  },
  radioContent: {
    type: Array,
    required: true,
  },
  gridColumn: {
    type: null,
    required: false,
  },
})

const emit = defineEmits(['update:selectedRadio'])

const updateSelectedOption = value => {
  if (value !== null)
    emit('update:selectedRadio', value)
}
</script>

<template>
  <VRadioGroup
    v-if="props.radioContent"
    :model-value="props.selectedRadio"
    class="custom-input-wrapper"
    @update:model-value="updateSelectedOption"
  >
    <VRow>
      <VCol
        v-for="item in props.radioContent"
        :key="item.bgImage"
        v-bind="gridColumn"
      >
        <VLabel
          class="custom-input custom-radio rounded cursor-pointer w-100"
          :class="props.selectedRadio === item.value ? 'active' : ''"
        >
          <slot
            name="content"
            :item="item"
          >
            <template v-if="typeof item.bgImage === 'object'">
              <Component
                :is="item.bgImage"
                class="custom-radio-image"
              />
            </template>
            <img
              v-else
              :src="item.bgImage"
              alt="bg-img"
              class="custom-radio-image"
            >
          </slot>

          <VRadio
            :id="`custom-radio-with-img-${item.value}`"
            :name="`custom-radio-with-img-${item.value}`"
            :value="item.value"
          />
        </VLabel>

        <VLabel
          v-if="item.label || $slots.label"
          :for="`custom-radio-with-img-${item.value}`"
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
  </VRadioGroup>
</template>

<style lang="scss" scoped>
.custom-radio {
  padding: 0 !important;

  &.active {
    border-width: 1px;
  }

  .custom-radio-image {
    block-size: 100%;
    inline-size: 100%;
    min-inline-size: 100%;
  }

  .v-radio {
    visibility: hidden;
  }
}
</style>
