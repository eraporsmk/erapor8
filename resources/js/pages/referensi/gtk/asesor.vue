<script setup>
import Ptk from '@/views/referensi/Ptk.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Guru',
    title: 'Data Asesor',
  },
})
</script>
<template>
  <Ptk :data="'asesor'" :titleCard="'Data Asesor'" :addBtn="true"></Ptk>
</template>
