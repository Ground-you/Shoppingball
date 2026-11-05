import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import Home from './Pages/Home.vue';
import Login from './Pages/Login.vue';
import Register from './Pages/Register.vue';
import Withdraw from './Pages/Withdraw.vue';
import Profile from './Pages/Profile.vue';

const pages = { Home, Login, Register, Withdraw, Profile };

const root = document.getElementById('app');
const page = JSON.parse(root?.dataset.page || '{}');
const component = pages[page.component] || Home;

createApp(component, page).mount(root);
