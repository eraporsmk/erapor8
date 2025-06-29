<script setup>
import { useStorage } from '@vueuse/core'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useTheme } from 'vuetify'
import {
  staticPrimaryColor,
  staticPrimaryDarkenColor,
} from '@/plugins/vuetify/theme'
import {
  Direction,
  Layout,
  Skins,
  Theme,
} from '@core/enums'
import { useConfigStore } from '@core/stores/config'
import horizontalLight from '@images/customizer-icons/horizontal-light.svg'
import {
  AppContentLayoutNav,
  ContentWidth,
} from '@layouts/enums'
import {
  cookieRef,
  namespaceConfig,
} from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'
import borderSkin from '@images/customizer-icons/border-light.svg'
import collapsed from '@images/customizer-icons/collapsed-light.svg'
import compact from '@images/customizer-icons/compact-light.svg'
import defaultSkin from '@images/customizer-icons/default-light.svg'
import ltrSvg from '@images/customizer-icons/ltr-light.svg'
import rtlSvg from '@images/customizer-icons/rtl-light.svg'
import wideSvg from '@images/customizer-icons/wide-light.svg'

const isNavDrawerOpen = ref(false)
const configStore = useConfigStore()
const vuetifyTheme = useTheme()

const colors = [
  {
    main: staticPrimaryColor,
    darken: staticPrimaryDarkenColor,
  },
  {
    main: '#0D9394',
    darken: '#0C8485',
  },
  {
    main: '#FFB400',
    darken: '#E6A200',
  },
  {
    main: '#FF4C51',
    darken: '#E64449',
  },
  {
    main: '#16B1FF',
    darken: '#149FE6',
  },
]

const customPrimaryColor = ref('#663131')

watch(() => configStore.theme, () => {
  const cookiePrimaryColor = cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryColor`, null).value
  if (cookiePrimaryColor && !colors.some(color => color.main === cookiePrimaryColor))
    customPrimaryColor.value = cookiePrimaryColor
}, { immediate: true })

const setPrimaryColor = useDebounceFn(color => {
  vuetifyTheme.themes.value[vuetifyTheme.name.value].colors.primary = color.main
  vuetifyTheme.themes.value[vuetifyTheme.name.value].colors['primary-darken-1'] = color.darken
  cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryColor`, null).value = color.main
  cookieRef(`${ vuetifyTheme.name.value }ThemePrimaryDarkenColor`, null).value = color.darken
  useStorage(namespaceConfig('initial-loader-color'), null).value = color.main
}, 100)

const themeMode = computed(() => {
  return [
    {
      bgImage: 'tabler-sun',
      value: Theme.Light,
      label: 'Light',
    },
    {
      bgImage: 'tabler-moon-stars',
      value: Theme.Dark,
      label: 'Dark',
    },
    {
      bgImage: 'tabler-device-desktop-analytics',
      value: Theme.System,
      label: 'System',
    },
  ]
})

const themeSkin = computed(() => {
  return [
    {
      bgImage: defaultSkin,
      value: Skins.Default,
      label: 'Default',
    },
    {
      bgImage: borderSkin,
      value: Skins.Bordered,
      label: 'Bordered',
    },
  ]
})

const currentLayout = ref(configStore.isVerticalNavCollapsed ? 'collapsed' : configStore.appContentLayoutNav)

const layouts = computed(() => {
  return [
    {
      bgImage: defaultSkin,
      value: Layout.Vertical,
      label: 'Vertical',
    },
    {
      bgImage: collapsed,
      value: Layout.Collapsed,
      label: 'Collapsed',
    },
    {
      bgImage: horizontalLight,
      value: Layout.Horizontal,
      label: 'Horizontal',
    },
  ]
})

watch(currentLayout, () => {
  if (currentLayout.value === 'collapsed') {
    configStore.isVerticalNavCollapsed = true
    configStore.appContentLayoutNav = AppContentLayoutNav.Vertical
  } else {
    configStore.isVerticalNavCollapsed = false
    configStore.appContentLayoutNav = currentLayout.value
  }
})
watch(() => configStore.isVerticalNavCollapsed, () => {
  currentLayout.value = configStore.isVerticalNavCollapsed ? 'collapsed' : configStore.appContentLayoutNav
})

const contentWidth = computed(() => {
  return [
    {
      bgImage: compact,
      value: ContentWidth.Boxed,
      label: 'Compact',
    },
    {
      bgImage: wideSvg,
      value: ContentWidth.Fluid,
      label: 'Wide',
    },
  ]
})

