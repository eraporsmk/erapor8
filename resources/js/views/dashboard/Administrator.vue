<script setup>
onMounted(async () => {
  await fetchData();
});
const status_penilaian = ref(false)
const rekap = ref([])
const sekolah = ref()
const app = ref()
const helpdesk = ref([])
const text_wa = ref()
const loadingBody = ref(true)
const textDialog = ref('')
const isConfirmDialogVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/dashboard', {
      query: {
        periode_aktif: $semester.nama,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
      },
    }))
    let getData = response.data
    status_penilaian.value = getData.value.app.status_penilaian
    rekap.value = getData.value.rekap
    sekolah.value = getData.value.sekolah
    app.value = getData.value.app
    helpdesk.value = getData.value.helpdesk
    text_wa.value = getData.value.text_wa
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
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
      sekolah_id: $user.sekolah_id,
      semester_id: $semester.semester_id,
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
</script>
<template>
  <VRow>
    <VCol cols="12">
      <VRow class="match-height">
        <VCol v-for="index in 6" :key="index" cols="6" sm="2" v-if="loadingBody">
          <div>
            <VCard class="text-center">
              <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
            </VCard>
          </div>
        </VCol>
        <template v-else>
          <VCol v-for="(data, index) in rekap" :key="index" cols="6" sm="2">
            <div>
              <VCard class="logistics-card-statistics cursor-pointer"
                :style="data.isHover ? `border-block-end-color: rgb(var(--v-theme-${data.color}))` : `border-block-end-color: rgba(var(--v-theme-${data.variant}),0.38)`"
                @mouseenter="data.isHover = true" @mouseleave="data.isHover = false">
                <VCardText>
                  <div class="d-flex align-center gap-x-4 mb-1">
                    <VAvatar variant="tonal" :color="data.variant" rounded>
                      <font-awesome-icon :icon="['fas', data.icon]" size="lg" />
                    </VAvatar>
                    <h4 class="text-h4">
                      {{ data.jml }}
                    </h4>
                  </div>
                  <div class="text-body-1 mb-1">
                    {{ data.data }}
                  </div>
                </VCardText>
              </VCard>
            </div>
          </VCol>
        </template>
      </VRow>
    </VCol>
    <VCol cols="12">
      <VRow>
        <VCol cols="7" md="7" sm="12">
          <VCard title="Data Sekolah" v-if="loadingBody">
            <VCardText class="text-center">
              <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
            </VCardText>
          </VCard>
          <VCard title="Data Sekolah" class="mb-10" v-else>
            <VDivider />
            <VTable class="text-no-wrap">
              <tbody>
                <tr>
                  <td>Nama Sekolah</td>
                  <td>{{ sekolah.nama }}</td>
                </tr>
                <tr>
                  <td>NPSN</td>
                  <td>{{ sekolah.npsn }}</td>
                </tr>
                <tr>
                  <td>Alamat</td>
                  <td>{{ sekolah.alamat }}</td>
                </tr>
                <tr>
                  <td>Kodepos</td>
                  <td>{{ sekolah.kode_pos }}</td>
                </tr>
                <tr>
                  <td>Desa/Kelurahan</td>
                  <td>{{ sekolah.desa_kelurahan }}</td>
                </tr>
                <tr>
                  <td>Kecamatan</td>
                  <td>{{ sekolah.kecamatan }}</td>
                </tr>
                <tr>
                  <td>Kabupaten/Kota</td>
                  <td>{{ sekolah.kabupaten }}</td>
                </tr>
                <tr>
                  <td>Provinsi</td>
                  <td>{{ sekolah.provinsi }}</td>
                </tr>
                <tr>
                  <td>Email</td>
                  <td>{{ sekolah.email }}</td>
                </tr>
                <tr>
                  <td>Website</td>
                  <td>{{ sekolah.website }}</td>
                </tr>
                <tr>
                  <td>Kepala Sekolah</td>
                  <td>{{ sekolah.kepala_sekolah?.nama_lengkap }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
          <p>Aplikasi e-Rapor SMK ini dibuat dan dikembangkan oleh Direktorat Sekolah Menengah Kejuruan<br>
            Kementerian Pendidikan Dasar dan Menengah Republik Indonesia</p>
        </VCol>
        <VCol cols="5" md="5" sm="12">
          <VCard title="Informasi Aplikasi" v-if="loadingBody">
            <VCardText class="text-center">
              <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
            </VCardText>
          </VCard>
          <VCard title="Informasi Aplikasi" v-else>
            <VDivider class="mt-2" />
            <VTable density="compact" class="text-no-wrap">
              <tbody>
                <tr>
                  <td>Nama Aplikasi</td>
                  <td>{{ app.app_name }}</td>
                </tr>
                <tr>
                  <td>Versi Aplikasi</td>
                  <td>{{ app.app_version }}</td>
                </tr>
                <tr>
                  <td>Versi Database</td>
                  <td>{{ app.db_version }}</td>
                </tr>
                <tr>
                  <td>Status Penilaian</td>
                  <td>
                    <VSwitch v-model="status_penilaian" :label="`${status_penilaian ? 'Aktif' : 'Non Aktif'}`"
                      @update:model-value="changeStatus" />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
          <VCard class="mt-4" title="Helpdesk e-Rapor SMK" v-if="loadingBody">
            <VCardText class="text-center">
              <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
            </VCardText>
          </VCard>
          <VCard title="Helpdesk e-Rapor SMK" class="mt-4" v-else>
            <VDivider class="mt-2" />
            <VTable density="compact" class="text-no-wrap">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Instansi</th>
                  <th><font-awesome-icon :icon="['fab', 'whatsapp']" /></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="hd in helpdesk" :key="hd.hp">
                  <td>{{ hd.nama }}</td>
                  <td>{{ hd.instansi }}</td>
                  <td>
                    <a target="_blank" :href="`https://wa.me/${hd.hp}/?text=${text_wa}`"><font-awesome-icon
                        :icon="['fab', 'whatsapp']" /></a>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </VCol>
      </VRow>
    </VCol>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" confirmation-question="Apakah Anda yakin?"
      :confirmation-text="textDialog" :confirm-color="notif.color" :confirm-title="notif.title"
      :confirm-msg="notif.text" @confirm="confirmDialog" @close="confirmClose" />
  </VRow>
</template>
<style lang="scss" scoped>
@use "@core-scss/base/mixins" as mixins;

.logistics-card-statistics {
  border-block-end-style: solid;
  border-block-end-width: 2px;

  &:hover {
    border-block-end-width: 3px;
    margin-block-end: -1px;

    @include mixins.elevation(8);

    transition: all 0.1s ease-out;
  }
}

.skin--bordered {
  .logistics-card-statistics {
    border-block-end-width: 2px;

    &:hover {
      border-block-end-width: 3px;
      margin-block-end: -2px;
      transition: all 0.1s ease-out;
    }
  }
}

.v-expansion-panel-text__wrapper {
  padding: 0 20px 20px 124px;
}
</style>
