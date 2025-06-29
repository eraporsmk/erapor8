<script setup>
import { useTheme } from 'vuetify'

const { global } = useTheme()

const authProviders = [
  {
    icon: 'tabler-brand-facebook-filled',
    color: '#4267b2',
    colorInDark: '#497CE2',
  },
  {
    icon: 'tabler-brand-twitter-filled',
    color: '#1da1f2',
    colorInDark: '#1da1f2',
  },
  {
    icon: 'tabler-brand-github-filled',
    color: '#272727',
    colorInDark: '#fff',
  },
  {
    icon: 'tabler-brand-google-filled',
    color: '#dd4b39',
    colorInDark: '#db4437',
  },
]
</script>

<template>
  <div class="d-flex justify-center flex-wrap gap-1">
    <VBtn
      v-for="link in authProviders"
      :key="link.icon"
      icon
      variant="text"
      size="small"
      :color="global.name.value === 'dark' ? link.colorInDark : link.color"
    >
      <VIcon
        size="20"
        :icon="link.icon"
      />
    </VBtn>
  </div>
</template>
