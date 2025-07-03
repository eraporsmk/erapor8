<script setup>
import PesertaDidik from '@/views/referensi/PesertaDidik.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Siswa',
    title: 'Peserta Didik Aktif',
  },
})
</script>
<template>
  <PesertaDidik :status="'aktif'" :title-card="'Peserta Didik Aktif'" />
</template>
