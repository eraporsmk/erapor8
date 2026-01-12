<script setup>
import CardPembelajaran from './CardPembelajaran.vue';
</script>
<template>
  <div>
    <VRow>
      <VCol cols="12">
        <CardPembelajaran :card-title="`Mata Pelajaran yang diampu di Tahun Pelajaran ${$semester.nama}`"
          card-url="/dashboard"></CardPembelajaran>
      </VCol>
      <VCol cols="12" v-if="$can('read', 'Wali')">
        <CardPembelajaran card-url="/dashboard/wali" :cardSubTitle="true"></CardPembelajaran>
      </VCol>
      <VCol cols="12" v-if="$can('read', 'Pilihan')">
        <CardPembelajaran card-url="/dashboard/wali-matpil" :cardSubTitle="true"></CardPembelajaran>
      </VCol>
    </VRow>
  </div>
</template>
