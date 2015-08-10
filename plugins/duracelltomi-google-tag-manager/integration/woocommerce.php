<?php
$gtm4wp_product_counter = 0;

function gtm4wp_woocommerce_addjs( $js ) {
  global $woocommerce;
  
	if ( version_compare( $woocommerce->version, "2.1", ">=" ) ) {
		wc_enqueue_js( $js );
	} else {
		$woocommerce->add_inline_js( $js );
	}
}

function gtm4wp_woocommerce_html_entity_decode( $val ) {
	return html_entity_decode( $val, ENT_QUOTES, "utf-8" );
}

function gtm4wp_woocommerce_datalayer_filter_items( $dataLayer ) {
	global $woocommerce, $gtm4wp_options, $wp_query, $gtm4wp_datalayer_name, $gtm4wp_product_counter;

	if ( is_product_category() || is_product_tag() || is_front_page() || is_shop() ) {
		if ( ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) || ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) ) {
			if ( count( $woocommerce->query->filtered_product_ids ) > 0 ) {
				// The following 5 lines was being borrowed from WC source
				$paged    = max( 1, $wp_query->get( 'paged' ) );
				$per_page = $wp_query->get( 'posts_per_page' );
				$total    = $wp_query->found_posts;
				$first    = ( $per_page * $paged ) - $per_page + 1;
				$last     = min( $total, $wp_query->get( 'posts_per_page' ) * $paged );

				$gtm4wp_product_counter = $first;

				$sumprice = 0;
				$product_ids = array();
				$product_impressions = array();
				$_temp_product_id_list = $woocommerce->query->filtered_product_ids;

				$i = ($first-1);
				foreach( $_temp_product_id_list as $itemid => $oneproductid ) {
					$product = get_product( $oneproductid );
					if ( false === $product ) {
						continue;
					}

					$product_price = $product->get_price();
					$_product_cats = get_the_terms($product->id, 'product_cat');
					if ( ( is_array($_product_cats) ) && ( count( $_product_cats ) > 0 ) ) {
						$product_cat = array_pop( $_product_cats );
						$product_cat = $product_cat->name;
					} else {
						$product_cat = "";
					}
					$sumprice += $product_price;

					$remarketing_id = $oneproductid;
					if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETINGSKU ] ) {
						$product_sku = $product->get_sku();
						if ( "" != $product_sku ) {
							$remarketing_id = $product_sku;
						}
					}
					$product_ids[] = $remarketing_id;

					$product_impressions[] = array(
						'name'     => $product->get_title(),
						'id'       => $oneproductid,
						'price'    => $product_price,
						'category' => $product_cat,
						'position' => ($i+1)
					);

					$i++;
					if ( $i>$last ) {
						break;
					}
				}

				if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) {
					$dataLayer["ecomm_prodid"] = $product_ids;
					$dataLayer["ecomm_pagetype"] = ( is_front_page() ? "home" : "category" );
					$dataLayer["ecomm_totalvalue"] = (float)$sumprice;
				}

				if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
					$dataLayer["ecommerce"] = array("impressions" => $product_impressions);
				}
			}
		}
	} else if ( is_product() ) {
		if ( ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) || ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) ) {
			$prodid        = get_the_ID();
			$product       = get_product( $prodid );
			$product_price = $product->get_price();
			$_product_cats = get_the_terms( $product->id, 'product_cat' );
			if ( ( is_array($_product_cats) ) && ( count( $_product_cats ) > 0 ) ) {
				$product_cat = array_pop( $_product_cats );
				$product_cat = $product_cat->name;
			} else {
				$product_cat = "";
			}

			if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) {
				$remarketing_id = (string)$prodid;
				if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETINGSKU ] ) {
					$product_sku = $product->get_sku();
					if ( "" != $product_sku ) {
						$remarketing_id = $product_sku;
					}
				}

				$dataLayer["ecomm_prodid"] = $remarketing_id;
				$dataLayer["ecomm_pagetype"] = "product";
				$dataLayer["ecomm_totalvalue"] = (float)$product_price;
			}
			
			if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
				$dataLayer["ecommerce"] = array(
					"detail" => array(
						"products" => array(array(
							"name"     => gtm4wp_woocommerce_html_entity_decode( get_the_title() ),
							"id"       => $prodid,
							"price"    => $product_price,
							"category" => $product_cat,
						))
					)
				);
			}
		}
	} else if ( is_cart() ) {
		if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
			gtm4wp_woocommerce_addjs("
		$('.product-remove').click(function() {
			var productdata = $( this )
				.parent()
				.find( '.gtm4wp_productdata' );
			
			". $gtm4wp_datalayer_name .".push({
				'event': 'gtm4wp.removeFromCart',
				'ecommerce': {
					'remove': {
						'products': [{
							'name':     productdata.data( 'product_name' ),
							'id':       productdata.data( 'product_id' ),
							'price':    productdata.data( 'product_price' ),
							'category': productdata.data( 'product_cat' ),
							'quantity': $( this ).parent().parent().find( '.product-quantity input.qty' ).val()
						}]
					}
				}
			});
		});
			");
		}

		if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) {
			$products = $woocommerce->cart->get_cart();
			$product_ids = array();
			foreach( $products as $oneproduct ) {
				$remarketing_id = $oneproduct['product_id'];
				if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETINGSKU ] ) {
					$product_sku = $oneproduct['product_sku'];
					if ( "" != $product_sku ) {
						$remarketing_id = $product_sku;
					}
				}

				$product_ids[] = $remarketing_id;
			}

			$dataLayer["ecomm_prodid"] = $product_ids;
			$dataLayer["ecomm_pagetype"] = "cart";
			$dataLayer["ecomm_totalvalue"] = (float)$woocommerce->cart->cart_contents_total;
		}
	} else if ( is_order_received_page() ) {
		$order_id  = apply_filters( 'woocommerce_thankyou_order_id', empty( $_GET['order'] ) ? ($GLOBALS["wp"]->query_vars["order-received"] ? $GLOBALS["wp"]->query_vars["order-received"] : 0) : absint( $_GET['order'] ) );
		$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : woocommerce_clean( $_GET['key'] ) );

		if ( $order_id > 0 ) {
			$order = new WC_Order( $order_id );
			if ( $order->order_key != $order_key )
				unset( $order );
		}

		if ( 1 == get_post_meta( $order_id, '_ga_tracked', true ) ) {
			unset( $order );
		}

		if ( isset( $order ) ) {
			if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKCLASSICEC ] ) {
				$dataLayer["transactionId"]             = $order->get_order_number();
				$dataLayer["transactionDate"]           = date("c");
				$dataLayer["transactionType"]           = "sale";
				$dataLayer["transactionAffiliation"]    = gtm4wp_woocommerce_html_entity_decode( get_bloginfo( 'name' ) );
				$dataLayer["transactionTotal"]          = $order->get_total();
				$dataLayer["transactionShipping"]       = $order->get_total_shipping();
				$dataLayer["transactionTax"]            = $order->get_total_tax();
				$dataLayer["transactionPaymentType"]    = $order->payment_method_title;
				$dataLayer["transactionCurrency"]       = get_woocommerce_currency();
				$dataLayer["transactionShippingMethod"] = $order->get_shipping_method();
				$dataLayer["transactionPromoCode"]      = implode( ", ", $order->get_used_coupons() );
			}

			if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
				$dataLayer["ecommerce"] = array(
					"purchase" => array(
						"actionField" => array(
							"id"          => $order->get_order_number(),
							"affiliation" => gtm4wp_woocommerce_html_entity_decode( get_bloginfo( 'name' ) ),
							"revenue"     => $order->get_total(),
							"tax"         => $order->get_total_tax(),
							"shipping"    => $order->get_total_shipping(),
							"coupon"      => implode( ", ", $order->get_used_coupons() )
						)
					)
				);
			}

			$_products = array();
			$_sumprice = 0;
			$_product_ids = array();

			if ( $order->get_items() ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $order->get_product_from_item( $item );

          $variation_data = null;
          if (get_class($_product) == "WC_Product_Variation") {
            $variation_data = $_product->get_variation_attributes();
          }

          if ( isset( $variation_data ) ) {

						$_category = woocommerce_get_formatted_variation( $_product->variation_data, true );

					} else {
						$out = array();
						$categories = get_the_terms( $_product->id, 'product_cat' );
						if ( $categories ) {
							foreach ( $categories as $category ) {
								$out[] = $category->name;
							}
						}
					
						$_category = implode( " / ", $out );
					}

					$remarketing_id = $_product->id;
					$product_sku    = $_product->get_sku();
					if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETINGSKU ] && ( "" != $product_sku ) ) {
						$remarketing_id = $product_sku;
					}

					$_prodprice = $order->get_item_total( $item );
					$_products[] = array(
					  "id"       => $_product->id,
					  "name"     => $item['name'],
					  "sku"      => $product_sku ? __( 'SKU:', GTM4WP_TEXTDOMAIN ) . ' ' . $product_sku : $_product->id,
					  "category" => $_category,
					  "price"    => $_prodprice,
					  "currency" => get_woocommerce_currency(),
					  "quantity" => $item['qty']
					);
			
					$_sumprice += $_prodprice;
					$_product_ids[] = $remarketing_id;
				}
			}

			if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKCLASSICEC ] ) {
				$dataLayer["transactionProducts"] = $_products;
				$dataLayer["event"] = "gtm4wp.orderCompleted";
			}

			if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
				$dataLayer["ecommerce"]["purchase"]["products"] = $_products;
				$dataLayer["event"] = "gtm4wp.orderCompleted";
			}

			if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) {
				$dataLayer["ecomm_prodid"] = $_product_ids;
				$dataLayer["ecomm_pagetype"] = "purchase";
				$dataLayer["ecomm_totalvalue"] = (float)$_sumprice;
			}

			update_post_meta( $order_id, '_ga_tracked', 1 );
		}
	} else if ( is_checkout() ) {
		if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
			foreach( $woocommerce->cart->get_cart() as $cart_item_id => $cart_item_data) {
				$product = $cart_item_data["data"];
				$_product_cats = get_the_terms($product->id, 'product_cat');
				if ( ( is_array($_product_cats) ) && ( count( $_product_cats ) > 0 ) ) {
					$product_cat = array_pop( $_product_cats );
					$product_cat = $product_cat->name;
				} else {
					$product_cat = "";
				}

				$gtm4wp_checkout_products[] = array(
					"id"       => $product->id,
					"name"     => $product->post->post_title,
					"price"    => $product->get_price(),
					"category" => $product_cat,
					"quantity" => $cart_item_data["quantity"]
				);
			}

			$dataLayer["ecommerce"] = array(
				"checkout" => array(
					"actionField" => array(
						"step" => 1
					),
					"products" => $gtm4wp_checkout_products
				)
			);
		}
	} else {
		if ( $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCREMARKETING ] ) {
			$dataLayer["ecomm_pagetype"] = "siteview";
		}
	}

	return $dataLayer;
}

