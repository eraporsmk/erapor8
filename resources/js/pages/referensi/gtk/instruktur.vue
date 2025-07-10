<script setup>
import Ptk from '@/views/referensi/Ptk.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Guru',
    title: 'Data Instruktur',
  },
})
</script>
<template>
  <Ptk :data="'instruktur'" :titleCard="'Data Instruktur'" :addBtn="true"></Ptk>
</template>
