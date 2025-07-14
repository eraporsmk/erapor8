<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Wali',
    title: 'Unduh Legger'
  },
})
onMounted(async () => {
  await fetchData();
});
const loading = ref({
  body: false,
})
const defaultForm = ref({
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  semester_id: $semester.semester_id,
  periode_aktif: $semester.nama,
  aksi: 'unduh-legger',
  rapor_pts: false,
  merdeka: false,
  is_ppa: false,
  rombongan_belajar_id: null,
})
const arrayData = ref({
  siswa: [],
  pembelajaran: [],
})
const fetchData = async () => {
  loading.value.body = true;
  try {
    const response = await useApi(createUrl('/walas', {
      query: {
        user_id: defaultForm.value.user_id,
        guru_id: defaultForm.value.guru_id,
        sekolah_id: defaultForm.value.sekolah_id,
        semester_id: defaultForm.value.semester_id,
        periode_aktif: defaultForm.value.nama,
        aksi: defaultForm.value.aksi,
      },
    }));
    let getData = response.data.value
    defaultForm.value.rombongan_belajar_id = getData.rombel.rombongan_belajar_id
    defaultForm.value.merdeka = getData.merdeka
    defaultForm.value.is_ppa = getData.is_ppa
    arrayData.value.siswa = getData.data_siswa
    arrayData.value.pembelajaran = getData.pembelajaran
  } catch (error) {
    console.error(error);
  } finally {
    loading.value.body = false;
  }
}
const unduhLegger = () => {
  if (defaultForm.value.rombongan_belajar_id) {
    if (defaultForm.value.merdeka || defaultForm.value.is_ppa) {
      var url = `/downloads/leger-nilai-kurmer/${defaultForm.value.rombongan_belajar_id}/${defaultForm.value.sekolah_id}/${defaultForm.value.semester_id}`
    } else {
      var url = `/downloads/leger-nilai-rapor/${defaultForm.value.rombongan_belajar_id}`
    }
    window.open(url, '_blank').focus();
  }
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
      <VCardTitle>Unduh Legger</VCardTitle>
      <template #append>
        <VBtn prepend-icon="tabler-file-type-xls" @click="unduhLegger">
          Unduh Legger
        </VBtn>
      </template>
    </VCardItem>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VTable class="text-no-wrap" v-if="arrayData.siswa.length">
        <thead>
          <template v-if="defaultForm.merdeka || defaultForm.is_ppa">
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
            <template v-if="defaultForm.merdeka">
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
                  <td class="text-center">{{ getNilaiPilihan(pembelajaran.all_nilai_akhir_kurmer, item.anggota_pilihan) }}
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
