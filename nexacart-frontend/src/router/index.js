import {
    createRouter,
    createWebHistory,
} from 'vue-router'

import { STORAGE_KEYS } from '@/constants/storage'
import { useAuthStore } from '@/stores/auth'
const routes = [
    {
        path: '/',
        component: () =>
            import('@/layouts/CustomerLayout.vue'),
        children: [
            {
                path: '',
                name: 'home',
                component: () =>
                    import(
                        '@/views/customer/HomeView.vue'
                    ),
            },
            {
                path: 'products',
                name: 'products',
                component: () =>
                    import(
                        '@/views/customer/ProductListView.vue'
                    ),
            },
            {
                path: 'products/:slug',
                name: 'product-detail',
                component: () =>
                    import(
                        '@/views/customer/ProductDetailView.vue'
                    ),
            },
            {
                path: 'cart',
                name: 'cart',
                component: () =>
                    import(
                        '@/views/customer/CartView.vue'
                    ),
            },
            {
                path: 'orders',
                name: 'customer-orders',
                meta: {
                    requiresAuth: true,
                    roles: ['customer'],
                },
                component: () =>
                    import(
                        '@/views/customer/OrderListView.vue'
                    ),
            },
            {
                path: 'orders/success/:orderCode',
                name: 'order-success',
                meta: {
                    requiresAuth: true,
                    roles: ['customer'],
                },
                component: () =>
                    import(
                        '@/views/customer/OrderSuccessView.vue'
                    ),
            },
            {
                path: 'checkout',
                name: 'checkout',
                meta: {
                    requiresAuth: true,
                    roles: ['customer'],
                },
                component: () =>
                    import(
                        '@/views/customer/CheckoutView.vue'
                    ),
            },

        ],

    },

    {
        path: '/',
        component: () =>
            import('@/layouts/AuthLayout.vue'),
        meta: {
            guestOnly: true,
        },
        children: [
            {
                path: 'login',
                name: 'login',
                component: () =>
                    import(
                        '@/views/auth/LoginView.vue'
                    ),
            },
            {
                path: 'register',
                name: 'register',
                component: () =>
                    import(
                        '@/views/auth/RegisterView.vue'
                    ),
            },
        ],
    },

    {
        path: '/seller',
        component: () =>
            import('@/layouts/SellerLayout.vue'),
        meta: {
            requiresAuth: true,
            roles: ['seller'],
        },
        children: [
            {
                path: '',
                name: 'seller-dashboard',
                component: () =>
                    import(
                        '@/views/seller/SellerDashboardView.vue'
                    ),
            },
        ],
    },
    {
        path: '/seller',
        component: () =>
            import(
                '@/layouts/SellerLayout.vue'
            ),
        meta: {
            requiresAuth: true,
            role: 'seller',
        },
        children: [
            {
                path: 'products',
                name: 'seller-products',
                component: () =>
                    import(
                        '@/views/seller/products/ProductListView.vue'
                    ),
            },
            {
                path: 'products/create',
                name: 'seller-products-create',
                component: () =>
                    import(
                        '@/views/seller/products/ProductCreateView.vue'
                    ),
            },
            {
                path: 'products/:id',
                name: 'seller-products-show',
                component: () =>
                    import(
                        '@/views/seller/products/ProductDetailView.vue'
                    ),
            },
            {
                path: 'products/:id/edit',
                name: 'seller-products-edit',
                component: () =>
                    import(
                        '@/views/seller/products/ProductEditView.vue'
                    ),
            },
            {
                path: 'orders',
                name: 'seller-orders',
                component: () =>
                    import(
                        '@/views/seller/orders/SellerOrderListView.vue'
                    ),
            },
            {
                path: 'orders/:id',
                name: 'seller-orders-show',
                component: () =>
                    import(
                        '@/views/seller/orders/SellerOrderDetailView.vue'
                    ),
            },
            {
                path: 'inventory',
                name: 'seller-inventory',
                component: () =>
                    import(
                        '@/views/seller/inventory/SellerInventoryView.vue'
                    ),
            },
              {
                path: 'voucher',
                name: 'seller-vouchers',
                component: () =>
                    import(
                        '@/views/seller/voucher/SellerVoucherView.vue'
                    ),
            },
        ],

    },
    {
        path: '/admin',
        component: () =>
            import('@/layouts/AdminLayout.vue'),
        meta: {
            requiresAuth: true,
            roles: ['admin'],
        },
        children: [
            {
                path: '',
                name: 'admin-dashboard',
                component: () =>
                    import(
                        '@/views/admin/AdminDashboardView.vue'
                    ),
            },
            {
                path: 'users',
                name: 'admin-users',
                component: () =>
                    import(
                        '@/views/admin/users/UserListView.vue'
                    ),
            },
            {
                path: 'users/:id',
                name: 'admin-user-detail',
                component: () =>
                    import(
                        '@/views/admin/users/UserDetailView.vue'
                    ),
                props: true,
            },
            {
                path: 'brands',
                name: 'admin-brands',
                component: () =>
                    import(
                        '@/views/admin/brands/BrandListView.vue'
                    ),
            },
            {
                path: 'categories',
                name: 'admin-categories',
                component: () =>
                    import(
                        '@/views/admin/categories/CategoryListView.vue'
                    ),
            },
            {
                path: 'vouchers',
                name: 'admin-vouchers',
                component: () =>
                    import(
                        '@/views/admin/vouchers/VoucherListView.vue'
                    ),
            },
            {
                path: 'voucher/create',
                name: 'admin.vouchers.create',
                component: () =>
                    import(
                        '@/views/admin/vouchers/VoucherCreateView.vue'
                    ),
            },
            {
                path: 'voucher/:id',
                name: 'admin.vouchers.show',
                component: () =>
                    import(
                        '@/views/admin/vouchers/VoucherDetailView.vue'
                    ),
            },
            {
                path: 'voucher/:id/edit',
                name: 'admin.vouchers.edit',
                component: () =>
                    import(
                        '@/views/admin/vouchers/VoucherEditView.vue'
                    ),
            },
            {
                path: 'products',
                name: 'admin-products',
                component: () =>
                    import(
                        '@/views/admin/products/ProductListView.vue'
                    ),
            },
            {
                path: 'products/:id',
                name: 'admin-products-show',
                component: () =>
                    import(
                        '@/views/admin/products/ProductDetailView.vue'
                    ),
            },
            {
                path: 'orders',
                name: 'admin-orders',
                component: () =>
                    import(
                        '@/views/admin/orders/OrderListView.vue'
                    ),
            },
            {
                path: 'seller-requests',
                name: 'admin-seller-requests',
                component: () =>
                    import(
                        '@/views/admin/seller-requests/SellerRequestListView.vue'
                    ),
            },

            {
                path: 'sellers',
                name: 'admin-sellers',
                component: () =>
                    import(
                        '@/views/admin/sellers/SellerListView.vue'
                    ),
            },
            {
                path: 'sellers/:id',
                name: 'admin-sellers-show',
                component: () =>
                    import(
                        '@/views/admin/sellers/SellerDetailView.vue'
                    ),
                props: true,
            },
        ],
    },

    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () =>
            import('@/views/NotFoundView.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,

    scrollBehavior() {
        return {
            top: 0,
            behavior: 'smooth',
        }
    },
})

function getStoredUser() {
    const rawUser = localStorage.getItem(
        STORAGE_KEYS.AUTH_USER,
    )

    if (!rawUser) {
        return null
    }

    try {
        return JSON.parse(rawUser)
    } catch {
        return null
    }
}

router.beforeEach((to) => {
    const authStore = useAuthStore()

    if (
        to.meta.guestOnly &&
        authStore.isAuthenticated
    ) {
        return {
            name: 'home',
        }
    }

    if (
        to.meta.requiresAuth &&
        !authStore.isAuthenticated
    ) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (
        Array.isArray(to.meta.roles) &&
        to.meta.roles.length > 0
    ) {
        const currentRole =
            String(
                authStore.user?.role ?? '',
            ).toUpperCase()

        const allowedRoles =
            to.meta.roles.map((role) => {
                return String(role).toUpperCase()
            })

        if (
            !allowedRoles.includes(
                currentRole,
            )
        ) {
            return {
                name: 'home',
            }
        }
    }

    return true
})

export default router