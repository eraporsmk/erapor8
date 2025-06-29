import 'sweetalert2/dist/sweetalert2.min.css';
import VueSweetalert2 from 'vue-sweetalert2';
export default function (app) {
  // ℹ️ We generate layout config from our themeConfig so you don't have to write config twice
  app.use(VueSweetalert2);
}
