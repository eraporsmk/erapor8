<script setup>
import {
  Layout,
} from '@core/enums';
const shortcuts = [
  {
    icon: 'tabler-device-mobile',
    title: 'Vertical',
    value: Layout.Vertical,
  },
  {
    icon: 'tabler-device-desktop',
    title: 'Horizontal',
    value: Layout.Horizontal,
  },
]
</script>

<template>
  <Shortcuts :shortcuts="shortcuts" />
</template>
