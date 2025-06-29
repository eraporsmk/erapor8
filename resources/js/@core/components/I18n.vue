<script setup>
const props = defineProps({
  languages: {
    type: Array,
    required: true,
  },
  location: {
    type: null,
    required: false,
    default: 'bottom end',
  },
})

const { locale } = useI18n({ useScope: 'global' })
</script>

<template>
  <IconBtn class="me-2">
    <!--VIcon icon="tabler-language" /-->
    <img :src="`/images/flags/${locale}.png`" alt="id" width="22" height="14" class="">
    <!-- Menu -->
    <VMenu
      activator="parent"
      :location="props.location"
      offset="12px"
      width="175"
    >
      <!-- List -->
      <VList
        :selected="[locale]"
        color="primary"
      >
        <!-- List item -->
        <VListItem
          v-for="lang in props.languages"
          :key="lang.i18nLang"
          :value="lang.i18nLang"
          @click="locale = lang.i18nLang"
        >
          <!-- Language label -->
          <VListItemTitle>
            {{lang.flag}} {{ lang.label }}
          </VListItemTitle>
        </VListItem>
      </VList>
    </VMenu>
  </IconBtn>
</template>
