<script setup>
import {
    ChevronDown,
    Clock3,
    Flame,
    Grid2X2,
    Heart,
    LogOut,
    MapPin,
    Menu,
    Package,
    Search,
    Settings,
    ShoppingCart,
    Store,
    Tag,
    TicketPercent,
    UserRound,
    X,
} from '@lucide/vue'

import {
    computed,
    ref,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    useAuthStore,
} from '@/stores/auth'

import {
    useCartStore,
} from '@/stores/cart'

const router = useRouter()

const authStore = useAuthStore()
const cartStore = useCartStore()

const searchQuery = ref('')

const isSearchFocused =
    ref(false)

const isCategoryOpen =
    ref(false)

const isAccountOpen =
    ref(false)

const isMobileOpen =
    ref(false)

const recentSearches = ref([
    'laptop gaming',
    'iphone 15',
    'tai nghe bluetooth',
])

const trendingKeywords = [
    'Laptop',
    'Điện thoại',
    'Tai nghe',
    'Đồng hồ',
]

const categories = [
    {
        name: 'Điện thoại',
        slug: 'dien-thoai',
    },
    {
        name: 'Laptop',
        slug: 'laptop',
    },
    {
        name: 'Phụ kiện',
        slug: 'phu-kien',
    },
    {
        name: 'Gia dụng',
        slug: 'gia-dung',
    },
]

const displayName =
    computed(() => {
        return (
            authStore.user?.name ??
            'Tài khoản'
        )
    })

const displayEmail =
    computed(() => {
        return (
            authStore.user?.email ??
            ''
        )
    })

const avatarLetter =
    computed(() => {
        return (
            displayName.value
                .trim()
                .charAt(0)
                .toUpperCase() ||
            'U'
        )
    })

const cartCount =
    computed(() => {
        return Number(
            cartStore.itemCount ??
            0,
        )
    })

function submitSearch() {
    const keyword =
        searchQuery.value.trim()

    if (!keyword) {
        return
    }

    addRecentSearch(
        keyword,
    )

    isSearchFocused.value =
        false

    router.push({
        name: 'products',

        query: {
            keyword,
        },
    })
}

function searchKeyword(
    keyword,
) {
    searchQuery.value =
        keyword

    submitSearch()
}

function addRecentSearch(
    keyword,
) {
    recentSearches.value = [
        keyword,

        ...recentSearches.value.filter(
            item => {
                return (
                    item !==
                    keyword
                )
            },
        ),
    ].slice(
        0,
        5,
    )
}

function removeRecentSearch(
    keyword,
) {
    recentSearches.value =
        recentSearches.value.filter(
            item => {
                return (
                    item !==
                    keyword
                )
            },
        )
}

function clearRecentSearches() {
    recentSearches.value = []
}

function toggleCategory() {
    isCategoryOpen.value =
        !isCategoryOpen.value

    isAccountOpen.value =
        false
}

function toggleAccount() {
    isAccountOpen.value =
        !isAccountOpen.value

    isCategoryOpen.value =
        false
}

function goCategory(
    slug,
) {
    isCategoryOpen.value =
        false

    router.push({
        name: 'products',

        query: {
            category: slug,
        },
    })
}

function closeMobileMenu() {
    isMobileOpen.value =
        false
}

async function handleLogout() {
    await authStore.logout()

    isAccountOpen.value =
        false

    router.push('/')
}
</script>

