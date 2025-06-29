<script setup>
const props = defineProps({
  menuList: {
    type: Array,
    required: false,
  },
  itemProps: {
    type: Boolean,
    required: false,
  },
  iconSize: {
    type: String,
    required: false,
  },
  class: {
    type: String,
    required: false,
    default: 'text-disabled',
  },
})
</script>

<template>
  <IconBtn :class="props.class">
    <VIcon
      :size="iconSize"
      icon="tabler-dots-vertical"
    />

    <VMenu
      v-if="props.menuList"
      activator="parent"
    >
      <VList
        :items="props.menuList"
        :item-props="props.itemProps"
      />
    </VMenu>
  </IconBtn>
</template>
