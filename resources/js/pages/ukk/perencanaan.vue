<script setup>
import { Indonesian } from "flatpickr/dist/l10n/id.js";
const dateConfig = ref({
  locale: Indonesian,
  altFormat: "j F Y",
  altInput: true,
});
definePage({
  meta: {
    action: 'read',
    subject: 'Kaprog',
    title: 'Rencana Penilaian UKK',
  },
})
const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  sortby: 'updated_at',
  sortbydesc: 'DESC',
});
// Headers
const headers = [
  {
    key: 'paket_ukk',
    title: 'Paket Kompetensi',
    sortable: false,
  },
  {
    key: 'guru_internal',
    title: 'Penguji Internal',
    sortable: false,
  },
  {
    key: 'guru_eksternal',
    title: 'Penguji Eksternal',
    sortable: false,
  },
  {
    key: 'pd_count',
    title: 'Jml PD',
    sortable: false,
    align: 'center',
  },
  {
    key: 'actions',
    title: 'Aksi',
    align: 'center',
    sortable: false,
    nowrap: true,
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
    const response = await useApi(createUrl('/ukk', {
      query: {
        user_id: $user.user_id,
        guru_id: $user.guru_id,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        periode_aktif: $semester.nama,
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
const isConfirmDialogVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const filter = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
})
const isNotifVisible = ref(false)
const loadingRombel = ref(false)
const loadingInternal = ref(false)
const loadingPaket = ref(false)
const loadingBody = ref(false)
const isDialogVisible = ref(false)
const dialogTitle = ref('')
const defaultForm = {
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  periode_aktif: $semester.nama,
}
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  jurusan_id: null,
  penguji_internal: null,
  penguji_eksternal: null,
  paket_ukk_id: null,
  tanggal: null,
  selected: [],
})
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  penguji_internal: null,
  penguji_eksternal: null,
  paket_ukk_id: null,
  tanggal: null,
})
const getAksi = ref()
const confirmationText = ref()
const rencanaUkkId = ref()
const detilRencana = ref()
const aksi = async (aksi, item) => {
  rencanaUkkId.value = item.rencana_ukk_id
  getAksi.value = aksi
  if (aksi == 'detil') {
    dialogTitle.value = 'Detil Perencanaan Penilaian UKK'
    await $api('/ukk/show', {
      method: 'POST',
      body: {
        rencana_ukk_id: rencanaUkkId.value,
      },
      onResponse({ response }) {
        let getData = response._data
        isDialogVisible.value = true
        detilRencana.value = getData.rencana
        dataSiswa.value = getData.data_siswa
      },
    })
  } else {
    isConfirmDialogVisible.value = true
    confirmationText.value = 'Tindakan ini tidak dapat dikembalikan!'
  }
}
const dataRombel = ref([])
const dataInternal = ref([])
const dataEksternal = ref([])
const paketUkk = ref([])
const dataSiswa = ref([])
const selectAll = ref(false)
const selectAllItems = () => {
  if (selectAll.value) {
    form.value.selected = dataSiswa.value.map(siswa => `${siswa.peserta_didik_id}#${siswa.anggota_rombel.anggota_rombel_id}`)
  } else {
    form.value.selected = []
  }
}
const toggleIndeterminateCheckbox = ref(false)
watch(
  () => form.value.selected,
  (newValue) => {
    if (newValue.length === dataSiswa.value.length) {
      selectAll.value = true
    } else {
      toggleIndeterminateCheckbox.value = true
      selectAll.value = false
    }
  }
)
const rencanaUkk = ref()
const addNewData = () => {
  getAksi.value = 'add'
  isDialogVisible.value = true
  dialogTitle.value = 'Tambah Perencanaan Penilaian UKK'
}
const confirmDelete = async (val) => {
  if (val) {
    await $api('/ukk/hapus', {
      method: 'POST',
      body: {
        data: 'rencana',
        rencana_ukk_id: rencanaUkkId.value,
      },
      onResponse({ response }) {
        let getData = response._data
        isDialogVisible.value = false
        isNotifVisible.value = true
        notif.value = getData
      },
    })
  }
}
const confirmClose = async () => {
  await fetchData();
}
const formReset = () => {
  form.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    jurusan_id: null,
    penguji_internal: null,
    penguji_eksternal: null,
    paket_ukk_id: null,
    tanggal: null,
    selected: [],
  }
  errors.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    penguji_internal: null,
    penguji_eksternal: null,
    paket_ukk_id: null,
    tanggal: null,
  }
  dataRombel.value = []
  dataInternal.value = []
  dataEksternal.value = []
  paketUkk.value = []
  dataSiswa.value = []
  selectAll.value = false
}
const saveData = async (val) => {
  if (val) {
    const aksiForm = { data: 'rencana' }
    const mergedForm = { ...aksiForm, ...defaultForm, ...form.value };
    await $api('/ukk/save', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        if (getData.errors) {
          errors.value = getData.errors
        } else {
          formReset()
          isDialogVisible.value = false
          isNotifVisible.value = true
          notif.value = getData
        }
      },
    })
  }
}
const changeFormTingkat = async (val) => {
  if (val) {
    loadingRombel.value = true
    const newForm = { data: 'rombel', 'aksi': 'rencana-ukk', jenis_rombel: 1 };
    const mergedForm = { ...newForm, ...defaultForm, ...form.value };
    await $api('/referensi/get-data', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        dataRombel.value = getData
        loadingRombel.value = false
      }
    })
  }
}
const changeFormRombel = async (val) => {
  if (val) {
    loadingInternal.value = true
    const newForm = { data: 'penguji-ukk' };
    const mergedForm = { ...newForm, ...defaultForm, ...form.value };
    await $api('/referensi/get-data', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        form.value.jurusan_id = getData.rombel.jurusan_sp.jurusan_id
        dataInternal.value = getData.internal
        dataEksternal.value = getData.eksternal
        loadingInternal.value = false
      }
    })
  }
}
const changeTanggal = async (val) => {
  if (val) {
    loadingPaket.value = true
    const newForm = { data: 'paket-ukk' };
    const mergedForm = { ...newForm, ...defaultForm, ...form.value };
    await $api('/referensi/get-data', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        paketUkk.value = getData
        loadingPaket.value = false
      }
    })
  }
}
const changePaket = async (val) => {
  if (val) {
    loadingBody.value = true
    const newForm = { data: 'siswa-ukk' };
    const mergedForm = { ...newForm, ...defaultForm, ...form.value };
    await $api('/referensi/get-data', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        rencanaUkk.value = getData.rencana_ukk
        dataSiswa.value = getData.data_siswa
        loadingBody.value = false
      }
    })
  }
}
const kesimpulanUkk = (item) => {
  var predikat = ''
  if (item.nilai_ukk && item.nilai_ukk.nilai) {
    var nilai = item.nilai_ukk.nilai
    /*if (nilai >= 90) {
        predikat = 'Sangat Kompeten';
    } else if (nilai >= 75 && nilai <= 89) {
        predikat = 'Kompeten';
    } else if (nilai >= 70 && nilai <= 74) {
        predikat = 'Cukup Kompeten';
    } else if (nilai < 70) {
        predikat = 'Belum Kompeten';
    }*/
    if (nilai >= 70) {
      predikat = 'Kompeten';
    } else {
      predikat = 'Belum Kompeten';
    }
  }
  return predikat;
}
const generateLink = (item) => {
  var link_cetak = null
  if (item.nilai_ukk.nilai) {
    link_cetak = `/cetak/sertifikat/${item.nilai_ukk.anggota_rombel_id}/${item.nilai_ukk.rencana_ukk_id}`
  }
  return link_cetak
}
</script>