<template>
    <header class="customer-header">
        <!-- TOP INFO -->
        <div class="top-bar">
            <div class="header-container top-bar__inner">
                <div class="top-bar__item">
                    <ShoppingCart
                        :size="15"
                    />

                    <span>
                        Miễn phí vận chuyển
                        cho đơn đủ điều kiện
                    </span>
                </div>

                <div class="top-bar__item">
                    <Store
                        :size="15"
                    />

                    <span>
                        Sản phẩm từ nhiều
                        nhà bán hàng
                    </span>
                </div>

                <div class="top-bar__item">
                    <Package
                        :size="15"
                    />

                    <span>
                        Theo dõi đơn hàng
                        trực tuyến
                    </span>
                </div>
            </div>
        </div>

        <!-- MAIN HEADER -->
        <div class="main-header">
            <div class="header-container main-header__inner">
                <!-- MOBILE BUTTON -->
                <button
                    type="button"
                    class="mobile-menu-button"
                    aria-label="Mở menu"
                    @click="
                        isMobileOpen =
                            true
                    "
                >
                    <Menu
                        :size="22"
                    />
                </button>

                <!-- LOGO -->
                <RouterLink
                    to="/"
                    class="logo"
                >
                    <span class="logo__mark">
                        <ShoppingCart
                            :size="34"
                            stroke-width="2.3"
                        />

                        <strong>
                            N
                        </strong>

                        <i />
                    </span>

                    <span class="logo__content">
                        <span class="logo__name">
                            Nexa<span>
                                Cart
                            </span>
                        </span>

                        <small>
                            SHOP SMART
                        </small>
                    </span>
                </RouterLink>

                <!-- CATEGORY -->
                <div class="category">
                   
                       
             

                   
                </div>

                <!-- SEARCH -->
                <div class="search-area">
                    <form
                        class="search-box"
                        @submit.prevent="
                            submitSearch
                        "
                    >
                        <Search
                            :size="20"
                        />

                        <input
                            v-model="
                                searchQuery
                            "
                            type="search"
                            placeholder="Tìm kiếm sản phẩm, thương hiệu, danh mục..."
                            @focus="
                                isSearchFocused =
                                    true
                            "
                        >

                        <button
                            type="submit"
                            class="search-box__submit"
                        >
                            <Search
                                :size="17"
                            />

                            <span>
                                Tìm kiếm
                            </span>
                        </button>
                    </form>

                    <!-- TRENDING -->
                    <div class="trending">
                        <span class="trending__title">
                            <Flame
                                :size="14"
                            />

                            Xu hướng:
                        </span>

                        <button
                            v-for="
                                keyword in
                                trendingKeywords
                            "
                            :key="keyword"
                            type="button"
                            @click="
                                searchKeyword(
                                    keyword,
                                )
                            "
                        >
                            {{ keyword }}
                        </button>
                    </div>

                    <!-- SEARCH DROPDOWN -->
                    <Transition name="dropdown">
                        <div
                            v-if="
                                isSearchFocused
                            "
                            class="search-dropdown"
                        >
                            <div class="recent-search">
                                <div class="recent-search__header">
                                    <strong>
                                        <Clock3
                                            :size="18"
                                        />

                                        Tìm kiếm gần đây
                                    </strong>

                                    <button
                                        v-if="
                                            recentSearches
                                                .length
                                        "
                                        type="button"
                                        @click="
                                            clearRecentSearches
                                        "
                                    >
                                        Xóa tất cả
                                    </button>
                                </div>

                                <div
                                    v-if="
                                        recentSearches
                                            .length
                                    "
                                    class="recent-search__list"
                                >
                                    <div
                                        v-for="
                                            keyword in
                                            recentSearches
                                        "
                                        :key="keyword"
                                        class="recent-search__item"
                                    >
                                        <button
                                            type="button"
                                            class="recent-search__keyword"
                                            @click="
                                                searchKeyword(
                                                    keyword,
                                                )
                                            "
                                        >
                                            <Clock3
                                                :size="16"
                                            />

                                            {{
                                                keyword
                                            }}
                                        </button>

                                        <button
                                            type="button"
                                            class="recent-search__remove"
                                            @click="
                                                removeRecentSearch(
                                                    keyword,
                                                )
                                            "
                                        >
                                            <X
                                                :size="15"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="recent-search__empty"
                                >
                                    Chưa có tìm kiếm
                                    gần đây.
                                </p>
                            </div>

                            <div class="search-offer">
                                <span>
                                    ƯU ĐÃI HÔM NAY
                                </span>

                                <strong>
                                    Săn sản phẩm
                                    đang giảm giá
                                </strong>

                                <p>
                                    Khám phá những
                                    sản phẩm đang có
                                    mức giá tốt.
                                </p>

                                <RouterLink
                                    :to="{
                                        name:
                                            'products',

                                        query: {
                                            promotion:
                                                1,
                                        },
                                    }"
                                    @click="
                                        isSearchFocused =
                                            false
                                    "
                                >
                                    Xem ưu đãi
                                </RouterLink>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- RIGHT ACTIONS -->
                <div class="main-actions">
                    <RouterLink
                        to="/wishlist"
                        class="action-item"
                    >
                        <Heart
                            :size="22"
                        />

                        <span>
                            Yêu thích
                        </span>
                    </RouterLink>

                    <RouterLink
                        to="/cart"
                        class="action-item"
                    >
                        <span class="action-item__icon">
                            <ShoppingCart
                                :size="23"
                            />

                            <b
                                v-if="
                                    cartCount > 0
                                "
                            >
                                {{
                                    cartCount
                                }}
                            </b>
                        </span>

                        <span>
                            Giỏ hàng
                        </span>
                    </RouterLink>

                    <!-- ACCOUNT -->
                    <div class="account">
                        <button
                            type="button"
                            class="account__button"
                            @click="
                                toggleAccount
                            "
                        >
                            <span class="avatar">
                                {{
                                    avatarLetter
                                }}
                            </span>

                            <span class="account__text">
                                <small>
                                    Xin chào
                                </small>

                                <strong>
                                    {{
                                        displayName
                                    }}
                                </strong>
                            </span>

                            <ChevronDown
                                :size="15"
                            />
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="
                                    isAccountOpen
                                "
                                class="account-dropdown"
                            >
                                <template
                                    v-if="
                                        authStore
                                            .isAuthenticated
                                    "
                                >
                                    <div class="account-dropdown__profile">
                                        <span class="avatar avatar--large">
                                            {{
                                                avatarLetter
                                            }}
                                        </span>

                                        <div>
                                            <strong>
                                                {{
                                                    displayName
                                                }}
                                            </strong>

                                            <span>
                                                {{
                                                    displayEmail
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="divider" />

                                    <RouterLink
                                        to="/account"
                                        @click="
                                            isAccountOpen =
                                                false
                                        "
                                    >
                                        <UserRound
                                            :size="18"
                                        />

                                        Hồ sơ cá nhân
                                    </RouterLink>

                                    <RouterLink
                                        to="/orders"
                                        @click="
                                            isAccountOpen =
                                                false
                                        "
                                    >
                                        <Package
                                            :size="18"
                                        />

                                        Đơn hàng của tôi
                                    </RouterLink>

                                    <RouterLink
                                        to="/wishlist"
                                        @click="
                                            isAccountOpen =
                                                false
                                        "
                                    >
                                        <Heart
                                            :size="18"
                                        />

                                        Sản phẩm yêu thích
                                    </RouterLink>

                                    <RouterLink
                                        to="/addresses"
                                        @click="
                                            isAccountOpen =
                                                false
                                        "
                                    >
                                        <MapPin
                                            :size="18"
                                        />

                                        Địa chỉ nhận hàng
                                    </RouterLink>

                                    <RouterLink
                                        to="/account"
                                        @click="
                                            isAccountOpen =
                                                false
                                        "
                                    >
                                        <Settings
                                            :size="18"
                                        />

                                        Cài đặt
                                    </RouterLink>

                                    <div class="divider" />

                                    <button
                                        type="button"
                                        class="logout-button"
                                        @click="
                                            handleLogout
                                        "
                                    >
                                        <LogOut
                                            :size="18"
                                        />

                                        Đăng xuất
                                    </button>
                                </template>

                                <template v-else>
                                    <div class="guest-account">
                                        <strong>
                                            Chào mừng đến NexaCart
                                        </strong>

                                        <p>
                                            Đăng nhập để quản lý
                                            đơn hàng và wishlist.
                                        </p>

                                        <RouterLink
                                            to="/login"
                                            @click="
                                                isAccountOpen =
                                                    false
                                            "
                                        >
                                            Đăng nhập
                                        </RouterLink>

                                        <RouterLink
                                            to="/register"
                                            class="guest-account__register"
                                            @click="
                                                isAccountOpen =
                                                    false
                                            "
                                        >
                                            Đăng ký
                                        </RouterLink>
                                    </div>
                                </template>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>

        <!-- NAV -->
        <div class="main-nav">
            <div class="header-container main-nav__inner">
                <RouterLink
                    to="/"
                    class="main-nav__item"
                >
                    Trang chủ
                </RouterLink>

                <RouterLink
                    :to="{
                        name:
                            'products',
                    }"
                    class="main-nav__item"
                >
                    <Grid2X2
                        :size="17"
                    />

                    Sản phẩm
                </RouterLink>

            </div>
        </div>

        <!-- SEARCH BACKDROP -->
        <button
            v-if="
                isSearchFocused
            "
            type="button"
            class="search-backdrop"
            aria-label="Đóng tìm kiếm"
            @click="
                isSearchFocused =
                    false
            "
        />

        <!-- MOBILE MENU -->
        <Transition name="mobile">
            <div
                v-if="
                    isMobileOpen
                "
                class="mobile-menu"
            >
                <button
                    type="button"
                    class="mobile-menu__backdrop"
                    aria-label="Đóng menu"
                    @click="
                        closeMobileMenu
                    "
                />

                <aside class="mobile-menu__panel">
                    <div class="mobile-menu__header">
                        <RouterLink
                            to="/"
                            class="logo"
                            @click="
                                closeMobileMenu
                            "
                        >
                            <span class="logo__mark">
                                <ShoppingCart
                                    :size="31"
                                />

                                <strong>
                                    N
                                </strong>
                            </span>

                            <span class="logo__content">
                                <span class="logo__name">
                                    Nexa<span>
                                        Cart
                                    </span>
                                </span>
                            </span>
                        </RouterLink>

                        <button
                            type="button"
                            aria-label="Đóng menu"
                            @click="
                                closeMobileMenu
                            "
                        >
                            <X
                                :size="22"
                            />
                        </button>
                    </div>

                    <form
                        class="mobile-search"
                        @submit.prevent="
                            submitSearch
                        "
                    >
                        <Search
                            :size="19"
                        />

                        <input
                            v-model="
                                searchQuery
                            "
                            type="search"
                            placeholder="Tìm kiếm sản phẩm..."
                        >
                    </form>

                    <nav class="mobile-nav">
                        <RouterLink
                            to="/"
                            @click="
                                closeMobileMenu
                            "
                        >
                            Trang chủ
                        </RouterLink>

                        <RouterLink
                            to="/products"
                            @click="
                                closeMobileMenu
                            "
                        >
                            Sản phẩm
                        </RouterLink>

                        <RouterLink
                            to="/wishlist"
                            @click="
                                closeMobileMenu
                            "
                        >
                            Yêu thích
                        </RouterLink>

                        <RouterLink
                            to="/orders"
                            @click="
                                closeMobileMenu
                            "
                        >
                            Đơn hàng
                        </RouterLink>

                        <RouterLink
                            to="/cart"
                            @click="
                                closeMobileMenu
                            "
                        >
                            Giỏ hàng
                        </RouterLink>
                    </nav>
                </aside>
            </div>
        </Transition>
    </header>
</template>

<style scoped>
.customer-header {
    --green: #087c3d;
    --green-dark: #056331;
    --green-light: #edf7f1;

    --text: #202a24;
    --secondary: #58655d;
    --muted: #8b968f;
    --border: #dce5df;

    position: sticky;
    top: 0;
    z-index: 50;

    color: var(--text);
    background: #ffffff;

    font-family:
        Roboto,
        Arial,
        sans-serif;
}

/* =====================================
   GENERAL
===================================== */

.header-container {
    width: min(
        calc(100% - 48px),
        1320px
    );

    margin: 0 auto;
}

button,
input {
    font-family: inherit;
}

/* =====================================
   TOP BAR
===================================== */

.top-bar {
    border-bottom:
        1px solid #dfeae3;

    background: #edf7f1;
}

.top-bar__inner {
    display: flex;

    min-height: 36px;

    align-items: center;
    justify-content: space-between;

    gap: 30px;
}

.top-bar__item {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #557061;

    font-size: 12px;
}

.top-bar__item svg {
    flex: 0 0 auto;

    color: var(--green);
}

/* =====================================
   MAIN HEADER
===================================== */

.main-header {
    position: relative;

    border-bottom:
        1px solid var(--border);

    background: #ffffff;
}

.main-header__inner {
    display: grid;

    grid-template-columns:
        165px
        112px
        minmax(400px, 1fr)
        auto;

    align-items: start;

    gap: 18px;

    min-height: 84px;

    padding-top: 10px;
}

/*
 * Đây là phần quan trọng để tất cả
 * logo/category/search/action thẳng hàng.
 */

.logo,
.category,
.main-actions {
    height: 46px;

    align-self: start;
}

/* =====================================
   LOGO
===================================== */

.logo {
    display: inline-flex;

    align-items: center;

    gap: 8px;

    color: inherit;

    text-decoration: none;
}

.logo__mark {
    position: relative;

    display: grid;

    width: 42px;
    height: 42px;

    place-items: center;

    color: var(--green);
}

.logo__mark strong {
    position: absolute;

    right: 0;
    bottom: 0;

    display: grid;

    width: 25px;
    height: 23px;

    place-items: center;

    color: #ffffff;

    background: var(--green);

    font-size: 14px;
    font-style: italic;
    font-weight: 800;

    transform:
        rotate(-4deg);
}

.logo__mark i {
    position: absolute;

    top: 0;
    right: 6px;

    width: 9px;
    height: 6px;

    background: #0ba04d;

    transform:
        skewX(-25deg)
        rotate(-18deg);
}

.logo__content {
    display: grid;
}

.logo__name {
    color: #172019;

    font-size: 21px;
    font-weight: 800;

    letter-spacing:
        -0.04em;
}

.logo__name span {
    color: var(--green);
}

.logo__content small {
    margin-top: 1px;

    color: #909b94;

    font-size: 9px;

    font-weight: 600;

    letter-spacing:
        0.14em;
}

/* =====================================
   CATEGORY
===================================== */

.category {
    position: relative;

    display: flex;

    align-items: center;
}

.category__button {
    display: inline-flex;

    width: 112px;
    height: 46px;

    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 0;

    border:
        1px solid
        #a8cdb7;

    border-radius: 0;

    color: #344238;

    background: #ffffff;

    font-size: 13px;
    font-weight: 600;

    cursor: pointer;
}

.category__button
svg:first-child {
    color: var(--green);
}

.category__dropdown {
    position: absolute;

    top: 54px;
    left: 0;

    z-index: 60;

    width: 210px;

    padding: 6px;

    border:
        1px solid var(--border);

    background: #ffffff;

    box-shadow:
        0 12px 30px
        rgb(29 59 39 / 10%);
}

.category__dropdown button {
    display: flex;

    width: 100%;
    min-height: 42px;

    align-items: center;

    gap: 9px;

    padding: 0 11px;

    border: 0;
    border-radius: 0;

    color: #536058;

    background: #ffffff;

    font-size: 13px;

    cursor: pointer;
}

.category__dropdown button:hover {
    color: var(--green);

    background:
        var(--green-light);
}

/* =====================================
   SEARCH
===================================== */

.search-area {
    position: relative;

    min-width: 0;
}

.search-box {
    display: grid;

    grid-template-columns:
        38px
        minmax(0, 1fr)
        110px;

    height: 46px;

    align-items: center;

    border:
        1px solid
        #99c6aa;

    border-radius: 0;

    background: #ffffff;
}

.search-box:focus-within {
    border-color: var(--green);

    box-shadow:
        inset 0 0 0 1px
        var(--green);
}

.search-box > svg {
    justify-self: center;

    color: #7c8981;
}

.search-box input {
    width: 100%;
    height: 44px;

    padding: 0 5px;

    border: 0;
    outline: 0;

    color: #29362e;

    background: transparent;

    font-size: 14px;
}

.search-box input::placeholder {
    color: #9da6a0;
}

.search-box__submit {
    display: flex;

    height: 44px;

    align-items: center;
    justify-content: center;

    gap: 6px;

    padding: 0;

    border: 0;
    border-radius: 0;

    color: #ffffff;

    background: var(--green);

    font-size: 13px;
    font-weight: 600;

    cursor: pointer;
}

.search-box__submit:hover {
    background:
        var(--green-dark);
}

/* TRENDING */

.trending {
    display: flex;

    height: 25px;

    align-items: center;

    gap: 7px;

    overflow: hidden;

    margin-top: 2px;
}

.trending__title {
    display: inline-flex;

    flex: 0 0 auto;

    align-items: center;

    gap: 4px;

    color: #414f46;

    font-size: 12px;
    font-weight: 600;
}

.trending__title svg {
    color: #ec5d33;
}

.trending > button {
    flex: 0 0 auto;

    padding:
        2px
        7px;

    border: 0;
    border-radius: 0;

    color: #148047;

    background: #edf7f1;

    font-size: 12px;

    cursor: pointer;
}

/* =====================================
   SEARCH DROPDOWN
===================================== */

.search-dropdown {
    position: absolute;

    top: 56px;
    right: 0;
    left: 0;

    z-index: 70;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        210px;

    min-width: 580px;

    border:
        1px solid var(--border);

    background: #ffffff;

    box-shadow:
        0 16px 38px
        rgb(29 59 39 / 12%);
}

.recent-search {
    padding: 20px;
}

.recent-search__header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding-bottom: 12px;

    border-bottom:
        1px solid #e8eeea;
}