const currentDir = ref(configStore.isAppRTL ? 'rtl' : 'ltr')

const direction = computed(() => {
  return [
    {
      bgImage: ltrSvg,
      value: Direction.Ltr,
      label: 'Left to right',
    },
    {
      bgImage: rtlSvg,
      value: Direction.Rtl,
      label: 'Right to left',
    },
  ]
})

watch(currentDir, () => {
  if (currentDir.value === 'rtl')
    configStore.isAppRTL = true
  else
    configStore.isAppRTL = false
})

const isCookieHasAnyValue = ref(false)
const { locale } = useI18n({ useScope: 'global' })

const isActiveLangRTL = computed(() => {
  const lang = themeConfig.app.i18n.langConfig.find(l => l.i18nLang === locale.value)
  
  return lang?.isRTL ?? false
})

watch([
  () => vuetifyTheme.current.value.colors.primary,
  configStore.$state,
  locale,
], () => {
  const initialConfigValue = [
    staticPrimaryColor,
    staticPrimaryColor,
    themeConfig.app.theme,
    themeConfig.app.skin,
    themeConfig.verticalNav.isVerticalNavSemiDark,
    themeConfig.verticalNav.isVerticalNavCollapsed,
    themeConfig.app.contentWidth,
    isActiveLangRTL.value,
    themeConfig.app.contentLayoutNav,
  ]

  const themeConfigValue = [
    vuetifyTheme.themes.value.light.colors.primary,
    vuetifyTheme.themes.value.dark.colors.primary,
    configStore.theme,
    configStore.skin,
    configStore.isVerticalNavSemiDark,
    configStore.isVerticalNavCollapsed,
    configStore.appContentWidth,
    configStore.isAppRTL,
    configStore.appContentLayoutNav,
  ]

  currentDir.value = configStore.isAppRTL ? 'rtl' : 'ltr'
  isCookieHasAnyValue.value = JSON.stringify(themeConfigValue) !== JSON.stringify(initialConfigValue)
}, {
  deep: true,
  immediate: true,
})

const resetCustomizer = async () => {
  if (isCookieHasAnyValue.value) {
    vuetifyTheme.themes.value.light.colors.primary = staticPrimaryColor
    vuetifyTheme.themes.value.dark.colors.primary = staticPrimaryColor
    vuetifyTheme.themes.value.light.colors['primary-darken-1'] = staticPrimaryDarkenColor
    vuetifyTheme.themes.value.dark.colors['primary-darken-1'] = staticPrimaryDarkenColor
    configStore.theme = themeConfig.app.theme
    configStore.skin = themeConfig.app.skin
    configStore.isVerticalNavSemiDark = themeConfig.verticalNav.isVerticalNavSemiDark
    configStore.appContentLayoutNav = themeConfig.app.contentLayoutNav
    configStore.appContentWidth = themeConfig.app.contentWidth
    configStore.isAppRTL = isActiveLangRTL.value
    configStore.isVerticalNavCollapsed = themeConfig.verticalNav.isVerticalNavCollapsed
    useStorage(namespaceConfig('initial-loader-color'), null).value = staticPrimaryColor
    currentLayout.value = themeConfig.app.contentLayoutNav
    cookieRef('lightThemePrimaryColor', null).value = null
    cookieRef('darkThemePrimaryColor', null).value = null
    cookieRef('lightThemePrimaryDarkenColor', null).value = null
    cookieRef('darkThemePrimaryDarkenColor', null).value = null
    await nextTick()
    isCookieHasAnyValue.value = false
    customPrimaryColor.value = '#ffffff'
  }
}
</script>

