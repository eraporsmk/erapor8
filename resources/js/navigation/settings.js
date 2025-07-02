export default [
  {
    title: 'Pengaturan',
    icon: 'wrench',
    children: [
      {
        title: 'Umum',
        to: 'pengaturan-umum',
        icon: 'gear',
        action: 'read',
        subject: 'Administrator',
      },
      {
        title: 'Akses Pengguna',
        to: 'pengaturan-pengguna',
        icon: 'user-lock',
        action: 'read',
        subject: 'Administrator',
      },
    ],
  },
]
