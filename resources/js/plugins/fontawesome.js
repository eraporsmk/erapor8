import { library } from '@fortawesome/fontawesome-svg-core'
import { faFacebook, faTelegram, faWhatsapp } from '@fortawesome/free-brands-svg-icons'
import { faArrowsRotate, faBriefcase, faBuilding, faChildren, faCloudArrowUp, faDatabase, faDownload, faGear, faGraduationCap, faHandPointRight, faHandsHoldingChild, faHouse, faLaptopCode, faList, faListCheck, faPersonRunning, faSpellCheck, faTerminal, faUpload, faUser, faUserGraduate, faUserLock, faUserSecret, faWrench } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
library.add(
  faUserSecret,
  faHouse,
  faUserGraduate,
  faChildren,
  faSpellCheck,
  faListCheck,
  faArrowsRotate,
  faDownload,
  faDatabase,
  faUpload,
  faWrench,
  faGear,
  faUserLock,
  faList,
  faGraduationCap,
  faBuilding,
  faHandsHoldingChild,
  faPersonRunning,
  faBriefcase,
  faLaptopCode,
  faTerminal,
  faUser,
  faFacebook,
  faTelegram,
  faWhatsapp,
  faCloudArrowUp,
  faHandPointRight,
)
export default function (app) {
  // ℹ️ We generate layout config from our themeConfig so you don't have to write config twice
  app.component('font-awesome-icon', FontAwesomeIcon)
}
