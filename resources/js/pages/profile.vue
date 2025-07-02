<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Web',
    title: 'Profil Pengguna',
  },
})
const loadingBody = ref(true)
const refVForm = ref()
const profilePhotoPath = ref()
const showNotif = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const form = ref({
  name: null,
  email: null,
  password: null,
  password_confirmation: null,
  photo: null,
})
onMounted(async () => {
  await fetchData();
});
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/auth/user'))
    let getData = response.data.value
    form.value.name = getData.name
    form.value.email = getData.email
    profilePhotoPath.value = getData.profile_photo_path
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const onFormSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      submitForm()
    }
  });
}
const errors = ref({
  name: undefined,
  email: undefined,
  password: undefined,
  password_confirmation: undefined,
  photo: undefined,
})
const bus = useEventBus('erapor');
const submitForm = async () => {
  errors.value = {
    name: undefined,
    email: undefined,
    password: undefined,
    password_confirmation: undefined,
    photo: undefined,
  }
  const dataForm = new FormData();
  dataForm.append('data', 'user');
  dataForm.append('name', (form.value.name) ? form.value.name : '');
  dataForm.append('email', (form.value.email) ? form.value.email : '');
  dataForm.append('password', (form.value.password) ? form.value.password : '');
  dataForm.append('password_confirmation', (form.value.password_confirmation) ? form.value.password_confirmation : '');
  dataForm.append('photo', (form.value.photo) ? form.value.photo : '');
  await $api('/auth/user', {
    method: 'POST',
    body: dataForm,
    onResponse({ request, response, options }) {
      let getData = response._data
      if (getData.errors) {
        errors.value = getData.errors
      } else {
        showNotif.value = true
        notif.value = getData
        form.value.photo = null
        form.value.password = null
        form.value.password_confirmation = null
        profilePhotoPath.value = getData.user.profile_photo_path
        useCookie("profilePhotoPath").value = profilePhotoPath.value;
        try {
          bus.emit(profilePhotoPath.value);
        } catch (error) {
          //console.error(error)
        }
        setTimeout(() => {
          showNotif.value = false
        }, 5000)
      }
    }
  })
}
</script>
<template>
  <div>
    <VRow>
      <VCol cols="12">
        <VCard title="Informasi Profil Pengguna">
          <template #append>
            <span class="text-disabled text-subtitle-2">Perbaharui informasi profil dan alamat email akun Anda jika
              diperlukan.</span>
          </template>
          <VDivider />
          <VCardText v-if="showNotif">
            <VAlert border="bottom" :color="notif.color" variant="tonal">
              <strong>{{ notif.title }}</strong> {{ notif.text }}
            </VAlert>
          </VCardText>
          <VCardText class="text-center" v-if="loadingBody">
            <VProgressCircular :size="60" indeterminate color="error" />
          </VCardText>
          <VCardText v-else>
            <VForm ref="refVForm" @submit.prevent="onFormSubmit">
              <VRow>
                <VCol cols="8">
                  <VRow>
                    <VCol cols="12">
                      <VRow no-gutters>
                        <VCol cols="12" md="3" class="d-flex align-items-center">
                          <label class="v-label text-body-2 text-high-emphasis" for="name">Nama Lengkap</label>
                        </VCol>
                        <VCol cols="12" md="9">
                          <AppTextField id="name" v-model="form.name" placeholder="Nama Lengkap" persistent-placeholder
                            :error-messages="errors.name" />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12">
                      <VRow no-gutters>
                        <VCol cols="12" md="3" class="d-flex align-items-center">
                          <label class="v-label text-body-2 text-high-emphasis" for="email">Email</label>
                        </VCol>
                        <VCol cols="12" md="9">
                          <AppTextField id="email" type="email" v-model="form.email" placeholder="Email"
                            persistent-placeholder :error-messages="errors.email" />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12">
                      <VRow no-gutters>
                        <VCol cols="12" md="3" class="d-flex align-items-center">
                          <label class="v-label text-body-2 text-high-emphasis" for="password">Kata Sandi Baru</label>
                        </VCol>
                        <VCol cols="12" md="9">
                          <AppTextField id="password" type="password" v-model="form.password"
                            placeholder="Kosongkan jika tidak ingin mengganti kata sandi" persistent-placeholder
                            :error-messages="errors.password" />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol cols="12">
                      <VRow no-gutters>
                        <VCol cols="12" md="3" class="d-flex align-items-center">
                          <label class="v-label text-body-2 text-high-emphasis" for="password_confirmation">Konfirmasi
                            Kata
                            Sandi</label>
                        </VCol>
                        <VCol cols="12" md="9">
                          <AppTextField id="password_confirmation" type="password" v-model="form.password_confirmation"
                            placeholder="Kosongkan jika tidak ingin mengganti kata sandi" persistent-placeholder
                            :error-messages="errors.password_confirmation" />
                        </VCol>
                      </VRow>
                    </VCol>
                    <VCol offset-md="3" cols="12" md="9" class="d-flex gap-4">
                      <VBtn type="submit">
                        Submit
                      </VBtn>
                    </VCol>
                  </VRow>
                </VCol>
                <VCol cols="4" class="text-center">
                  <VImg :alt="form.name" :src="profilePhotoPath" cover class="mx-auto mb-10" width="250px" />
                  <VFileInput v-model="form.photo" :error-messages="errors.photo" accept="image/*"
                    label="Unggah Foto Profil" />
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
          <VCardText></VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
