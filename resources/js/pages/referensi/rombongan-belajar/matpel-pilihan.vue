<script setup>
import RombonganBelajar from '@/views/referensi/RombonganBelajar.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Rombel',
    title: 'Rombel Matpel Pilihan',
  },
})
</script>
<template>
  <div>
    <VCard title="Rombel Matpel Pilihan">
      <VDivider />
      <RombonganBelajar :jenis-rombel="16"></RombonganBelajar>
    </VCard>
  </div>
</template>
