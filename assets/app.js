/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

import { createApp } from 'vue';
import naive from 'naive-ui';
import report from './components/report-naive.vue';
const app = createApp(report);
app.use(naive);
app.mount('#app');
