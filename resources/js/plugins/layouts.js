import { createLayouts } from '@layouts'
import { layoutConfig } from '@themeConfig'

// Styles
import '@layouts/styles/index.scss'

export default function (app) {
  // ℹ️ We generate layout config from our themeConfig so you don't have to write config twice
  app.use(createLayouts(layoutConfig))
}
