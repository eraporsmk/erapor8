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
  semester_id: undefined,
  tanggal_rapor: undefined,
  tanggal_rapor_kelas_akhir: undefined,
  zona: undefined,
  kepala_sekolah: undefined,
  jabatan: undefined,
  rombel_4_tahun: [],
  url_dapodik: undefined,
  token_dapodik: undefined,
  photo: undefined,
  bg_login: undefined,
  ttd_kepsek: undefined,
  ttd_tinggi: undefined,
  ttd_lebar: undefined,
  ttd_top: undefined,
  ttd_left: undefined,
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
const bgLogin = ref()
const tddKepsek = ref()
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
    bgLogin.value = getData.bg_login
    tddKepsek.value = getData.ttd_kepsek
    form.value.semester_id = getData.semester_id;
    form.value.tanggal_rapor = getData.tanggal_rapor
    form.value.tanggal_rapor_kelas_akhir = getData.tanggal_rapor_kelas_akhir
    form.value.zona = getData.zona
    form.value.kepala_sekolah = getData.kepala_sekolah
    form.value.jabatan = getData.jabatan
    form.value.rombel_4_tahun = getData.rombel_4_tahun
    form.value.url_dapodik = getData.url_dapodik
    form.value.token_dapodik = getData.token_dapodik
    form.value.ttd_tinggi = getData.ttd_tinggi
    form.value.ttd_lebar = getData.ttd_lebar
    form.value.ttd_top = getData.ttd_top
    form.value.ttd_left = getData.ttd_left
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
const errors = ref({
  jabatan: undefined,
  semester_id: undefined,
  zona: undefined,
  photo: undefined,
  bg_login: undefined,
  ttd_kepsek: undefined,
  ttd_lebar: undefined,
  ttd_tinggi: undefined,
  ttd_top: undefined,
  ttd_left: undefined,
})
const submitForm = async () => {
  const dataForm = new FormData();
  dataForm.append('photo', (form.value.photo) ? form.value.photo : '');
  dataForm.append('bg_login', (form.value.bg_login) ? form.value.bg_login : '');
  dataForm.append('ttd_kepsek', (form.value.ttd_kepsek) ? form.value.ttd_kepsek : '');
  dataForm.append('ttd_lebar', (form.value.ttd_lebar) ? form.value.ttd_lebar : '');
  dataForm.append('ttd_tinggi', (form.value.ttd_tinggi) ? form.value.ttd_tinggi : '');
  dataForm.append('ttd_top', (form.value.ttd_top) ? form.value.ttd_top : '');
  dataForm.append('ttd_left', (form.value.ttd_left) ? form.value.ttd_left : '');
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
      if (getData.errors) {
        errors.value = getData.errors
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
  form.value.photo = null
  form.value.bg_login = null
}
const dateConfig = ref({
  locale: Indonesian,
  altFormat: "j F Y",
  altInput: true,
});
const resetSetting = async (data) => {
  await $api('/setting/reset-setting', {
    method: 'POST',
    body: {
      data: data,
    },
    onResponse() {
      fetchData()
    }
  })
}
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
                <VCol cols="12" md="6">
                  <AppSelect v-model="form.semester_id" :items="data_semester" :rules="[requiredValidator]"
                    placeholder="== Pilih Semester ==" label="Periode Aktif" name="semester_id" item-title="nama"
                    item-value="semester_id" require />
                </VCol>
                <VCol cols="12" md="6">
                  <AppDateTimePicker v-model="form.tanggal_rapor" label="Tanggal Rapor Semester"
                    placeholder="== Pilih Tanggal Rapor Semester ==" :config="dateConfig" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppDateTimePicker v-model="form.tanggal_rapor_kelas_akhir" label="Tanggal Rapor Kelas Akhir"
                    placeholder="== Pilih Tanggal Rapor Kelas Akhir ==" :config="dateConfig" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect v-model="form.zona" :items="data_zona" :rules="[requiredValidator]"
                    placeholder="== Pilih Zona Waktu ==" label="Zona Waktu" name="zona" require />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect v-model="form.kepala_sekolah" :items="data_guru" :rules="[requiredValidator]"
                    placeholder="== Pilih Kepala Sekolah ==" label="Kepala Sekolah" name="kepala_sekolah"
                    item-title="nama_lengkap" item-value="guru_id" require />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect v-model="form.jabatan" :items="jabatan" :rules="[requiredValidator]"
                    placeholder="== Pilih Semester ==" label="Jabatan Kepala Sekolah" name="jabatan" require />
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect v-model="form.rombel_4_tahun" chips multiple closable-chips :items="data_rombel"
                    placeholder="== Pilih Rombel 4 Tahun ==" label="Rombel 4 Tahun" name="rombel_4_tahun"
                    item-title="nama" item-value="rombongan_belajar_id" require />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.url_dapodik" label="URL Dapodik"
                    placeholder="Contoh: http://localhost:5774 (tanpa garis miring di akhir!)" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.token_dapodik" label="Token Web Services Dapodik"
                    placeholder="Token Web Services Dapodik" />
                </VCol>
                <VCol cols="12" md="6">
                  <label class="v-label text-body-2 text-high-emphasis" for="ttd_kepsek">Scan TTD Kepala Sekolah</label>
                  <VFileInput id="ttd_kepsek" v-model="form.ttd_kepsek" :error-messages="errors.ttd_kepsek"
                    accept="image/*" label="Unggah Scan TTD Kepala Sekolah" v-if="tddKepsek">
                    <template v-slot:append>
                      <VIcon icon="tabler-trash" color="error" size="26" @click="resetSetting('ttd_kepsek')" />
                    </template>
                  </VFileInput>
                  <VFileInput id="ttd_kepsek" v-model="form.ttd_kepsek" :error-messages="errors.ttd_kepsek"
                    accept="image/*" label="Unggah Scan TTD Kepala Sekolah" v-else />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.ttd_tinggi" label="Ukuran Tinggi Scan TTD Kepala Sekolah"
                    placeholder="Ukuran Tinggi Scan TTD Kepala Sekolah" :error-messages="errors.ttd_tinggi" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.ttd_lebar" label="Ukuran Lebar Scan TTD Kepala Sekolah"
                    placeholder="Ukuran Lebar Scan TTD Kepala Sekolah" :error-messages="errors.ttd_lebar" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.ttd_top" label="Minus Margin Atas Scan TTD Kepala Sekolah"
                    placeholder="Minus Margin Atas Scan TTD Kepala Sekolah" :error-messages="errors.ttd_top" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.ttd_left" label="Minus Margin Kiri Scan TTD Kepala Sekolah"
                    placeholder="Minus Margin Kiri Scan TTD Kepala Sekolah" :error-messages="errors.ttd_left" />
                </VCol>
                <VCol cols="12" class="d-flex gap-4">
                  <VBtn type="submit">
                    Simpan
                  </VBtn>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="5">
              <VRow>
                <VCol cols="12">
                  <VImg alt="Logo Sekolah" :src="logo_sekolah" cover class="w-80 mx-auto mb-10" />
                  <VFileInput v-model="form.photo" :error-messages="errors.photo" accept="image/*"
                    label="Unggah Logo Sekolah" />
                </VCol>
                <VCol cols="12">
                  <label class="v-label text-body-2 text-high-emphasis" for="bg_login">Kustom Background (Laman Login
                    &amp; Register)</label>
                  <VFileInput id="bg_login" v-model="form.bg_login" :error-messages="errors.bg_login" accept="image/*"
                    label="Unggah Kustom Background" v-if="bgLogin">
                    <template v-slot:append>
                      <VIcon icon="tabler-trash" color="error" size="26" @click="resetSetting('bg_login')" />
                    </template>
                  </VFileInput>
                  <VFileInput id="bg_login" v-model="form.bg_login" :error-messages="errors.bg_login" accept="image/*"
                    label="Unggah Kustom Background" v-else />
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmAlert"></AlertDialog>
  </section>
</template>
