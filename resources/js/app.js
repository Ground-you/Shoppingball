import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import Home from './Pages/Home.vue';
import Login from './Pages/Login.vue';
import Register from './Pages/Register.vue';
import Withdraw from './Pages/Withdraw.vue';
import Profile from './Pages/Profile.vue';
import ProductDetail from './Pages/ProductDetail.vue';
import Checkout from './Pages/Checkout.vue';
import SellerLogin from './Pages/Seller/Login.vue';
import SellerRegister from './Pages/Seller/Register.vue';
import SellerDashboard from './Pages/Seller/Dashboard.vue';
import SellerProfile from './Pages/Seller/Profile.vue';
import SellerStore from './Pages/Seller/Store.vue';
import SellerProducts from './Pages/Seller/Products.vue';
import SellerProductForm from './Pages/Seller/ProductForm.vue';
import SellerInventory from './Pages/Seller/Inventory.vue';

const pages = {
    Home,
    Login,
    Register,
    Withdraw,
    Profile,
    ProductDetail,
    Checkout,
    SellerLogin,
    SellerRegister,
    SellerDashboard,
    SellerProfile,
    SellerStore,
    SellerProducts,
    SellerProductForm,
    SellerInventory,
};

const root = document.getElementById('app');
const page = JSON.parse(root?.dataset.page || '{}');
const component = pages[page.component] || Home;

createApp(component, page).mount(root);
