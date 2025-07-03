<script setup>
const props = defineProps({
  confirmationQuestion: {
    type: String,
    required: true,
  },
  confirmationText: {
    type: String,
    required: true,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  isNotifVisible: {
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

const emit = defineEmits([
  'update:isDialogVisible',
  'update:isNotifVisible',
  'confirm',
  'close'
])

const confirmed = ref(false)

const updateModelValue = val => {
  emit('update:isDialogVisible', val)
}
const updateNotifValue = val => {
  emit('update:isNotifVisible', val)
}
const onConfirmation = () => {
  emit('confirm', true)
  updateModelValue(false)
  confirmed.value = true
}

const onCancel = () => {
  emit('confirm', false)
  emit('update:isDialogVisible', false)
}
const closeConfirm = () => {
  updateNotifValue(false)
  confirmed.value = false
  emit('close', true)
}
</script>

<template>
  <!-- 👉 Confirm Dialog -->
  <VDialog max-width="500" :model-value="props.isDialogVisible" @update:model-value="updateModelValue">
    <VCard class="text-center px-10 py-6">
      <VCardText>
        <VBtn icon variant="outlined" color="warning" class="my-4"
          style=" block-size: 88px;inline-size: 88px; pointer-events: none;">
          <span class="text-5xl">!</span>
        </VBtn>

        <h6 class="text-lg font-weight-medium">
          {{ props.confirmationQuestion }}
        </h6>
        <p>{{ props.confirmationText }}</p>
      </VCardText>

      <VCardText class="d-flex align-center justify-center gap-2">
        <VBtn color="secondary" variant="tonal" @click="onCancel">
          Batal
        </VBtn>
        <VBtn variant="elevated" @click="onConfirmation" color="error">
          Yakin!
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- confirmed -->
  <VDialog v-model="confirmed" width="300">
    <VCard color="primary" width="300">
      <VCardText class="pt-3">
        Mohon menunggu...
        <VProgressLinear indeterminate bg-color="rgba(var(--v-theme-surface), 0.1)" :height="8" class="mb-0 mt-4" />
      </VCardText>
    </VCard>
  </VDialog>
  <VDialog :model-value="props.isNotifVisible" @update:model-value="updateNotifValue" max-width="500">
    <VCard>
      <VCardText class="text-center px-10 py-6">
        <VBtn icon variant="outlined" :color="props.confirmColor" class="my-4"
          style=" block-size: 88px;inline-size: 88px; pointer-events: none;">
          <VIcon :icon="(props.confirmColor == 'success') ? 'tabler-checks' : 'tabler-xbox-x'" size="38" />
        </VBtn>
        <h1 class="text-h4 mb-4">
          {{ props.confirmTitle }}
        </h1>
        <p>{{ props.confirmMsg }}</p>
        <VBtn color="success" @click="closeConfirm">
          Ok
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
