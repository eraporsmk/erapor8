import { ofetch } from 'ofetch';

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value
    options.headers.append('Authorization', `Bearer ${accessToken}`)
    if (accessToken)
      options.headers.append('accept', `application/json`)
  },
  async onResponseError({ request, response, options }) {
    if(response.status === 401){
      useCookie('userAbilityRules').value = null
      useCookie('userData').value = null
      useCookie('accessToken').value = null
      useCookie('languages').value = null
      window.location.replace('/login')
    }
  },
})
