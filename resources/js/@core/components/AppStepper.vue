<script setup>
const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  currentStep: {
    type: Number,
    required: false,
    default: 0,
  },
  direction: {
    type: String,
    required: false,
    default: 'horizontal',
  },
  iconSize: {
    type: [
      String,
      Number,
    ],
    required: false,
    default: 60,
  },
  isActiveStepValid: {
    type: Boolean,
    required: false,
    default: undefined,
  },
  align: {
    type: String,
    required: false,
    default: 'default',
  },
})

const emit = defineEmits(['update:currentStep'])

const currentStep = ref(props.currentStep || 0)
const activeOrCompletedStepsClasses = computed(() => index => index < currentStep.value ? 'stepper-steps-completed' : index === currentStep.value ? 'stepper-steps-active' : '')
const isHorizontalAndNotLastStep = computed(() => index => props.direction === 'horizontal' && props.items.length - 1 !== index)

// check if validation is enabled
const isValidationEnabled = computed(() => {
  return props.isActiveStepValid !== undefined
})

watchEffect(() => {
  if (props.currentStep !== undefined && props.currentStep < props.items.length && props.currentStep >= 0)
    currentStep.value = props.currentStep
  emit('update:currentStep', currentStep.value)
})
</script>

<template>
  <VSlideGroup
    v-model="currentStep"
    class="app-stepper"
    show-arrows
    :direction="props.direction"
    :class="`app-stepper-${props.align} ${props.items[0].icon ? 'app-stepper-icons' : ''}`"
  >
    <VSlideGroupItem
      v-for="(item, index) in props.items"
      :key="item.title"
      :value="index"
    >
      <div
        class="cursor-pointer app-stepper-step pa-1"
        :class="[
          (!props.isActiveStepValid && (isValidationEnabled)) && 'stepper-steps-invalid',
          activeOrCompletedStepsClasses(index),
        ]"
        @click="!isValidationEnabled && emit('update:currentStep', index)"
      >
        <!-- SECTION stepper step with icon -->
        <template v-if="item.icon">
          <div class="stepper-icon-step text-high-emphasis d-flex align-center ">
            <!-- 👉 icon and title -->
            <div
              class="d-flex align-center gap-x-3 step-wrapper"
              :class="[props.direction === 'horizontal' && 'flex-column']"
            >
              <div class="stepper-icon">
                <template v-if="typeof item.icon === 'object'">
                  <Component :is="item.icon" />
                </template>

                <VIcon
                  v-else
                  :icon="item.icon"
                  :size="item.size || props.iconSize"
                />
              </div>

              <div>
                <p class="stepper-title font-weight-medium mb-0">
                  {{ item.title }}
                </p>
                <p
                  v-if="item.subtitle"
                  class="stepper-subtitle mb-0"
                >
                  {{ item.subtitle }}
                </p>
              </div>
            </div>

            <!-- 👉 append chevron -->
            <VIcon
              v-if="isHorizontalAndNotLastStep(index)"
              class="flip-in-rtl stepper-chevron-indicator mx-6"
              size="20"
              icon="tabler-chevron-right"
            />
          </div>
        </template>
        <!-- !SECTION  -->

        <!-- SECTION stepper step without icon -->
        <template v-else>
          <div class="d-flex align-center gap-x-3">
            <div>
              <!-- 👉 custom circle icon -->
              <template v-if="index >= currentStep">
                <VAvatar
                  v-if="(!isValidationEnabled || props.isActiveStepValid || index !== currentStep)"
                  size="38"
                  rounded
                  :variant="index === currentStep ? 'elevated' : 'tonal'"
                  :color="index === currentStep ? 'primary' : 'default'"
                >
                  <h5
                    class="text-h5"
                    :style="index === currentStep ? { color: '#fff' } : ''"
                  >
                    {{ index + 1 }}
                  </h5>
                </VAvatar>

                <VAvatar
                  v-else
                  color="error"
                  size="38"
                  rounded
                >
                  <VIcon

                    icon="tabler-alert-circle"
                    size="22"
                  />
                </VAvatar>
              </template>

              <!-- 👉 step completed icon -->

              <VAvatar
                v-else
                class="stepper-icon"
                variant="tonal"
                color="primary"
                size="38"
                rounded
              >
                <h5
                  class="text-h5"
                  style="color: rgb(var(--v-theme-primary));"
                >
                  {{ index + 1 }}
                </h5>
              </VAvatar>
            </div>

            <!-- 👉 title and subtitle -->
            <div class="d-flex flex-column justify-center">
              <div class="stepper-title font-weight-medium">
                {{ item.title }}
              </div>

              <div
                v-if="item.subtitle"
                class="stepper-subtitle text-sm text-disabled"
              >
                {{ item.subtitle }}
              </div>
            </div>

            <!-- 👉 stepper step icon -->
            <div
              v-if="isHorizontalAndNotLastStep(index)"
              class="stepper-step-line stepper-chevron-indicator mx-6"
            >
              <VIcon
                icon="tabler-chevron-right"
                size="20"
              />
            </div>
          </div>
        </template>
        <!-- !SECTION  -->
      </div>
    </VSlideGroupItem>
  </VSlideGroup>
