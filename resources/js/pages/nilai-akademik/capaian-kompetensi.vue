<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Guru',
    title: 'Capaian Kompetensi'
  },
})
const loadingBody = ref(true)
const statusPenilaian = ref(true)
const fetchData = async () => {
  try {
    const response = await $api('/setting/status-penilaian', {
      method: 'POST',
      body: {
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
      }
    })
    statusPenilaian.value = response
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
onMounted(async () => {
  await fetchData();
});
const form = ref({
  tingkat: undefined,
  rombongan_belajar_id: undefined,
  pembelajaran_id: undefined,
  mata_pelajaran_id: undefined,
})
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
})
const arrayData = ref({
  rombel: [],
  mapel: [],
  siswa: [],
})
const loading = ref({
  body: false,
  rombel: false,
  mapel: false,
})
const showBtn = ref(false)
const showReset = ref(0)
const defaultForm = {
  opsi: 'capaian-kompetensi',
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  merdeka: false,
}
const nilai = ref({
  angka: {},
  kompeten: {},
  inkompeten: {},
})
const isKunciWalas = ref(false)
const getData = async (postData) => {
  const mergedForm = { ...postData, ...defaultForm };
  await $api("/referensi/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      arrayData.value.siswa = []
      showBtn.value = false
      let getData = response._data;
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData;
      }
      if (postData.data == "mapel") {
        arrayData.value.mapel = getData.mapel;
        defaultForm.merdeka = getData.merdeka
        isKunciWalas.value = getData.rombel.kunci_nilai ? true : false
      }
    },
  });
}
const changeTingkat = async (val) => {
  form.value.rombongan_belajar_id = null;
  form.value.pembelajaran_id = null;
  if (val) {
    loading.value.rombel = true;
    await getData({
      data: "rombel",
      nilai: 1,
      tingkat: val,
    }).then(() => {
      loading.value.rombel = false;
    });
  }
}
const changeRombel = async (val) => {
  form.value.pembelajaran_id = null;
  if (val) {
    loading.value.mapel = true;
    await getData({
      data: "mapel",
      nilai: 1,
      rombongan_belajar_id: val,
    }).then(() => {
      loading.value.mapel = false;
    });
  }
}
const lcfirst = (string) => {
  return string.charAt(0).toLowerCase() + string.slice(1);
}
const convertUTF7toUTF8 = (string) => {
  var b64Token = /\+([a-z\d\/+]*\-?)/gi,
    hex, len, replace, i;

  return string.replace(b64Token, function (match, grp) {
    hex = Buffer(grp, 'base64');
    len = hex.length >> 1 << 1;
    replace = '';
    i = 1;

    for (i; i < len; i = i + 2) {
      replace += String.fromCharCode(hex.readUInt16BE(i - 1));
    }
    return replace;
  });
}
const changeMapel = async (val) => {
  arrayData.value.siswa = []
  if (val) {
    const mergedForm = { ...form.value, ...defaultForm };
    loading.value.body = true;
    showBtn.value = false;
    await $api("/penilaian/get-capaian-kompetensi", {
      method: "POST",
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data;
        arrayData.value.siswa = getData.data_siswa
        showReset.value = getData.show_reset
        showBtn.value = (getData.data_siswa?.length) ? true : false
        loading.value.body = false
        getData.data_siswa.forEach((siswa) => {
          nilai.value.angka[siswa.anggota_rombel.anggota_rombel_id] = (siswa.anggota_rombel.nilai_akhir_mapel) ? siswa.anggota_rombel.nilai_akhir_mapel.nilai : ''
          if (siswa.anggota_rombel.deskripsi_mata_pelajaran) {
            nilai.value.kompeten[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.deskripsi_mata_pelajaran.deskripsi_pengetahuan
            nilai.value.inkompeten[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.deskripsi_mata_pelajaran.deskripsi_keterampilan
          } else if (siswa.anggota_rombel.single_deskripsi_mata_pelajaran) {
            nilai.value.kompeten[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_deskripsi_mata_pelajaran.deskripsi_pengetahuan
            nilai.value.inkompeten[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_deskripsi_mata_pelajaran.deskripsi_keterampilan
          } else {
            var tempTpKompeten = []
            var tempTpInKompeten = []
            if (siswa.anggota_rombel.tp_kompeten.length) {
              siswa.anggota_rombel.tp_kompeten.forEach((tp_kompeten) => {
                tempTpKompeten.push(convertUTF7toUTF8(lcfirst(tp_kompeten.tp.deskripsi)))
              })
              nilai.value.kompeten[siswa.anggota_rombel.anggota_rombel_id] = 'Menunjukkan penguasaan yang baik dalam ' + tempTpKompeten.join(', ')
            } else {
              nilai.value.kompeten[siswa.anggota_rombel.anggota_rombel_id] = null
            }
            if (siswa.anggota_rombel.tp_inkompeten.length) {
              siswa.anggota_rombel.tp_inkompeten.forEach((tp_inkompeten) => {
                tempTpInKompeten.push(convertUTF7toUTF8(lcfirst(tp_inkompeten.tp.deskripsi)))
              })
              nilai.value.inkompeten[siswa.anggota_rombel.anggota_rombel_id] = 'Perlu ditingkatkan dalam ' + tempTpInKompeten.join(', ')
            } else {
              nilai.value.inkompeten[siswa.anggota_rombel.anggota_rombel_id] = null
            }
          }
        })
      },
    });
  }
}
const confirmed = ref(false)
const isNotifVisible = ref(false)
const isConfirmDialogVisible = ref(false)
const confirmationText = ref('')
const notif = ref({
  color: "",
  title: "",
  text: "",
});
const onSubmit = async () => {
  confirmed.value = true
  const mergedForm = { ...defaultForm, ...form.value, ...nilai.value };
  await $api("/penilaian/simpan", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      confirmed.value = false;
      if (getData.errors) {
        errors.value = getData.errors;
      } else {
        isNotifVisible.value = true;
        notif.value = getData;
      }
    },
  });
}
const resetForm = ref(false)
const formReset = () => {
  resetForm.value = true
  form.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    pembelajaran_id: null,
    mata_pelajaran_id: null,
  }
  errors.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    pembelajaran_id: null,
  }
  arrayData.value = {
    rombel: [],
    mapel: [],
    siswa: [],
  }
  showBtn.value = false
  showReset.value = 0
  nilai.value = {
    angka: {},
    kompeten: {},
    inkompeten: {},
  }
}
const confirmClose = () => {
  formReset()
}
const resetData = () => {
  isConfirmDialogVisible.value = true
  confirmationText.value = 'Tindakan ini tidak dapat dikembalikan!'
  notif.value = {
    color: 'warning',
    title: 'a',
    text: 'b',
  }
}
const confirmDialog = async () => {
  defaultForm.opsi = 'reset-kompetensi'
  const mergedForm = { ...defaultForm, ...form.value, ...nilai.value };
  await $api('/penilaian/simpan', {
    method: 'POST',
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data
      defaultForm.opsi = 'capaian-kompetensi'
      notif.value = getData
      isNotifVisible.value = true
      formReset()
    }
  })
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Capaian Kompetensi</VCardTitle>
    </VCardItem>
    <VDivider />
    <template v-if="loadingBody">
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else-if="statusPenilaian">
      <VForm @submit.prevent="onSubmit">
        <VCardText>
          <VRow>
            <DefaultForm v-model:form="form" v-model:errors="errors" v-model:arrayData="arrayData"
              v-model:loading="loading" v-model:resetForm="resetForm" v-model:isKunci="isKunciWalas"
              @tingkat="changeTingkat" @rombongan_belajar_id="changeRombel" @pembelajaran_id="changeMapel">
            </DefaultForm>
            <VCol cols="12" v-if="showReset">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis">Reset Capaian Kompetensi</label>
                </VCol>
                <VCol cols="12" md="9">
                  <VBtn variant="tonal" color="error" @click="resetData">
                    Reset Capaian Kompetensi
                  </VBtn>
                </VCol>
              </VRow>
            </VCol>
          </VRow>
          <div class="text-center" v-if="loading.body">
            <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
          </div>
          <VAlert color="error" class="text-center my-4" variant="tonal" v-if="isKunciWalas">
            <h2 class="mt-4 mb-4">Penilaian tidak aktif. Silahkan hubungi Wali Kelas!</h2>
          </VAlert>
        </VCardText>
        <template v-if="arrayData.siswa.length">
          <VTable>
            <thead>
              <tr>
                <th class="text-center" rowspan="2" style="vertical-align:middle">No</th>
                <th class="text-center" rowspan="2" style="vertical-align:middle">Nama Peserta Didik</th>
                <th class="text-center" rowspan="2" style="vertical-align:middle">Nilai Akhir</th>
                <th class="text-center" colspan="2">Capaian Kompetensi</th>
              </tr>
              <tr>
                <th class="text-center">Kompetensi yang telah dicapai</th>
                <th class="text-center">Kompetensi yang perlu ditingkatkan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(siswa, index) in arrayData.siswa">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <ProfileSiswa :item="siswa" />
                </td>
                <td class="text-center">{{ nilai.angka[siswa.anggota_rombel.anggota_rombel_id] }}</td>
                <td>
                  <AppTextarea v-model="nilai.kompeten[siswa.anggota_rombel.anggota_rombel_id]"
                    placeholder="Kompetensi yang telah dicapai" class="py-2 px-2" />
                </td>
                <td>
                  <AppTextarea v-model="nilai.inkompeten[siswa.anggota_rombel.anggota_rombel_id]"
                    placeholder="Kompetensi yang perlu ditingkatkan" />
                </td>
              </tr>
            </tbody>
          </VTable>
        </template>
        <VDivider />
        <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible" v-if="showBtn">
          <VBtn variant="elevated" type="submit" :loading="confirmed" :disabled="confirmed">
            Simpan
          </VBtn>
        </VCardText>
      </VForm>
    </template>
    <template v-else>
      <VCardText>
        <VAlert color="error" class="text-center my-4" variant="tonal">
          <h2 class="mt-4 mb-4">Penilaian tidak aktif. Silahkan hubungi administrator!</h2>
        </VAlert>
      </VCardText>
    </template>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" :confirmation-text="confirmationText" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog" @close="confirmClose" />
  </VCard>
</template>
