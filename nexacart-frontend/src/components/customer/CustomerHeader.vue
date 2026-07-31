<script setup>
import {
    ChevronDown,
    Heart,
    Menu,
    Search,
    ShoppingBag,
    UserRound,
    X,
} from '@lucide/vue'

import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
const cartStore = useCartStore()
const router = useRouter()
const authStore = useAuthStore()

const searchQuery = ref('')
const isMobileMenuOpen = ref(false)
const isAccountMenuOpen = ref(false)

const displayName = computed(() => {
    return authStore.user?.name ?? 'Tài khoản'
})

function submitSearch() {
    const keyword = searchQuery.value.trim()

    if (!keyword) {
        return
    }

    router.push({
        name: 'products',
        query: {
            search: keyword,
        },
    })
}

function toggleMobileMenu() {
    isMobileMenuOpen.value =
        !isMobileMenuOpen.value
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false
}

function toggleAccountMenu() {
    isAccountMenuOpen.value =
        !isAccountMenuOpen.value
}

async function handleLogout() {
    await authStore.logout()

    router.push('/')
}
</script>

<template>
    <header class="customer-header">
        <div class="customer-header__utility">
            <div class="customer-container utility-bar">
                <p class="utility-bar__message">
                    Mua sắm thuận tiện và minh bạch
                </p>

                <nav class="utility-bar__links" aria-label="Liên kết hỗ trợ">
                    <RouterLink to="/orders">
                        Theo dõi đơn hàng
                    </RouterLink>

                    <a href="#">
                        Hỗ trợ
                    </a>
                </nav>
            </div>
        </div>

        <div class="customer-header__main">
            <div class="customer-container header-main">
                <button type="button" class="header-main__mobile-button" aria-label="Mở danh mục điều hướng"
                    @click="toggleMobileMenu">
                    <Menu :size="22" />
                </button>

                <RouterLink to="/" class="header-logo" aria-label="Trang chủ NexaCart">
                    <span class="header-logo__mark">
                        N
                    </span>

                    <span class="header-logo__text">
                        NexaCart
                    </span>
                </RouterLink>

                <form class="header-search" role="search" @submit.prevent="submitSearch">
                    <Search :size="19" class="header-search__icon" />

                    <input v-model="searchQuery" type="search" class="header-search__input"
                        placeholder="Tìm sản phẩm, thương hiệu hoặc danh mục" aria-label="Tìm kiếm sản phẩm" />

                    <button type="submit" class="header-search__button">
                        Tìm kiếm
                    </button>
                </form>

                <div class="header-actions">
                    <RouterLink to="/wishlist" class="header-action" aria-label="Sản phẩm yêu thích">
                        <Heart :size="21" />

                        <span class="header-action__label">
                            Yêu thích
                        </span>
                    </RouterLink>

                    <div class="account-menu">
                        <button type="button" class="header-action header-action--account" aria-haspopup="menu"
                            :aria-expanded="isAccountMenuOpen" @click="toggleAccountMenu">
                            <UserRound :size="21" />

                            <span class="header-action__content">
                                <span class="header-action__caption">
                                    Xin chào
                                </span>

                                <span class="header-action__label">
                                    {{ displayName }}
                                </span>
                            </span>

                            <ChevronDown :size="16" />
                        </button>

                        <div v-if="isAccountMenuOpen" class="account-dropdown" role="menu">
                            <template v-if="authStore.isAuthenticated">
                                <RouterLink to="/profile" class="account-dropdown__item" role="menuitem" @click="
                                    isAccountMenuOpen = false
                                    ">
                                    Thông tin tài khoản
                                </RouterLink>

                                <RouterLink to="/orders" class="account-dropdown__item" role="menuitem" @click="
                                    isAccountMenuOpen = false
                                    ">
                                    Đơn hàng của tôi
                                </RouterLink>

                                <button type="button" class="account-dropdown__item" role="menuitem"
                                    @click="handleLogout">
                                    Đăng xuất
                                </button>
                            </template>

                            <template v-else>
                                <RouterLink v-if="!authStore.isAuthenticated" to="/login" class="header-login">
                                    Đăng nhập
                                </RouterLink>

                                <div v-else class="header-user">
                                    <span>
                                        {{
                                            authStore.user?.name ??
                                        'Tài khoản'
                                        }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <RouterLink to="/cart" class="header-action header-action--cart" aria-label="Giỏ hàng">
                        <span class="header-action__icon">
                            <ShoppingBag :size="22" />

                            <span v-if="cartStore.itemCount > 0" class="header-action__count">
                                {{ cartStore.itemCount }}
                            </span>
                        </span>

                        <span class="header-action__label">
                            Giỏ hàng
                        </span>
                    </RouterLink>
                </div>
            </div>
        </div>

        <div class="customer-header__navigation">
            <div class="customer-container navigation-bar">
                <nav class="desktop-navigation" aria-label="Điều hướng chính">
                    <RouterLink to="/" class="desktop-navigation__link">
                        Trang chủ
                    </RouterLink>

                    <RouterLink to="/products" class="desktop-navigation__link">
                        Sản phẩm
                    </RouterLink>

                    <RouterLink :to="{
                        name: 'products',
                        query: {
                            category: 'electronics',
                        },
                    }" class="desktop-navigation__link">
                        Điện tử
                    </RouterLink>

                    <RouterLink :to="{
                        name: 'products',
                        query: {
                            category: 'fashion',
                        },
                    }" class="desktop-navigation__link">
                        Thời trang
                    </RouterLink>

                    <RouterLink :to="{
                        name: 'products',
                        query: {
                            category: 'home-living',
                        },
                    }" class="desktop-navigation__link">
                        Nhà cửa
                    </RouterLink>

                    <RouterLink :to="{
                        name: 'products',
                        query: {
                            sort: 'newest',
                        },
                    }" class="desktop-navigation__link">
                        Hàng mới
                    </RouterLink>
                </nav>

                <RouterLink :to="{
                    name: 'products',
                    query: {
                        promotion: 1,
                    },
                }" class="navigation-bar__promotion">
                    Ưu đãi trong tuần
                </RouterLink>
            </div>
        </div>

        <div v-if="isMobileMenuOpen" class="mobile-menu">
            <button type="button" class="mobile-menu__backdrop" aria-label="Đóng điều hướng" @click="closeMobileMenu" />

            <aside class="mobile-menu__panel">
                <div class="mobile-menu__header">
                    <RouterLink to="/" class="header-logo" @click="closeMobileMenu">
                        <span class="header-logo__mark">
                            N
                        </span>

                        <span class="header-logo__text">
                            NexaCart
                        </span>
                    </RouterLink>

                    <button type="button" class="mobile-menu__close" aria-label="Đóng danh mục điều hướng"
                        @click="closeMobileMenu">
                        <X :size="22" />
                    </button>
                </div>

                <form class="mobile-search" @submit.prevent="
                    submitSearch();
                closeMobileMenu()
                    ">
                    <Search :size="18" />

                    <input v-model="searchQuery" type="search" placeholder="Tìm kiếm sản phẩm" />
                </form>

                <nav class="mobile-navigation" aria-label="Điều hướng di động">
                    <RouterLink to="/" @click="closeMobileMenu">
                        Trang chủ
                    </RouterLink>

                    <RouterLink to="/products" @click="closeMobileMenu">
                        Tất cả sản phẩm
                    </RouterLink>

                    <RouterLink to="/orders" @click="closeMobileMenu">
                        Đơn hàng của tôi
                    </RouterLink>

                    <RouterLink to="/cart" @click="closeMobileMenu">
                        Giỏ hàng
                    </RouterLink>
                </nav>
            </aside>
        </div>
    </header>