function gtm4wp_woocommerce_single_add_to_cart_tracking() {
	if ( ! is_single() ) {
		return;
	}

	global $product, $woocommerce, $gtm4wp_datalayer_name, $gtm4wp_options;

	if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKCLASSICEC ] ) {
		gtm4wp_woocommerce_addjs("
		$('.single_add_to_cart_button').click(function() {
			". $gtm4wp_datalayer_name .".push({
				'event': 'gtm4wp.addProductToCart',
				'productName': '". esc_js( $product->post->post_title ) ."',
				'productSKU': '". esc_js( $product->get_sku() ) ."',
				'productID': '". esc_js( $product->id ) ."'
			});
		});
		");
	}

	if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
		$_product_cats = get_the_terms($product->id, 'product_cat');
		if ( ( is_array($_product_cats) ) && ( count( $_product_cats ) > 0 ) ) {
			$product_cat = array_pop( $_product_cats );
			$product_cat = $product_cat->name;
		} else {
			$product_cat = "";
		}

		gtm4wp_woocommerce_addjs("
		$('.single_add_to_cart_button').click(function() {
			". $gtm4wp_datalayer_name .".push({
				'event': 'gtm4wp.addProductToCart',
				'ecommerce': {
					'currencyCode': '".get_woocommerce_currency()."',
					'add': {
						'products': [{
							'name': '". esc_js( $product->post->post_title ) ."',
							'id': '". esc_js( $product->id ) ."',
							'price': '". esc_js( $product->get_price() ) ."',
							'category': '". esc_js( $product_cat ) ."',
							'quantity': $('form.cart:first input[name=quantity]').val()
						}]
					}
				}
			});
		});
		");
	}
}

