import { stringifyQuery } from 'ufo'

export const createUrl = (url, options) => computed(() => {
  if (!options?.query)
    return toValue(url)
  const _url = toValue(url)
  const _query = toValue(options?.query)
  const queryObj = Object.fromEntries(Object.entries(_query).map(([key, val]) => [key, toValue(val)]))
  
  return `${_url}${queryObj ? `?${stringifyQuery(queryObj)}` : ''}`
})
