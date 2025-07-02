export default [
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
]
