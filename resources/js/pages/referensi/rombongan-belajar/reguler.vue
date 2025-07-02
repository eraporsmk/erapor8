<script setup>
import RombonganBelajar from '@/views/referensi/RombonganBelajar.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Ref_Rombel',
    title: 'Rombongan Belajar',
  },
})
</script>
<template>
  <div>
    <VCard title="Data Rombongan Belajar">
      <VDivider />
      <RombonganBelajar :jenis-rombel="1"></RombonganBelajar>
    </VCard>
  </div>
</template>
