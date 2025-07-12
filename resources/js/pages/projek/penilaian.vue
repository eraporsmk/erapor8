<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Projek',
    title: "Proses Penilaian P5",
  },
})
const refVForm = ref();
const form = ref({
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  tingkat: null,
  rombongan_belajar_id: null,
  rencana_budaya_kerja_id: null,
  nilai: {},
  deskripsi: {},
})
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  rencana_budaya_kerja_id: null,
})
const arrayData = ref({
  rombel: [],
  rencana: [],
  siswa: [],
  aspek: [],
  opsi: [],
})
const loading = ref({
  rombel: false,
  rencana: false,
  body: false,
})
const confirmed = ref(false)
const onFormSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      submitForm()
    }
  });
}
const submitForm = async () => {
  confirmed.value = true
  const postData = { data: 'nilai' }
  const mergedForm = { ...postData, ...form.value }
  await $api("/projek/save", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data
      isNotifVisible.value = true
      notif.value = getData
      confirmed.value = false
    },
  })
}
const getData = async (postData) => {
  const mergedForm = { ...postData, ...form.value }
  await $api("/referensi/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData
      }
      if (postData.data == "rencana-p5") {
        arrayData.value.rencana = getData
      }
      if (postData.data == "nilai-p5") {
        arrayData.value.siswa = getData.data_siswa
        arrayData.value.aspek = getData.rencana_budaya_kerja.aspek_budaya_kerja
        arrayData.value.opsi = getData.opsi_budaya_kerja
        getData.data_siswa.forEach(siswa => {
          /*const item = siswa.anggota_rombel.nilai_budaya_kerja
          if (item)
            form.value.nilai[siswa.anggota_rombel.anggota_rombel_id + '#' + item.aspek_budaya_kerja_id] = item ? `${item.opsi_id}#${item.elemen_id}` : null*/
          siswa.anggota_rombel.nilai_budaya_kerja.forEach(nilai => {
            form.value.nilai[siswa.anggota_rombel.anggota_rombel_id + '#' + nilai.aspek_budaya_kerja_id] = nilai.opsi_id + '#' + nilai.elemen_id
          });
          form.value.deskripsi[form.value.rencana_budaya_kerja_id + '#' + siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.catatan_budaya_kerja?.catatan
        });
      }
    },
  })
}
const changeTingkat = async (val) => {
  arrayData.value.rombel = []
  form.value.rombongan_belajar_id = null
  form.value.rencana_budaya_kerja_id = null
  if (val) {
    loading.value.rombel = true
    await getData({
      data: "rombel",
      aksi: 'nilai-p5',
      tingkat: val,
    }).then(() => {
      loading.value.rombel = false
    })
  }
}
const changeRombel = async (val) => {
  arrayData.value.rencana = []
  form.value.rencana_budaya_kerja_id = null
  if (val) {
    loading.value.rencana = true
    await getData({
      data: "rencana-p5",
      rombongan_belajar_id: val,
    }).then(() => {
      loading.value.rencana = false
    })
  }
}
const changeRencana = async (val) => {
  arrayData.value.aspek = []
  arrayData.value.siswa = []
  if (val) {
    loading.value.body = true
    await getData({
      data: "nilai-p5",
      rencana_budaya_kerja_id: val,
    }).then(() => {
      loading.value.body = false
    })
  }
}
const isConfirmDialogVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const isNotifVisible = ref(false)
const confirmClose = async () => {
  form.value.tingkat = null
  form.value.rombongan_belajar_id = null
  form.value.rencana_budaya_kerja_id = null
  form.value.nilai = {}
  form.value.deskripsi = {}
  errors.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    rencana_budaya_kerja_id: null,
  }
  arrayData.value = {
    rombel: [],
    rencana: [],
    siswa: [],
    aspek: [],
    opsi: [],
  }
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Proses Penilaian P5</VCardTitle>
    </VCardItem>
    <VDivider />
    <VForm ref="refVForm" @submit.prevent="onFormSubmit">
      <VCardText>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="semester_id">Tahun Pelajaran</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppTextField id="semester_id" :value="$semester.nama" disabled />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="tingkat">Tingkat Kelas</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.tingkat" placeholder="== Pilih Tingkat kelas ==" :items="tingkatKelas" clearable
                clear-icon="tabler-x" @update:model-value="changeTingkat" :error-messages="errors.tingkat" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="rombongan_belajar_id">Rombongan Belajar</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar =="
                item-value="rombongan_belajar_id" item-title="nama" :items="arrayData.rombel" clearable
                clear-icon="tabler-x" @update:model-value="changeRombel" :error-messages="errors.rombongan_belajar_id"
                :loading="loading.rombel" :disabled="loading.rombel" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="rencana_budaya_kerja_id">Rencana
                Penilaian</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.rencana_budaya_kerja_id" placeholder="== Pilih Rencana Penilaian =="
                item-value="rencana_budaya_kerja_id" item-title="nama" :items="arrayData.rencana" clearable
                clear-icon="tabler-x" @update:model-value="changeRencana"
                :error-messages="errors.rencana_budaya_kerja_id" :loading="loading.rencana"
                :disabled="loading.rencana" />
            </VCol>
          </VRow>
        </VCol>
      </VCardText>
      <VCardText class="text-center" v-if="loading.body">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
      <template v-if="arrayData.aspek.length">
        <VDivider />
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-center border-right" rowspan="2">Nama Peserta Didik</th>
              <th class="text-center" :colspan="arrayData.aspek.length">Sub Elemen</th>
            </tr>
            <tr>
              <th class="text-center" v-for="(aspek, index) in arrayData.aspek">
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <a href="javascript:void(0)" v-bind="props">{{ index + 1 }}</a>
                  </template>
                  <span>
                    <p><strong>Dimensi:</strong> {{ aspek.budaya_kerja.aspek }}</p>
                    <p><strong>Elemen:</strong> {{ aspek.elemen_budaya_kerja.elemen }}</p>
                    <p><strong>Sub Elemen:</strong> {{ aspek.elemen_budaya_kerja.deskripsi }}</p>
                  </span>
                </VTooltip>
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="siswa in arrayData.siswa">
              <tr>
                <td rowspan="2">
                  <ProfileSiswa :item="siswa" />
                </td>
                <td v-for="aspek in arrayData.aspek" class="py-4">
                  <AppSelect
                    v-model="form.nilai[siswa.anggota_rombel.anggota_rombel_id + '#' + aspek.aspek_budaya_kerja_id]"
                    placeholder="== Nilai ==" :items="arrayData.opsi" clearable clear-icon="tabler-x"
                    style="inline-size: 8rem;" item-title="nama"
                    :item-value="item => `${item.opsi_id}#${aspek.elemen_id}`">
                  </AppSelect>
                </td>
              </tr>
              <tr>
                <td :colspan="arrayData.aspek.length" class="py-4">
                  <VTextarea label="Catatan proses" placeholder="Catatan proses"
                    v-model="form.deskripsi[form.rencana_budaya_kerja_id + '#' + siswa.anggota_rombel.anggota_rombel_id]" />
                </td>
              </tr>
            </template>
          </tbody>
        </VTable>
        <VDivider />
        <VCardText class="d-flex justify-end">
          <VBtn variant="elevated" :loading="confirmed" :disabled="confirmed" type="submit">
            Simpan
          </VBtn>
        </VCardText>
      </template>
    </VForm>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
      :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" @close="confirmClose" />
  </VCard>
</template>