<template>
  <div class="d-lg-block d-none">
    <VBtn
      icon
      class="app-customizer-toggler rounded-s-lg rounded-0"
      style="z-index: 1001;"
      @click="isNavDrawerOpen = true"
    >
      <VIcon
        size="22"
        icon="tabler-settings"
      />
    </VBtn>

    <VNavigationDrawer
      v-model="isNavDrawerOpen"
      data-allow-mismatch
      temporary
      touchless
      border="none"
      location="end"
      width="400"
      elevation="10"
      :scrim="false"
      class="app-customizer"
    >
      <!-- 👉 Header -->
      <div class="customizer-heading d-flex align-center justify-space-between">
        <div>
          <h6 class="text-h6">
            Theme Customizer
          </h6>
          <p class="text-body-2 mb-0">
            Customize & Preview in Real Time
          </p>
        </div>

        <div class="d-flex align-center gap-1">
          <VBtn
            icon
            variant="text"
            size="small"
            color="medium-emphasis"
            @click="resetCustomizer"
          >
            <VBadge
              v-show="isCookieHasAnyValue"
              dot
              color="error"
              offset-x="-29"
              offset-y="-14"
            />

            <VIcon
              size="24"
              color="high-emphasis"
              icon="tabler-refresh"
            />
          </VBtn>

          <VBtn
            icon
            variant="text"
            color="medium-emphasis"
            size="small"
            @click="isNavDrawerOpen = false"
          >
            <VIcon
              icon="tabler-x"
              color="high-emphasis"
              size="24"
            />
          </VBtn>
        </div>
      </div>

      <VDivider />

      <PerfectScrollbar
        tag="ul"
        :options="{ wheelPropagation: false }"
      >
        <!-- SECTION Theming -->
        <CustomizerSection
          title="Theming"
          :divider="false"
        >
          <!-- 👉 Primary Color -->
          <div class="d-flex flex-column gap-2">
            <h6 class="text-h6">
              Primary Color
            </h6>

            <div
              class="d-flex app-customizer-primary-colors"
              style="column-gap: 0.75rem; margin-block-start: 2px;"
            >
              <div
                v-for="color in colors"
                :key="color.main"
                style="
              border-radius: 0.375rem;
              outline: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
              padding-block: 0.5rem;
              padding-inline: 0.625rem;"
                class="primary-color-wrapper cursor-pointer"
                :class="vuetifyTheme.current.value.colors.primary === color.main ? 'active' : ''"
                :style="vuetifyTheme.current.value.colors.primary === color.main ? `outline-color: ${color.main}; outline-width:2px;` : `--v-color:${color.main}`"
                @click="setPrimaryColor(color)"
              >
                <div
                  style="border-radius: 0.375rem;block-size: 2.125rem; inline-size: 1.8938rem;"
                  :style="{ backgroundColor: color.main }"
                />
              </div>

              <div
                class="primary-color-wrapper cursor-pointer d-flex align-center"
                style="
              border-radius: 0.375rem;
              outline: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
              padding-block: 0.5rem;
              padding-inline: 0.625rem;"
                :class="vuetifyTheme.current.value.colors.primary === customPrimaryColor ? 'active' : ''"
                :style="vuetifyTheme.current.value.colors.primary === customPrimaryColor ? `outline-color: ${customPrimaryColor}; outline-width:2px;` : ''"
              >
                <VBtn
                  icon
                  size="30"
                  :color="vuetifyTheme.current.value.colors.primary === customPrimaryColor ? customPrimaryColor : $vuetify.theme.current.dark ? '#8692d029' : '#4b465c29'"
                  variant="flat"
                  style="border-radius: 0.375rem;"
                >
                  <VIcon
                    size="20"
                    icon="tabler-color-picker"
                    :color="vuetifyTheme.current.value.colors.primary === customPrimaryColor ? 'rgb(var(--v-theme-on-primary))' : ''"
                  />
                </VBtn>

                <VMenu
                  activator="parent"
                  :close-on-content-click="false"
                >
                  <VList>
                    <VListItem>
                      <VColorPicker
                        v-model="customPrimaryColor"
                        mode="hex"
                        :modes="['hex']"
                        @update:model-value="setPrimaryColor({ main: customPrimaryColor, darken: customPrimaryColor })"
                      />
                    </VListItem>
                  </VList>
                </VMenu>
              </div>
            </div>
          </div>

          <!-- 👉 Theme -->
          <div class="d-flex flex-column gap-2">
            <h6 class="text-h6">
              Theme
            </h6>

            <CustomRadiosWithImage
              :key="configStore.theme"
              v-model:selected-radio="configStore.theme"
              :radio-content="themeMode"
              :grid-column="{ cols: '4' }"
              class="customizer-skins"
            >
              <template #label="item">
                <span class="text-sm text-medium-emphasis mt-1">{{ item?.label }}</span>
              </template>

              <template #content="{ item }">
                <div
                  class="customizer-skins-icon-wrapper d-flex align-center justify-center py-3 w-100"
                  style="min-inline-size: 100%;"
                >
                  <VIcon
                    size="30"
                    :icon="item.bgImage"
                    color="high-emphasis"
                  />
                </div>
              </template>
            </CustomRadiosWithImage>
          </div>

          <!-- 👉 Skin -->
          <div class="d-flex flex-column gap-2">
            <h6 class="text-h6">
              Skins
            </h6>

            <CustomRadiosWithImage
              :key="configStore.skin"
              v-model:selected-radio="configStore.skin"
              :radio-content="themeSkin"
              :grid-column="{ cols: '4' }"
            >
              <template #label="item">
                <span class="text-sm text-medium-emphasis">{{ item?.label }}</span>
              </template>
            </CustomRadiosWithImage>
          </div>

          <!-- 👉 Semi Dark -->
          <div
            class="align-center justify-space-between"
            :class="vuetifyTheme.global.name.value === 'light' && configStore.appContentLayoutNav === AppContentLayoutNav.Vertical ? 'd-flex' : 'd-none'"
          >
            <VLabel
              for="customizer-semi-dark"
              class="text-h6 text-high-emphasis"
            >
              Semi Dark Menu
            </VLabel>

            <div>
              <VSwitch
                id="customizer-semi-dark"
                v-model="configStore.isVerticalNavSemiDark"
                class="ms-2"
              />
            </div>
          </div>
        </CustomizerSection>
        <!-- !SECTION -->

        <!-- SECTION LAYOUT -->
        <CustomizerSection title="Layout">
          <!-- 👉 Layouts -->
          <div class="d-flex flex-column gap-2">
            <h6 class="text-base font-weight-medium">
              Layout
            </h6>

            <CustomRadiosWithImage
              :key="currentLayout"
              v-model:selected-radio="currentLayout"
              :radio-content="layouts"
              :grid-column="{ cols: '4' }"
            >
              <template #label="item">
                <span class="text-sm text-medium-emphasis">{{ item.label }}</span>
              </template>
            </CustomRadiosWithImage>
          </div>

          <!-- 👉 Content Width -->
          <div class="d-flex flex-column gap-2">
            <h6 class="text-base font-weight-medium">
              Content
            </h6>

            <CustomRadiosWithImage
              :key="configStore.appContentWidth"
              v-model:selected-radio="configStore.appContentWidth"
              :radio-content="contentWidth"
              :grid-column="{ cols: '4' }"
            >
              <template #label="item">
                <span class="text-sm text-medium-emphasis">{{ item.label }}</span>
              </template>
            </CustomRadiosWithImage>
          </div>

          <!-- 👉 Direction -->
          <div class="d-flex flex-column gap-2">
            <h6 class="text-base font-weight-medium">
              Direction
            </h6>

            <CustomRadiosWithImage
              :key="currentDir"
              v-model:selected-radio="currentDir"
              :radio-content="direction"
              :grid-column="{ cols: '4' }"
            >
              <template #label="item">
                <span class="text-sm text-medium-emphasis">{{ item?.label }}</span>
              </template>
            </CustomRadiosWithImage>
          </div>
        </CustomizerSection>
        <!-- !SECTION -->
      </PerfectScrollbar>
    </VNavigationDrawer>
  </div>