.recent-search__header strong {
    display: inline-flex;

    align-items: center;

    gap: 8px;

    font-size: 14px;
}

.recent-search__header
> button {
    padding: 0;

    border: 0;

    color: var(--green);

    background: transparent;

    font-size: 12px;

    cursor: pointer;
}

.recent-search__list {
    margin-top: 5px;
}

.recent-search__item {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        34px;

    min-height: 42px;

    align-items: center;
}

.recent-search__keyword {
    display: flex;

    height: 42px;

    align-items: center;

    gap: 9px;

    padding: 0;

    border: 0;

    color: #59665e;

    background: transparent;

    font-size: 13px;

    text-align: left;

    cursor: pointer;
}

.recent-search__remove {
    display: grid;

    width: 34px;
    height: 34px;

    place-items: center;

    padding: 0;

    border: 0;

    color: #87938b;

    background: transparent;

    cursor: pointer;
}

.recent-search__empty {
    color: var(--muted);

    font-size: 13px;
}

/* SEARCH OFFER */

.search-offer {
    display: flex;

    flex-direction: column;

    justify-content: center;

    padding: 20px;

    border-left:
        1px solid var(--border);

    background: #eaf7ef;
}

.search-offer > span {
    color: var(--green);

    font-size: 11px;
    font-weight: 700;

    letter-spacing:
        0.08em;
}

