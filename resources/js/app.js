import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import Home from './Pages/Home.vue';

const pages = { Home };

const root = document.getElementById('app');
const page = JSON.parse(root?.dataset.page || '{}');
const component = pages[page.component] || Home;

createApp(component, page).mount(root);
