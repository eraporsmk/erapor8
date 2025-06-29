// Ported from [Nuxt](https://github.com/nuxt/nuxt/blob/main/packages/nuxt/src/app/composables/cookie.ts)
import { parse, serialize } from 'cookie-es'
import { destr } from 'destr'

const CookieDefaults = {
  path: '/',
  watch: true,
  decode: val => destr(decodeURIComponent(val)),
  encode: val => encodeURIComponent(typeof val === 'string' ? val : JSON.stringify(val)),
}

export const useCookie = (name, _opts) => {
  const opts = { ...CookieDefaults, ..._opts || {} }
  const cookies = parse(document.cookie, opts)
  const cookie = ref(cookies[name] ?? opts.default?.())

  watch(cookie, () => {
    document.cookie = serializeCookie(name, cookie.value, opts)
  })
  
  return cookie
}
function serializeCookie(name, value, opts = {}) {
  if (value === null || value === undefined)
    return serialize(name, value, { ...opts, maxAge: -1 })
  
  return serialize(name, value, { ...opts, maxAge: 60 * 60 * 24 * 30 })
}