</template>

<style scoped>
.customer-header {
    position: relative;
    z-index: 30;
    background: var(--color-surface);
}

.customer-container {
    width: min(calc(100% - 48px),
            var(--content-max-width));
    margin-inline: auto;
}

.customer-header__utility {
    color: rgb(255 255 255 / 78%);
    background:#015828;;
}

.utility-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 34px;
}

.utility-bar__message {
    color: inherit;
    font-size: 12px;
}

.utility-bar__links {
    display: flex;
    align-items: center;
    gap: 24px;
    font-size: 12px;
}

.utility-bar__links a {
    transition: color var(--transition-fast);
}

.utility-bar__links a:hover {
    color: var(--color-white);
}

.customer-header__main {
    border-bottom: 1px solid var(--color-border);
}

.header-main {
    display: grid;
    grid-template-columns:
        auto minmax(320px, 1fr) auto;
    align-items: center;
    gap: 40px;
    min-height: 84px;
}

.header-main__mobile-button {
    display: none;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    padding: 0;
    color: var(--color-text-primary);
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.header-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    width: fit-content;
}

.header-logo__mark {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border-radius: 10px;
    font-size: 17px;
    font-weight: 700;
}

.header-logo__text {
    color: var(--color-text-primary);
    font-size: 21px;
    font-weight: 700;
    letter-spacing: -0.035em;
}

