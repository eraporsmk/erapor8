<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  permissionName: {
    type: String,
    required: false,
    default: '',
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'update:permissionName',
])

const currentPermissionName = ref('')

const onReset = () => {
  emit('update:isDialogVisible', false)
  currentPermissionName.value = ''
}

const onSubmit = () => {
  emit('update:isDialogVisible', false)
  emit('update:permissionName', currentPermissionName.value)
}

watch(() => props, () => {
  currentPermissionName.value = props.permissionName
})
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 600"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <!-- 👉 dialog close btn -->
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <!-- 👉 Title -->
        <h4 class="text-h4 text-center mb-2">
          {{ props.permissionName ? 'Edit' : 'Add' }} Permission
        </h4>
        <p class="text-body-1 text-center mb-6">
          {{ props.permissionName ? 'Edit' : 'Add' }}  permission as per your requirements.
        </p>

        <!-- 👉 Form -->
        <VForm>
          <VAlert
            type="warning"
            title="Warning!"
            variant="tonal"
            class="mb-6"
          >
            <template #text>
              By {{ props.permissionName ? 'editing' : 'adding' }} the permission name, you might break the system permissions functionality.
            </template>
          </VAlert>

          <!-- 👉 Role name -->
          <div class="d-flex gap-4 mb-6 flex-wrap flex-column flex-sm-row">
            <AppTextField
              v-model="currentPermissionName"
              placeholder="Enter Permission Name"
            />

            <VBtn @click="onSubmit">
              {{ props.permissionName ? 'Update' : 'Add' }}
            </VBtn>
          </div>

          <VCheckbox label="Set as core permission" />
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;
    padding-inline: 0;
  }
}
</style>
