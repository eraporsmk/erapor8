<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Kirim Data e-Rapor',
  },
})
const loadingBody = ref(true)
const isAlertDialogVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
onMounted(async () => {
  await fetchData();
});
const last_sync = ref()
const table_sync = ref([])
const jumlah = ref(0)
const error = ref()
const message = ref('')
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/sinkronisasi/erapor', {
      query: {
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        user_id: $user.user_id,
      },
    }))
    let getData = response.data.value
    last_sync.value = getData.last_sync
    table_sync.value = getData.table_sync
    jumlah.value = getData.jumlah
    error.value = getData.error
    message.value = getData.message
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const isDialogVisible = ref(false)
if (jumlah.value > 1000) {
  isDialogVisible.value = true
}
const loading = ref(false)
const kirimData = async () => {
  loading.value = true
  await $api('/sinkronisasi/kirim-data', {
    method: 'POST',
    body: {
      sekolah_id: $user.sekolah_id,
      semester_id: $semester.semester_id,
      user_id: $user.user_id,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      loading.value = false
      isAlertDialogVisible.value = true
      notif.value = {
        color: getData.color,
        title: getData.title,
        text: getData.text,
      }
    }
  })
}
const confirmAlert = () => {
  fetchData()
}
</script>
<template>
  <div>
    <VRow>
      <VCol cols="6" xl="8" md="8" sm="6">
        <VCard v-if="loadingBody">
          <VCardText class="text-center">
            <VProgressCircular :size="60" indeterminate color="error" />
          </VCardText>
        </VCard>
        <VCard v-else>
          <VTable class="text-no-wrap">
            <tbody>
              <tr>
                <td rowspan="3" width="10%" style="border-right:1px solid #dddddd">
                  <img src="/images/logo/logo-small.png" alt="Logo e-Rapor SMK" style="max-width: 100px">
                </td>
                <td width="30%">&nbsp;&nbsp;&nbsp;NPSN Sekolah</td>
                <td width="60%">{{ $sekolah.npsn }}</td>
              </tr>
              <tr>
                <td>Nama Sekolah</td>
                <td>{{ $sekolah.alamat }}</td>
              </tr>
              <tr>
                <td>Desa Kelurahan Sekolah</td>
                <td>{{ $sekolah.desa_kelurahan }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
      <VCol cols="6" xl="4" md="4" sm="6">
        <VCard v-if="loadingBody">
          <VCardText class="text-center">
            <VProgressCircular :size="60" indeterminate color="error" />
          </VCardText>
        </VCard>
        <VCard v-else>
          <VCardText class="text-center">
            <p>Pengiriman data terakhir dilakukan pada <br>
              <strong>{{ last_sync }}</strong>
            </p>
            <VBtn :loading="loading" :disabled="loading || error || !jumlah" size="large" color="success"
              @click="kirimData">
              <font-awesome-icon icon="fa-solid fa-cloud-arrow-up" />&nbsp;&nbsp;&nbsp;<strong>KIRIM DATA</strong>
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <VRow v-if="!loadingBody">
      <VCol cols="12">
        <VAlert color="secondary" class="text-center">
          <h3 class="text-white">DATA YANG MENGALAMI PERUBAHAN</h3>
        </VAlert>
        <VAlert color="error" class="text-center mt-4" v-if="error">
          <h3 class="text-white">{{ message }}</h3>
        </VAlert>
      </VCol>
    </VRow>
    <VRow>
      <VCol cols="12">
        <VCard v-if="loadingBody">
          <VCardText class="text-center">
            <VProgressCircular :size="60" indeterminate color="error" />
          </VCardText>
        </VCard>
        <VCard v-else>
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center" width="5%">No</th>
                <th width="80%">Data</th>
                <th class="text-center" width="15%">Jml Data</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="jumlah">
                <tr v-for="(item, index) in table_sync" :key="index">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>{{ item.data }}</td>
                  <td class="text-center">{{ item.count }}</td>
                </tr>
              </template>
              <template v-else>
                <tr>
                  <td colspan="3" class="text-center">Tidak ada data yang mengalami perubahan</td>
                </tr>
              </template>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="2" class="text-right">Jumlah</th>
                <th class="text-center">{{ jumlah }}</th>
              </tr>
            </tfoot>
          </VTable>
        </VCard>
      </VCol>
    </VRow>
    <VDialog v-model="isDialogVisible" width="800">
      <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />
      <VCard title="Informasi">
        <VCardText>
          <p>Terdeteksi jumlah data yang akan dikirim melebihi 1.000 row. Sangat disarankan melakukan pengiriman data
            melalui <code>Command Prompt</code> untuk menghindari kegagalan pengiriman data.</p>
          <p>Cara melakukan pengiriman data melalui <code>Command Prompt</code>:</p>
          <p>Untuk Pengguna <strong>Windows</strong></p>
          <ol style="padding-left: 20px">
            <li>Buka CMD</li>
            <li>Ketik: <code>cd c:\eRaporSMK\dataweb</code> [enter]</li>
            <li>Ketik: <code>php artisan kirim:erapor</code> [enter]</li>
            <li>Ketikkan email administrator</li>
            <li>Tunggu hingga proses pengiriman data selesai.</li>
          </ol>
          <p>Untuk Pengguna <strong>Linux</strong></p>
          <ol style="padding-left: 20px">
            <li>Buka Aplikasi Putty dan login ke <code>shh</code></li>
            <li>Ketik: <code>cd /var/www/html/dataweb<sup>*</sup></code> [enter]</li>
            <li>Ketik: <code>php artisan kirim:erapor</code> [enter]</li>
            <li>Ketikkan email administrator</li>
            <li>Tunggu hingga proses pengiriman data selesai.</li>
          </ol>
          <p>Keterangan: <br>
            * : Sesuaikan dengan direktori root folder aplikasi e-Rapor SMK di install <br>
            Untuk mengirimkan seluruh data yang tersimpan di database tanpa filter apapun, jalankan perintah: <code>php
          artisan kirim:erapor --force</code>
          </p>
        </VCardText>
      </VCard>
    </VDialog>
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmAlert"></AlertDialog>
  </div>
</template>
