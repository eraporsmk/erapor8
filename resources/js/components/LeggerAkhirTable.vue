<script setup>
const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const selectedSiswa = ref(null)
const detailDialog = ref(false)

const openDetail = (siswa) => {
  selectedSiswa.value = siswa
  detailDialog.value = true
}

const formatScore = (val) => {
  if (val === null || val === undefined) return '-'
  return Number(val).toFixed(2)
}
</script>

<template>
  <div v-if="data?.siswa_list?.length">
    <VAlert
      v-if="data.siswa_list.some(s => s.is_incomplete)"
      type="warning"
      variant="tonal"
      class="ma-4"
    >
      Terdapat peserta didik dengan riwayat semester belum lengkap (murid pindahan). Anda dapat melengkapi nilai rapor sekolah asal langsung pada sheet <strong>Rincian Per Semester</strong> di file Excel unduhan; nilai rekap akan otomatis terhitung ulang.
    </VAlert>

    <VTable class="text-no-wrap">
      <thead>
        <tr>
          <th class="text-center">No</th>
          <th>Peserta Didik</th>
          <th class="text-center">NISN</th>
          <th class="text-center">Jml Sem</th>
          <th v-for="mapel in data.mapel_list" :key="mapel.mata_pelajaran_id" class="text-center">
            {{ mapel.nama_mata_pelajaran }}
          </th>
          <th class="text-center">PKL</th>
          <th class="text-center">UKK</th>
          <th class="text-center">Rata-rata Total</th>
          <th class="text-center">Rincian</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(siswa, idx) in data.siswa_list" :key="siswa.peserta_didik_id">
          <td class="text-center">{{ idx + 1 }}</td>
          <td>{{ siswa.nama }}</td>
          <td class="text-center">{{ siswa.nisn }}</td>
          <td class="text-center">
            <VChip
              size="small"
              :color="siswa.is_incomplete ? 'warning' : 'success'"
              variant="tonal"
            >
              {{ siswa.semester_count }}/{{ data.max_semester_count }}
            </VChip>
          </td>
          <td v-for="mapel in data.mapel_list" :key="mapel.mata_pelajaran_id" class="text-center">
            {{ formatScore(siswa.nilai_per_mapel?.[mapel.mata_pelajaran_id]?.rata_rata) }}
          </td>
          <td class="text-center font-weight-medium">
            {{ formatScore(siswa.nilai_pkl) }}
          </td>
          <td class="text-center font-weight-medium">
            {{ formatScore(siswa.nilai_ukk) }}
          </td>
          <td class="text-center font-weight-bold">
            {{ formatScore(siswa.rata_rata_total) }}
          </td>
          <td class="text-center">
            <VBtn size="x-small" variant="tonal" color="info" @click="openDetail(siswa)">
              Detail
            </VBtn>
          </td>
        </tr>
      </tbody>
    </VTable>

    <VDialog v-model="detailDialog" max-width="960">
      <VCard v-if="selectedSiswa">
        <VCardItem>
          <VCardTitle>Rincian Nilai Per Semester — {{ selectedSiswa.nama }}</VCardTitle>
          <template #append>
            <IconBtn @click="detailDialog = false">
              <VIcon icon="tabler-x" />
            </IconBtn>
          </template>
        </VCardItem>
        <VDivider />
        <VCardText>
          <VTable density="compact" class="text-no-wrap">
            <thead>
              <tr>
                <th>Mata Pelajaran</th>
                <th v-for="sem in data.semesters" :key="sem.semester_id" class="text-center">
                  {{ sem.label }}
                </th>
                <th class="text-center">Rata-rata</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mapel in data.mapel_list" :key="mapel.mata_pelajaran_id">
                <td>{{ mapel.nama_mata_pelajaran }}</td>
                <td v-for="sem in data.semesters" :key="sem.semester_id" class="text-center">
                  {{ selectedSiswa.nilai_per_mapel?.[mapel.mata_pelajaran_id]?.per_semester?.[sem.semester_id] ?? '-' }}
                </td>
                <td class="text-center font-weight-bold">
                  {{ formatScore(selectedSiswa.nilai_per_mapel?.[mapel.mata_pelajaran_id]?.rata_rata) }}
                </td>
              </tr>
              <tr>
                <td class="font-weight-medium">PKL (Praktik Kerja Lapangan)</td>
                <td :colspan="data.semesters.length" class="text-center text-medium-emphasis">Nilai Rapor PKL</td>
                <td class="text-center font-weight-bold">{{ formatScore(selectedSiswa.nilai_pkl) }}</td>
              </tr>
              <tr>
                <td class="font-weight-medium">UKK (Uji Kompetensi Keahlian)</td>
                <td :colspan="data.semesters.length" class="text-center text-medium-emphasis">Nilai Uji Kompetensi</td>
                <td class="text-center font-weight-bold">{{ formatScore(selectedSiswa.nilai_ukk) }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
