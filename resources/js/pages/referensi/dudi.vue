<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Data DUDI',
  },
})

const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  selectedRole: null,
  sortby: 'nama',
  sortbydesc: 'ASC',
});
// Headers
const headers = [
  {
    title: 'Nama',
    key: 'nama',
  },
  {
    title: 'Bidang Usaha',
    key: 'nama_bidang_usaha',
  },
  {
    title: 'Alamat',
    key: 'alamat_jalan',
  },
  {
    title: 'Jml Aktifitas',
    key: 'aktifitas',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Aksi',
    key: 'aksi',
    align: 'center',
    sortable: false,
  },
]
const items = ref([])
const total = ref(0)
const loadingTable = ref(false)
onMounted(async () => {
  await fetchData();
});
watch(options, async () => {
  await fetchData();
}, { deep: true });
watch(
  () => options.value.searchQuery,
  () => {
    options.value.page = 1
  }
)
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const fetchData = async () => {
  loadingTable.value = true;
  try {
    const response = await useApi(createUrl('/referensi/dudi', {
      query: {
        sekolah_id: $user.sekolah_id,
        q: options.value.searchQuery,
        page: options.value.page,
        per_page: options.value.itemsPerPage,
        sortby: options.value.sortby,
        sortbydesc: options.value.sortbydesc,
      },
    }));
    let getData = response.data
    items.value = getData.value.data.data
    total.value = getData.value.data.total
  } catch (error) {
    console.error(error);
  } finally {
    loadingTable.value = false;
  }
}
const showAnggota = ref(false)
const isLoading = ref(false)
const dialogTitle = ref('')
const anggotaRombel = ref([])
const aktPdId = ref()
const getAnggota = async (akt_pd_id) => {
  aktPdId.value = akt_pd_id
  isLoading.value = true
  showAnggota.value = true
  await $api('/referensi/dudi/anggota-prakerin', {
    method: 'POST',
    body: {
      akt_pd_id: aktPdId.value,
      semester_id: $semester.semester_id,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      dialogTitle.value = `Anggota Aktifitas Prakerin`
      anggotaRombel.value = getData
      isLoading.value = false
    }
  })
}
const loadingDetil = ref([])
const detilDialog = ref(false)
const cardTitle = ref()
const detilDudi = ref()
const dudiId = ref()
const detil = async (dudi_id) => {
  dudiId.value = dudi_id
  loadingDetil.value[dudi_id] = true
  await $api('/referensi/dudi/detil-dudi', {
    method: 'POST',
    body: {
      dudi_id: dudi_id,
      semester_id: $semester.semester_id,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      loadingDetil.value[dudi_id] = false
      cardTitle.value = `Informasi DUDI ${getData.nama}`
      detilDudi.value = getData
      detilDialog.value = true
    }
  })
}
const reFecthAnggota = async () => {
  await getAnggota(aktPdId.value)
}
const reFectDetil = async () => [
  await detil(dudiId.value)
]
</script>
<template>
  <section>
    <!-- 👉 Widgets -->
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Data Ekstrakurikuler</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol cols="12" md="4" class="d-flex align-items-center">
            <AppSelect v-model="options.itemsPerPage" :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
            ]" />
          </VCol>
          <VCol cols="12" md="4" offset-md="4" class="d-flex gap-4">
            <AppTextField v-model="options.searchQuery" placeholder="Cari Data" />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <!-- SECTION datatable -->
      <VDataTableServer class="text-no-wrap" :items="items" :server-items-length="total" :headers="headers"
        :options="options" :loading="loadingTable" loading-text="Loading..." @update:sortBy="updateSortBy">
        <template #item.aktifitas="{ item }">
          {{ item.akt_pd_count }}
        </template>
        <template #item.aksi="{ item }">
          <VBtn color="error" @click="detil(item.dudi_id)" size="x-small" :loading="loadingDetil[item.dudi_id]"
            :disabled="loadingDetil[item.dudi_id]">
            <VIcon start icon="tabler-eye" />Detil
          </VBtn>
        </template>
        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <VDialog v-model="detilDialog" scrollable width="100%" content-class="scrollable-dialog">
      <VCard>
        <VCardItem class="pb-5">
          <VCardTitle>{{ cardTitle }}</VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText style="padding:0">
          <VTable class="text-no-wrap">
            <tbody>
              <tr>
                <td>Nama</td>
                <td>{{ detilDudi?.nama }}</td>
              </tr>
              <tr>
                <td>Bidang Usaha</td>
                <td>{{ detilDudi?.nama_bidang_usaha }}</td>
              </tr>
              <tr>
                <td>Alamat</td>
                <td>{{ detilDudi?.alamat_jalan }}</td>
              </tr>
            </tbody>
          </VTable>
          <VDivider />
          <VCardItem class="pb-5">
            <VCardTitle>MoU</VCardTitle>
          </VCardItem>
          <VDivider />
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center">Nomor MoU</th>
                <th class="text-center">Judul MoU</th>
                <th class="text-center">Periode Kerja Sama</th>
                <th class="text-center">Narahubung</th>
                <th class="text-center">Telp. Narahubung</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="detilDudi.mou.length">
                <tr v-for="mou in detilDudi.mou">
                  <td>{{ mou.nomor_mou }}</td>
                  <td>{{ mou.judul_mou }}</td>
                  <td>{{ mou.tanggal_mulai }} s/d {{ mou.tanggal_selesai }}</td>
                  <td>{{ mou.contact_person }}</td>
                  <td>{{ mou.telp_cp }}</td>
                </tr>
              </template>
              <template v-else>
                <tr>
                  <td class="text-center" colspan="5">Tidak ada data untuk ditampilkan</td>
                </tr>
              </template>
            </tbody>
          </VTable>
          <VDivider />
          <VCardItem class="pb-5">
            <VCardTitle>Aktifitas Peserta Didik</VCardTitle>
          </VCardItem>
          <VDivider />
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center">Nama Kegiatan</th>
                <th class="text-center">SK Tugas</th>
                <th class="text-center">Guru Pembimbing</th>
                <th class="text-center">Jml PD</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="detilDudi.mou.length">
                <template v-for="mou in detilDudi.mou">
                  <tr v-for="akt_pd in mou.akt_pd">
                    <td>{{ akt_pd.judul_akt_pd }}</td>
                    <td>{{ akt_pd.sk_tugas }}</td>
                    <td>
                      <VList lines="two">
                        <template v-for="ptk of akt_pd.bimbing_pd">
                          <VListItem>
                            <template #prepend>
                              <VAvatar :image="ptk.guru.photo" />
                            </template>
                            <VListItemTitle>{{ ptk.guru.nama_lengkap }}</VListItemTitle>
                            <VListItemSubtitle class="mt-1">{{ ptk.guru.email }}</VListItemSubtitle>
                          </VListItem>
                        </template>
                      </VList>
                    </td>
                    <td class="text-center">{{ akt_pd.anggota_akt_pd_count }}</td>
                    <td class="text-center">
                      <VBtn color="error" @click="getAnggota(akt_pd.akt_pd_id)" size="x-small">
                        <VIcon start icon="tabler-eye" />Detil
                      </VBtn>
                    </td>
                  </tr>
                </template>
              </template>
              <template v-else>
                <tr>
                  <td class="text-center" colspan="5">Tidak ada data untuk ditampilkan</td>
                </tr>
              </template>
            </tbody>
          </VTable>
        </VCardText>
        <VDivider />
        <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible">
          <VBtn color="secondary" variant="tonal" @click="detilDialog = false">
            Tutup
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    <AnggotaRombelDialog v-model:isDialogVisible="showAnggota" v-model:isLoading="isLoading" :dialog-title="dialogTitle"
      v-model:listData="anggotaRombel" @refresh="reFecthAnggota" @close="reFectDetil" />
  </section>
</template>
<style lang="scss">
.scrollable-dialog {
  overflow: visible !important;
}
</style>
