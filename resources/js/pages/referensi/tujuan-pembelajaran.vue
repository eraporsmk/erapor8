<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Guru',
    title: 'Ref. Tujuan Pembelajaran',
  },
})
const linkTemplateTp = ref()
const showUpload = ref(false)
const showCp = ref(false)
const showKd = ref(false)
const options = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  sortby: 'updated_at',
  sortbydesc: 'DESC',
});
// Headers
const headers = [
  {
    key: 'mata_pelajaran_id',
    title: 'Mata Pelajaran',
  },
  {
    key: 'rombel',
    title: 'rombel',
    align: 'center',
    sortable: false,
  },
  {
    key: 'kd_cp',
    title: 'CP/KD',
    sortable: false,
  },
  {
    key: 'kelas',
    title: 'Fase/Tingkat',
    sortable: false,
    align: 'center',
  },
  {
    key: 'deskripsi',
    title: 'Deskripsi',
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
const data_rombel = ref([])
const data_mapel = ref([])
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
    const response = await useApi(createUrl('/referensi/tujuan-pembelajaran', {
      query: {
        user_id: $user.user_id,
        guru_id: $user.guru_id,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        periode_aktif: $semester.nama,
        tingkat: options.value.tingkat,
        rombongan_belajar_id: options.value.rombongan_belajar_id,
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
const loadingMapel = ref(false)
const loadingCp = ref(false)
const loadingKd = ref(false)
const changeTingkat = async (val) => {
  loadingRombel.value = true
  data_rombel.value = []
  data_mapel.value = []
  filter.value.rombongan_belajar_id = null
  filter.value.pembelajaran_id = null
  options.value.tingkat = val
  if (val) {
    await $api('/referensi/get-data', {
      method: 'POST',
      body: {
        data: 'rombel',
        tingkat: val,
        user_id: $user.user_id,
        guru_id: $user.guru_id,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        periode_aktif: $semester.nama,
      },
      onResponse({ response }) {
        let getData = response._data
        loadingRombel.value = false
        data_rombel.value = getData
      }
    })
  } else {
    loadingRombel.value = false
  }
}
const changeRombel = async (val) => {
  data_mapel.value = []
  loadingMapel.value = true
  filter.value.pembelajaran_id = null
  options.value.rombongan_belajar_id = val
  if (val) {
    await $api('/referensi/get-data', {
      method: 'POST',
      body: {
        data: 'mapel',
        rombongan_belajar_id: val,
        user_id: $user.user_id,
        guru_id: $user.guru_id,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        periode_aktif: $semester.nama,
      },
      onResponse({ response }) {
        let getData = response._data
        loadingMapel.value = false
        data_mapel.value = getData.mapel
      }
    })
  } else {
    loadingMapel.value = false
  }
}
const changeMapel = async (val) => {
  options.value.pembelajaran_id = val
}
const isDialogVisible = ref(false)
const dialogTitle = ref('')
const defaultForm = {
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id
}
const form = ref({
  tp_id: null,
  tingkat: null,
  rombongan_belajar_id: null,
  mata_pelajaran_id: null,
  cp_id: null,
  kd_id: null,
  template_excel: null,
  deskripsi: null,
  merdeka: false,
})
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  mata_pelajaran_id: null,
  cp_id: null,
  kd_id: null,
  template_excel: null,
  deskripsi: null,
})
const getAksi = ref()
const confirmationText = ref()
const aksi = async (aksi, item) => {
  form.value.tp_id = item.tp_id
  getAksi.value = aksi
  if (aksi == 'mapping') {
    isDialogVisible.value = true
    dialogTitle.value = 'Petakan TP ke Rombongan Belajar'
  } else if (aksi == 'edit') {
    isDialogVisible.value = true
    form.value.deskripsi = item.deskripsi
    dialogTitle.value = 'Ubah Deskripsi Kompetensi Dasar'
  } else {
    isConfirmDialogVisible.value = true
    confirmationText.value = 'Tindakan ini tidak dapat dikembalikan!'
  }
}
const dataRombel = ref([])
const dataMapel = ref([])
const dataCp = ref([])
const dataKd = ref([])
const addNewData = () => {
  getAksi.value = 'add'
  isDialogVisible.value = true
  dialogTitle.value = 'Tambah Data Tujuan Pembelajaran'
}
const confirmDelete = async (val) => {
  if (val) {
    await $api('/referensi/tujuan-pembelajaran/delete', {
      method: 'POST',
      body: form.value,
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
const pembelajaranId = ref()
const postData = async () => {
  const aksiForm = { aksi: getAksi.value }
  const mergedForm = { ...aksiForm, ...defaultForm, ...form.value };
  const dataForm = new FormData();
  dataForm.append('aksi', mergedForm.aksi);
  dataForm.append('guru_id', mergedForm.guru_id);
  dataForm.append('sekolah_id', mergedForm.sekolah_id);
  dataForm.append('semester_id', mergedForm.semester_id);
  dataForm.append('user_id', mergedForm.user_id);
  dataForm.append('cp_id', (mergedForm.cp_id) ? mergedForm.cp_id : '');
  dataForm.append('kd_id', (mergedForm.kd_id) ? mergedForm.kd_id : '');
  dataForm.append('deskripsi', (mergedForm.deskripsi) ? mergedForm.deskripsi : '');
  dataForm.append('mata_pelajaran_id', (mergedForm.mata_pelajaran_id) ? mergedForm.mata_pelajaran_id : '');
  dataForm.append('pembelajaran_id', pembelajaranId.value);
  dataForm.append('rombongan_belajar_id', (mergedForm.rombongan_belajar_id) ? mergedForm.rombongan_belajar_id : '');
  dataForm.append('template_excel', (mergedForm.template_excel) ? mergedForm.template_excel : '');
  dataForm.append('tingkat', (mergedForm.tingkat) ? mergedForm.tingkat : '');
  dataForm.append('tp_id', (mergedForm.tp_id) ? mergedForm.tp_id : '');
  await $api('/referensi/tujuan-pembelajaran/save', {
    method: 'POST',
    body: dataForm,
    onResponse({ response }) {
      let getData = response._data
      if (getData.errors) {
        errors.value = getData.errors
      } else {
        pembelajaranId.value = null
        showUpload.value = false
        isDialogVisible.value = false
        isNotifVisible.value = true
        notif.value = getData
        form.value = {
          tp_id: null,
          tingkat: null,
          rombongan_belajar_id: null,
          mata_pelajaran_id: null,
          cp_id: null,
          kd_id: null,
          template_excel: null,
          deskripsi: null,
          merdeka: false,
        }
        errors.value = {
          tingkat: null,
          rombongan_belajar_id: null,
          mata_pelajaran_id: null,
          cp_id: null,
          kd_id: null,
          template_excel: null,
          deskripsi: null,
        }
      }
    },
  })
}
const saveData = async (val) => {
  if (val) {
    await postData()
  } else {
    form.value = {
      tp_id: null,
      tingkat: null,
      rombongan_belajar_id: null,
      mata_pelajaran_id: null,
      cp_id: null,
      kd_id: null,
      template_excel: null,
      deskripsi: null,
      merdeka: false,
    }
    errors.value = {
      tingkat: null,
      rombongan_belajar_id: null,
      mata_pelajaran_id: null,
      cp_id: null,
      kd_id: null,
      template_excel: null,
      deskripsi: null,
    }
  }
}
const changeFormTingkat = async (val) => {
  if (val) {
    loadingRombel.value = true
    const newForm = { data: 'rombel', mapping: 1 };
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
    loadingMapel.value = true
    const newForm = { data: 'mapel' };
    const mergedForm = { ...newForm, ...defaultForm, ...form.value };
    await $api('/referensi/get-data', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        dataMapel.value = getData.mapel
        form.value.merdeka = getData.merdeka
        loadingMapel.value = false
      }
    })
  }
}
const changeFormMapel = async (val) => {
  showCp.value = false
  showKd.value = false
  if (val) {
    loadingCp.value = true
    loadingKd.value = true
    const newForm = { data: 'cp_kd' };
    const mergedForm = { ...newForm, ...defaultForm, ...form.value };
    await $api('/referensi/get-data', {
      method: 'POST',
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data
        dataCp.value = getData.cp
        dataKd.value = getData.kd
        pembelajaranId.value = getData.pembelajaran_id
        if (getData.cp.length) {
          showCp.value = true
        }
        if (getData.kd.length) {
          showKd.value = true
        }
        loadingKd.value = false
        loadingCp.value = false
      }
    })
  }
}
const changeCp = () => {
  showUpload.value = true
  linkTemplateTp.value = '/downloads/template-tp/' + form.value.cp_id + '/' + form.value.rombongan_belajar_id + '/' + pembelajaranId.value
}
const changeKd = () => {
  showUpload.value = true
  linkTemplateTp.value = '/downloads/template-tp/' + form.value.kd_id + '/' + form.value.rombongan_belajar_id + '/' + pembelajaranId.value
}
const getRombel = (tp_mapel) => {
  let rombel = tp_mapel.map(x => x.rombongan_belajar.nama);//.join(", ");
  let uniqueChars = [...new Set(rombel)];
  return uniqueChars.join(", ");
}
</script>

<template>
  <section>
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Referensi Tujuan Pembelajaran</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol cols="12" sm="4">
            <AppSelect v-model="filter.tingkat" placeholder="== Filter Tingkat ==" :items="tingkatKelas" clearable
              clear-icon="tabler-x" @update:model-value="changeTingkat" />
          </VCol>
          <VCol cols="12" sm="4">
            <AppSelect v-model="filter.rombongan_belajar_id" placeholder="== Filter Rombel == " :items="data_rombel"
              clearable clear-icon="tabler-x" @update:model-value="changeRombel" item-value="rombongan_belajar_id"
              item-title="nama" :loading="loadingRombel" :disabled="loadingRombel" />
          </VCol>
          <VCol cols="12" sm="4">
            <AppSelect v-model="filter.pembelajaran_id" placeholder="== Filter Mapel ==" :items="data_mapel" clearable
              clear-icon="tabler-x" @update:model-value="changeMapel" item-value="pembelajaran_id"
              item-title="nama_mata_pelajaran" :loading="loadingMapel" :disabled="loadingMapel" />
          </VCol>
        </VRow>
      </VCardText>
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
        <template #item.mata_pelajaran_id="{ item }">
          <template v-if="item.cp">
            {{ item.cp.mata_pelajaran.nama }}
          </template>
          <template v-else>
            {{ item.kd.mata_pelajaran.nama }}
          </template>
        </template>
        <template #item.rombel="{ item }">
          {{ getRombel(item.tp_mapel) }}
        </template>
        <template #item.kd_cp="{ item }">
          <template v-if="item.cp">
            {{ item.cp.elemen }}
          </template>
          <template v-else>
            {{ item.kd.kompetensi_dasar }}
          </template>
        </template>
        <template #item.kelas="{ item }">
          <template v-if="item.cp">
            {{ item.cp.fase }}
          </template>
          <template v-else>
            <span v-if="item.kelas_10">10</span>
            <span v-if="item.kelas_11">&nbsp;11</span>
            <span v-if="item.kelas_12">&nbsp;12</span>
            <span v-if="item.kelas_13">&nbsp;13</span>
          </template>
        </template>
        <template #item.status="{ item }">
          <VChip size="x-small" color="success" variant="elevated" v-if="item.aktif">
            Aktif
          </VChip>
          <VChip size="x-small" color="error" variant="elevated" v-else>
            Non Aktif
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <IconBtn @click="aksi('mapping', item)">
            <VTooltip activator="parent" location="top">
              Mapping Rombel
            </VTooltip>
            <VIcon icon="tabler-copy" />
          </IconBtn>
          <VBtn icon variant="text" color="medium-emphasis">
            <VIcon icon="tabler-dots-vertical" />
            <VMenu activator="parent">
              <VList>
                <VListItem @click="aksi('edit', item)">
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <VListItemTitle>Ubah Data</VListItemTitle>
                </VListItem>
                <VListItem @click="aksi('hapus', item)">
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <VListItemTitle>Hapus Data</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </template>
        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <DefaultDialog v-model:isDialogVisible="isDialogVisible" :dialog-title="dialogTitle" :errors="errors"
      @confirm="saveData">
      <template #content>
        <VRow>
          <template v-if="getAksi == 'edit'">
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="deskripsi">Deskripsi</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppTextarea v-model="form.deskripsi" placeholder="Deskripsi Tujuan Pembelajaran" auto-grow
                    :error-messages="errors.deskripsi" />
                </VCol>
              </VRow>
            </VCol>
          </template>
          <template v-else>
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
            <template v-if="getAksi == 'add'">
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
                    <label class="v-label text-body-2 text-high-emphasis" for="mata_pelajaran_id">Mata Pelajaran</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppSelect v-model="form.mata_pelajaran_id" placeholder="== Pilih Mata Pelajaran =="
                      :items="dataMapel" clearable clear-icon="tabler-x" @update:model-value="changeFormMapel"
                      item-value="mata_pelajaran_id" item-title="nama_mata_pelajaran" :loading="loadingMapel"
                      :disabled="loadingMapel" :error-messages="errors.mata_pelajaran_id" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" v-if="showCp">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="cp_id">Capaian Pembelajaran (CP)</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppSelect v-model="form.cp_id" placeholder="== Pilih Capaian Pembelajaran (CP) ==" :items="dataCp"
                      clearable clear-icon="tabler-x" @update:model-value="changeCp" item-value="cp_id"
                      item-title="deskripsi" :loading="loadingCp" :disabled="loadingCp"
                      :error-messages="errors.cp_id" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" v-if="showKd">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="kd_id">Kompetensi Dasar (KD)</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppSelect v-model="form.kd_id" placeholder="== Pilih Capaian Pembelajaran (CP) ==" :items="dataKd"
                      clearable clear-icon="tabler-x" @update:model-value="changeKd" item-value="kd_id"
                      item-title="kompetensi_dasar" :loading="loadingKd" :disabled="loadingKd"
                      :error-messages="errors.kd_id" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12" v-if="showUpload">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="template_excel">Template Excel</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <VRow no-gutters>
                      <VCol cols="6">
                        <VFileInput id="template_excel" v-model="form.template_excel"
                          :error-messages="errors.template_excel"
                          accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                          label="Unggah Template Excel" />
                      </VCol>
                      <VCol cols="6">
                        <VBtn color="primary" class="ms-3" block :href="linkTemplateTp" target="_blank">
                          Unduh Template TP
                        </VBtn>
                      </VCol>
                    </VRow>
                  </VCol>
                </VRow>
              </VCol>
            </template>
            <template v-else>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="rombongan_belajar_id">Rombongan
                      Belajar</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppSelect v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                      :items="dataRombel" chips multiple closable-chips item-value="rombongan_belajar_id"
                      item-title="nama" :loading="loadingRombel" :disabled="loadingRombel"
                      :error-messages="errors.rombongan_belajar_id" />
                  </VCol>
                </VRow>
              </VCol>
            </template>
          </template>
        </VRow>
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