<template>
  <section>
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Rencana Penilaian UKK</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="me-3 d-flex gap-3">
          <AppSelect v-model="options.itemsPerPage" :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
          ]" style="inline-size: 6.25rem;" />
        </div>
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <div style="inline-size: 15.625rem;">
            <AppTextField v-model="options.searchQuery" placeholder="Cari Data" />
          </div>

          <!-- 👉 Add user button -->
          <VBtn prepend-icon="tabler-plus" @click="addNewData">
            Tambah Data
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- SECTION datatable -->
      <VDataTableServer :items="items" :server-items-length="total" :headers="headers" :options="options"
        :loading="loadingTable" loading-text="Loading..." @update:sortBy="updateSortBy">
        <template #item.paket_ukk="{ item }">
          {{ item.paket_ukk.nama_paket_id }}
        </template>
        <template #item.guru_internal="{ item }">
          <ProfilePtk :item="item.guru_internal" />
        </template>
        <template #item.guru_eksternal="{ item }">
          <ProfilePtk :item="item.guru_eksternal" />
        </template>
        <template #item.actions="{ item }">
          <IconBtn @click="aksi('detil', item)">
            <VTooltip activator="parent" location="top">
              Detil Data
            </VTooltip>
            <VIcon icon="tabler-file-search" />
          </IconBtn>
          <IconBtn @click="aksi('hapus', item)">
            <VTooltip activator="parent" location="top">
              Hapus Data
            </VTooltip>
            <VIcon icon="tabler-trash" color="error" />
          </IconBtn>
        </template>
        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <DefaultDialog v-model:isDialogVisible="isDialogVisible" :dialog-title="dialogTitle" :errors="errors"
      :isSubmitBtn="getAksi == 'add'" @confirm="saveData">
      <template #content>
        <template v-if="getAksi == 'add'">
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
                  <AppSelect v-model="form.tingkat" placeholder="== Pilih Tingkat kelas ==" :items="tingkatKelas"
                    clearable clear-icon="tabler-x" @update:model-value="changeFormTingkat"
                    :error-messages="errors.tingkat" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="rombongan_belajar_id">Rombongan
                    Belajar</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppSelect v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                    :items="dataRombel" clearable clear-icon="tabler-x" @update:model-value="changeFormRombel"
                    item-value="rombongan_belajar_id" item-title="nama" :loading="loadingRombel"
                    :disabled="loadingRombel" :error-messages="errors.rombongan_belajar_id" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="penguji_internal">Penguji Internal</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppSelect v-model="form.penguji_internal" placeholder="== Pilih Penguji Internal =="
                    :items="dataInternal" clearable clear-icon="tabler-x" item-value="guru_id" item-title="nama_lengkap"
                    :loading="loadingInternal" :disabled="loadingInternal" :error-messages="errors.penguji_internal" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="penguji_eksternal">Penguji
                    Eksternal</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppSelect v-model="form.penguji_eksternal" placeholder="== Pilih Penguji Eksternal =="
                    :items="dataEksternal" clearable clear-icon="tabler-x" item-value="guru_id"
                    item-title="nama_lengkap" :loading="loadingInternal" :disabled="loadingInternal"
                    :error-messages="errors.penguji_eksternal" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="tanggal">Tanggal Sertifikat</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppDateTimePicker v-model="form.tanggal" placeholder="== Pilih Tanggal Sertifikat =="
                    :config="dateConfig" @update:model-value="changeTanggal" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="paket_ukk_id">Paket Kompetensi</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppSelect v-model="form.paket_ukk_id" placeholder="== Pilih Paket Kompetensi ==" :items="paketUkk"
                    clearable clear-icon="tabler-x" item-value="paket_ukk_id" item-title="nama_paket_id"
                    :loading="loadingPaket" :disabled="loadingPaket" :error-messages="errors.paket_ukk_id"
                    @update:model-value="changePaket" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" class="text-center" v-if="loadingBody">
              <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
            </VCol>
          </VRow>
        </template>
      </template>
      <template #table>
        <template v-if="getAksi == 'detil'">
          <VTable class="text-no-wrap">
            <tbody>
              <tr>
                <td>Paket UKK</td>
                <td>{{ detilRencana?.paket_ukk?.nama_paket_id }}</td>
              </tr>
              <tr>
                <td>Penguji Internal</td>
                <td>
                  <ProfilePtk :item="detilRencana?.guru_internal" />
                </td>
              </tr>
              <tr>
                <td>Penguji Eksternal</td>
                <td>
                  <ProfilePtk :item="detilRencana?.guru_eksternal" />
                </td>
              </tr>
            </tbody>
          </VTable>
        </template>
        <template v-if="dataSiswa.length">
          <VDivider />
          <VTable class="text-no-wrap" :fixed-header="true">
            <thead>
              <tr>
                <th class="text-center" v-if="getAksi == 'add'">
                  <VCheckbox v-model:indeterminate="toggleIndeterminateCheckbox" v-model="selectAll"
                    @change="selectAllItems" />
                </th>
                <th>Nama Peserta Didik</th>
                <template v-if="getAksi == 'detil'">
                  <th class="text-center">Nilai</th>
                  <th class="text-center">Kesimpulan</th>
                  <th class="text-center">Cetak</th>
                </template>
              </tr>
            </thead>
            <tbody>
              <tr v-for="siswa in dataSiswa">
                <td class="text-center" v-if="getAksi == 'add'">
                  <VCheckbox v-model="form.selected"
                    :value="`${siswa.peserta_didik_id}#${siswa.anggota_rombel.anggota_rombel_id}`" />
                </td>
                <td>
                  <ProfileSiswa :item="siswa" />
                </td>
                <template v-if="getAksi == 'detil'">
                  <td class="text-center">{{ siswa.nilai_ukk?.nilai }}</td>
                  <td>{{ kesimpulanUkk(siswa) }}</td>
                  <td class="text-center">
                    <VBtn prepend-icon="tabler-printer" color="success" size="small" :href="generateLink(siswa)"
                      target="_blank" v-if="generateLink(siswa)">Cetak</VBtn>
                  </td>
                </template>
              </tr>
            </tbody>
          </VTable>
        </template>
      </template>
    </DefaultDialog>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" :confirmation-text="confirmationText" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDelete" @close="confirmClose" />
  </section>
</template>
<style lang="scss">
.scrollable-dialog {
  overflow: visible !important;
}
</style>
