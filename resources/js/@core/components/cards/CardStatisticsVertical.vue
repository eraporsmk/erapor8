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
  height: {
    type: Number,
    required: true,
  },
  series: {
    type: Array,
    required: true,
  },
  chartOptions: {
    type: null,
    required: true,
  },
})
</script>

<template>
  <VCard>
    <VCardText class="d-flex flex-column pb-0">
      <VAvatar
        v-if="props.icon"
        size="42"
        variant="tonal"
        :color="props.color"
        rounded
        class="mb-2"
      >
        <VIcon
          :icon="props.icon"
          size="26"
        />
      </VAvatar>

      <h5 class="text-h5">
        {{ props.stats }}
      </h5>
      <div class="text-sm">
        {{ props.title }}
      </div>
    </VCardText>

    <VueApexCharts
      :series="props.series"
      :options="props.chartOptions"
      :height="props.height"
    />
  </VCard>
</template>