</template>

<style lang="scss">
@use "@layouts/styles/mixins" as layoutMixins;

.app-customizer {
  &.v-navigation-drawer--temporary:not(.v-navigation-drawer--active) {
    transform: translateX(110%) !important;

    @include layoutMixins.rtl {
      transform: translateX(-110%) !important;
    }
  }

  .customizer-section {
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    gap: 1.5rem;
  }

  .customizer-heading {
    padding-block: 1rem;
    padding-inline: 1.5rem;
  }

  .custom-input-wrapper {
    .v-col {
      padding-inline: 10px;
    }

    .v-label.custom-input {
      border: none;
      color: rgb(var(--v-theme-on-surface));
      outline: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    }
  }

  .v-navigation-drawer__content {
    display: flex;
    flex-direction: column;
  }

  .v-label.custom-input.active {
    border-color: transparent;
    outline: 2px solid rgb(var(--v-theme-primary));
  }

  .v-label.custom-input:not(.active):hover {
    border-color: rgba(var(--v-border-color), 0.22);
  }

  .customizer-skins {
    .custom-input.active {
      .customizer-skins-icon-wrapper {
        background-color: rgba(var(--v-global-theme-primary), var(--v-selected-opacity));
      }
    }
  }

  .app-customizer-primary-colors {
    .primary-color-wrapper:not(.active) {
      &:hover {
        outline-color: rgba(var(--v-border-color), 0.22) !important;
      }
    }
  }
}

.app-customizer-toggler {
  position: fixed !important;
  inset-block-start: 20%;
  inset-inline-end: 0;
}
</style>
