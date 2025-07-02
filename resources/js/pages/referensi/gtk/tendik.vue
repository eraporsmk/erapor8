<script setup>
import Ptk from '@/views/referensi/Ptk.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Guru',
    title: 'Data Tenaga Kependidikan',
  },
})
</script>
<template>
  <div>
    <VCard title="Data Tenaga Kependidikan">
      <VDivider />
      <Ptk :data="'tendik'"></Ptk>
    </VCard>
  </div>
</template>