.search-offer strong {
    margin-top: 8px;

    color: #175f37;

    font-size: 17px;

    line-height: 1.35;
}

.search-offer p {
    margin: 8px 0 0;

    color: #688074;

    font-size: 12px;

    line-height: 1.55;
}

.search-offer a {
    display: inline-flex;

    width: fit-content;
    min-height: 37px;

    align-items: center;

    margin-top: 15px;

    padding: 0 14px;

    color: #ffffff;

    background:
        var(--green-dark);

    font-size: 12px;
    font-weight: 600;

    text-decoration: none;
}

/* =====================================
   ACTIONS
===================================== */

.main-actions {
    display: flex;

    align-items: center;

    gap: 1px;
}

.action-item {
    display: flex;

    width: 60px;
    height: 46px;

    align-items: center;
    justify-content: center;

    flex-direction: column;

    gap: 3px;

    color: #58655d;

    text-decoration: none;
}

.action-item:hover {
    color: var(--green);

    background:
        #f3f8f5;
}

.action-item > span:last-child {
    font-size: 12px;
}

.action-item__icon {
    position: relative;
}

.action-item__icon b {
    position: absolute;

    top: -9px;
    right: -11px;

    display: grid;

    min-width: 18px;
    height: 18px;

    place-items: center;

    padding: 0 3px;

    border:
        2px solid #ffffff;

    border-radius: 50%;

    color: #ffffff;

    background: #e93341;

    font-size: 10px;
}

