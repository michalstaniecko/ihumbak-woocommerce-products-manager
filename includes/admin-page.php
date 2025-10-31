<?php
/**
 * Admin Page Template
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap ihumbak-wpm-wrap">
    <h1><?php esc_html_e( 'Products Manager', 'ihumbak-wpm' ); ?></h1>

    <div id="ihumbak-products-app">
        <!-- Filters Section -->
        <div class="ihumbak-filters-section">
            <h2><?php esc_html_e( 'Filters', 'ihumbak-wpm' ); ?></h2>
            
            <div class="ihumbak-filters">
                <div class="filter-group">
                    <label for="search-input"><?php esc_html_e( 'Search:', 'ihumbak-wpm' ); ?></label>
                    <input 
                        type="text" 
                        id="search-input" 
                        v-model="filters.search" 
                        @input="debounceSearch"
                        placeholder="<?php esc_attr_e( 'Search products...', 'ihumbak-wpm' ); ?>"
                    />
                </div>

                <div class="filter-group">
                    <label for="category-select"><?php esc_html_e( 'Category:', 'ihumbak-wpm' ); ?></label>
                    <select id="category-select" v-model="filters.category" @change="loadProducts">
                        <option value="0"><?php esc_html_e( 'All Categories', 'ihumbak-wpm' ); ?></option>
                        <option v-for="cat in filterOptions.categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }} ({{ cat.count }})
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="orderby-select"><?php esc_html_e( 'Order by:', 'ihumbak-wpm' ); ?></label>
                    <select id="orderby-select" v-model="filters.orderby" @change="loadProducts">
                        <option value="title"><?php esc_html_e( 'Name', 'ihumbak-wpm' ); ?></option>
                        <option value="date"><?php esc_html_e( 'Date', 'ihumbak-wpm' ); ?></option>
                        <option value="ID"><?php esc_html_e( 'ID', 'ihumbak-wpm' ); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="order-select"><?php esc_html_e( 'Order:', 'ihumbak-wpm' ); ?></label>
                    <select id="order-select" v-model="filters.order" @change="loadProducts">
                        <option value="ASC"><?php esc_html_e( 'Ascending', 'ihumbak-wpm' ); ?></option>
                        <option value="DESC"><?php esc_html_e( 'Descending', 'ihumbak-wpm' ); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bulk Edit Section -->
        <div class="ihumbak-bulk-section">
            <h2><?php esc_html_e( 'Bulk Price Update', 'ihumbak-wpm' ); ?></h2>
            
            <div class="bulk-controls">
                <div class="filter-group">
                    <label for="price-type"><?php esc_html_e( 'Price Type:', 'ihumbak-wpm' ); ?></label>
                    <select id="price-type" v-model="bulkEdit.priceType">
                        <option value="regular"><?php esc_html_e( 'Regular Price', 'ihumbak-wpm' ); ?></option>
                        <option value="sale"><?php esc_html_e( 'Sale Price', 'ihumbak-wpm' ); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="change-type"><?php esc_html_e( 'Change Type:', 'ihumbak-wpm' ); ?></label>
                    <select id="change-type" v-model="bulkEdit.changeType">
                        <option value="percentage"><?php esc_html_e( 'Percentage', 'ihumbak-wpm' ); ?></option>
                        <option value="fixed"><?php esc_html_e( 'Fixed Amount', 'ihumbak-wpm' ); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="change-value"><?php esc_html_e( 'Value:', 'ihumbak-wpm' ); ?></label>
                    <input 
                        type="number" 
                        id="change-value" 
                        v-model="bulkEdit.changeValue" 
                        step="0.01"
                        placeholder="<?php esc_attr_e( 'e.g., 10 for +10% or -5 for -5%', 'ihumbak-wpm' ); ?>"
                    />
                    <span v-if="bulkEdit.changeType === 'percentage'">%</span>
                </div>

                <div class="filter-group">
                    <button 
                        class="button button-primary" 
                        @click="bulkUpdatePrices"
                        :disabled="loading || !bulkEdit.changeValue"
                    >
                        <?php esc_html_e( 'Apply to Filtered Products', 'ihumbak-wpm' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="ihumbak-loading">
            <span class="spinner is-active"></span>
            <p><?php esc_html_e( 'Loading...', 'ihumbak-wpm' ); ?></p>
        </div>

        <!-- Products Table -->
        <div v-else-if="products.length > 0" class="ihumbak-products-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-id"><?php esc_html_e( 'ID', 'ihumbak-wpm' ); ?></th>
                        <th class="column-name"><?php esc_html_e( 'Name', 'ihumbak-wpm' ); ?></th>
                        <th class="column-sku"><?php esc_html_e( 'SKU', 'ihumbak-wpm' ); ?></th>
                        <th class="column-categories"><?php esc_html_e( 'Categories', 'ihumbak-wpm' ); ?></th>
                        <th class="column-regular-price"><?php esc_html_e( 'Regular Price', 'ihumbak-wpm' ); ?></th>
                        <th class="column-sale-price"><?php esc_html_e( 'Sale Price', 'ihumbak-wpm' ); ?></th>
                        <th class="column-current-price"><?php esc_html_e( 'Current Price', 'ihumbak-wpm' ); ?></th>
                        <th class="column-stock"><?php esc_html_e( 'Stock', 'ihumbak-wpm' ); ?></th>
                        <th class="column-actions"><?php esc_html_e( 'Actions', 'ihumbak-wpm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in products" :key="product.id">
                        <td class="column-id">{{ product.id }}</td>
                        <td class="column-name">
                            <strong>{{ product.name }}</strong>
                        </td>
                        <td class="column-sku">{{ product.sku || '-' }}</td>
                        <td class="column-categories">
                            <span v-for="(cat, index) in product.categories" :key="cat.id">
                                {{ cat.name }}<span v-if="index < product.categories.length - 1">, </span>
                            </span>
                            <span v-if="product.categories.length === 0">-</span>
                        </td>
                        <td class="column-regular-price">
                            <input 
                                type="number" 
                                :value="product.regular_price" 
                                @change="updatePrice(product.id, $event.target.value, product.sale_price)"
                                step="0.01"
                                min="0"
                                class="small-text price-input"
                            />
                        </td>
                        <td class="column-sale-price">
                            <input 
                                type="number" 
                                :value="product.sale_price" 
                                @change="updatePrice(product.id, product.regular_price, $event.target.value)"
                                step="0.01"
                                min="0"
                                class="small-text price-input"
                            />
                        </td>
                        <td class="column-current-price">
                            <strong>{{ formatPrice(product.price) }}</strong>
                        </td>
                        <td class="column-stock">
                            <span :class="'stock-' + product.stock_status">
                                {{ getStockLabel(product.stock_status) }}
                            </span>
                            <span v-if="product.stock_quantity"> ({{ product.stock_quantity }})</span>
                        </td>
                        <td class="column-actions">
                            <a :href="product.edit_url" class="button button-small" target="_blank">
                                <?php esc_html_e( 'Edit', 'ihumbak-wpm' ); ?>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="ihumbak-pagination" v-if="pagination.pages > 1">
                <button 
                    class="button" 
                    @click="changePage(pagination.currentPage - 1)"
                    :disabled="pagination.currentPage <= 1"
                >
                    <?php esc_html_e( 'Previous', 'ihumbak-wpm' ); ?>
                </button>
                
                <span class="pagination-info">
                    <?php esc_html_e( 'Page', 'ihumbak-wpm' ); ?> 
                    {{ pagination.currentPage }} <?php esc_html_e( 'of', 'ihumbak-wpm' ); ?> {{ pagination.pages }}
                    (<?php esc_html_e( 'Total:', 'ihumbak-wpm' ); ?> {{ pagination.total }})
                </span>
                
                <button 
                    class="button" 
                    @click="changePage(pagination.currentPage + 1)"
                    :disabled="pagination.currentPage >= pagination.pages"
                >
                    <?php esc_html_e( 'Next', 'ihumbak-wpm' ); ?>
                </button>
            </div>
        </div>

        <!-- No Products Message -->
        <div v-else class="ihumbak-no-products">
            <p><?php esc_html_e( 'No products found', 'ihumbak-wpm' ); ?></p>
        </div>

        <!-- Messages -->
        <div v-if="message.text" :class="'notice notice-' + message.type + ' is-dismissible'">
            <p>{{ message.text }}</p>
            <button type="button" class="notice-dismiss" @click="message.text = ''">
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'ihumbak-wpm' ); ?></span>
            </button>
        </div>
    </div>
</div>
