export default [
  {
    title: 'Monitoring',
    icon: { icon: 'tabler-chart-histogram' },
    children: [
      {
        title: 'Catatan Sikap',
        to: 'monitoring-catatan-sikap',
        icon: { icon: 'tabler-hand-finger-right' },
        action: 'read',
        subject: 'Wali',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Nilai Akademik',
        route: 'monitoring-penilaian',
        subject: 'Waka',
        action: 'read',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Nilai Projek',
        route: 'monitoring-nilai-projek',
        subject: 'Waka',
        action: 'read',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Nilai Ekstrakurikuler',
        route: 'monitoring-nilai-ekskul',
        subject: 'Waka',
        action: 'read',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Nilai UKK',
        route: 'monitoring-nilai-ukk',
        subject: 'Waka',
        action: 'read',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Nilai PKL',
        route: 'monitoring-nilai-pkl',
        subject: 'Waka',
        action: 'read',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Cetak Rapor Semester',
        route: 'monitoring-cetak-rapor',
        subject: 'Waka',
        action: 'read',
      },
      {
        icon: { icon: 'tabler-hand-finger-right' },
        title: 'Unduh Leger',
        route: 'monitoring-unduh-legger',
        subject: 'Waka',
        action: 'read',
      },
    ],
  },
]