/* =====================================
   ACCOUNT
===================================== */

.account {
    position: relative;

    height: 46px;
}

.account__button {
    display: grid;

    grid-template-columns:
        36px
        minmax(90px, 1fr)
        16px;

    height: 46px;

    align-items: center;

    gap: 8px;

    padding:
        0
        6px
        0
        10px;

    border: 0;
    border-left:
        1px solid var(--border);

    border-radius: 0;

    color: #3d4a42;

    background: #ffffff;

    cursor: pointer;
}

.account__button:hover {
    background:
        #f6f9f7;
}

.avatar {
    display: grid;

    width: 36px;
    height: 36px;

    place-items: center;

    border-radius: 50%;

    color: #ffffff;

    background: var(--green);

    font-size: 14px;
    font-weight: 700;
}

.avatar--large {
    width: 44px;
    height: 44px;

    font-size: 16px;
}

.account__text {
    display: grid;

    min-width: 0;

    text-align: left;
}

.account__text small {
    color: var(--muted);

    font-size: 11px;
}

.account__text strong {
    max-width: 110px;

    overflow: hidden;

    margin-top: 1px;

    color: #26342b;

    font-size: 13px;

    text-overflow: ellipsis;

    white-space: nowrap;
}

/* ACCOUNT DROPDOWN */

