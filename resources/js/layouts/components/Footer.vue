<script setup>
import { themeConfig } from '@themeConfig';
</script>
<template>
  <div class="h-100 d-flex align-center justify-md-space-between justify-center">
    <!-- 👉 Footer: left content -->
    <span class="d-flex align-center text-medium-emphasis">
      Sistem Informasi Penilaian SMK (SIPENA SMK)
      <!--Hak Cipta &copy;
      {{ new Date().getFullYear() }}
      <a href="https://smk.kemendikdasmen.go.id/" target="_blank" rel="noopener noreferrer"
        class="text-primary ms-1">Direktorat SMK Kemendikdasmen RI</a>-->
    </span>
    <!-- 👉 Footer: right content -->
    <span class="d-md-flex gap-x-4 text-primary d-none">
      Versi {{ themeConfig.app.version }}
    </span>
  </div>
</template>
