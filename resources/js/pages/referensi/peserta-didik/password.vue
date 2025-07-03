<script setup>
import PesertaDidik from '@/views/referensi/PesertaDidik.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Password_pd',
    title: 'Akun Peserta Didik',
  },
})
</script>
<template>
  <PesertaDidik :status="'password'" :title-card="'Akun Peserta Didik'" />
</template>