function gtm4wp_woocommerce_wp_footer() {
	global $woocommerce, $gtm4wp_options, $gtm4wp_datalayer_name;

	if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKCLASSICEC ] ) {
		gtm4wp_woocommerce_addjs("
		$('.add_to_cart_button:not(.product_type_variable, .product_type_grouped)').click(function() {
			". $gtm4wp_datalayer_name .".push({
				'event': 'gtm4wp.addProductToCart',
				'productName': $( this ).parent().find('h3').text(),
				'productSKU': $( this ).data( 'product_sku' ),
				'productID': $( this ).data( 'product_id' ),
			});
		});
		");
	}

	if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
		gtm4wp_woocommerce_addjs("
		$('.add_to_cart_button:not(.product_type_variable, .product_type_grouped)').click(function() {
			var productdata = $( this ).closest( 'li' ).find( 'a .gtm4wp_productdata' );

			". $gtm4wp_datalayer_name .".push({
				'event': 'gtm4wp.addProductToCart',
				'ecommerce': {
					'currencyCode': '".get_woocommerce_currency()."',
					'add': {
						'products': [{
							'name':     productdata.data( 'product_name' ),
							'id':       productdata.data( 'product_id' ),
							'price':    productdata.data( 'product_price' ),
							'category': productdata.data( 'product_cat' ),
							'quantity': 1
						}]
					}
				}
			});
		});
		");
	}
}

