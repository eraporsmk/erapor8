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
      :key="item.title"
      v-bind="gridColumn"
    >
      <VLabel
        class="custom-input custom-checkbox rounded cursor-pointer"
        :class="props.selectedCheckbox.includes(item.value) ? 'active' : ''"
      >
        <div>
          <VCheckbox
            :model-value="props.selectedCheckbox"
            :value="item.value"
            @update:model-value="updateSelectedOption"
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
            <p class="text-sm mb-0">
              {{ item.desc }}
            </p>
          </div>
        </slot>
      </VLabel>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
.custom-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;

  .v-checkbox {
    margin-block-start: -0.375rem;
  }

  .cr-title {
    font-weight: 500;
    line-height: 1.375rem;
  }
}
</style>
