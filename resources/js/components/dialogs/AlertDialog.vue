<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  confirmTitle: {
    type: String,
    required: true,
  },
  confirmMsg: {
    type: String,
    required: true,
  },
  confirmColor: {
    type: String,
    required: true,
  },
})
const updateModelValue = val => {
  emit('update:isDialogVisible', val)
}
const onConfirmation = () => {
  emit('confirm', true)
  updateModelValue(false)
}
const emit = defineEmits([
  'update:isDialogVisible',
  'confirm',
])
</script>

<template>
  <!-- confirmed -->
  <VDialog :model-value="props.isDialogVisible"
    @update:model-value="updateModelValue" max-width="500">
    <VCard>
      <VCardText class="text-center px-10 py-6">
        <VBtn icon variant="outlined" :color="props.confirmColor" class="my-4" style=" block-size: 88px;inline-size: 88px; pointer-events: none;">
          <VIcon :icon="(props.confirmColor == 'success') ? 'tabler-checks' : 'tabler-xbox-x'" size="38" />
        </VBtn>
        <h1 class="text-h4 mb-4">
          {{ props.confirmTitle }}
        </h1>
        <p>{{ props.confirmMsg }}</p>
        <VBtn color="success" @click="onConfirmation">
          Ok
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
