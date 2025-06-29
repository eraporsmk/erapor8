export default [
  {
    title: 'Referensi',
    icon: 'list',
    children: [
      {
        title: 'Referensi GTK',
        icon: 'graduation-cap',
        action: 'read',
        subject: 'Ref_Guru',
        children: [
          {
            title: 'Guru',
            to: 'referensi-gtk-guru',
            icon: 'graduation-cap',
            action: 'read',
            subject: 'Ref_Guru',
          },
          {
            title: 'Tendik',
            to: 'referensi-gtk-tendik',
            icon: 'graduation-cap',
            action: 'read',
            subject: 'Ref_Guru',
          },
          {
            title: 'Instruktur',
            to: 'referensi-gtk-instruktur',
            icon: 'graduation-cap',
            action: 'read',
            subject: 'Ref_Guru',
          },
          {
            title: 'Asesor',
            to: 'referensi-gtk-asesor',
            icon: 'graduation-cap',
            action: 'read',
            subject: 'Ref_Guru',
          },
        ]
      },
      {
        title: 'Rombongan Belajar',
        icon: 'building',
        action: 'read',
        subject: 'Ref_Rombel',
        children: [
          {
            title: 'Reguler',
            to: 'referensi-rombongan-belajar-reguler',
            icon: 'building',
            action: 'read',
            subject: 'Ref_Rombel',
          },
          {
            title: 'Matpel Pilihan',
            to: 'referensi-rombongan-belajar-matpel-pilihan',
            icon: 'building',
            action: 'read',
            subject: 'Ref_Rombel',
          },
        ]
      },
      {
        title: 'Peserta Didik',
        icon: 'children',
        children: [
          {
            title: 'PD Aktif',
            to: 'referensi-peserta-didik-aktif',
            icon: 'children',
            action: 'read',
            subject: 'Ref_Siswa',
          },
          {
            title: 'PD Keluar',
            to: 'referensi-peserta-didik-keluar',
            icon: 'children',
            action: 'read',
            subject: 'Ref_Siswa_Keluar',
          },
          {
            title: 'Password PD',
            to: 'referensi-peserta-didik-password',
            icon: 'hands-holding-child',
            action: 'read',
            subject: 'Password_pd',
          },
        ]
      },
      {
        title: 'Mata Pelajaran',
        to: 'referensi-mata-pelajaran',
        icon: 'list-check',
        action: 'read',
        subject: 'Administrator',
      },
      {
        title: 'Ekstrakurikuler',
        to: 'referensi-ekstrakurikuler',
        icon: 'person-running',
        action: 'read',
        subject: 'Administrator',
      },
      {
        title: 'DUDI',
        to: 'referensi-dudi',
        icon: 'briefcase',
        action: 'read',
        subject: 'Administrator',
      },
      {
        title: 'Kompetensi Dasar',
        to: 'referensi-kompetensi-dasar',
        icon: { icon: 'tabler-hand-finger-right' },
        action: 'read',
        subject: 'Guru',
      },
      {
        title: 'Capaian Pembelajaran',
        to: 'referensi-capaian-pembelajaran',
        icon: { icon: 'tabler-hand-finger-right' },
        action: 'read',
        subject: 'Guru',
      },
      {
        title: 'Tujuan Pembelajaran',
        to: 'referensi-tujuan-pembelajaran',
        icon: { icon: 'tabler-hand-finger-right' },
        action: 'read',
        subject: 'Guru',
      },
      {
        title: 'Bobot Penilaian',
        to: 'referensi-bobot-penilaian',
        icon: { icon: 'tabler-hand-finger-right' },
        action: 'read',
        subject: 'Guru',
      },
      {
        title: 'Uji Kompetensi Keahlian',
        to: 'referensi-ukk',
        icon: { icon: 'tabler-hand-finger-right' },
        action: 'read',
        subject: 'Kaprog',
      },
    ],
  },
]
