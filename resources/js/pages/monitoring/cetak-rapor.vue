<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Waka',
    title: 'Monitoring \u00bb Cetak Rapor',
  },
})
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  merdeka: false,
  rapor_pts: false,
  is_ppa: false,
  is_new_ppa: true,
})
const arrayData = ref({
  rombel: [],
  siswa: [],
})
const loading = ref({
  rombel: false,
  body: false,
})
const getData = async (postData) => {
  const mergedForm = { ...postData, ...form.value };
  await $api("/monitoring/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData;
      }
      if (postData.data == "siswa") {
        arrayData.value.siswa = getData.data_siswa
        form.value.merdeka = getData.merdeka
        form.value.rapor_pts = getData.rapor_pts
        form.value.is_ppa = getData.is_ppa
        form.value.is_new_ppa = getData.is_new_ppa
      }
    },
  });
}
const changeTingkat = async (val) => {
  form.value.rombongan_belajar_id = null;
  arrayData.value.rombel = []
  arrayData.value.siswa = []
  if (val) {
    loading.value.rombel = true;
    await getData({
      data: "rombel",
    }).then(() => {
      loading.value.rombel = false;
    });
  }
}
const changeRombel = async (val) => {
  arrayData.value.siswa = []
  if (val && val !== 'all') {
    loading.value.body = true;
    await getData({
      data: "siswa",
    }).then(() => {
      loading.value.body = false;
    });
  }
}

// Tambah opsi "Semua Rombel" di depan list
const rombelOptions = computed(() => {
  return [
    { rombongan_belajar_id: 'all', nama: '== Semua Rombel ==' },
    ...arrayData.value.rombel,
  ]
})

// =============================================
// Bulk Rapor
// =============================================
const bulkForm = ref({
  komponen: {
    cover: true,
    akademik: true,
    pts: false,
    p5: false,
    pelengkap: true,
  },
  peserta_didik_ids: [],
  format: 'zip',
})
const bulkStatus = ref({
  loading: false,
  job_id: null,
  progress: 0,
  total: 0,
  status: null,
  download_url: null,
  error_msg: null,
})
const isBulkDialogOpen = ref(false)
let pollingInterval = null

const getNamaRombel = () => {
  if (form.value.rombongan_belajar_id === 'all') return 'Semua-Kelas'
  const rombel = arrayData.value.rombel.find(r => r.rombongan_belajar_id === form.value.rombongan_belajar_id)
  return rombel?.nama ?? ''
}

const unduhBulkRapor = async () => {
  bulkStatus.value = { loading: true, job_id: null, progress: 0, total: 0, status: 'preparing', download_url: null, error_msg: null }

  const rombelId = form.value.rombongan_belajar_id || 'all';
  const payload = {
    rombongan_belajar_ids: rombelId === 'all' ? 'all' : [rombelId],
    peserta_didik_ids: bulkForm.value.peserta_didik_ids.length ? bulkForm.value.peserta_didik_ids : 'all',
    nama_rombel: getNamaRombel(),
    sekolah_id: form.value.sekolah_id,
    semester_id: form.value.semester_id,
    periode_aktif: $semester.nama,
    komponen: bulkForm.value.komponen,
    format: bulkForm.value.format,
  }

  try {
    const response = await $api('/cetak/bulk-rapor', {
      method: 'POST',
      body: payload,
    })
    if (response?.redirect_to_queue) {
      await startQueueJob(payload)
    } else {
      bulkStatus.value.loading = false
      bulkStatus.value.status = 'done'
    }
  } catch (e) {
    console.error(e)
    bulkStatus.value.loading = false
    bulkStatus.value.status = 'error'
    bulkStatus.value.error_msg = 'Terjadi kesalahan saat memproses rapor. Coba lagi.'
  }
}