</template>

<style lang="scss">
@use "@core-scss/template/mixins" as templateMixins;

.app-stepper {
  // 👉 stepper step with bg color
  &.stepper-icon-step-bg {
    .stepper-icon-step {
      .step-wrapper {
        flex-direction: row !important;
      }

      .stepper-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        background-color: rgba(var(--v-theme-on-surface), var(--v-selected-opacity));
        block-size: 2.375rem;
        color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
        inline-size: 2.375rem;
      }
    }

    .stepper-steps-active {
      .stepper-icon-step {
        .stepper-icon {
          @include templateMixins.custom-elevation(var(--v-theme-primary), "sm");

          background-color: rgb(var(--v-theme-primary));
          color: rgba(var(--v-theme-on-primary));
        }
      }
    }

    .stepper-steps-completed {
      .stepper-icon-step {
        .stepper-icon {
          background: rgba(var(--v-theme-primary), var(--v-activated-opacity));
          color: rgba(var(--v-theme-primary));
        }
      }
    }
  }

  &.app-stepper-icons:not(.stepper-icon-step-bg) {
    /* stylelint-disable-next-line no-descending-specificity */
    .stepper-icon {
      line-height: 0;
    }

    .step-wrapper {
      padding: 1.25rem;
      gap: 0.5rem;
      min-inline-size: 9.375rem;
    }

    .stepper-chevron-indicator {
      margin-inline: 1rem !important;
    }

    .stepper-steps-completed,
    .stepper-steps-active {
      .stepper-icon-step,
      .stepper-step-icon,
      .stepper-title,
      .stepper-subtitle {
        color: rgb(var(--v-theme-primary)) !important;
      }
    }
  }

  // 👉 stepper step with icon and  default
  .v-slide-group__content {
    row-gap: 1rem;

    /* stylelint-disable-next-line no-descending-specificity */
    .stepper-title {
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      font-size: 0.9375rem;
      font-weight: 500 !important;
    }

    /* stylelint-disable-next-line no-descending-specificity */
    .stepper-subtitle {
      color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
      font-size: 0.8125rem;
      line-height: 1.25rem;
    }

    /* stylelint-disable-next-line no-descending-specificity */
    .stepper-chevron-indicator {
      color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    }

    /* stylelint-disable-next-line no-descending-specificity */
    .stepper-steps-completed {
      /* stylelint-disable-next-line no-descending-specificity */
      .stepper-title,
      .stepper-subtitle {
        color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity));
      }

      .stepper-chevron-indicator {
        color: rgb(var(--v-theme-primary));
      }
    }

    /* stylelint-disable-next-line no-descending-specificity */
    .stepper-steps-active {
      .v-avatar.bg-primary {
        @include templateMixins.custom-elevation(var(--v-theme-primary), "sm");
      }

      .v-avatar.bg-error {
        @include templateMixins.custom-elevation(var(--v-theme-error), "sm");
      }
    }

    .stepper-steps-invalid.stepper-steps-active {
      .stepper-icon-step,
      .step-number,
      .stepper-title,
      .stepper-subtitle {
        color: rgb(var(--v-theme-error)) !important;
      }
    }

    .app-stepper-step {
      &:not(.stepper-steps-active,.stepper-steps-completed) .v-avatar--variant-tonal {
        --v-activated-opacity: 0.06;
      }
    }
  }

  // 👉 stepper alignment
  &.app-stepper-center {
    .v-slide-group__content {
      justify-content: center;
    }
  }

  &.app-stepper-start {
    .v-slide-group__content {
      justify-content: start;
    }
  }

  &.app-stepper-end {
    .v-slide-group__content {
      justify-content: end;
    }
  }
}
</style>
