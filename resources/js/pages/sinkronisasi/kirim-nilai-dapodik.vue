<script setup>
import KirimNilai from '@/views/sinkronisasi/KirimNilai.vue';
import { themeConfig } from '@themeConfig';
import { useHead } from '@unhead/vue';
import Swal from 'sweetalert2';
useHead({
  title: `Kirim Nilai ke Dapodik | ${themeConfig.app.title}`
})
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
  },
})
const currentTab = ref('Rombongan Belajar')
const tabs = [
  'Rombongan Belajar',
  'Mata Evaluasi Rapor'
]
const searchQuery = ref('')
const per_page = ref(10)
const page = ref(1)
const sortby = ref('nama')
const sortbydesc = ref('ASC')
const updateOptions = options => {
  if (options.sortBy.length) {
    sortby.value = options.sortBy[0]?.key
    sortbydesc.value = options.sortBy[0]?.order
  }
}
const headers = [
  {
    title: 'Kelas',
    key: 'nama',
    sortable: true,
  },
  {
    title: 'Wali Kelas',
    key: 'wali_kelas',
    sortable: false,
  },
  {
    title: 'Mata Evaluasi',
    key: 'matev',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Mata Evaluasi Terkirim',
    key: 'matev_terkirim',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Generate Matev',
    key: 'generate_matev',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Kirim Nilai',
    key: 'nilai',
    align: 'center',
    sortable: false,
  },
]
const {
  data: getRombel,
  execute: fetchRombel,
} = await useApi(createUrl('/sinkronisasi/rombongan-belajar', {
  query: {
    sekolah_id: $user.sekolah_id,
    semester_id: $user.semester.semester_id,
    q: searchQuery,
    per_page,
    page,
    sortby,
    sortbydesc,
  },
}))
const items = computed(() => getRombel.value.data.data)
const total = computed(() => getRombel.value.data.total)
const url_dapodik = computed(() => getRombel.value.url_dapodik)
const token_dapodik = computed(() => getRombel.value.token_dapodik)
const handleMatev = (rombongan_belajar_id, nama) => {
  Swal.fire({
    title: 'Konfirmasi',
    text: `Aksi ini akan menggenerate Mata Evaluasi seluruh Mata Pelajaran di kelas ${nama}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Lanjutkan!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-outline-danger ml-1',
    },
    allowOutsideClick: () => false,
  }).then(async (result) => {
    if (result.value) {
      await $api('/sinkronisasi/matev-rapor', {
        method: 'POST',
        body: {
          rombongan_belajar_id: rombongan_belajar_id,
          nama_kelas: nama,
        },
        onResponse({ request, response, options }) {
          let getData = response._data
          Swal.fire({
            icon: getData.icon,
            title: getData.title,
            text: getData.text,
            customClass: {
              confirmButton: 'btn btn-success',
            },
          }).then(result => {
            fetchRombel()
          })
        }
      })
    }
  })
}
const kirimData = (rombongan_belajar_id, nama_kelas) => {
  Swal.fire({
    title: 'Konfirmasi',
    text: `Aksi ini akan mengirim nilai akhir seluruh Mata Pelajaran di kelas ${nama_kelas}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Lanjutkan!',
    cancelButtonText: 'Batal',
    customClass: {
      confirmButton: 'btn btn-primary',
      cancelButton: 'btn btn-outline-danger ml-1',
    },
    allowOutsideClick: () => false,
  }).then(async (result) => {
    if (result.value) {
      await $api('/sinkronisasi/kirim-nilai', {
        method: 'POST',
        body: {
          rombongan_belajar_id: rombongan_belajar_id,
          nama_kelas: nama_kelas,
          npsn: $user.sekolah.npsn,
          semester_id: $user.semester.semester_id,
          url_dapodik: url_dapodik.value,
          token_dapodik: token_dapodik.value,
        },
        onResponse({ request, response, options }) {
          let getData = response._data
          Swal.fire({
            icon: getData.icon,
            title: getData.title,
            text: getData.text,
            customClass: {
              confirmButton: 'btn btn-success',
            },
          }).then(result => {
            fetchRombel()
          })
        }
      })
    }
  })
}
const getLoad = ref(false)
const changeTab = (val) => {
  getLoad.value = false
  if(val == 'Mata Evaluasi Rapor'){
    getLoad.value = true
  }
}
</script>
<template>
  <div>
    <VCard title="Kirim Nilai ke Dapodik">
      <VCardText class="mt-4">
        <VAlert color="error" variant="tonal">
          <h2 class="mt-4 mb-4">Informasi Penting</h2>
          <p>Prioritaskan pengiriman nilai semester pada tingkat akhir, nilai rapor 5 semester untuk siswa kelas 12 dan
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
          <VCardText class="d-flex flex-wrap gap-4">
            <div class="d-flex gap-2 align-center">
              <AppSelect :model-value="per_page" :items="[
                { value: 10, title: '10' },
                { value: 25, title: '25' },
                { value: 50, title: '50' },
                { value: 100, title: '100' },
                { value: -1, title: 'All' },
              ]" style="inline-size: 15.5rem;" @update:model-value="per_page = parseInt($event, 10)" />
            </div>
            <VSpacer />
            <div class="d-flex align-center flex-wrap gap-4">
              <!-- 👉 Search  -->
              <AppTextField v-model="searchQuery" placeholder="Cari data" style="inline-size: 15.625rem;" />
            </div>
          </VCardText>
          <VDivider />
          <VDataTableServer v-model:items-per-page="per_page" v-model:page="page" :items-per-page-options="[
            { value: 10, title: '10' },
            { value: 20, title: '20' },
            { value: 50, title: '50' },
            { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
          ]" :items="items" :items-length="total" :headers="headers" items-per-page-text="Item" class="text-no-wrap" @update:options="updateOptions">
            <template #item.wali_kelas="{ item }">
              {{ item.wali_kelas.nama_lengkap }}
            </template>
            <template #item.generate_matev="{ item }">
              <template v-if="item.pembelajaran_count">
                <VBtn color="warning" size="small" @click="handleMatev(item.rombongan_belajar_id, item.nama)">
                  <VIcon start icon="tabler-bolt" />Generate
                </VBtn>
              </template>
              <template v-else>
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VIcon v-bind="props" size="30" icon="tabler-help" />
                  </template>
                  <span>Kelas {{ item.nama }} belum memiliki Pembelajaran termapping!</span>
                </VTooltip>
              </template>
            </template>
            <template #item.nilai="{ item }">
              <template v-if="item.matev">
                <VBtn color="primary" size="small" @click="kirimData(item.rombongan_belajar_id, item.nama)">
                  <VIcon start icon="tabler-database" />Kirim Data
                </VBtn>
              </template>
              <template v-else>
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VIcon v-bind="props" size="30" icon="tabler-help" />
                  </template>
                  <span>Kelas {{ item.nama }} belum memiliki Mata Evaluasi!</span>
                </VTooltip>
              </template>
            </template>
          </VDataTableServer>
        </VWindowItem>
        <VWindowItem value="Mata Evaluasi Rapor">
          <KirimNilai v-model:isTabActive="getLoad" />
        </VWindowItem>
      </VWindow>
    </VCard>
  </div>
</template>
