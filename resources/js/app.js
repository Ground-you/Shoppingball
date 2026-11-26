import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import Home from './pages/Home.vue';
import Login from './pages/Login.vue';
import Register from './pages/Register.vue';
import Withdraw from './pages/Withdraw.vue';
import Profile from './pages/Profile.vue';
import ProductDetail from './pages/ProductDetail.vue';
import Checkout from './pages/Checkout.vue';
import SellerLogin from './pages/Seller/Login.vue';
import SellerRegister from './pages/Seller/Register.vue';
import SellerDashboard from './pages/Seller/Dashboard.vue';
import SellerProfile from './pages/Seller/Profile.vue';
import SellerStore from './pages/Seller/Store.vue';
import SellerProducts from './pages/Seller/Products.vue';
import SellerProductForm from './pages/Seller/ProductForm.vue';
import SellerInventory from './pages/Seller/Inventory.vue';
import SellerOrders from './pages/Seller/Orders.vue';
import SellerOrderDetail from './pages/Seller/OrderDetail.vue';

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
    SellerOrders,
    SellerOrderDetail,
};

const root = document.getElementById('app');
const page = JSON.parse(root?.dataset.page || '{}');
const component = pages[page.component] || Home;

createApp(component, page).mount(root);
