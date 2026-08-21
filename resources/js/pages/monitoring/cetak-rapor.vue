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

const filterJurusan = ref([])
const filterTingkat = ref([])
const allRombels = ref([])
const jurusanOptions = ref([])
const bulkSiswa = ref([])
const bulkRombonganBelajarIds = ref([])

const loadBulkDependencies = async () => {
  if (allRombels.value.length === 0) {
    loading.value.body = true
    try {
      const responseRombel = await $api('/monitoring/get-data', { method: 'POST', body: { data: 'semua_rombel', sekolah_id: form.value.sekolah_id, semester_id: form.value.semester_id } })
      allRombels.value = responseRombel
      const responseJurusan = await $api('/monitoring/get-data', { method: 'POST', body: { data: 'jurusan_sp', sekolah_id: form.value.sekolah_id } })
      jurusanOptions.value = responseJurusan
    } catch (e) { console.error(e) }
    loading.value.body = false
  }
}

watch(isBulkDialogOpen, (val) => {
  if (val) loadBulkDependencies()
})

const filteredRombels = computed(() => {
  return allRombels.value.filter(r => {
    let matchJurusan = filterJurusan.value.length === 0 || filterJurusan.value.includes(r.jurusan_sp_id)
    let matchTingkat = filterTingkat.value.length === 0 || filterTingkat.value.includes(r.tingkat)
    return matchJurusan && matchTingkat
  })
})

const applyFilter = () => {
  const ids = filteredRombels.value.map(r => r.rombongan_belajar_id)
  bulkRombonganBelajarIds.value = [...new Set([...bulkRombonganBelajarIds.value, ...ids])]
}

watch(bulkRombonganBelajarIds, async (newVal) => {
  bulkSiswa.value = []
  bulkForm.value.peserta_didik_ids = []
  if (newVal.length > 0) {
     try {
         const resp = await $api('/monitoring/get-data', { method: 'POST', body: { data: 'siswa_by_rombels', rombongan_belajar_ids: newVal } })
         bulkSiswa.value = resp.data_siswa
         bulkForm.value.peserta_didik_ids = bulkSiswa.value.map(s => s.peserta_didik_id)
     } catch (e) { console.error(e) }
  }
}, { deep: true })

const getNamaRombel = () => {
  if (bulkRombonganBelajarIds.value.length === 0) return 'Belum ada kelas terpilih'
  if (bulkRombonganBelajarIds.value.length > 1) return `Multi-Kelas (${bulkRombonganBelajarIds.value.length} rombel)`
  const rombel = allRombels.value.find(r => r.rombongan_belajar_id === bulkRombonganBelajarIds.value[0])
  return rombel?.nama ?? ''
}

const unduhBulkRapor = async () => {
  if (bulkRombonganBelajarIds.value.length === 0) return
  bulkStatus.value = { loading: true, job_id: null, progress: 0, total: 0, status: 'preparing', download_url: null, error_msg: null }

  const payload = {
    rombongan_belajar_ids: bulkRombonganBelajarIds.value,
    peserta_didik_ids: bulkForm.value.peserta_didik_ids.length !== bulkSiswa.value.length ? bulkForm.value.peserta_didik_ids : 'all',
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
          <!-- Advanced Filters -->
          <VCol cols="12" md="6">
            <p class="text-body-2 font-weight-medium mb-2">Filter Jurusan & Tingkat (Pencarian Rombel):</p>
            <AppSelect v-model="filterJurusan" placeholder="Semua Jurusan"
              :items="jurusanOptions" item-value="jurusan_sp_id" item-title="nama_jurusan_sp"
              multiple clearable class="mb-3" />
            
            <AppSelect v-model="filterTingkat" placeholder="Semua Tingkat"
              :items="tingkatKelas" multiple clearable />

            <VBtn color="secondary" variant="tonal" class="mt-3 w-100" prepend-icon="tabler-filter-plus" @click="applyFilter">
              Tambahkan Rombel dari Filter ke Kotak Kanan
            </VBtn>
          </VCol>

          <!-- Pusat Kendali -->
          <VCol cols="12" md="6">
            <p class="text-body-2 font-weight-medium mb-2">Pilih Rombongan Belajar (Pusat Kendali):</p>
            <AppAutocomplete v-model="bulkRombonganBelajarIds" placeholder="Semua Rombel di Sekolah"
              :items="allRombels" item-value="rombongan_belajar_id" item-title="nama"
              multiple clearable chips closable-chips class="mb-4" />
            
            <p class="text-body-2 font-weight-medium mb-2">Siswa (Hilangkan centang untuk mengecualikan):</p>
            <AppAutocomplete
              v-model="bulkForm.peserta_didik_ids"
              :items="bulkSiswa"
              item-title="nama"
              item-value="peserta_didik_id"
              placeholder="Semua Siswa"
              multiple
              clearable
              :disabled="bulkRombonganBelajarIds.length === 0"
            >
              <template #selection="{ item, index }">
                <span v-if="index === 0" class="text-caption font-weight-bold text-primary">{{ bulkForm.peserta_didik_ids.length }} siswa akan diunduh</span>
              </template>
            </AppAutocomplete>
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
