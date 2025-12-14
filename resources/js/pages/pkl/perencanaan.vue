<script setup>
import { Indonesian } from "flatpickr/dist/l10n/id.js";
definePage({
  meta: {
    action: 'read',
    subject: 'Pkl',
    title: 'Perencanaan Penilaian PKL',
  },
})
const options = ref({
  itemsPerPage: 10,
  searchQuery: null,
  page: 1,
  sortby: 'updated_at',
  sortbydesc: 'DESC',
  rombongan_belajar_id: null,
})
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const items = ref([])
const total = ref(0)
const loading = ref({
  table: false,
  rombel: false,
  dudi: false,
  akt_pd: false,
})
const headers = [
  {
    key: 'rombongan_belajar',
    title: 'Kelas',
    sortable: false,
  },
  {
    key: 'dudi',
    title: 'DUDI',
    sortable: false,
    align: 'center'
  },
  {
    key: 'pks',
    title: 'PKS',
    sortable: false,
    align: 'center'
  },
  {
    key: 'tanggal_mulai_str',
    title: 'Tanggal Mulai',
    sortable: true,
    align: 'center',
  },
  {
    key: 'tanggal_selesai_str',
    title: 'Tanggal Selesai',
    sortable: false,
    align: 'center',
  },
  {
    key: 'pd_pkl_count',
    title: 'JML Peserta',
    sortable: false,
    align: 'center',
  },
  {
    key: 'actions',
    title: 'Aksi',
    sortable: false,
    align: 'center',
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
const defaultForm = ref({
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  semester_id: $semester.semester_id,
  periode_aktif: $semester.nama,
})
const fetchData = async () => {
  loading.value.table = true
  try {
    const response = await useApi(createUrl('/praktik-kerja-lapangan', {
      query: {
        user_id: defaultForm.value.user_id,
        guru_id: defaultForm.value.guru_id,
        sekolah_id: defaultForm.value.sekolah_id,
        semester_id: defaultForm.value.semester_id,
        periode_aktif: defaultForm.value.nama,
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
    loading.value.table = false
  }
}
const form = ref({
  aksi: null,
  pkl_id: null,
  tingkat: null,
  rombongan_belajar_id: null,
  dudi_id: null,
  akt_pd_id: null,
  tanggal_mulai: null,
  tanggal_selesai: null,
  instruktur: null,
  nip: null,
  tp_id: [],
})
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  dudi_id: null,
  akt_pd_id: null,
  tanggal_mulai: null,
  tanggal_selesai: null,
  instruktur: null,
})
const arrayData = ref({
  rombel: [],
  dudi: [],
  akt_pd: [],
  tp: [],
})
const showTp = ref(false)
const isDialogVisible = ref(false)
const isNotifVisible = ref(false)
const isConfirmDialogVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const dialogTitle = ref()
const pklID = ref()
const addNewData = () => {
  form.value.aksi = 'add'
  isDialogVisible.value = true
  dialogTitle.value = 'Tambah Perencanaan Penilaian PKL'
}
const aksi = async (aksi, item) => {
  form.value.aksi = aksi
  form.value.pkl_id = item.pkl_id
  pklID.value = item.pkl_id
  if (aksi == 'hapus') {
    isConfirmDialogVisible.value = true
  } else {
    await getData({
      data: "detil",
    }).then(() => {
      isDialogVisible.value = true
    })
  }
  if (aksi == 'edit') {
    dialogTitle.value = 'Edit Perencanaan Penilaian PKL'
  }
  if (aksi == 'detil') {
    dialogTitle.value = 'Detil Perencanaan Penilaian PKL'
  }
}
const formReset = () => {
  form.value = {
    aksi: null,
    pkl_id: null,
    tingkat: null,
    rombongan_belajar_id: null,
    dudi_id: null,
    akt_pd_id: null,
    tanggal_mulai: null,
    tanggal_selesai: null,
    instruktur: null,
    nip: null,
    tp_id: [],
  }
  errors.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    dudi_id: null,
    akt_pd_id: null,
    tanggal_mulai: null,
    tanggal_selesai: null,
    instruktur: null,
  }
  arrayData.value = {
    rombel: [],
    dudi: [],
    akt_pd: [],
    tp: [],
  }
  showTp.value = false
}
const saveData = async (val) => {
  if (val) {
    const mergedForm = { ...defaultForm.value, ...form.value }
    await $api('/praktik-kerja-lapangan/save', {
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
  } else {
    formReset()
  }
}
const getData = async (postData) => {
  const mergedForm = { ...postData, ...defaultForm.value, ...form.value }
  await $api("/praktik-kerja-lapangan/get-data", {
    method: "POST",
    body: mergedForm,
    async onResponse({ response }) {
      let getData = response._data
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData
      }
      if (postData.data == "dudi") {
        arrayData.value.dudi = getData
      }
      if (postData.data == "akt-pd") {
        arrayData.value.akt_pd = getData
      }
      if (postData.data == "tp") {
        arrayData.value.tp = getData
        if (getData.length) {
          showTp.value = true
        }
      }
      if (postData.data == "detil") {
        form.value.tingkat = getData.rombongan_belajar.tingkat
        form.value.tanggal_mulai = getData.tanggal_mulai
        form.value.tanggal_selesai = getData.tanggal_selesai
        form.value.instruktur = getData.instruktur
        form.value.nip = getData.nip
        getData.tp_pkl.forEach(tp => {
          form.value.tp_id.push(tp.tp_id)
        });
        showTp.value = true
        await changeTingkat(getData.rombongan_belajar.tingkat)
        form.value.rombongan_belajar_id = getData.rombongan_belajar_id
        await changeRombel(getData.rombongan_belajar_id)
        form.value.dudi_id = getData.dudi.dudi_id
        await changeDudi(getData.dudi.dudi_id)
        form.value.akt_pd_id = getData.akt_pd_id
        await changeAktPd(getData.akt_pd_id)
      }
    },
  })
}
const changeTingkat = async (val) => {
  form.value.rombongan_belajar_id = null
  form.value.dudi_id = null
  form.value.akt_pd_id = null
  arrayData.value.rombel = []
  arrayData.value.dudi = []
  arrayData.value.akt_pd = []
  if (val) {
    loading.value.rombel = true
    await getData({
      data: "rombel",
      tingkat: val,
    }).then(() => {
      loading.value.rombel = false
    })
  }
}
const changeRombel = async (val) => {
  form.value.dudi_id = null
  form.value.akt_pd_id = null
  arrayData.value.dudi = []
  arrayData.value.akt_pd = []
  if (val) {
    loading.value.dudi = true
    await getData({
      data: "dudi",
      tingkat: val,
    }).then(() => {
      loading.value.dudi = false
    })
  }
}
const changeDudi = async (val) => {
  form.value.akt_pd_id = null
  arrayData.value.tp = []
  if (val) {
    loading.value.akt_pd = true
    await getData({
      data: "akt-pd",
      tingkat: val,
    }).then(() => {
      loading.value.akt_pd = false
    })
  }
}
const changeAktPd = async (val) => {
  if (val) {
    loading.value.body = true
    await getData({
      data: "tp",
      tingkat: val,
    }).then(() => {
      loading.value.body = false
    })
  }
}
const dateConfig = ref({
  locale: Indonesian,
  altFormat: "j F Y",
  altInput: true,
});
const confirmDelete = async (val) => {
  if (val) {
    await $api('/praktik-kerja-lapangan/save', {
      method: 'POST',
      body: {
        aksi: 'hapus',
        pkl_id: pklID.value,
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
  await fetchData().then(() => {
    formReset()
  });
}
const getPembimbing = (pembimbing) => {
  return pembimbing.map(p => p.nama_lengkap).join('<br>')
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Rencana Penilaian PKL</VCardTitle>
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
      :loading="loading.table" loading-text="Loading..." @update:sortBy="updateSortBy">
      <template #item.rombongan_belajar="{ item }">
        {{ item.rombongan_belajar.nama }}
      </template>
      <template #item.dudi="{ item }">
        {{ item.akt_pd.dudi.nama }}
      </template>
      <template #item.pks="{ item }">
        {{ item.akt_pd.judul_akt_pd }}
      </template>
      <template #item.actions="{ item }">
        <IconBtn @click="aksi('detil', item)">
          <VTooltip activator="parent" location="top">
            Detil Data
          </VTooltip>
          <VIcon icon="tabler-search" />
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
      <template #bottom>
        <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
      </template>
    </VDataTableServer>
  </VCard>
  <DefaultDialog v-model:isDialogVisible="isDialogVisible" :dialog-title="dialogTitle" :errors="errors"
    :isSubmitBtn="form.aksi == 'add' || form.aksi == 'edit'" @confirm="saveData">
    <template #content>
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
              <AppAutocomplete v-model="form.tingkat" placeholder="== Pilih Tingkat kelas ==" :items="tingkatKelas"
                clearable clear-icon="tabler-x" @update:model-value="changeTingkat" :error-messages="errors.tingkat"
                :disabled="form.aksi == 'detil'" />
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
              <AppAutocomplete v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                :items="arrayData.rombel" clearable clear-icon="tabler-x" @update:model-value="changeRombel"
                item-value="rombongan_belajar_id" item-title="nama" :loading="loading.rombel"
                :disabled="loading.rombel || form.aksi == 'detil'" :error-messages="errors.rombongan_belajar_id" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="dudi_id">DUDI</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppAutocomplete v-model="form.dudi_id" placeholder="== Pilih DUDI == " :items="arrayData.dudi" clearable
                clear-icon="tabler-x" @update:model-value="changeDudi" item-value="dudi_id" item-title="nama"
                :loading="loading.dudi" :disabled="loading.dudi || form.aksi == 'detil'"
                :error-messages="errors.dudi_id" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="akt_pd_id">Perjanjian Kerja Sama
                (PKS)</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppAutocomplete v-model="form.akt_pd_id" placeholder="== Pilih Perjanjian Kerja Sama (PKS) == "
                :items="arrayData.akt_pd" clearable clear-icon="tabler-x" @update:model-value="changeAktPd"
                item-value="akt_pd_id" item-title="judul_akt_pd" :loading="loading.akt_pd"
                :disabled="loading.akt_pd || form.aksi == 'detil'" :error-messages="errors.akt_pd_id">
                <template #item="{ props: listItemProp, item }">
                  <VListItem v-bind="listItemProp">
                    <template #subtitle="{ subtitle }">
                      Pembimbing:<br>
                    </template>
                    <span v-html="getPembimbing(item?.raw?.pembimbing)"></span>
                  </VListItem>
                </template>
              </AppAutocomplete>
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="tanggal_mulai">Tanggal Mulai</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppDateTimePicker v-model="form.tanggal_mulai" placeholder="== Pilih Tanggal Mulai =="
                :config="dateConfig" :error-messages="errors.tanggal_mulai" :disabled="form.aksi == 'detil'" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="tanggal_mulai">Tanggal Selesai</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppDateTimePicker v-model="form.tanggal_selesai" placeholder="== Pilih Tanggal Selesai =="
                :config="dateConfig" :error-messages="errors.tanggal_selesai" :disabled="form.aksi == 'detil'" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="instruktur">Instruktur</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppTextField v-model="form.instruktur" placeholder="Nama Instruktur" :error-messages="errors.instruktur"
                :disabled="form.aksi == 'detil'" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="nip">NIP</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppTextField v-model="form.nip" placeholder="NIP Instruktur (Jika ada)"
                :disabled="form.aksi == 'detil'" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12" v-if="showTp">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="tp">Tujuan Pembelajaran</label>
            </VCol>
            <VCol cols="12" md="9">
              <template v-if="arrayData.tp">
                <VCheckbox v-for="tp in arrayData.tp" :key="tp.tp_id" v-model="form.tp_id" :label="tp.deskripsi"
                  :value="tp.tp_id" :disabled="form.aksi == 'detil'" />
              </template>
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </template>
  </DefaultDialog>
  <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
    confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
    :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDelete"
    @close="confirmClose" />
</template>
