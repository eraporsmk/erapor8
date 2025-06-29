<script setup>
const themes = [
  {
    name: 'light',
    icon: 'tabler-sun-high',
  },
  {
    name: 'dark',
    icon: 'tabler-moon-stars',
  },
  {
    name: 'system',
    icon: 'tabler-device-desktop-analytics',
  },
]
</script>

<template>
  <ThemeSwitcher :themes="themes" />
</template>
