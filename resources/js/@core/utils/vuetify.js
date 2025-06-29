import { cookieRef } from '@layouts/stores/config'

export const resolveVuetifyTheme = defaultTheme => {
  const cookieColorScheme = cookieRef('color-scheme', usePreferredDark().value ? 'dark' : 'light')
  const storedTheme = cookieRef('theme', defaultTheme).value
  
  return storedTheme === 'system'
    ? cookieColorScheme.value === 'dark'
      ? 'dark'
      : 'light'
    : storedTheme
}
