<script setup>
import MatevRapor from '@/views/sinkronisasi/dapodik/MatevRapor.vue';
import RombonganBelajar from '@/views/sinkronisasi/dapodik/RombonganBelajar.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Kirim Nilai ke Dapodik',
  },
})
const currentTab = ref('Rombongan Belajar')
const tabs = [
  'Rombongan Belajar',
  'Mata Evaluasi Rapor'
]
onMounted(async () => {
  await fetchData();
});
const loadingBody = ref(true)
const lengkap = ref(false)
const form = ref({
  url_dapodik: null,
  token_dapodik: null,
})
const dapodik = ref()
const getLoad = ref('')
const fetchData = async () => {
  loadingBody.value = true;
  try {
    const response = await useApi(createUrl('/sinkronisasi/nilai-dapodik', {
      query: {
        sekolah_id: $user.sekolah_id,
        user_id: $user.user_id,
        npsn: $sekolah.npsn,
        semester_id: $semester.semester_id,
        periode_aktif: $semester.nama,
      },
    }));
    let getData = response.data.value
    lengkap.value = (getData.url_dapodik) ? true : false
    if (lengkap.value) {
      getLoad.value = 'Rombongan Belajar'
    }
    form.value.url_dapodik = getData.url_dapodik
    form.value.token_dapodik = getData.token_dapodik
    dapodik.value = getData.dapodik
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const isAlertDialogVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const changeTab = (val) => {
  getLoad.value = val
}
const isLoading = ref(false)
const refVForm = ref()
const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) cekKoneksi();
  });
}
const errors = ref({
  url_dapodik: undefined,
  token_dapodik: undefined
})
const cekKoneksi = async () => {
  console.log('cekKoneksi');
  isLoading.value = true
  await $api('/sinkronisasi/cek-koneksi', {
    method: 'POST',
    body: {
      sekolah_id: $user.sekolah_id,
      semester_id: $semester.semester_id,
      url_dapodik: form.value.url_dapodik,
      token_dapodik: form.value.token_dapodik,
      npsn: $sekolah.npsn,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      console.log(getData);
      isLoading.value = false
      if (getData.errors) {
        errors.value = getData.errors
      } else {
        errors.value = {
          url_dapodik: undefined,
          token_dapodik: undefined,
        }
        notif.value = {
          color: getData.color,
          title: getData.title,
          text: getData.text,
        }
        isAlertDialogVisible.value = true
      }
    }
  })
}
const confirmDialog = () => {
  isLoading.value = false
  fetchData()
}
</script>
<template>
  <div>
    <VCard title="Kirim Nilai ke Dapodik" v-if="loadingBody">
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </VCard>
    <VCard title="Kirim Nilai ke Dapodik" v-else>
      <template v-if="!lengkap">
        <VCardText class="mt-4">
          <VAlert color="info" class="mb-4">
            <h2 class="mt-4 mb-4 text-white">Pengaturan Web Services Dapodik</h2>
            <p>Untuk melakukan pengiriman nilai e-Rapor ke Dapodik, silahkan isi form dibawah ini:</p>
          </VAlert>
          <VForm ref="refVForm" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="url_dapodik">URL Dapodik</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppTextField id="url_dapodik" v-model="form.url_dapodik"
                      placeholder="Contoh: http://localhost:5774 (tanpa garis miring di akhir!)" persistent-placeholder
                      :rules="[requiredValidator]" :error-messages="errors.url_dapodik" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="token_dapodik">Token Dapodik</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppTextField id="token_dapodik" v-model="form.token_dapodik" placeholder="Token Dapodik"
                      persistent-placeholder :rules="[requiredValidator]" :error-messages="errors.token_dapodik" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol offset-md="3" cols="12" md="9" class="d-flex gap-4">
                <VBtn type="submit">
                  Cek Koneksi
                </VBtn>
              </VCol>
              <VOverlay v-model="isLoading" contained persistent scroll-strategy="none"
                class="align-center justify-center">
                <VProgressCircular indeterminate />
              </VOverlay>
            </VRow>
          </VForm>
        </VCardText>
      </template>
      <template v-else>
        <VCardText class="mt-4">
          <VAlert color="warning">
            <h2 class="mt-4 mb-4 text-white">Informasi Penting</h2>
            <p>Prioritaskan pengiriman nilai semester pada tingkat akhir, nilai rapor 5 semester untuk siswa kelas 12
              dan
              7 semester untuk siswa kelas 13 ke Dapodik</p>
          </VAlert>
        </VCardText>
        <VTabs v-model="currentTab" grow @update:model-value="changeTab">
          <VTab v-for="item in tabs" :key="item" :value="item">
            {{ item }}
          </VTab>
        </VTabs>
        <VWindow v-model="currentTab">
          <VWindowItem value="Rombongan Belajar">
            <RombonganBelajar v-model:isTabActive="getLoad" :url-dapodik="form.url_dapodik"
              :token-dapodik="form.token_dapodik" />
          </VWindowItem>
          <VWindowItem value="Mata Evaluasi Rapor">
            <MatevRapor v-model:isTabActive="getLoad" />
          </VWindowItem>
        </VWindow>
      </template>
    </VCard>
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog" />
  </div>
</template>
