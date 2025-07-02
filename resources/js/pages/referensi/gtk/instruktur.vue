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
  <div>
    <VCard title="Data Instruktur">
      <VDivider />
      <Ptk :data="'instruktur'"></Ptk>
    </VCard>
  </div>
</template>
