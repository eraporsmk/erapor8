<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Tarik Dapodik',
  },
})
const loadingBody = ref(true)
const jam_sinkron = ref(false)
const data_sinkron = ref([])
const error = ref()
const isAlertDialogVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/sinkronisasi', {
      query: {
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        user_id: $user.user_id,
      },
    }))
    let getData = response.data.value
    jam_sinkron.value = getData.jam_sinkron
    data_sinkron.value = getData.data_sinkron
    error.value = getData.error
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const kurang = (item) => {
  if (item.dapodik > item.erapor)
    return true
  return false
}
const loading = ref(false)
const show = ref(false)
const syncText = ref()
const myTimer = async () => {
  await $api(`/sinkronisasi/hitung/${$user.sekolah_id}`, {
    method: 'GET',
    onResponse({ request, response, options }) {
      let getData = response._data
      if (getData.output) {
        if (getData.output.jumlah) {
          if (getData.output.jumlah === 1 && getData.output.inserted === 1) {
            syncText.value = 'Proses sinkronisasi selesai'
          } else {
            syncText.value = `${getData.output.table} (${getData.output.inserted}/${getData.output.jumlah})`
          }
        } else {
          syncText.value = getData.output.table
        }
      }
    }
  })
}
const syncSatuan = async (server, aksi) => {
  console.log(server);
  console.log(aksi);
  if (server && aksi) {
    show.value = true
    syncText.value = 'Menyiapkan proses sinkronisasi'
    loading.value = true
    var myInterval;
    myInterval = setInterval(myTimer, 500);
    await $api('/sinkronisasi/dapodik', {
      method: 'POST',
      body: {
        email: $user.email,
        satuan: aksi,
        tujuan: server,
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        user_id: $user.user_id,
      },
      onResponse({ request, response, options }) {
        let getData = response._data
        loading.value = false
        show.value = false
        clearInterval(myInterval);
        syncText.value = 'Proses sinkronisasi selesai'
        isAlertDialogVisible.value = true
        notif.value = {
          color: 'success',
          title: 'Berhasil',
          text: 'Sinkronisasi Dapodik Berhasil',
        }
      }
    })
  }
}
onMounted(async () => {
  await fetchData();
});
const confirmAlert = () => {
  fetchData()
}
</script>
<template>
  <div>
    <VCard class="text-center mb-10" v-if="loadingBody">
      <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
    </VCard>
    <VCard v-else>
      <VCardText v-if="show">
        <VAlert color="secondary" class="text-center">
          {{ syncText }}
        </VAlert>
      </VCardText>
      <VCardText>
        <template v-if="jam_sinkron">
          <VAlert color="error" class="text-center" variant="tonal">
            <h2 class="mt-4 mb-4">Penyesuaian Waktu Layanan Sinkronisasi Dapodik</h2>
            <p>Dikarenakan adanya proses rutin sinkronisasi data Dapodik antara Server PUSDATIN dan Server Direktorat
              SMK, <br>maka layanan sinkronisasi hanya dapat diakses antara pukul <span class="text-danger"><b>03.00 s/d
                  24.00 (WIB)</b></span></p>
          </VAlert>
        </template>
        <template v-else-if="error">
          <VAlert title="Pengambilan Dapodik Gagal" type="error" variant="tonal">
            Status Server: {{ error.message }}
          </VAlert>
        </template>
        <template v-else>
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center">Data</th>
                <th class="text-center">Jml Dapodik</th>
                <th class="text-center">Jml e-Rapor</th>
                <th class="text-center">Jml Sinkron</th>
                <th class="text-center">Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data_sinkron" :key="item.nama">
                <td>{{ item.nama }}</td>
                <td class="text-center">{{ item.dapodik }}</td>
                <td class="text-center">{{ item.erapor }}</td>
                <td class="text-center">{{ item.sinkron }}</td>
                <td class="text-center">
                  <template v-if="item.sinkron">
                    <VChip color="warning" v-if="kurang(item)">Kurang</VChip>
                    <VChip color="success" v-else>Lengkap</VChip>
                  </template>
                  <template v-else>
                    <VChip color="error">Belum</VChip>
                  </template>
                </td>
                <td class="text-center">
                  <template v-if="item.dapodik">
                    <VBtn :loading="loading" :disabled="loading" size="small" color="success"
                      @click="syncSatuan(item.server, item.aksi)">
                      Sinkronisasi
                    </VBtn>
                  </template>
                  <template v-else>
                    -
                  </template>
                </td>
              </tr>
            </tbody>
          </VTable>
        </template>
      </VCardText>
    </VCard>
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmAlert"></AlertDialog>
  </div>
</template>
