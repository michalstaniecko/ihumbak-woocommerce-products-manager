<?php
/**
 * Products Handler Class
 *
 * Handles product data retrieval and price updates
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IHumbak_Products_Handler {

    /**
     * Get products based on filters
     */
    public function get_products( $args = array() ) {
        $page = isset( $args['page'] ) ? absint( $args['page'] ) : 1;
        $per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 20;
        
        // Limit per_page to prevent excessive queries
        if ( $per_page > 100 && $per_page !== -1 ) {
            $per_page = 100;
        }
        
        $search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
        $category = isset( $args['category'] ) ? absint( $args['category'] ) : 0;
        
        // Validate orderby to prevent SQL injection
        $allowed_orderby = array( 'title', 'date', 'ID', 'name', 'modified' );
        $orderby = isset( $args['orderby'] ) && in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'title';
        
        // Validate order
        $order = isset( $args['order'] ) && 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

        // Build query args
        $query_args = array(
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        );

        // Add search
        if ( ! empty( $search ) ) {
            $query_args['s'] = $search;
        }

        // Add category filter
        if ( $category > 0 ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category,
                ),
            );
        }

        // Execute query
        $query = new WP_Query( $query_args );

        $products = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product( $product_id );

                if ( ! $product ) {
                    continue;
                }

                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'sku' => $product->get_sku(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'price' => $product->get_price(),
                    'stock_status' => $product->get_stock_status(),
                    'stock_quantity' => $product->get_stock_quantity(),
                    'categories' => $this->get_product_categories( $product_id ),
                    'type' => $product->get_type(),
                    'edit_url' => get_edit_post_link( $product_id ),
                );
            }
            wp_reset_postdata();
        }

        return array(
            'products' => $products,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        );
    }

    /**
     * Get product categories
     */
    private function get_product_categories( $product_id ) {
        $terms = get_the_terms( $product_id, 'product_cat' );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return array();
        }

        $categories = array();
        foreach ( $terms as $term ) {
            $categories[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }

        return $categories;
    }

    /**
     * Update product prices
     */
    public function update_product_prices( $product_id, $regular_price, $sale_price ) {
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return false;
        }

        // Validate and sanitize regular price
        if ( '' !== $regular_price ) {
            $regular_price = $this->sanitize_price( $regular_price );
            if ( false !== $regular_price ) {
                $product->set_regular_price( $regular_price );
            }
        }

        // Validate and sanitize sale price
        if ( '' !== $sale_price ) {
            $sale_price = $this->sanitize_price( $sale_price );
            if ( false !== $sale_price ) {
                $product->set_sale_price( $sale_price );
            }
        } elseif ( '' === $sale_price ) {
            $product->set_sale_price( '' );
        }

        $product->save();

        return true;
    }

    /**
     * Sanitize price value
     */
    private function sanitize_price( $price ) {
        // Remove any non-numeric characters except decimal point
        $price = preg_replace( '/[^0-9.]/', '', $price );
        
        // Validate that it's a valid number
        if ( ! is_numeric( $price ) ) {
            return false;
        }

        // Convert to float and ensure it's not negative
        $price = floatval( $price );
        if ( $price < 0 ) {
            return false;
        }

        // Round to 2 decimal places
        return round( $price, 2 );
    }

    /**
     * Bulk update prices
     */
    public function bulk_update_prices( $filters, $change_type, $change_value, $price_type ) {
        // Validate inputs
        if ( ! in_array( $change_type, array( 'percentage', 'fixed' ), true ) ) {
            return false;
        }

        if ( ! in_array( $price_type, array( 'regular', 'sale' ), true ) ) {
            return false;
        }

        // Validate change value is numeric
        if ( ! is_numeric( $change_value ) ) {
            return false;
        }

        $change_value = floatval( $change_value );

        // Get all products matching filters
        $filters['per_page'] = -1;
        $result = $this->get_products( $filters );
        $products = $result['products'];

        $updated_count = 0;

        foreach ( $products as $product_data ) {
            $product = wc_get_product( $product_data['id'] );

            if ( ! $product ) {
                continue;
            }

            $current_price = 0;
            $new_price = 0;

            // Get current price based on type
            if ( 'regular' === $price_type ) {
                $current_price = floatval( $product->get_regular_price() );
            } else {
                $current_price = floatval( $product->get_sale_price() );
                if ( empty( $current_price ) ) {
                    $current_price = floatval( $product->get_regular_price() );
                }
            }

            // Skip if current price is 0 or invalid
            if ( $current_price <= 0 ) {
                continue;
            }

            // Calculate new price
            if ( 'percentage' === $change_type ) {
                $new_price = $current_price * ( 1 + ( $change_value / 100 ) );
            } else {
                $new_price = $current_price + $change_value;
            }

            // Ensure price is not negative
            $new_price = max( 0, $new_price );
            $new_price = round( $new_price, 2 );

            // Update price
            if ( 'regular' === $price_type ) {
                $product->set_regular_price( $new_price );
            } else {
                $product->set_sale_price( $new_price );
            }

            $product->save();
            $updated_count++;
        }

        return $updated_count;
    }

    /**
     * Get filter options (categories, taxonomies)
     */
    public function get_filter_options() {
        $categories = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ) );

        $category_options = array();
        if ( ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $category_options[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'count' => $category->count,
                );
            }
        }

        return array(
            'categories' => $category_options,
        );
    }
}
