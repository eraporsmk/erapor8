<script setup>
import { layoutConfig } from '@layouts'
import { can } from '@layouts/plugins/casl'
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
  isSubItem: {
    type: Boolean,
    required: false,
    default: false,
  },
})
</script>

<template>
  <li v-if="can(item.action, item.subject)" class="nav-link" :class="[{
    'sub-item': props.isSubItem,
    'disabled': item.disable,
    'text-error': item.color == 'error',
    'text-success': item.color == 'success'
  }]">
    <Component :is="item.to ? 'RouterLink' : 'a'" v-bind="getComputedNavLinkToProp(item)"
      :class="{ 'router-link-active router-link-exact-active': isNavLinkActive(item, $router) }">
      <template v-if="item.icon">
        <template v-if="typeof item.icon == 'string'">
          <font-awesome-icon :icon="['fas', item.icon]" class="nav-item-icon" :class="{
            'text-error': item.color == 'error',
            'text-success': item.color == 'success'
          }" />
        </template>
        <template v-else>
          <Component :is="layoutConfig.app.iconRenderer || 'div'" v-bind="item.icon" class="nav-item-icon" />
        </template>
      </template>
      <template v-else>
        <template v-if="layoutConfig.verticalNav.defaultNavItemAwesomeIconProps">
          <font-awesome-icon :icon="['fas', layoutConfig.verticalNav.defaultNavItemAwesomeIconProps]"
            class="nav-item-icon" :class="{
              'text-error': item.color == 'error',
              'text-success': item.color == 'success'
            }" />
        </template>
        <template v-else>
          <Component :is="layoutConfig.app.iconRenderer || 'div'"
            v-bind="layoutConfig.verticalNav.defaultNavItemIconProps" class="nav-item-icon" />
        </template>
      </template>
      <Component :is="layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'" class="nav-item-title"
        v-bind="getDynamicI18nProps(item.title, 'span')">
        {{ item.title }}
      </Component>
    </Component>
  </li>
</template>

<style lang="scss">
.layout-horizontal-nav {
  .nav-link a {
    display: flex;
    align-items: center;
  }
}
</style>
