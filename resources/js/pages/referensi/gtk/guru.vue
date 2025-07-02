<script setup>
import Ptk from '@/views/referensi/Ptk.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Guru',
    title: 'Data Guru',
  },
})
</script>
<template>
  <div>
    <VCard title="Data Guru">
      <VDivider />
      <Ptk :data="'guru'"></Ptk>
    </VCard>
  </div>
</template>
