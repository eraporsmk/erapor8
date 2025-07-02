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
  <div>
    <VCard title="Data Asesor">
      <VDivider />
      <Ptk :data="'asesor'"></Ptk>
    </VCard>
  </div>
</template>
