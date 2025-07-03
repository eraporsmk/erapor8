const userData = useCookie('userData')
const sekolah = useCookie("sekolah");
const semester = useCookie("semester");
const roles = useCookie("roles");
const profilePhotoPath = useCookie("profilePhotoPath");
export const $user = userData.value
export const $sekolah = sekolah.value
export const $semester = semester.value
export const $roles = roles.value
export const $profilePhotoPath = profilePhotoPath.value
export const tingkatKelas = [
    {
        title: 'Kelas 10',
        value: 10,
    },
    {
        title: 'Kelas 11',
        value: 11,
    },
    {
        title: 'Kelas 12',
        value: 12,
    },
    {
        title: 'Kelas 13',
        value: 13,
    },
]
