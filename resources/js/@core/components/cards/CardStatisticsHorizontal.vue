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
    <VCardText class="d-flex align-center justify-space-between">
      <div>
        <div class="d-flex align-center flex-wrap">
          <h5 class="text-h5">
            {{ props.stats }}
          </h5>
        </div>
        <div class="text-subtitle-1">
          {{ props.title }}
        </div>
      </div>

      <VAvatar
        :color="props.color"
        :size="42"
        rounded
        variant="tonal"
      >
        <VIcon
          :icon="props.icon"
          size="26"
        />
      </VAvatar>
    </VCardText>
  </VCard>
</template>
