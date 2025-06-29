export default [
  {
    title: 'Nilai Akademik',
    icon: { icon: 'tabler-list-check' },
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
]