.account-dropdown {
    position: absolute;

    top: 55px;
    right: 0;

    z-index: 70;

    width: 275px;

    padding: 8px;

    border:
        1px solid var(--border);

    background: #ffffff;

    box-shadow:
        0 16px 38px
        rgb(29 59 39 / 12%);
}

.account-dropdown__profile {
    display: grid;

    grid-template-columns:
        auto
        minmax(0, 1fr);

    align-items: center;

    gap: 11px;

    padding: 10px;
}

.account-dropdown__profile
strong,
.account-dropdown__profile
span {
    display: block;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.account-dropdown__profile
strong {
    font-size: 14px;
}

.account-dropdown__profile
span {
    margin-top: 3px;

    color: var(--muted);

    font-size: 12px;
}

.divider {
    height: 1px;

    margin: 6px 4px;

    background: #e8eeea;
}

.account-dropdown > a,
.logout-button {
    display: flex;

    width: 100%;
    min-height: 43px;

    align-items: center;

    gap: 10px;

    padding:
        0
        11px;

    border: 0;
    border-radius: 0;

    color: #58665d;

    background: transparent;

    font-size: 13px;

    text-align: left;

    text-decoration: none;

    cursor: pointer;
}

.account-dropdown > a:hover {
    color: var(--green);

    background:
        var(--green-light);
}

.logout-button {
    color: #c83e45;
}

.logout-button:hover {
    background: #fff2f2;
}

/* GUEST */

.guest-account {
    display: grid;

    gap: 10px;

    padding: 11px;
}

.guest-account strong {
    font-size: 14px;
}

.guest-account p {
    margin: 0;

    color: #7b8780;

    font-size: 12px;

    line-height: 1.55;
}

.guest-account a {
    display: grid;

    min-height: 40px;

    place-items: center;

    color: #ffffff;

    background: var(--green);

    font-size: 13px;
    font-weight: 600;

    text-decoration: none;
}

.guest-account
.guest-account__register {
    border:
        1px solid var(--green);

    color: var(--green);

    background: #ffffff;
}

/* =====================================
   NAVIGATION
===================================== */

.main-nav {
    border-bottom:
        1px solid var(--border);

    background: #ffffff;
}

.main-nav__inner {
    display: flex;

    min-height: 48px;

    align-items: stretch;
}

.main-nav__item {
    position: relative;

    display: inline-flex;

    min-height: 48px;

    align-items: center;

    gap: 7px;

    padding:
        0
        18px;

    color: #58655d;

    font-size: 14px;
    font-weight: 500;

    text-decoration: none;
}

.main-nav__item:hover {
    color: var(--green);

    background:
        #f4f8f5;
}

.main-nav__item
.router-link-exact-active {
    color: var(--green);
}

.main-nav__item.router-link-exact-active::after {
    position: absolute;

    right: 18px;
    bottom: -1px;
    left: 18px;

    height: 2px;

    background: var(--green);

    content: '';
}

.hot-badge {
    padding:
        2px
        5px;

    color: #ffffff;

    background: #eb3340;

    font-size: 10px;
}

/* =====================================
   SEARCH BACKDROP
===================================== */

.search-backdrop {
    position: fixed;

    inset: 0;

    z-index: -1;

    width: 100%;

    padding: 0;

    border: 0;

    background:
        rgb(18 41 26 / 8%);
}

/* =====================================
   MOBILE
===================================== */

.mobile-menu-button {
    display: none;

    width: 40px;
    height: 40px;

    place-items: center;

    padding: 0;

    border:
        1px solid var(--border);

    border-radius: 0;

    color: var(--text);

    background: #ffffff;
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

    border: 0;

    background:
        rgb(16 31 21 / 46%);
}

.mobile-menu__panel {
    position: relative;

    display: grid;

    align-content: start;

    gap: 22px;

    width:
        min(
            88%,
            360px
        );

    height: 100%;

    padding: 20px;

    background: #ffffff;
}

.mobile-menu__header {
    display: flex;

    align-items: center;
    justify-content:
        space-between;
}

.mobile-menu__header
> button {
    display: grid;

    width: 40px;
    height: 40px;

    place-items: center;

    padding: 0;

    border:
        1px solid var(--border);

    border-radius: 0;

    background: #ffffff;
}

.mobile-search {
    display: grid;

    grid-template-columns:
        40px
        minmax(0, 1fr);

    height: 46px;

    align-items: center;

    border:
        1px solid #aacdb8;
}

.mobile-search svg {
    justify-self: center;

    color: #78867d;
}

.mobile-search input {
    width: 100%;
    height: 44px;

    border: 0;
    outline: 0;

    font-size: 14px;
}

.mobile-nav {
    display: grid;
}

.mobile-nav a {
    display: flex;

    min-height: 49px;

    align-items: center;

    border-bottom:
        1px solid #e8eeea;

    color: #526057;

    font-size: 14px;
    font-weight: 500;

    text-decoration: none;
}

/* =====================================
   TRANSITION
===================================== */

.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        opacity 130ms ease,
        transform 130ms ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;

    transform:
        translateY(-5px);
}

