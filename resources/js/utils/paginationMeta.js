export const paginationMeta = (options, total) => {
  const { t } = useI18n() 
  const start = (options.page - 1) * options.itemsPerPage + 1
  const end = Math.min(options.page * options.itemsPerPage, total)
  
  return `${t(`Showing`)} ${total === 0 ? 0 : start} ${t(`to`)} ${end} ${t(`of`)} ${total} ${t(`entries`)}`
}
