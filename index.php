<?php
/**
 * Plugin Name: Woocommerce Custom Sale Tag
 * Plugin URI: http://wordpress.org/plugins/woo-custom-sale-tag/
 * Description: Customize the sale tag that appears on WooCommerce product thumbnails when a product sale price is set lower than the retail price.
 * Author: Jamie Chong
 * Version: 1.0
 * Author URI: http://jamiechong.ca
 *
 * Copyright (C) 2015 Jamie Chong
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
**/


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_custom_sale_tag_action_links');
function woocommerce_custom_sale_tag_action_links ($links) {
	return array_merge($links, array('<a href="' . admin_url('admin.php?page=wc-settings&tab=products&section=wcsaletag') . '">Settings</a>'));
}

add_filter('woocommerce_sale_flash', 'woocommerce_custom_sale_tag_sale_flash', 10, 3);
function woocommerce_custom_sale_tag_sale_flash($original, $post, $product) {
	$saleTag = $original;
	
	$format = get_option('wcsaletag_format');
	
	if (!empty($format)) {
		if ($format == 'custom') {
			$format = get_option('wcsaletag_custom_format');
		}

		$priceDiff = 0;
		$percentDiff = 0;
		$regularPrice = '';
		$salePrice = '';

		if (!empty($product) && $product->is_in_stock()) {
			$salePrice = get_post_meta($product->id, '_price', true);
			$regularPrice = get_post_meta($product->id, '_regular_price', true);
			
			if (empty($regularPrice)) { //then this is a variable product
				$variations = $product->get_available_variations();
				$variation_id = $variations[0]['variation_id'];
				$variation = new WC_Product_Variation($variation_id);
				$regularPrice = $variation->regular_price;
				$salePrice = $variation->sale_price;
			}
		}

		if (!empty($regularPrice) && !empty($salePrice ) && $regularPrice > $salePrice ) {
			$priceDiff = $regularPrice - $salePrice;
			$percentDiff = round($priceDiff / $regularPrice * 100);
			
			$parsed = str_replace('{price-diff}', number_format((float)$priceDiff, 2, '.', ''), $format);
			$parsed = str_replace('{percent-diff}', $percentDiff, $parsed);
			$saleTag = '<span class="onsale">'.__($parsed, 'woocommerce_custom_sale_tag').'</span>';
		}
	}
	echo $saleTag;
}

add_filter('woocommerce_get_sections_products', 'woocommerce_custom_sale_tag_add_section');
function woocommerce_custom_sale_tag_add_section($sections) {
	$sections['wcsaletag'] = __('Sale Tags', 'woocommerce_custom_sale_tag');
	return $sections;
}

add_filter('woocommerce_get_settings_products', 'wcsaletag_all_settings', 10, 2 );
function wcsaletag_all_settings($settings, $current_section) {
	if ($current_section != 'wcsaletag') {
		return $settings;
	} else {

		$settings = array();

		$settings[] = array( 
			'name' => __('Sale Tag Settings', 'woocommerce_custom_sale_tag'), 
			'type' => 'title', 
			'id' => 'wcsaletag_title' 
		);

		$settings[] = array(
			'name' => __('Predefined Format'),
			'id' => 'wcsaletag_format',
			'type' => 'radio',
			'options' => array(
				'' 						=> __('Sale!', 'woocommerce_custom_sale_tag'),
				'Save {percent-diff}%' 	=> __('Save {percent-diff}%', 'woocommerce_custom_sale_tag'),
				'Save ${price-diff}' 	=> __('Save ${price-diff}', 'woocommerce_custom_sale_tag'),
				'custom' 				=> __('Custom (use field below)', 'woocommerce_custom_sale_tag'),
			)
		);

		$settings[] = array(

			'name'     => __('Custom Format', 'woocommerce_custom_sale_tag'),
			'id'       => 'wcsaletag_custom_format',
			'type'     => 'text',
			'desc'     => __('<br/>{price-diff} inserts the dollar amount off.<br/>{percent-diff} inserts the percent reduction (rounded).', 'woocommerce_custom_sale_tag' ),

		);
		
		$settings[] = array('type' => 'sectionend', 'id' => 'wcsaletag');

		return $settings;
	
	}

}

