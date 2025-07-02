export default [
  {
    title: 'Sinkronisasi',
    icon: 'arrows-rotate',
    children: [
      {
        title: 'Tarik Dapodik',
        to: 'sinkronisasi-dapodik',
        icon: 'download',
        action: 'read',
        subject: 'Administrator',
      },
      {
        title: 'Kirim Nilai ke Dapodik',
        to: 'sinkronisasi-kirim-nilai-dapodik',
        icon: 'database',
        action: 'read',
        subject: 'Administrator',
      },
      {
        title: 'Kirim Data e-Rapor',
        to: 'sinkronisasi-erapor',
        icon: 'upload',
        action: 'read',
        subject: 'Administrator',
      },
    ],
  },
]
