<script setup>
const props = defineProps({
  cardTitle: {
    type: String,
    required: false,
  },
  cardSubTitle: {
    type: Boolean,
    default: false,
  },
  cardUrl: {
    type: String,
    required: true,
  },
})
const cardTitleAlt = ref(props.cardTitle)
onMounted(async () => {
  await fetchData();
});
const loadingBody = ref(true)
const isDialogVisible = ref(false)
const titleDetilNilai = ref('')
const rombel = ref()
const status_penilaian = ref(false)
const rombel_pilihan = ref()
const pembelajaran = ref([])
const pembelajaran_pilihan = ref([])
const textDialog = ref('')
const isConfirmDialogVisible = ref(false)
const loadingBtn = ref([])
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const fetchData = async () => {
  try {
    const response = await $api(props.cardUrl, {
      method: 'POST',
      body: {
        guru_id: $user.guru_id,
        periode_aktif: $semester.nama,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
      },
      onResponse({ request, response, options }) {
        let getData = response._data
        rombel.value = getData.rombel
        cardTitleAlt.value = cardTitleAlt.value ?? `Anda adalah Wali Kelas Rombongan Belajar ${rombel.value.nama}`
        status_penilaian.value = rombel.value?.kunci_nilai ? false : true
        rombel_pilihan.value = getData.rombel_pilihan
        pembelajaran.value = getData.pembelajaran
        pembelajaran_pilihan.value = getData.pembelajaran_pilihan
      },
    })
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const induk = ref()
const sub_mapel = ref()
const data_siswa = ref([])
const merdeka = ref(false)
const is_ppa = ref(false)
const itemData = ref({})
const detilPenilaian = async (params) => {
  loadingBtn.value[params.pembelajaran_id] = true
  await $api('/dashboard/detil-penilaian', {
    method: 'POST',
    body: {
      pembelajaran_id: params.pembelajaran_id,
      rombongan_belajar_id: params.rombongan_belajar_id,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      loadingBtn.value[params.pembelajaran_id] = false
      isDialogVisible.value = true
      itemData.value = getData.pembelajaran
      titleDetilNilai.value = `Detil Penilaian Mata Pelajaran ${getData.pembelajaran.nama_mata_pelajaran}`
      induk.value = getData.pembelajaran.induk
      if (getData.pembelajaran.mata_pelajaran_id == 800001000) {
        sub_mapel.value = 1
      } else {
        sub_mapel.value = getData.pembelajaran.tema_count
      }
      data_siswa.value = getData.data_siswa
      merdeka.value = getData.merdeka
      is_ppa.value = getData.is_ppa
    },
  })
}
const pembelajaranId = ref()
const rombonganBelajarId = ref()
const detil = async (item) => {
  pembelajaranId.value = item.pembelajaran_id
  rombonganBelajarId.value = item.rombongan_belajar_id
  detilPenilaian({
    pembelajaran_id: item.pembelajaran_id,
    rombongan_belajar_id: item.rombongan_belajar_id,
  })
}
const changeStatus = (val) => {
  if (val) {
    textDialog.value = 'Penilaian akan di aktifkan'
  } else {
    textDialog.value = 'Penilaian akan di nonaktifkan'
  }
  isConfirmDialogVisible.value = true
}
const confirmDialog = async (val) => {
  if (!val) {
    status_penilaian.value = !status_penilaian.value
  }
  await $api('/dashboard/status-penilaian', {
    method: 'POST',
    body: {
      status: status_penilaian.value,
      rombongan_belajar_id: rombel.value.rombongan_belajar_id,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      notif.value = {
        color: getData.color,
        title: getData.title,
        text: getData.text,
      }
    },
  })
}
const confirmClose = () => {
  fetchData()
}
const refreshNilai = () => {
  console.log('refreshNilai');
  detilPenilaian({
    pembelajaran_id: pembelajaranId.value,
    rombongan_belajar_id: rombonganBelajarId.value,
  })
}
</script>
<template>
  <div>
    <VRow>
      <VCol cols="12">
        <VCard class="text-center mb-10" v-if="loadingBody">
          <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
        </VCard>
        <VCard :title="cardTitleAlt" v-else>
          <template #append v-if="cardSubTitle">
            <div class="d-flex align-center text-disabled text-subtitle-2">Status Penilaian di Rombongan Belajar ini :
              &nbsp;&nbsp;&nbsp;
              <VSwitch v-model="status_penilaian" :label="`${status_penilaian ? 'Aktif' : 'Non Aktif'}`"
                @update:model-value="changeStatus" />
            </div>
          </template>
          <VDivider />
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th class="text-center">Mata Pelajaran</th>
                <template v-if="rombel">
                  <th class="text-center">Guru Mata Pelajaran</th>
                </template>
                <template v-else>
                  <th class="text-center">Rombel</th>
                  <th class="text-center">Wali Kelas</th>
                </template>
                <th class="text-center">Jml PD</th>
                <th class="text-center">Jml PD Dinilai</th>
                <th class="text-center">Detil</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="pembelajaran.length">
                <tr v-for="(item, index) in pembelajaran">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>{{ item.nama_mata_pelajaran }}</td>
                  <template v-if="rombel">
                    <td>{{ item.guru }}</td>
                  </template>
                  <template v-else>
                    <td class="text-center">{{ item.rombel }}</td>
                    <td>{{ item.wali_kelas }}</td>
                  </template>
                  <template v-if="item.mata_pelajaran_id === 800001000">
                    <td class="text-center">{{ item.pd_pkl_count }}</td>
                    <td class="text-center">{{ item.pd_pkl_dinilai }}</td>
                  </template>
                  <template v-else>
                    <td class="text-center">{{ item.pd }}</td>
                    <td class="text-center">{{ item.pd_dinilai }}</td>
                  </template>
                  <td class="text-center">
                    <VBtn size="x-small" color="success" @click="detil(item)"
                      :loading="loadingBtn[item.pembelajaran_id]" :disabled="loadingBtn[item.pembelajaran_id]">
                      Detil
                    </VBtn>
                  </td>
                </tr>
              </template>
              <template v-else>
                <tr>
                  <td colspan="7" class="text-center">Tidak ada data untuk ditampilkan</td>
                </tr>
              </template>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
      <DetilNilaiDialog v-model:isDialogVisible="isDialogVisible" :item-data="itemData"
        :title-detil-nilai="titleDetilNilai" :merdeka="merdeka" :is-ppa="is_ppa" :data-siswa="data_siswa"
        :sub-mapel="sub_mapel" @refresh="refreshNilai">
      </DetilNilaiDialog>
      <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" confirmation-question="Apakah Anda yakin?"
        :confirmation-text="textDialog" :confirm-color="notif.color" :confirm-title="notif.title"
        :confirm-msg="notif.text" @confirm="confirmDialog" @close="confirmClose" />
    </VRow>
  </div>
</template>
<style lang="scss">
.scrollable-dialog {
  overflow: visible !important;
}
</style>
