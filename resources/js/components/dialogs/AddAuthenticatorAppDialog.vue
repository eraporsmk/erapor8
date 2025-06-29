<script setup>
import themeselectionQr from '@images/pages/themeselection-qr.png'

const props = defineProps({
  authCode: {
    type: String,
    required: false,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'submit',
])

const authCode = ref(structuredClone(toRaw(props.authCode)))

const formSubmit = () => {
  if (authCode.value) {
    emit('submit', authCode.value)
    emit('update:isDialogVisible', false)
  }
}

const resetAuthCode = () => {
  authCode.value = structuredClone(toRaw(props.authCode))
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="(val) => $emit('update:isDialogVisible', val)"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />

    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <!-- 👉 Title -->
        <h4 class="text-h4 text-center mb-6">
          Add Authenticator App
        </h4>
        <h5 class="text-h5 mb-2">
          Authenticator Apps
        </h5>

        <p class="text-body-1 mb-6">
          Using an authenticator app like Google Authenticator, Microsoft Authenticator, Authy, or 1Password, scan the QR code. It will generate a 6 digit code for you to enter below.
        </p>

        <div class="mb-6">
          <VImg
            width="150"
            :src="themeselectionQr"
            class="mx-auto"
          />
        </div>

        <VAlert
          title="ASDLKNASDA9AHS678dGhASD78AB"
          text="If you are unable to scan the QR code, you can manually enter the secret key below."
          variant="tonal"
          color="warning"
        />
        <VForm @submit.prevent="() => {}">
          <AppTextField
            v-model="authCode"
            name="auth-code"
            label="Enter Authentication Code"
            placeholder="123 456"
            class="mt-4 mb-6"
          />

          <div class="d-flex justify-end flex-wrap gap-4">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="resetAuthCode"
            >
              Cancel
            </VBtn>

            <VBtn
              type="submit"
              @click="formSubmit"
            >
              Continue
              <VIcon
                end
                icon="tabler-arrow-right"
                class="flip-in-rtl"
              />
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
