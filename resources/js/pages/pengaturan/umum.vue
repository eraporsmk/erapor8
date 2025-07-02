<script setup>
import { Indonesian } from "flatpickr/dist/l10n/id.js";
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Pengaturan Umum',
  },
})
const refVForm = ref();
const form = ref({
  semester_id: null,
  tanggal_rapor: null,
  tanggal_rapor_kelas_akhir: null,
  zona: null,
  kepala_sekolah: null,
  jabatan: null,
  rombel_4_tahun: [],
  url_dapodik: null,
  token_dapodik: null,
  file: null,
})
onMounted(async () => {
  await fetchData();
});
const data_semester = ref([])
const data_guru = ref([])
const data_rombel = ref([])
const loadingBody = ref(true)
const isAlertDialogVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/setting', {
      query: {
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
      },
    }))
    let getData = response.data.value
    data_semester.value = getData.semester
    data_guru.value = getData.data_guru
    data_rombel.value = getData.data_rombel
    form.value.semester_id = getData.semester_id;
    form.value.tanggal_rapor = getData.tanggal_rapor
    form.value.tanggal_rapor_kelas_akhir = getData.tanggal_rapor_kelas_akhir
    form.value.zona = getData.zona
    form.value.kepala_sekolah = getData.kepala_sekolah
    form.value.jabatan = getData.jabatan
    form.value.rombel_4_tahun = getData.rombel_4_tahun
    form.value.url_dapodik = getData.url_dapodik
    form.value.token_dapodik = getData.token_dapodik
    if (getData.logo_sekolah) {
      logo_sekolah.value = getData.logo_sekolah
    }
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const data_zona = ref([
  { value: 'Asia/Jakarta', title: 'Waktu Indonesia Barat (WIB)' },
  { value: 'Asia/Makassar', title: 'Waktu Indonesia Tengah (WITA)' },
  { value: 'Asia/Jayapura', title: 'Waktu Indonesia Timur (WIT)' },
])
const jabatan = ref([
  { value: 'Kepala Sekolah', title: 'Kepala Sekolah' },
  { value: 'Plt. Kepala Sekolah', title: 'PLT Kepala Sekolah' },
  { value: 'Plh. Kepala Sekolah', title: 'PLH Kepala Sekolah' },
])
const logo_sekolah = ref('/images/logo/tutwuri.png')
const onFormSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      submitForm()
    }
  });
}
const logoError = ref(false)
const logoErrorMessage = ref()
const submitForm = async () => {
  const dataForm = new FormData();
  dataForm.append('photo', (form.value.file) ? form.value.file : '');
  dataForm.append('semester_id', form.value.semester_id);
  dataForm.append('sekolah_id', $user.sekolah_id);
  dataForm.append('semester_aktif', $semester.semester_id);
  dataForm.append('tanggal_rapor_pts', (form.value.tanggal_rapor_pts) ? form.value.tanggal_rapor_pts : '')
  dataForm.append('tanggal_rapor', (form.value.tanggal_rapor) ? form.value.tanggal_rapor : '')
  dataForm.append('tanggal_rapor_kelas_akhir', (form.value.tanggal_rapor_kelas_akhir) ? form.value.tanggal_rapor_kelas_akhir : '')
  dataForm.append('zona', (form.value.zona) ? form.value.zona : '')
  dataForm.append('kepala_sekolah', (form.value.kepala_sekolah) ? form.value.kepala_sekolah : '')
  dataForm.append('jabatan', (form.value.jabatan) ? form.value.jabatan : '')
  dataForm.append('rombel_4_tahun', JSON.stringify(form.value.rombel_4_tahun))
  dataForm.append('url_dapodik', (form.value.url_dapodik) ? form.value.url_dapodik : '')
  dataForm.append('token_dapodik', (form.value.token_dapodik) ? form.value.token_dapodik : '')
  await $api('/setting/update', {
    method: 'POST',
    body: dataForm,
    onResponse({ request, response, options }) {
      let getData = response._data
      logoError.value = false
      logoErrorMessage.value = null
      if (getData.errors) {
        logoError.value = true
        logoErrorMessage.value = getData.message
      } else {
        isAlertDialogVisible.value = true
        notif.value = {
          color: getData.color,
          title: getData.title,
          text: getData.text,
        }
      }
    }
  })
}
const confirmAlert = () => {
  fetchData()
  form.value.file = null
}
const dateConfig = ref({
  locale: Indonesian,
  altFormat: "j F Y",
  altInput: true,
});
</script>
<template>
  <section>
    <VCard title="Pengaturan Umum">
      <VDivider />
      <VCardText class="text-center" v-if="loadingBody">
        <VProgressCircular :size="60" indeterminate color="error" />
      </VCardText>
      <VCardText v-else>
        <VForm ref="refVForm" @submit.prevent="onFormSubmit">
          <VRow>
            <VCol cols="7">
              <VRow>
                <VCol cols="12">
                  <AppSelect v-model="form.semester_id" :items="data_semester" :rules="[requiredValidator]"
                    placeholder="== Pilih Semester ==" label="Periode Aktif" name="semester_id" item-title="nama"
                    item-value="semester_id" require />
                </VCol>
                <VCol cols="12">
                  <AppDateTimePicker v-model="form.tanggal_rapor" label="Tanggal Rapor Semester"
                    placeholder="== Pilih Tanggal Rapor Semester ==" :config="dateConfig" />
                </VCol>
                <VCol cols="12">
                  <AppDateTimePicker v-model="form.tanggal_rapor_kelas_akhir" label="Tanggal Rapor Kelas Akhir"
                    placeholder="== Pilih Tanggal Rapor Kelas Akhir ==" :config="dateConfig" />
                </VCol>
                <VCol cols="12">
                  <AppSelect v-model="form.zona" :items="data_zona" :rules="[requiredValidator]"
                    placeholder="== Pilih Zona Waktu ==" label="Zona Waktu" name="semester_id" require />
                </VCol>
                <VCol cols="12">
                  <AppSelect v-model="form.kepala_sekolah" :items="data_guru" :rules="[requiredValidator]"
                    placeholder="== Pilih Kepala Sekolah ==" label="Kepala Sekolah" name="semester_id"
                    item-title="nama_lengkap" item-value="guru_id" require />
                </VCol>
                <VCol cols="12">
                  <AppSelect v-model="form.jabatan" :items="jabatan" :rules="[requiredValidator]"
                    placeholder="== Pilih Semester ==" label="Jabatan Kepala Sekolah" name="jabatan" require />
                </VCol>
                <VCol cols="12">
                  <AppSelect v-model="form.rombel_4_tahun" chips multiple closable-chips :items="data_rombel"
                    placeholder="== Pilih Rombel 4 Tahun ==" label="Rombel 4 Tahun" name="rombel_4_tahun"
                    item-title="nama" item-value="rombongan_belajar_id" require />
                </VCol>
                <VCol cols="12">
                  <AppTextField v-model="form.url_dapodik" label="URL Dapodik"
                    placeholder="Contoh: http://localhost:5774 (tanpa garis miring di akhir!)" />
                </VCol>
                <VCol cols="12">
                  <AppTextField v-model="form.token_dapodik" label="Token Web Services Dapodik"
                    placeholder="Token Web Services Dapodik" />
                </VCol>
                <VCol cols="12" class="d-flex gap-4">
                  <VBtn type="submit">
                    Simpan
                  </VBtn>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="5">
              <VImg alt="Logo Sekolah" :src="logo_sekolah" cover class="w-100 mx-auto mb-10" />
              <VFileInput v-model="form.file" :error="logoError" :error-messages="logoErrorMessage" accept="image/*"
                label="Unggah Logo Sekolah" />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmAlert"></AlertDialog>
  </section>
</template>
