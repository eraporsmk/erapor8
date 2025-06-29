import checkboxChecked from '@images/svg/checkbox-checked.svg'
import checkboxIndeterminate from '@images/svg/checkbox-indeterminate.svg'
import checkboxUnchecked from '@images/svg/checkbox-unchecked.svg'
import radioChecked from '@images/svg/radio-checked.svg'
import radioUnchecked from '@images/svg/radio-unchecked.svg'

const customIcons = {
  'mdi-checkbox-blank-outline': checkboxUnchecked,
  'mdi-checkbox-marked': checkboxChecked,
  'mdi-minus-box': checkboxIndeterminate,
  'mdi-radiobox-marked': radioChecked,
  'mdi-radiobox-blank': radioUnchecked,
}

const aliases = {
  calendar: 'tabler-calendar',
  collapse: 'tabler-chevron-up',
  complete: 'tabler-check',
  cancel: 'tabler-x',
  close: 'tabler-x',
  delete: 'tabler-circle-x-filled',
  clear: 'tabler-circle-x',
  success: 'tabler-circle-check',
  info: 'tabler-info-circle',
  warning: 'tabler-alert-triangle',
  error: 'tabler-alert-circle',
  prev: 'tabler-chevron-left',
  ratingEmpty: 'tabler-star',
  ratingFull: 'tabler-star-filled',
  ratingHalf: 'tabler-star-half-filled',
  next: 'tabler-chevron-right',
  delimiter: 'tabler-circle',
  sort: 'tabler-arrow-up',
  expand: 'tabler-chevron-down',
  menu: 'tabler-menu-2',
  subgroup: 'tabler-caret-down',
  dropdown: 'tabler-chevron-down',
  edit: 'tabler-pencil',
  loading: 'tabler-refresh',
  first: 'tabler-player-skip-back',
  last: 'tabler-player-skip-forward',
  unfold: 'tabler-arrows-move-vertical',
  file: 'tabler-paperclip',
  plus: 'tabler-plus',
  minus: 'tabler-minus',
  sortAsc: 'tabler-arrow-up',
  sortDesc: 'tabler-arrow-down',
}

export const iconify = {
  component: props => {
    // Load custom SVG directly instead of going through icon component
    if (typeof props.icon === 'string') {
      const iconComponent = customIcons[props.icon]
      if (iconComponent)
        return h(iconComponent)
    }
    
    return h(props.tag, {
      ...props,

      // As we are using class based icons
      class: [props.icon],

      // Remove used props from DOM rendering
      tag: undefined,
      icon: undefined,
    })
  },
}
export const icons = {
  defaultSet: 'iconify',
  aliases,
  sets: {
    iconify,
  },
}
