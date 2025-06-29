<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  color: {
    type: String,
    required: false,
    default: 'primary',
  },
  icon: {
    type: String,
    required: true,
  },
  stats: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <VCard>
    <VCardText class="d-flex flex-column align-center justify-center">
      <VAvatar
        v-if="props.icon"
        size="40"
        variant="tonal"
        rounded
        :color="props.color"
      >
        <VIcon :icon="props.icon" />
      </VAvatar>

      <h5 class="text-h5 pt-2 mb-1">
        {{ props.stats }}
      </h5>
      <div class="text-body-1">
        {{ props.title }}
      </div>
    </VCardText>
  </VCard>
</template>
