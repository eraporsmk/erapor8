export default [
  {
    title: 'Penilaian',
    icon: { icon: 'tabler-list-check' },
    children: [
      {
        title: 'Nilai Akademik',
        icon: { icon: 'tabler-list-check' },
        action: 'read',
        subject: 'Guru',
        children: [
          {
            title: 'Asesmen Sumatif',
            to: 'nilai-akademik-asesmen-sumatif',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Guru',
          },
          {
            title: 'Nilai Akhir',
            to: 'nilai-akademik-nilai-akhir',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Guru',
          },
          {
            title: 'Capaian Kompetensi',
            to: 'nilai-akademik-capaian-kompetensi',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Guru',
          },
          {
            title: 'Penilaian Sikap',
            to: 'nilai-akademik-nilai-sikap',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Guru',
          },
          {
            title: 'Nilai Ekstrakurikuler',
            to: 'nilai-akademik-ekstrakurikuler',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Ekskul',
          },
        ],
      },
      {
        title: 'Nilai UKK',
        icon: { icon: 'tabler-password-user' },
        children: [
          {
            title: 'Perencanaan',
            to: 'ukk-perencanaan',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Kaprog',
          },
          {
            title: 'Penilaian',
            to: 'ukk-penilaian',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Internal',
          },
        ],
      },
      {
        title: 'Nilai Projek',
        icon: { icon: 'tabler-file-check' },
        children: [
          {
            title: 'Perencanaan',
            to: 'projek-perencanaan',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Projek',
          },
          {
            title: 'Penilaian',
            to: 'projek-penilaian',
            icon: { icon: 'tabler-hand-finger-right' },
            action: 'read',
            subject: 'Projek',
          },
        ],
      },
    ]
  }
]
