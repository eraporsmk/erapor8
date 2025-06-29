<script setup>
import { themeConfig } from '@themeConfig';
import { useHead } from '@unhead/vue';
import { Indonesian } from "flatpickr/dist/l10n/id.js";
import Swal from 'sweetalert2';
useHead({
  title: `Pengaturan Umum | ${themeConfig.app.title}`
})
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
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
const {
  data: getData,
  execute: fetchData,
} = await useApi(createUrl('/setting', {
  query: {
    sekolah_id: $user.sekolah_id,
    semester_id: $user.semester.semester_id,
  },
}))
const data_semester = computed(() => getData.value.semester);
const data_guru = computed(() => getData.value.data_guru);
const data_rombel = computed(() => getData.value.data_rombel);
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
const setData = ref(getData.value);
form.value.semester_id = setData.value.semester_id;
form.value.tanggal_rapor = setData.value.tanggal_rapor
form.value.tanggal_rapor_kelas_akhir = setData.value.tanggal_rapor_kelas_akhir
form.value.zona = setData.value.zona
form.value.kepala_sekolah = setData.value.kepala_sekolah
form.value.jabatan = setData.value.jabatan
form.value.rombel_4_tahun = setData.value.rombel_4_tahun
form.value.url_dapodik = setData.value.url_dapodik
form.value.token_dapodik = setData.value.token_dapodik
if(setData.value.logo_sekolah){
  logo_sekolah.value = setData.value.logo_sekolah
}
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
  dataForm.append('semester_aktif', $user.semester.semester_id);
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
      if(getData.errors){
        logoError.value = true
        logoErrorMessage.value = getData.message
      } else {
        Swal.fire({
          icon: getData.icon,
          title: getData.title,
          html: getData.text,
        }).then(result => {
          fetchData()
          logo_sekolah.value = getData.logo_sekolah
          form.value.file = null
        })
      }
    }
  })
}
const dateConfig = ref({
  locale: Indonesian,
  altFormat: "j F Y",
  altInput: true,
});
</script>
<template>
  <div>
    <VCard title="Pengaturan Umum">
      <VCardText>
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
                  <AppTextField v-model="form.url_dapodik" label="URL Dapodik" placeholder="Contoh: http://localhost:5774 (tanpa garis miring di akhir!)" />
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
              <VFileInput v-model="form.file" :error="logoError" :error-messages="logoErrorMessage" accept="image/*" label="Unggah Logo Sekolah" />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </div>
</template>
