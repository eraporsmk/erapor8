<script setup>
import Administrator from '@/views/dashboard/Administrator.vue';
import Guru from '@/views/dashboard/Guru.vue';
definePage({
  meta: {
    action: 'read',
    subject: 'Web',
    title: 'Beranda',
  },
})
</script>

<template>
  <div>
    <template v-if="$can('read', 'Administrator')">
      <Administrator />
    </template>
    <template v-if="$can('read', 'Guru')">
      <Guru />
    </template>
  </div>
</template>
