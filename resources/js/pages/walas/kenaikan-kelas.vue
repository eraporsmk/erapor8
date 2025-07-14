<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Kenaikan',
    title: 'Kenaikan Kelas'
  },
})
onMounted(async () => {
  await fetchData();
});
const loading = ref({
  body: false,
})
const defaultForm = ref({
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  semester_id: $semester.semester_id,
  periode_aktif: $semester.nama,
  aksi: 'kenaikan-kelas',
})
const form = ref({
  status: {},
  nama_kelas: {},
  rombongan_belajar_id: {},
  id_rombel: null,
})
const arrayData = ref({
  siswa: [],
  options: [],
})
const confirmed = ref(false)
const isConfirmDialogVisible = ref(false)
const isNotifVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const fetchData = async () => {
  loading.value.body = true;
  try {
    const response = await useApi(createUrl('/walas', {
      query: {
        user_id: defaultForm.value.user_id,
        guru_id: defaultForm.value.guru_id,
        sekolah_id: defaultForm.value.sekolah_id,
        semester_id: defaultForm.value.semester_id,
        periode_aktif: defaultForm.value.nama,
        aksi: defaultForm.value.aksi,
      },
    }));
    let getData = response.data.value
    arrayData.value.siswa = getData.data_siswa
    arrayData.value.options = getData.options
    form.value.id_rombel = getData.rombel?.rombongan_belajar_id
    getData.data_siswa.forEach((siswa) => {
      form.value.status[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_kenaikan_kelas?.status ?? null
      form.value.nama_kelas[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_kenaikan_kelas?.nama_kelas ?? null
      form.value.rombongan_belajar_id[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_kenaikan_kelas?.rombongan_belajar_id ?? null
    })
  } catch (error) {
    console.error(error);
  } finally {
    loading.value.body = false;
  }
}
const onSubmit = async () => {
  confirmed.value = true
  const mergedForm = { ...defaultForm.value, ...form.value };
  await $api("/walas/save", {
    method: "POST",
    body: mergedForm,
    onResponseError({ response }) {
      console.log('ERROR:', response._data.errors);
      confirmed.value = false
    },
    onResponse({ response }) {
      confirmed.value = false
      let getData = response._data
      if (!getData.errors) {
        isNotifVisible.value = true
        notif.value = getData
      }
    }
  })
}
const changeStatus = async (status, anggota_rombel_id, rombongan_belajar_id) => {
  if (status === 1) {
    /*this.loadingForm = true
    this.$http.post('/walas/get-next-rombel', this.form).then(response => {
      this.loadingForm = false
      let getData = response.data
      this.$swal({
        title: 'Pilih Rombongan Belajar',
        input: 'select',
        inputOptions: getData,
        inputPlaceholder: '== Pilih Rombongan Belajar ==',
        showCancelButton: true,
        allowOutsideClick: false,
        customClass: {
          confirmButton: 'btn btn-success mr-1',
        },
        inputValidator: (value) => {
          return new Promise((resolve) => {
            if (value) {
              if(value == rombongan_belajar_id){
                this.form.rombongan_belajar_id[anggota_rombel_id] = rombongan_belajar_id
                resolve()
              } else {
                this.$http.post('/walas/find-rombel', {
                  rombongan_belajar_id: value
                }).then(response => {
                  let getData = response.data
                  this.form.rombongan_belajar_id[anggota_rombel_id] = rombongan_belajar_id
                  this.form.nama_kelas[anggota_rombel_id] = getData.nama
                  resolve()
                });
              }
            } else {
              resolve('Rombongan Belajar tidak boleh kosong!')
            }
          })
        }
      })
    })*/
  }
}
</script>
<template>
  <VCard>
    <VCardItem class="pb-4">
      <VCardTitle>Kenaikan Kelas</VCardTitle>
    </VCardItem>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VForm @submit.prevent="onSubmit">
        <VTable v-if="arrayData.siswa.length">
          <thead>
            <tr>
              <th class="text-center">Nama Peserta Didik</th>
              <th class="text-center">Status Kenaikan</th>
              <th class="text-center">Kelas Tujuan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in arrayData.siswa">
              <td>
                <ProfileSiswa :item="item" />
              </td>
              <td>
                <AppAutocomplete id="rombongan_belajar_id" v-model="form.status[item.anggota_rombel.anggota_rombel_id]"
                  placeholder="== Pilih Status Kenaikan ==" :items="arrayData.options" clearable clear-icon="tabler-x"
                  @update:model-value="changeStatus(form.status[item.anggota_rombel.anggota_rombel_id], item.anggota_rombel.anggota_rombel_id, item.anggota_rombel.rombongan_belajar_id)" />
              </td>
              <td>
                <AppTextField v-model="form.nama_kelas[item.anggota_rombel.anggota_rombel_id]" />
              </td>
            </tr>
          </tbody>
        </VTable>
        <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible" v-if="arrayData.siswa.length">
          <VBtn variant="elevated" type="submit" :loading="confirmed" :disabled="confirmed">
            Simpan
          </VBtn>
        </VCardText>
      </VForm>
    </template>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
      :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" />
  </VCard>
</template>
