<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Projek',
    title: "Rencana Penilaian P5",
  },
})
const options = ref({
  itemsPerPage: 10,
  searchQuery: null,
  page: 1,
  sortby: 'updated_at',
  sortbydesc: 'DESC',
})
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const items = ref([])
const total = ref(0)
const loadingTable = ref(false)
const headers = [
  {
    key: 'rombongan_belajar',
    title: 'Kelas',
    sortable: false,
  },
  {
    key: 'pembelajaran',
    title: 'Tema',
    sortable: false,
  },
  {
    key: 'nama',
    title: 'Nama Projek',
    sortable: true,
  },
  {
    key: 'deskripsi',
    title: 'Deskripsi',
    sortable: true,
  },
  {
    key: 'no_urut',
    title: 'No. Urut',
    sortable: false,
    align: 'center'
  },
  {
    key: 'aspek_budaya_kerja_count',
    title: 'Jml Sub Elemen',
    sortable: false,
    align: 'center'
  },
  {
    key: 'actions',
    title: 'Aksi',
    sortable: false,
    align: 'center'
  },
]
onMounted(async () => {
  await fetchData()
})
watch(options, async () => {
  await fetchData()
}, { deep: true })
watch(
  () => options.value.searchQuery,
  () => {
    options.value.page = 1
  }
)
const fetchData = async () => {
  loadingTable.value = true
  try {
    const response = await useApi(createUrl('/projek', {
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
    }))
    let getData = response.data
    items.value = getData.value.data.data
    total.value = getData.value.data.total
  } catch (error) {
    console.error(error)
  } finally {
    loadingTable.value = false
  }
}
const isDialogVisible = ref(false)
const dialogTitle = ref()
const isConfirmDialogVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const isNotifVisible = ref(false)
const errors = ref({
  nama_projek: undefined,
  deskripsi: undefined,
  no_urut: undefined,
})
const defaultForm = ref({
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  merdeka: false,
})
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
})
const pageForm = ref({
  rencana_budaya_kerja_id: null,
  nama_projek: null,
  deskripsi: null,
  no_urut: null,
  sub_elemen: {},
})
const arrayData = ref({
  rombel: [],
  mapel: [],
  tema: [],
})
const loading = ref({
  rombel: false,
  mapel: false,
  body: false,
})
const isKunciWalas = ref(false)
const resetForm = ref(false)
const isSubmitBtn = ref(false)
const getAksi = ref()
const addNewData = () => {
  getAksi.value = 'add'
  isDialogVisible.value = true
  dialogTitle.value = 'Tambah Perencanaan Penilaian P5'
}
const getData = async (postData) => {
  const mergedForm = { ...postData, ...defaultForm.value }
  await $api("/referensi/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData
      }
      if (postData.data == "mapel") {
        arrayData.value.mapel = getData.mapel
        defaultForm.value.merdeka = getData.merdeka
        isKunciWalas.value = getData.rombel.kunci_nilai ? true : false
      }
      if (postData.data == "budaya-kerja") {
        isSubmitBtn.value = true
        arrayData.value.tema = getData
      }
    },
  })
}
const changeTingkat = async (val) => {
  arrayData.value.rombel = []
  arrayData.value.mapel = []
  form.value.rombongan_belajar_id = null
  form.value.pembelajaran_id = null
  if (val) {
    loading.value.rombel = true
    await getData({
      data: "rombel",
      aksi: 'rencana-p5',
      tingkat: val,
    }).then(() => {
      loading.value.rombel = false
    })
  }
}
const changeRombel = async (val) => {
  arrayData.value.mapel = []
  form.value.pembelajaran_id = null
  if (val) {
    loading.value.mapel = true
    await getData({
      data: "mapel",
      aksi: 'tema',
      rombongan_belajar_id: val,
    }).then(() => {
      loading.value.mapel = false
    })
  }
}
const changeMapel = async (val) => {
  if (val) {
    loading.value.body = true
    await getData({
      data: "budaya-kerja",
    }).then(() => {
      loading.value.body = false
    })
  }
}
const confirmDelete = async (val) => {
  if (val) {
    await $api('/projek/hapus', {
      method: 'POST',
      body: {
        data: 'rencana',
        rencana_budaya_kerja_id: rencanaBudayaKerjaId.value,
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
    mata_pelajaran_id: null,
  }
  errors.value = {
    nama_projek: undefined,
    deskripsi: undefined,
    no_urut: undefined,
  }
  pageForm.value = {
    nama_projek: null,
    deskripsi: null,
    no_urut: null,
    sub_elemen: {},
  }
  arrayData.value = {
    rombel: [],
    mapel: [],
    tema: [],
  }
}
const aksiForm = ref({
  data: 'rencana'
})
const saveData = async (val) => {
  if (val) {
    const mergedForm = { ...aksiForm.value, ...defaultForm.value, ...form.value, ...pageForm.value };
    await $api('/projek/save', {
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
const warnaDimensi = (id) => {
  var data = {
    1: 'bg-primary lighten-5',
    2: 'bg-success lighten-5',
    3: 'bg-error lighten-5',
    4: 'bg-warning lighten-5',
    5: 'bg-info lighten-5',
    6: 'bg-secondary'
  }
  return data[id];
}
const rencanaBudayaKerjaId = ref()
const aksi = async (aksi, item) => {
  rencanaBudayaKerjaId.value = item.rencana_budaya_kerja_id
  getAksi.value = aksi
  if (aksi == 'edit') {
    aksiForm.value.data = 'update-rencana'
    dialogTitle.value = 'Edit Perencanaan Penilaian P5'
    await $api('/projek/show', {
      method: 'POST',
      body: {
        rencana_budaya_kerja_id: rencanaBudayaKerjaId.value,
      },
      onResponse({ response }) {
        let getData = response._data
        console.log(getData);
        isDialogVisible.value = true
        pageForm.value.rencana_budaya_kerja_id = getData.rencana_budaya_kerja_id
        pageForm.value.nama_projek = getData.nama
        pageForm.value.deskripsi = getData.deskripsi
        pageForm.value.no_urut = getData.no_urut
        isSubmitBtn.value = true
      },
    })
  } else {
    isConfirmDialogVisible.value = true
  }
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Rencana Penilaian P5</VCardTitle>
      <template #append>
        <VBtn prepend-icon="tabler-plus" @click="addNewData">
          Tambah Data
        </VBtn>
      </template>
    </VCardItem>
    <VDivider />
    <VCardText>
      <VRow>
        <VCol cols="12" md="4">
          <AppSelect v-model="options.itemsPerPage" :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
          ]" />
        </VCol>
        <VCol cols="12" md="4" offset-md="4">
          <AppTextField v-model="options.searchQuery" placeholder="Cari Data" />
        </VCol>
      </VRow>
    </VCardText>
    <VDivider />
    <VDataTableServer :items="items" :server-items-length="total" :headers="headers" :options="options"
      :loading="loadingTable" loading-text="Loading..." @update:sortBy="updateSortBy">
      <template #item.rombongan_belajar="{ item }">
        {{ item.pembelajaran.rombongan_belajar.nama }}
      </template>
      <template #item.pembelajaran="{ item }">
        {{ item.pembelajaran.nama_mata_pelajaran }}
      </template>
      <template #item.actions="{ item }">
        <IconBtn @click="aksi('edit', item)">
          <VTooltip activator="parent" location="top">
            Detil Data
          </VTooltip>
          <VIcon icon="tabler-pencil" />
        </IconBtn>
        <IconBtn @click="aksi('hapus', item)">
          <VTooltip activator="parent" location="top">
            Hapus Data
          </VTooltip>
          <VIcon icon="tabler-trash" color="error" />
        </IconBtn>
      </template>
      <template #bottom>
        <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
      </template>
    </VDataTableServer>
    <DefaultDialog v-model:isDialogVisible="isDialogVisible" :dialog-title="dialogTitle" :errors="errors"
      :isSubmitBtn="isSubmitBtn" @confirm="saveData">
      <template #content>
        <VRow>
          <DefaultForm v-model:form="form" v-model:errors="errors" v-model:arrayData="arrayData"
            v-model:loading="loading" v-model:resetForm="resetForm" v-model:isKunci="isKunciWalas"
            @tingkat="changeTingkat" @rombongan_belajar_id="changeRombel" @pembelajaran_id="changeMapel"
            v-if="getAksi == 'add'">
          </DefaultForm>
          <VCol cols="12">
            <VRow no-gutters>
              <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="nama_projek">Nama Projek</label>
              </VCol>
              <VCol cols="12" md="9">
                <AppTextField id="nama_projek" v-model="pageForm.nama_projek" :error-messages="errors.nama_projek" />
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="12">
            <VRow no-gutters>
              <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="deskripsi">Deskripsi Projek</label>
              </VCol>
              <VCol cols="12" md="9">
                <AppTextarea id="deskripsi" v-model="pageForm.deskripsi" :error-messages="errors.deskripsi" />
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="12">
            <VRow no-gutters>
              <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="no_urut">Nomor Urut</label>
              </VCol>
              <VCol cols="12" md="9">
                <AppTextField id="no_urut" v-model="pageForm.no_urut" :error-messages="errors.no_urut" />
              </VCol>
            </VRow>
          </VCol>
        </VRow>
        <div class="text-center" v-if="loading.body">
          <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
        </div>
      </template>
      <template #table v-if="arrayData.tema.length">
        <VTable>
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th class="text-center">Dimensi</th>
              <th class="text-center">Elemen</th>
              <th class="text-center">Sub Elemen</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="tema in arrayData.tema">
              <template v-for="elemen in tema.elemen_budaya_kerja">
                <tr :class="warnaDimensi(tema.budaya_kerja_id)">
                  <td style="vertical-align: top;" class="py-2">
                    <VCheckbox v-model="pageForm.sub_elemen[elemen.elemen_id]"
                      :value="`${tema.budaya_kerja_id}#${elemen.elemen_id}`" />
                  </td>
                  <td style="vertical-align: top;" class="py-2">
                    {{ tema.aspek }}
                  </td>
                  <td style="vertical-align: top;" class="py-2">
                    {{ elemen.elemen }}
                  </td>
                  <td style="vertical-align: top;" class="py-2">
                    {{ elemen.deskripsi }}
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </VTable>
      </template>
    </DefaultDialog>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
      :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDelete"
      @close="confirmClose" />
  </VCard>
</template>