.mobile-enter-active,
.mobile-leave-active {
    transition:
        opacity 160ms ease;
}

.mobile-enter-from,
.mobile-leave-to {
    opacity: 0;
}

/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 1180px) {
    .main-header__inner {
        grid-template-columns:
            160px
            minmax(360px, 1fr)
            auto;

        gap: 15px;
    }

    .category {
        display: none;
    }

    .account__text,
    .account__button > svg {
        display: none;
    }

    .account__button {
        grid-template-columns:
            36px;

        width: 48px;

        padding:
            0
            0
            0
            8px;
    }
}

@media (max-width: 850px) {
    .top-bar,
    .main-nav {
        display: none;
    }

    .header-container {
        width:
            calc(
                100% -
                28px
            );
    }

    .main-header__inner {
        grid-template-columns:
            42px
            1fr
            auto;

        align-items: center;

        min-height: 68px;

        padding-top: 0;

        gap: 10px;
    }

    .mobile-menu-button {
        display: grid;
    }

    .logo {
        justify-self: center;
    }

    .search-area {
        display: none;
    }

    .main-actions
    > .action-item:first-child,
    .account {
        display: none;
    }

    .main-actions {
        height: auto;
    }

    .action-item {
        width: 44px;
        height: 44px;
    }

    .action-item
    > span:last-child {
        display: none;
    }
}

@media (max-width: 430px) {
    .logo__content small {
        display: none;
    }

    .logo__name {
        font-size: 20px;
    }

    .logo__mark {
        width: 39px;
    }
}
</style>