const startQueueJob = async (payload) => {
  try {
    const response = await $api('/cetak/bulk-rapor/queue', {
      method: 'POST',
      body: payload,
    })
    bulkStatus.value.job_id = response.job_id
    bulkStatus.value.total  = response.total
    bulkStatus.value.status = 'queued'

    pollingInterval = setInterval(async () => {
      try {
        const statusResp = await $api(`/cetak/bulk-rapor/status/${bulkStatus.value.job_id}`)
        bulkStatus.value.progress = statusResp.progress ?? 0
        bulkStatus.value.status   = statusResp.status
        if (statusResp.status === 'done') {
          clearInterval(pollingInterval)
          bulkStatus.value.download_url = `/cetak/bulk-rapor/download/${bulkStatus.value.job_id}`
          bulkStatus.value.loading = false
        }
        if (statusResp.status === 'error') {
          clearInterval(pollingInterval)
          bulkStatus.value.loading   = false
          bulkStatus.value.error_msg = statusResp.message ?? 'Terjadi kesalahan saat memproses.'
        }
      } catch (pollErr) { console.error(pollErr) }
    }, 3000)
  } catch (e) {
    console.error(e)
    bulkStatus.value.loading = false
    bulkStatus.value.status  = 'error'
  }
}

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Monitoring &raquo; Cetak Rapor</VCardTitle>
      <template #append>
        <VBtn
          color="success"
          prepend-icon="tabler-download"
          @click="isBulkDialogOpen = true"
        >
          Unduh Semua Kelas
        </VBtn>
      </template>
    </VCardItem>
    <VDivider />
    <VCardText>
      <VRow>
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
                clear-icon="tabler-x" @update:model-value="changeTingkat" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="rombonganBelajarId">Rombongan
                Belajar</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                :items="rombelOptions" clearable clear-icon="tabler-x" @update:model-value="changeRombel"
                item-value="rombongan_belajar_id" item-title="nama" :loading="loading.rombel"
                :disabled="loading.rombel" />
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VCardText>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VTable class="text-no-wrap" v-if="arrayData.siswa.length">
        <thead>
          <tr>
            <th class="text-center">Peserta Didik</th>
            <th class="text-center">Halaman Depan</th>
            <th class="text-center">Rapor Akademik</th>
            <th class="text-center" v-if="form.rapor_pts">Rapor Tengah Semester</th>
            <th class="text-center" v-if="form.merdeka && !form.is_new_ppa">Rapor P5</th>
            <th class="text-center">Dokumen Pendukung</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in arrayData.siswa">
            <td>
              <ProfileSiswa :item="item" />
            </td>
            <td class="text-center">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="success" variant="text"
                :href="`/cetak/rapor-cover/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="form.is_new_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-akademik/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else-if="form.merdeka || form.is_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-nilai-akhir/${item.anggota_rombel.anggota_rombel_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else>
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-semester/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="form.rapor_pts">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="primary" variant="text"
                :href="`/cetak/rapor-tengah-semester/${item.peserta_didik_id}/${form.semester_id}`" target="_blank" />
            </td>
            <td class="text-center" v-if="form.merdeka && !form.is_new_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="info" variant="text"
                :href="`/cetak/rapor-p5/${item.anggota_rombel.anggota_rombel_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="error" variant="text"
                :href="`/cetak/rapor-pelengkap/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
          </tr>
        </tbody>
      </VTable>
    </template>
  </VCard>

  <!-- Bulk Rapor Dialog -->
  <VDialog v-model="isBulkDialogOpen" max-width="800">
    <VCard>
      <VCardItem class="pb-4">
        <VCardTitle>Unduh Rapor Semua Siswa</VCardTitle>
        <template #append>
          <VBtn icon="tabler-x" variant="text" @click="isBulkDialogOpen = false" />
        </template>
      </VCardItem>
      <VDivider />
      <VCardText>
        <VRow>
          <!-- Info Target -->
          <VCol cols="12" md="6">
            <p class="text-body-2 font-weight-medium mb-2">Pilih Rombongan Belajar:</p>
            <AppSelect v-model="form.rombongan_belajar_id" placeholder="Semua Rombel"
              :items="rombelOptions" clearable clear-icon="tabler-x" @update:model-value="changeRombel"
              item-value="rombongan_belajar_id" item-title="nama" :loading="loading.rombel"
              :disabled="loading.rombel" />
          </VCol>
          <VCol cols="12" md="6">
            <p class="text-body-2 font-weight-medium mb-2">Pilih Siswa (Kosongkan untuk semua):</p>
            <AppAutocomplete
              v-model="bulkForm.peserta_didik_ids"
              :items="arrayData.siswa"
              item-title="nama"
              item-value="peserta_didik_id"
              placeholder="Semua Siswa di Rombel"
              multiple
              clearable
              chips
              closable-chips
              :disabled="!form.rombongan_belajar_id || form.rombongan_belajar_id === 'all'"
            />
          </VCol>
          <!-- Pilihan Komponen -->
          <VCol cols="12">
            <p class="text-body-2 font-weight-medium mb-2">Pilih Komponen Rapor:</p>
            <VRow>
              <VCol cols="6" md="4">
                <VCheckbox v-model="bulkForm.komponen.cover" label="Cover (Halaman Depan)" />
              </VCol>
              <VCol cols="6" md="4">
                <VCheckbox v-model="bulkForm.komponen.akademik" label="Rapor Akademik" />
              </VCol>
              <VCol cols="6" md="4" v-if="form.rapor_pts || true">
                <VCheckbox v-model="bulkForm.komponen.pts" label="Rapor Tengah Semester" />
              </VCol>
              <VCol cols="6" md="4" v-if="(form.merdeka && !form.is_new_ppa) || true">
                <VCheckbox v-model="bulkForm.komponen.p5" label="Rapor P5" />
              </VCol>
              <VCol cols="6" md="4">
                <VCheckbox v-model="bulkForm.komponen.pelengkap" label="Dokumen Pendukung" />
              </VCol>
            </VRow>
          </VCol>
          <!-- Pilihan Format -->
          <VCol cols="12">
            <p class="text-body-2 font-weight-medium mb-2">Format Output:</p>
            <VRadioGroup v-model="bulkForm.format" inline>
              <VRadio label="ZIP (PDF per-siswa)" value="zip" />
              <VRadio label="PDF Gabungan (1 file)" value="pdf" />
            </VRadioGroup>
          </VCol>
          <!-- Progress Bar -->
          <VCol cols="12" v-if="bulkStatus.status === 'queued' || bulkStatus.status === 'processing'">
            <p class="text-body-2 mb-1">Memproses {{ bulkStatus.progress }}% dari {{ bulkStatus.total }} siswa...</p>
            <VProgressLinear :model-value="bulkStatus.progress" color="primary" height="8" rounded />
          </VCol>
          <!-- Tombol download setelah selesai -->
          <VCol cols="12" v-if="bulkStatus.status === 'done' && bulkStatus.download_url">
            <VAlert type="success" variant="tonal" class="d-flex align-center">
              File rapor siap!
              <VBtn :href="bulkStatus.download_url" color="success" class="ml-4" prepend-icon="tabler-download" size="small">
                Klik untuk Download
              </VBtn>
            </VAlert>
          </VCol>
          <!-- Error -->
          <VCol cols="12" v-if="bulkStatus.status === 'error'">
            <VAlert type="error" variant="tonal">{{ bulkStatus.error_msg ?? 'Terjadi kesalahan. Coba lagi.' }}</VAlert>
          </VCol>
        </VRow>
      </VCardText>
      <VCardText class="d-flex justify-end pt-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="isBulkDialogOpen = false"
          class="mr-3"
        >
          Tutup
        </VBtn>
        <VBtn
          prepend-icon="tabler-download"
          color="primary"
          :loading="bulkStatus.loading"
          :disabled="bulkStatus.loading"
          @click="unduhBulkRapor"
        >
          Unduh Rapor
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
