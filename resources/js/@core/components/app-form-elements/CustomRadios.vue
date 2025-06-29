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
        :key="item.title"
        v-bind="gridColumn"
      >
        <VLabel
          class="custom-input custom-radio rounded cursor-pointer"
          :class="props.selectedRadio === item.value ? 'active' : ''"
        >
          <div>
            <VRadio
              :name="item.value"
              :value="item.value"
            />
          </div>
          <slot :item="item">
            <div class="flex-grow-1">
              <div class="d-flex align-center mb-2">
                <h6 class="cr-title text-base">
                  {{ item.title }}
                </h6>
                <VSpacer />
                <span
                  v-if="item.subtitle"
                  class="text-disabled text-body-2"
                >{{ item.subtitle }}</span>
              </div>
              <p class="text-body-2 mb-0">
                {{ item.desc }}
              </p>
            </div>
          </slot>
        </VLabel>
      </VCol>
    </VRow>
  </VRadioGroup>
</template>

<style lang="scss" scoped>
.custom-radio {
  display: flex;
  align-items: flex-start;
  gap: 0.25rem;

  .v-radio {
    margin-block-start: -0.45rem;
  }

  .cr-title {
    font-weight: 500;
    line-height: 1.375rem;
  }
}
</style>
