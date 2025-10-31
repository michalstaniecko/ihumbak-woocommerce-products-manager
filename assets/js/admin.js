/**
 * Admin JavaScript for iHumbak WooCommerce Products Manager
 */

(function() {
    'use strict';

    // Wait for DOM and Vue to be ready
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue === 'undefined') {
            console.error('Vue.js is not loaded');
            return;
        }

        const { createApp } = Vue;

        const app = createApp({
            data() {
                return {
                    products: [],
                    filterOptions: {
                        categories: []
                    },
                    filters: {
                        search: '',
                        category: 0,
                        orderby: 'title',
                        order: 'ASC',
                        page: 1,
                        per_page: 20
                    },
                    bulkEdit: {
                        priceType: 'regular',
                        changeType: 'percentage',
                        changeValue: 0
                    },
                    pagination: {
                        total: 0,
                        pages: 0,
                        currentPage: 1
                    },
                    loading: false,
                    message: {
                        text: '',
                        type: 'success'
                    },
                    searchTimeout: null
                };
            },
            mounted() {
                this.loadFilterOptions();
                this.loadProducts();
            },
            methods: {
                /**
                 * Load filter options
                 */
                async loadFilterOptions() {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'ihumbak_get_filters');
                        formData.append('nonce', ihumbakWpm.nonce);

                        const response = await fetch(ihumbakWpm.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.filterOptions = data.data;
                        }
                    } catch (error) {
                        console.error('Error loading filters:', error);
                    }
                },

                /**
                 * Load products
                 */
                async loadProducts() {
                    this.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'ihumbak_get_products');
                        formData.append('nonce', ihumbakWpm.nonce);
                        
                        // Add filters
                        Object.keys(this.filters).forEach(key => {
                            formData.append(key, this.filters[key]);
                        });

                        const response = await fetch(ihumbakWpm.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.products = data.data.products;
                            this.pagination.total = data.data.total;
                            this.pagination.pages = data.data.pages;
                            this.pagination.currentPage = data.data.current_page;
                        } else {
                            this.showMessage(data.data.message || ihumbakWpm.i18n.error, 'error');
                        }
                    } catch (error) {
                        console.error('Error loading products:', error);
                        this.showMessage(ihumbakWpm.i18n.error, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                /**
                 * Debounced search
                 */
                debounceSearch() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.filters.page = 1;
                        this.loadProducts();
                    }, 500);
                },

                /**
                 * Change page
                 */
                changePage(page) {
                    if (page < 1 || page > this.pagination.pages) {
                        return;
                    }
                    this.filters.page = page;
                    this.loadProducts();
                },

                /**
                 * Update single product price
                 */
                async updatePrice(productId, regularPrice, salePrice) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'ihumbak_update_prices');
                        formData.append('nonce', ihumbakWpm.nonce);
                        formData.append('product_id', productId);
                        formData.append('regular_price', regularPrice);
                        formData.append('sale_price', salePrice);

                        const response = await fetch(ihumbakWpm.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showMessage(ihumbakWpm.i18n.success, 'success');
                            this.loadProducts();
                        } else {
                            this.showMessage(data.data.message || ihumbakWpm.i18n.error, 'error');
                        }
                    } catch (error) {
                        console.error('Error updating price:', error);
                        this.showMessage(ihumbakWpm.i18n.error, 'error');
                    }
                },

                /**
                 * Bulk update prices
                 */
                async bulkUpdatePrices() {
                    if (!this.bulkEdit.changeValue) {
                        this.showMessage('Please enter a value for price change', 'error');
                        return;
                    }

                    if (!confirm(ihumbakWpm.i18n.confirmBulk)) {
                        return;
                    }

                    this.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'ihumbak_bulk_update_prices');
                        formData.append('nonce', ihumbakWpm.nonce);
                        formData.append('change_type', this.bulkEdit.changeType);
                        formData.append('change_value', this.bulkEdit.changeValue);
                        formData.append('price_type', this.bulkEdit.priceType);

                        // Add filters
                        Object.keys(this.filters).forEach(key => {
                            if (key !== 'page' && key !== 'per_page') {
                                formData.append('filters[' + key + ']', this.filters[key]);
                            }
                        });

                        const response = await fetch(ihumbakWpm.ajaxUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showMessage(data.data.message, 'success');
                            this.loadProducts();
                        } else {
                            this.showMessage(data.data.message || ihumbakWpm.i18n.error, 'error');
                        }
                    } catch (error) {
                        console.error('Error bulk updating prices:', error);
                        this.showMessage(ihumbakWpm.i18n.error, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                /**
                 * Show message
                 */
                showMessage(text, type = 'success') {
                    this.message.text = text;
                    this.message.type = type;

                    setTimeout(() => {
                        this.message.text = '';
                    }, 5000);
                },

                /**
                 * Format price
                 */
                formatPrice(price) {
                    if (!price) {
                        return '-';
                    }
                    return parseFloat(price).toFixed(2);
                },

                /**
                 * Get stock label
                 */
                getStockLabel(status) {
                    const labels = {
                        'instock': 'In Stock',
                        'outofstock': 'Out of Stock',
                        'onbackorder': 'On Backorder'
                    };
                    return labels[status] || status;
                }
            }
        });

        app.mount('#ihumbak-products-app');
    });
})();
