<script setup>
import PesertaDidik from '@/views/referensi/PesertaDidik.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Siswa_Keluar',
    title: 'Peserta Didik Keluar',
  },
})
</script>
<template>
  <PesertaDidik :status="'keluar'" :title-card="'Peserta Didik Keluar'" />
</template>
