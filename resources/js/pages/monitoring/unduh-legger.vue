<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Waka',
    title: 'Monitoring \u00bb Unduh Legger',
  },
})
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  merdeka: false,
  is_ppa: false,
})
const arrayData = ref({
  rombel: [],
  siswa: [],
  pembelajaran: [],
})
const loading = ref({
  rombel: false,
  body: false,
})
const getData = async (postData) => {
  const mergedForm = { ...postData, ...form.value };
  await $api("/monitoring/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData;
      }
      if (postData.data == "legger") {
        arrayData.value.siswa = getData.data_siswa
        arrayData.value.pembelajaran = getData.pembelajaran
        form.value.merdeka = getData.merdeka
        form.value.is_ppa = getData.is_ppa
      }
    },
  });
}
const changeTingkat = async (val) => {
  form.value.rombongan_belajar_id = null;
  arrayData.value.rombel = []
  arrayData.value.siswa = []
  if (val) {
    loading.value.rombel = true;
    await getData({
      data: "rombel",
    }).then(() => {
      loading.value.rombel = false;
    });
  }
}
const changeRombel = async (val) => {
  arrayData.value.siswa = []
  if (val) {
    loading.value.body = true;
    await getData({
      data: "legger",
    }).then(() => {
      loading.value.body = false;
    });
  }
}
const unduhLegger = () => {
  if (form.value.rombongan_belajar_id) {
    if (form.value.merdeka || form.value.is_ppa) {
      var url = `/downloads/leger-nilai-kurmer/${form.value.rombongan_belajar_id}/${form.value.sekolah_id}/${form.value.semester_id}`
    } else {
      var url = `/downloads/leger-nilai-rapor/${form.value.rombongan_belajar_id}`
    }
    window.open(url, '_blank').focus();
  }
}
const unduhSemuaLegger = () => {
  const url = `/downloads/leger-nilai-bulk/${form.value.sekolah_id}/${form.value.semester_id}`
  window.open(url, '_blank').focus();
}
const getNilai = (nilai, anggota_rombel_id) => {
  const nilai_akhir = nilai.find(item => item.anggota_rombel_id === anggota_rombel_id)
  return nilai_akhir?.nilai ?? '-'
}
const getNilaiPilihan = (arr, anggota_pilihan) => {
  const nilai_akhir = arr.find(item => item.anggota_rombel_id === anggota_pilihan.anggota_rombel_id)
  return nilai_akhir?.nilai ?? '-'
}
const getRerata = (nilai_p, nilai_k, rasio_p, rasio_k) => {
  nilai_p = nilai_p * getRasio(rasio_p);
  nilai_k = nilai_k * getRasio(rasio_k);
  var nilai_akhir = (nilai_p + nilai_k) / 100;
  if (isNaN(nilai_akhir)) {
    nilai_akhir = '-'
  } else {
    nilai_akhir = Math.ceil(nilai_akhir);
  }
  return nilai_akhir
}
const getRasio = (rasio) => {
  return rasio ?? 50
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Monitoring &raquo; Unduh Legger</VCardTitle>
      <template #append>
        <VBtn prepend-icon="tabler-file-type-xls" color="success" @click="unduhSemuaLegger">
          Unduh Semua Kelas
        </VBtn>
      </template>
    </VCardItem>
    <VDivider />
    <VCardText>
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
              <AppSelect v-model="form.tingkat" placeholder="== Pilih Tingkat kelas ==" :items="tingkatKelas" clearable
                clear-icon="tabler-x" @update:model-value="changeTingkat" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="rombonganBelajarId">Rombongan
                Belajar</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                :items="arrayData.rombel" clearable clear-icon="tabler-x" @update:model-value="changeRombel"
                item-value="rombongan_belajar_id" item-title="nama" :loading="loading.rombel"
                :disabled="loading.rombel" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12" v-if="arrayData.siswa.length">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis">Unduh Legger</label>
            </VCol>
            <VCol cols="12" md="9">
              <VBtn prepend-icon="tabler-file-type-xls" @click="unduhLegger">
                Unduh Legger
              </VBtn>
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VCardText>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VTable class="text-no-wrap" v-if="arrayData.siswa.length">
        <thead>
          <template v-if="form.merdeka || form.is_ppa">
            <tr>
              <th class="text-center">Peserta Didik</th>
              <template v-for="(pembelajaran, index) in arrayData.pembelajaran">
                <th class="text-center">{{ pembelajaran.nama_mata_pelajaran }}</th>
              </template>
              <th class="text-center align-middle" rowspan="3">S</th>
              <th class="text-center align-middle" rowspan="3">I</th>
              <th class="text-center align-middle" rowspan="3">A</th>
            </tr>
          </template>
          <template v-else>
            <th class="text-center">Peserta Didik</th>
            <template v-for="(pembelajaran, index) in arrayData.pembelajaran">
              <th class="text-center" rowspan="3">{{ pembelajaran.nama_mata_pelajaran }}</th>
            </template>
            <th class="text-center align-middle" rowspan="3">S</th>
            <th class="text-center align-middle" rowspan="3">I</th>
            <th class="text-center align-middle" rowspan="3">A</th>
            <tr>
              <template v-for="(pembelajaran, index) in arrayData.pembelajaran">
                <th class="text-center">P</th>
                <th class="text-center">K</th>
                <th class="text-center">R</th>
              </template>
            </tr>
          </template>
        </thead>
        <tbody>
          <tr v-for="item in arrayData.siswa">
            <td>
              <ProfileSiswa :item="item" />
            </td>
            <template v-if="form.merdeka">
              <template v-for="(pembelajaran, index) in arrayData.pembelajaran">
                <template v-if="pembelajaran.rombongan_belajar.jenis_rombel == '1'">
                  <td class="text-center">
                    {{ getNilai(pembelajaran.all_nilai_akhir_kurmer, item.anggota_rombel.anggota_rombel_id) }}
                  </td>
                </template>
                <template v-else>
                  <td class="text-center">{{ getNilaiPilihan(pembelajaran.all_nilai_akhir_kurmer, item.anggota_pilihan)
                  }}
                  </td>
                </template>
              </template>
            </template>
            <template v-else>
              <template v-for="(pembelajaran, index) in data_pembelajaran">
                <template v-if="pembelajaran.rombongan_belajar.jenis_rombel == '1'">
                  <template v-if="is_ppa">
                    <td class="text-center">
                      {{ getNilai(pembelajaran.all_nilai_akhir_pengetahuan, item.anggota_rombel.anggota_rombel_id) }}
                    </td>
                  </template>
                  <template v-else>
                    <td class="text-center">
                      {{ getNilai(pembelajaran.all_nilai_akhir_pengetahuan, item.anggota_rombel.anggota_rombel_id) }}
                    </td>
                    <td class="text-center">
                      {{ getNilai(pembelajaran.all_nilai_akhir_keterampilan, item.anggota_rombel.anggota_rombel_id) }}
                    </td>
                    <td class="text-center">
                      {{ getRerata(getNilai(pembelajaran.all_nilai_akhir_pengetahuan,
                        item.anggota_rombel.anggota_rombel_id), getNilai(pembelajaran.all_nilai_akhir_keterampilan,
                          item.anggota_rombel.anggota_rombel_id), getRasio(pembelajaran.rasio_p),
                        getRasio(pembelajaran.rasio_k)) }}
                    </td>
                  </template>
                </template>
                <template v-else>
                  <td class="text-center">{{ getNilaiPilihan(pembelajaran.all_nilai_akhir_kurmer, item.anggota_pilihan)
                  }}
                  </td>
                </template>
              </template>
            </template>
            <td class="text-center">
              {{ item.anggota_rombel.absensi?.sakit ?? '-' }}
            </td>
            <td class="text-center">
              {{ item.anggota_rombel.absensi?.izin ?? '-' }}
            </td>
            <td class="text-center">
              {{ item.anggota_rombel.absensi?.alpa ?? '-' }}
            </td>
          </tr>
        </tbody>
      </VTable>
    </template>
  </VCard>
</template>