function gtm4wp_woocommerce_enhanced_ecom_product_click() {
	global $woocommerce, $gtm4wp_datalayer_name;

	gtm4wp_woocommerce_addjs("
		$('.products li a:not(.add_to_cart_button)').click(function() {
			var productdata = $( this ).find( '.gtm4wp_productdata' );

			if ( 0 == productdata.length ) {
				return true;
			}
			
			". $gtm4wp_datalayer_name .".push({
				'event': 'gtm4wp.productClick',
				'ecommerce': {
					'click': {
						'products': [{
							'name':     productdata.data( 'product_name' ),
							'id':       productdata.data( 'product_id' ),
							'price':    productdata.data( 'product_price' ),
							'category': productdata.data( 'product_cat' ),
							'position': productdata.data( 'product_listposition' )
					         }]
					}
				},
				'eventCallback': function() {
					document.location = productdata.data( 'product_url' )
				}
			});
			return false;
		});
	");
}

function gtm4wp_woocommerce_enhanced_ecom_add_prod_data() {
	global $product, $gtm4wp_product_counter;
	
	$product_price = $product->get_price();
	$_product_cats = get_the_terms($product->id, 'product_cat');
	if ( ( is_array($_product_cats) ) && ( count( $_product_cats ) > 0 ) ) {
		$product_cat = array_pop( $_product_cats );
		$product_cat = $product_cat->name;
	} else {
		$product_cat = "";
	}

	echo '<span class="gtm4wp_productdata" data-product_id="' . $product->id . '" data-product_name="' . str_replace( '"', '&quot;', $product->get_title() ) . '" data-product_price="' .$product_price . '" data-product_cat="' . str_replace( '"', '&quot;', $product_cat ) . '" data-product_url="' . get_permalink() . '" data-product_listposition="' . $gtm4wp_product_counter . '"></span>';
	$gtm4wp_product_counter++;
}

$GLOBALS["gtm4wp_cart_item_proddata"] = '';
function gtm4wp_woocommerce_cart_item_product_filter($product) {
	$product_price = $product->get_price();
	$_product_cats = get_the_terms($product->id, 'product_cat');
	if ( ( is_array( $_product_cats ) ) && ( count( $_product_cats ) > 0 ) ) {
		$product_cat = array_pop( $_product_cats );
		$product_cat = $product_cat->name;
	} else {
		$product_cat = "";
	}

	$GLOBALS["gtm4wp_cart_item_proddata"] = '<span class="gtm4wp_productdata" data-product_id="' . $product->id . '" data-product_name="' . str_replace( '"', '&quot;', $product->get_title() ) . '" data-product_price="' .$product_price . '" data-product_cat="' . str_replace( '"', '&quot;', $product_cat ) . '" data-product_url="' . get_permalink() . '"></span>';
	return $product;
}

function gtm4wp_woocommerce_cart_item_remove_link_filter($arg) {
	echo $GLOBALS["gtm4wp_cart_item_proddata"];
	$GLOBALS["gtm4wp_cart_item_proddata"] = '';

	return $arg;
}

// do not add filter if someone enabled WooCommerce integration without an activated WooCommerce plugin
if ( isset ( $GLOBALS["woocommerce"] ) ) {
	add_filter( GTM4WP_WPFILTER_COMPILE_DATALAYER, "gtm4wp_woocommerce_datalayer_filter_items" );
	add_filter( "woocommerce_cart_item_product", "gtm4wp_woocommerce_cart_item_product_filter" );
	add_filter( "woocommerce_cart_item_remove_link", "gtm4wp_woocommerce_cart_item_remove_link_filter" );

	add_action( 'woocommerce_after_add_to_cart_button', "gtm4wp_woocommerce_single_add_to_cart_tracking" );
	add_action( 'wp_footer', "gtm4wp_woocommerce_wp_footer" );
	
	if ( true === $gtm4wp_options[ GTM4WP_OPTION_INTEGRATE_WCTRACKENHANCEDEC ] ) {
		add_action( 'wp_footer', 'gtm4wp_woocommerce_enhanced_ecom_product_click' );
		add_action( 'woocommerce_before_shop_loop_item_title', 'gtm4wp_woocommerce_enhanced_ecom_add_prod_data' );
	}
}
