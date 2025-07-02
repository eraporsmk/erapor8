<script setup>
import { layoutConfig } from '@layouts'
import { can } from '@layouts/plugins/casl'
import { useLayoutConfigStore } from '@layouts/stores/config'
import {
  getComputedNavLinkToProp,
  getDynamicI18nProps,
  isNavLinkActive,
} from '@layouts/utils'

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
})

const configStore = useLayoutConfigStore()
const hideTitleAndBadge = configStore.isVerticalNavMini()
</script>

<template>
  <li v-if="can(item.action, item.subject)" class="nav-link" :class="{ disabled: item.disable }">
    <Component :is="item.to ? 'RouterLink' : 'a'" v-bind="getComputedNavLinkToProp(item)"
      :class="{ 'router-link-active router-link-exact-active': isNavLinkActive(item, $router) }">
      <template v-if="item.icon">
        <template v-if="typeof item.icon == 'string'">
          <font-awesome-icon :icon="['fas', item.icon]" class="nav-item-icon" />
        </template>
        <template v-else>
          <Component :is="layoutConfig.app.iconRenderer || 'div'" v-bind="item.icon" class="nav-item-icon" />
        </template>
      </template>
      <template v-else>
        <template v-if="layoutConfig.verticalNav.defaultNavItemAwesomeIconProps">
          <font-awesome-icon :icon="['fas', layoutConfig.verticalNav.defaultNavItemAwesomeIconProps]"
            class="nav-item-icon" />
        </template>
        <template v-else>
          <Component :is="layoutConfig.app.iconRenderer || 'div'"
            v-bind="layoutConfig.verticalNav.defaultNavItemIconProps" class="nav-item-icon" />
        </template>
      </template>
      <TransitionGroup name="transition-slide-x">
        <!-- 👉 Title -->
        <Component :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'" v-show="!hideTitleAndBadge" key="title"
          class="nav-item-title" v-bind="getDynamicI18nProps(item.title, 'span')">
          {{ item.title }}
        </Component>

        <!-- 👉 Badge -->
        <Component :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'" v-if="item.badgeContent"
          v-show="!hideTitleAndBadge" key="badge" class="nav-item-badge" :class="item.badgeClass"
          v-bind="getDynamicI18nProps(item.badgeContent, 'span')">
          {{ item.badgeContent }}
        </Component>
      </TransitionGroup>
    </Component>
  </li>
</template>

<style lang="scss">
.layout-vertical-nav {
  .nav-link a {
    display: flex;
    align-items: center;
  }
}
</style>