.header-search {
    display: grid;
    grid-template-columns:
        auto 1fr auto;
    align-items: center;
    min-height: 48px;
    padding-left: 16px;
    background: var(--color-gray-50);
    border: 1px solid var(--color-border-strong);
    border-radius: 12px;
    transition:
        background-color var(--transition-fast),
        border-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.header-search:focus-within {
    background: var(--color-white);
    border-color: var(--color-primary-500);
    box-shadow:
        0 0 0 4px rgb(137 87 243 / 10%);
}

.header-search__icon {
    color: var(--color-text-muted);
}

.header-search__input {
    width: 100%;
    height: 46px;
    padding: 0 14px;
    color: var(--color-text-primary);
    background: transparent;
    border: 0;
    outline: 0;
}

.header-search__input::placeholder {
    color: var(--color-text-muted);
}

.header-search__button {
    align-self: stretch;
    min-width: 104px;
    padding-inline: 18px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 0;
    border-radius: 0 10px 10px 0;
    font-size: 13px;
    font-weight: 600;
    transition:
        background-color var(--transition-fast);
}

.header-search__button:hover {
    background: var(--color-primary-800);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-action {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 44px;
    padding: 0 10px;
    color: var(--color-text-secondary);
    background: transparent;
    border: 0;
    border-radius: var(--radius-md);
    transition:
        color var(--transition-fast),
        background-color var(--transition-fast);
}

.header-action:hover {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
}

.header-action__content {
    display: grid;
    gap: 1px;
    text-align: left;
}

.header-action__caption {
    color: var(--color-text-muted);
    font-size: 11px;
    line-height: 1;
}

.header-action__label {
    color: inherit;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

.header-action__icon {
    position: relative;
    display: inline-flex;
}

.header-action__count {
    position: absolute;
    top: -9px;
    right: -10px;
    display: grid;
    place-items: center;
    min-width: 18px;
    height: 18px;
    padding-inline: 4px;
    color: var(--color-white);
    background: var(--color-danger);
    border: 2px solid var(--color-white);
    border-radius: var(--radius-pill);
    font-size: 10px;
    font-weight: 700;
}

.account-menu {
    position: relative;
}

.account-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    display: grid;
    width: 220px;
    padding: 8px;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
}

.account-dropdown__item {
    width: 100%;
    padding: 11px 12px;
    color: var(--color-text-secondary);
    background: transparent;
    border: 0;
    border-radius: var(--radius-md);
    font-size: 13px;
    text-align: left;
}

.account-dropdown__item:hover {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
}

.customer-header__navigation {
    border-bottom: 1px solid var(--color-border);
}

.navigation-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 48px;
}

.desktop-navigation {
    display: flex;
    align-items: stretch;
    min-height: 48px;
}

.desktop-navigation__link {
    position: relative;
    display: inline-flex;
    align-items: center;
    padding-inline: 17px;
    color: var(--color-text-secondary);
    font-size: 13px;
    font-weight: 500;
}

.desktop-navigation__link::after {
    position: absolute;
    right: 17px;
    bottom: -1px;
    left: 17px;
    height: 2px;
    background: var(--color-primary-600);
    content: '';
    opacity: 0;
    transform: scaleX(0.6);
    transition:
        opacity var(--transition-fast),
        transform var(--transition-fast);
}

.desktop-navigation__link:hover,
.desktop-navigation__link.router-link-exact-active {
    color: var(--color-primary-700);
}

.desktop-navigation__link.router-link-exact-active::after {
    opacity: 1;
    transform: scaleX(1);
}

.navigation-bar__promotion {
    color: var(--color-primary-700);
    font-size: 13px;
    font-weight: 600;
}

.mobile-menu {
    position: fixed;
    inset: 0;
    z-index: 100;
}

.mobile-menu__backdrop {
    position: absolute;
    inset: 0;
    width: 100%;
    padding: 0;
    background: rgb(20 14 28 / 44%);
    border: 0;
}

.mobile-menu__panel {
    position: relative;
    display: grid;
    align-content: start;
    gap: 24px;
    width: min(88%, 340px);
    height: 100%;
    padding: 22px;
    background: var(--color-white);
    box-shadow: var(--shadow-md);
}

.mobile-menu__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mobile-menu__close {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    padding: 0;
    color: var(--color-text-primary);
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.mobile-search {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 10px;
    min-height: 44px;
    padding-inline: 13px;
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.mobile-search input {
    width: 100%;
    height: 42px;
    background: transparent;
    border: 0;
    outline: 0;
}

.mobile-navigation {
    display: grid;
}

.mobile-navigation a {
    padding-block: 14px;
    color: var(--color-text-secondary);
    border-bottom: 1px solid var(--color-border);
    font-size: 14px;
    font-weight: 500;
}

.mobile-navigation a.router-link-exact-active {
    color: var(--color-primary-700);
}

@media (max-width: 1180px) {
    .header-main {
        gap: 24px;
    }

    .header-action__label,
    .header-action__content,
    .header-action--account>svg:last-child {
        display: none;
    }

    .header-action {
        width: 42px;
        justify-content: center;
        padding: 0;
    }
}

@media (max-width: 860px) {

    .customer-header__utility,
    .customer-header__navigation {
        display: none;
    }

    .customer-container {
        width: min(calc(100% - 32px), 100%);
    }

    .header-main {
        grid-template-columns:
            auto 1fr auto;
        gap: 14px;
        min-height: 68px;
    }

    .header-main__mobile-button {
        display: inline-flex;
    }

    .header-search {
        display: none;
    }

    .header-logo {
        justify-self: center;
    }

    .header-actions {
        gap: 2px;
    }

    .header-action:not(.header-action--cart),
    .account-menu {
        display: none;
    }
}

@media (max-width: 420px) {
    .header-logo__text {
        display: none;
    }
}
</style>