<?php
/**
 * File Type: Order Detail
 */
if (!class_exists('Foodbakery_Order_Detail')) {

    class Foodbakery_Order_Detail {

        /**
         * Start construct Functions
         */
        public function __construct() {
            add_action('wp_enqueue_scripts', array($this, 'foodbakery_order_element_scripts'), 11);
            add_action('foodbakery_order_detail', array($this, 'foodbakery_order_detail_callback'), 11, 2);
            add_action('wp_ajax_foodbakery_update_order_status', array($this, 'foodbakery_update_order_status_callback'), 10);
        }

        public function foodbakery_order_element_scripts() {
            wp_enqueue_script('foodbakery-orders-functions');
            wp_enqueue_script('jquery-mCustomScrollbar');
            wp_enqueue_style('jquery-mCustomScrollbar');
            wp_enqueue_script('jquery-print');
        }

        public function foodbakery_order_detail_callback($order_id = '', $type = 'my') {
            global $post, $foodbakery_var_options;
            $foodbakery_custom_logo = isset($foodbakery_var_options['foodbakery_var_custom_logo']) ? $foodbakery_var_options['foodbakery_var_custom_logo'] : '';
            if ($foodbakery_custom_logo != '') {
                $logo = $foodbakery_custom_logo;
            } else {
                $logo = esc_url(wp_foodbakery::plugin_url()) . '/assets/frontend/images/logo-classic.png';
            }

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $args = array(
                'post_type' => 'foodbakery-trans',
                'posts_per_page' => '1',
                'fields' => 'ids',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'foodbakery_transaction_order_id',
                        'value' => $order_id,
                        'compare' => '=',
                    ),
                ),
            );
            $trans_query = new WP_Query($args);
            $trans_query_f = $trans_query->posts;

            $order_trans_f = isset($trans_query_f[0]) ? $trans_query_f[0] : 0;

            $pay_mode = get_post_meta($order_trans_f, 'foodbakery_transaction_pay_method', true);

            $payment_status_array = array(
                'pending' => foodbakery_plugin_text_srt('foodbakery_transaction_status_pending'),
                'approved' => foodbakery_plugin_text_srt('foodbakery_transaction_status_approved'),
                'cancelled' => foodbakery_plugin_text_srt('foodbakery_transaction_status_cancelled'),
                'processing' => foodbakery_plugin_text_srt('foodbakery_status_processing'),
                'Completed' => foodbakery_plugin_text_srt('foodbakery_status_completed'),
                'completed' => foodbakery_plugin_text_srt('foodbakery_status_completed'),
            );
            $order_status = get_post_meta($order_id, 'foodbakery_order_status', true);
            $payment_status_array[$order_status];
            $args = array(
                'post_type' => 'orders_inquiries',
                'post_status' => 'publish',
                'p' => $order_id,
            );
            $order_query = new WP_Query($args);
            while ($order_query->have_posts()): $order_query->the_post();

                $order_id = get_the_ID();
                $foodbakery_restaurant_id = get_post_meta($order_id, 'foodbakery_restaurant_id', true);
                $order_type = get_post_meta($order_id, 'foodbakery_order_type', true);
                $foodbakery_delivery_date = get_post_meta($order_id, 'foodbakery_delivery_date', true);
                $order_date = '';
                ?>
                <?php if ($type != 'my') { ?>
                    <div class="print-order-detail menu-order-detail order-detail" id="print-order-det-<?php echo intval($order_id); ?>" style="display: none;">
                        <div class="logo"><img src="<?php echo esc_url($logo); ?>" alt="<?php esc_html(bloginfo('name')) ?>" /></div>
                        <h2><?php esc_html_e('Order Detail', 'foodbakery') ?></h2>
                        <div class="order-detail-inner">
                            <div class="description-holder">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <div class="list-detail-options has-checkbox">
                                            <h3>
                                                <?php echo get_the_title($foodbakery_restaurant_id); ?>
                                            </h3>
                                            <ul class="order-detail-options">
                                                <li class="order-number">
                                                    <strong><?php esc_html_e('Order ID:', 'foodbakery') ?></strong>
                                                    <span><?php echo esc_html($order_id); ?></span>
                                                </li>
                                                <?php $this->order_pick_delivery_time($order_id); ?>
                                                <li class="created-date">
                                                    <strong><?php esc_html_e('Delivery Date:', 'foodbakery') ?></strong>
                                                    <span>
                                                        <?php
                                                        if ($foodbakery_delivery_date != '') {
                                                            echo date_i18n('M j, Y h:i A', $foodbakery_delivery_date);
                                                        } else {
                                                            echo date_i18n('M j, Y h:i A', $order_date);
                                                        }
                                                        ?>
                                                    </span>
                                                </li>
                                                <li class="order-type">
                                                    <strong><?php esc_html_e('Type:', 'foodbakery') ?></strong>
                                                    <span><?php echo esc_html($order_type); ?></span>
                                                </li>
                                                <li class="order-type">
                                                    <strong><?php esc_html_e('Payment Status:', 'foodbakery') ?></strong>
                                                    <span><?php echo $payment_status_array[$this->order_payment_status($order_id)]; ?><?php echo ($pay_mode == 'cash' ? ' <small><em>' . esc_html__('(Cash on Delivery)', 'foodbakery') . '</em></small>' : '') ?></span>
                                                </li>

                                            </ul>
                                        </div>
                                    </div>
                                    <?php
                                    // Order buyer info.
                                    $this->order_buyer_info($order_id);
                                    // Order status
                                    $this->print_order_status($order_id);
                                    // Order menu list.
                                    $this->order_menu_list($order_id);
                                    // Order price.
                                    $this->order_price($order_id);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="modal fade menu-order-detail order-detail" id="order-det-<?php echo intval($order_id); ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h2><?php esc_html_e('Order Detail', 'foodbakery') ?></h2>
                                <?php if ($type != 'my') { ?>
                                    <button class="btn-print" onclick="CallPrint('print-order-det-<?php echo ($order_id); ?>');"><i class="icon-printer"></i><span><?php echo esc_html__('Receipt', 'foodbakery'); ?></span></button>
                                    <script type="text/javascript">
                                        function CallPrint(divName) {
                                            var title = "<?php esc_html_e('Order Detail', 'foodbakery'); ?>";
                                            var stylesheet_url = "<?php echo esc_url(wp_foodbakery::plugin_url()) . '/assets/frontend/css/cm-print.css'; ?>";
                                            jQuery('#' + divName).show();
                                            jQuery("#" + divName).print({
                                                stylesheet: stylesheet_url,
                                                title: title,
                                            });
                                            jQuery('#' + divName).hide();
                                        }
                                    </script>
                                <?php } ?>
                            </div>
                            <div class="modal-body">
                                <div class="order-detail-inner">
                                    <div class="description-holder">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                <div class="list-detail-options has-checkbox">
                                                    <h3>
                                                        <?php echo get_the_title($foodbakery_restaurant_id); ?>
                                                    </h3>
                                                    <ul class="order-detail-options">
                                                        <li class="order-number">
                                                            <strong><?php esc_html_e('Order ID:', 'foodbakery') ?></strong>
                                                            <span><?php echo esc_html($order_id); ?></span>
                                                        </li>
                                                        <?php $this->order_pick_delivery_time($order_id); ?>
                                                        <li class="created-date">
                                                            <strong><?php esc_html_e('Delivery Date:', 'foodbakery') ?></strong>
                                                            <span>
                                                                <?php
                                                                if ($foodbakery_delivery_date != '') {
                                                                    echo date_i18n('M j, Y h:i A', $foodbakery_delivery_date);
                                                                } else {
                                                                    echo date_i18n('M j, Y h:i A', $order_date);
                                                                }
                                                                ?>
                                                            </span>
                                                        </li>
                                                        <li class="order-type">
                                                            <strong><?php esc_html_e('Type:', 'foodbakery') ?></strong>
                                                            <span><?php echo esc_html($order_type); ?></span>
                                                        </li>
                                                        <li class="order-type">
                                                            <strong><?php esc_html_e('Payment Status:', 'foodbakery') ?></strong>
                                                            <span><?php echo $payment_status_array[$this->order_payment_status($order_id)]; ?><?php echo ($pay_mode == 'cash' ? ' <small><em>' . esc_html__('(Cash on Delivery)', 'foodbakery') . '</em></small>' : '') ?></span>
                                                        </li>

                                                    </ul>
                                                </div>

                                            </div>
                                            <?php
                                            // Order buyer info.

                                            $this->order_buyer_info($order_id);

                                            // Order Status.
                                            $this->order_status($order_id);

                                            // Order menu list.
                                            $this->order_menu_list($order_id);

                                            // Order price.
                                            $this->order_price($order_id);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            endwhile;
            wp_reset_postdata();
            ?>
            <script>
                (function ($) {
                    $(document).ready(function () {
                        $(".order-detail .modal-dialog .modal-content").mCustomScrollbar({
                            setHeight: 724,
                            theme: "minimal-dark",
                            mouseWheelPixels: 100
                        });
                    });
                })(jQuery);
            </script>
            <?php
        }

        public function order_pick_delivery_time($order_id = '') {
            global $post;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $menu_order_fee_type = get_post_meta($order_id, 'menu_order_fee_type', true);
            $order_restaurant_id = get_post_meta($order_id, 'foodbakery_restaurant_id', true);

            if ($menu_order_fee_type != '') {
                ?>
                <li class="req-delivery">
                    <?php
                    $delivery_time = get_post_meta($order_restaurant_id, 'foodbakery_delivery_time', true);
                    $pickup_time = get_post_meta($order_restaurant_id, 'foodbakery_restaurant_pickup_time', true);
                    if ($menu_order_fee_type == 'delivery' && is_numeric($delivery_time) && $delivery_time > 0) {
                        ?>
                        <strong><?php esc_html_e('Delivery Time:', 'foodbakery') ?></strong>
                        <span><?php echo ($this->convertFromMinutes($delivery_time)); ?></span>
                    <?php } else if (is_numeric($pickup_time) && $pickup_time > 0) { ?>
                        <strong><?php esc_html_e('Pick Up Time:', 'foodbakery') ?></strong>
                        <span><?php echo ($this->convertFromMinutes($pickup_time)); ?></span>
                    <?php } ?>
                </li>
                <?php
            }
        }

        public function convertFromMinutes($minutes) {
            $time = '';
            $days = floor($minutes / 1440);
            $hours = (int) ($minutes / 60);
            $minutes = $minutes - ($hours * 60);
            if ($days > 0) {
                $days_string = esc_html__(' day ', 'foodbakery');
                if ($days > 1) {
                    $days_string = esc_html__(' days ', 'foodbakery');
                }
                $time .= $days . $days_string;
            }
            if ($hours > 0) {
                $hours_string = esc_html__(' Hour ', 'foodbakery');
                if ($hours > 1) {
                    $hours_string = esc_html__(' Hours ', 'foodbakery');
                }
                $time .= $hours . $hours_string;
            }
            if ($minutes > 0) {
                $minutes_string = esc_html__(' Minute ', 'foodbakery');
                if ($minutes > 1) {
                    $minutes_string = esc_html__(' Minutes ', 'foodbakery');
                }
                $time .= $minutes . $minutes_string;
            }
            return $time;
        }

        public function order_payment_status($order_id = '') {
            global $post;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $transaction_payment_status = 'pending';
            $transaction_order_id = $this->order_transaction_id($order_id);
            if ($transaction_order_id != '') {
                $transaction_status = get_post_meta($transaction_order_id, 'foodbakery_transaction_status', true);
                if ($transaction_status != '') {
                    $transaction_payment_status = $transaction_status;
                }
            }
            return $transaction_payment_status;
        }

        public function order_transaction_id($order_id = '') {
            global $post;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $order_trans_id = '';
            $args = array(
                'post_type' => 'foodbakery-trans',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'orderby'   => 'ID',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'foodbakery_transaction_order_id',
                        'value' => $order_id,
                        'compare' => '=',
                    )
                ),
            );
            $order_trans = new WP_Query($args);
            if ($order_trans->have_posts()) {
                while ($order_trans->have_posts()): $order_trans->the_post();
                    $order_trans_id = get_the_ID();
                endwhile;
            }
            wp_reset_postdata();
            return $order_trans_id;
        }

        public function order_buyer_info($order_id = '') {
            global $post;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $transaction_order_id = $this->order_transaction_id($order_id);
            if ($transaction_order_id != '') {
                $trans_first_name = get_post_meta($transaction_order_id, 'foodbakery_trans_first_name', true);
                $trans_last_name = get_post_meta($transaction_order_id, 'foodbakery_trans_last_name', true);
                $trans_email = get_post_meta($transaction_order_id, 'foodbakery_trans_email', true);
                $trans_phone_number = get_post_meta($transaction_order_id, 'foodbakery_trans_phone_number', true);
                $trans_address = get_post_meta($transaction_order_id, 'foodbakery_trans_address', true);
                if ($trans_first_name != '' || $trans_last_name != '' || $trans_email != '' || $trans_phone_number != '' || $trans_address != '') {
                    ?>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <div class="customer-detail-holder">
                            <h3><?php esc_html_e('Customer Detail', 'foodbakery'); ?></h3>
                            <ul class="customer-detail">
                                <?php if ($trans_first_name != '' || $trans_last_name) { ?>
                                    <li>
                                        <strong><?php esc_html_e('Name :', 'foodbakery') ?></strong>
                                        <span><?php echo esc_html($trans_first_name) . ' ' . esc_html($trans_last_name); ?></span>
                                    </li>
                                <?php } ?>
                                <?php if ('' != $trans_phone_number) { ?>
                                    <li>
                                        <strong><?php esc_html_e('Phone Number :', 'foodbakery') ?></strong>
                                        <span><?php echo esc_html($trans_phone_number); ?></span>
                                    </li>
                                <?php } ?>
                                <?php if ('' != $trans_email) { ?>
                                    <li>
                                        <strong><?php esc_html_e('Email :', 'foodbakery') ?></strong>
                                        <span><?php echo esc_html($trans_email); ?></span>
                                    </li>
                                <?php } ?>
                                <?php
                                $flag_address = apply_filters('foodbakery_check_delivery_tax', false);
                                if($flag_address) {
                                    do_action('foodbakery_show_delivery_details', $order_id);
                                }else{
                                if ('' != $trans_address) { ?>
                                    <li>
                                        <strong><?php esc_html_e('Address :', 'foodbakery') ?></strong>
                                        <span><?php echo esc_html($trans_address); ?></span>
                                    </li>
                                <?php }
                                }
                                ?>

                            </ul>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        public function print_order_status($order_id = '') {
            global $post, $foodbakery_plugin_options, $foodbakery_form_fields, $current_user;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $user_id = $current_user->ID;
            $publisher_id = foodbakery_company_id_form_user_id($user_id);
            $publisher_type = get_post_meta($publisher_id, 'foodbakery_publisher_profile_type', true);

            $order_status = get_post_meta($order_id, 'foodbakery_order_status', true);
            $order_status_color = $this->order_status_color($order_status);
            ?>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="order-status-holder">
                        <h3> <?php esc_html_e('Order Status:', 'foodbakery'); ?></h3>
                        <div class="order-status-process order-status">
                            <p><?php echo esc_html__($order_status); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public function order_status($order_id = '') {
            global $post, $foodbakery_plugin_options, $foodbakery_form_fields, $current_user;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $user_id = $current_user->ID;
            $publisher_id = foodbakery_company_id_form_user_id($user_id);
            $publisher_type = get_post_meta($publisher_id, 'foodbakery_publisher_profile_type', true);

            $order_status = get_post_meta($order_id, 'foodbakery_order_status', true);
            ?>
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="order-status-holder">
                    <?php if ($publisher_type == 'restaurant') { ?>
                        <div class="row">
                            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">	
                                <h3>
                                    <?php esc_html_e('Order Status', 'foodbakery'); ?>
                                </h3>
                            </div>
                            <div class="col-lg-9 col-md-8 col-sm-8 col-xs-12">
                                <div class="input-field">
                                    <?php
                                    $orders_status = isset($foodbakery_plugin_options['orders_status']) ? $foodbakery_plugin_options['orders_status'] : '';
                                    if (is_array($orders_status) && sizeof($orders_status) > 0) {
                                        foreach ($orders_status as $key => $label) {
                                            $drop_down_options[$label] = $label;
                                        }
                                    } else {
                                        $drop_down_options = array(
                                            'Processing' => esc_html__('Processing', 'foodbakery'),
                                            'Completed' => esc_html__('Completed', 'foodbakery'),
                                        );
                                    }
                                    $foodbakery_opt_array = array();
                                    $foodbakery_opt_array['std'] = $order_status;
                                    $foodbakery_opt_array['cust_id'] = 'order-inquiry-status';
                                    $foodbakery_opt_array['cust_name'] = 'order-inquiry-status';
                                    $foodbakery_opt_array['options'] = $drop_down_options;
                                    $foodbakery_opt_array['classes'] = 'chosen-select-no-single';
                                    $foodbakery_opt_array['extra_atr'] = ' onchange="foodbakery_update_order_status(this, \'' . $order_id . '\', \'' . admin_url('admin-ajax.php') . '\')"';
                                    $foodbakery_opt_array['return'] = false;
                                    $foodbakery_form_fields->foodbakery_form_select_render($foodbakery_opt_array);
                                    ?>
                                    <script type="text/javascript">
                                        jQuery(document).ready(function () {
                                            chosen_selectionbox();
                                        });
                                    </script>
                                    <span class="status-loader order-status-loader-<?php echo intval($order_id); ?>"></span> 
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <?php $order_status_color = $this->order_status_color($order_status); ?>
                        <div class="order-status-process order-status">
                            <p style="background:<?php echo esc_html($order_status_color); ?>;"><?php
                                printf(esc_html__('Your order is %s', 'foodbakery'), strtolower($order_status));
                                ?>
                            </p>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php
        }

        public function order_menu_list($order_id = '') {
            global $post;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $order_menu_list = get_post_meta($order_id, 'menu_items_list', true);
            $currency_sign = get_post_meta($order_id, 'foodbakery_currency', true);
            if (is_array($order_menu_list)) {
                $menu_order_fee = get_post_meta($order_id, 'menu_order_fee', true);
                $menu_order_fee_type = get_post_meta($order_id, 'menu_order_fee_type', true);
                $payment_list = '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
                $payment_list .= '
				<h2 class="heading">' . esc_html__('Food Menu', 'foodbakery') . '</h2>
				<div class="responsive-table">    
				<ul class="categories-order table-generic">';
                $payment_list .= '<li class="order-heading-titles">
					<div>' . esc_html__('Products', 'foodbakery') . '</div>	
					<div>' . esc_html__('Price per', 'foodbakery') . '</div>
				    </li>';
                $order_m_total = 0;
                foreach ($order_menu_list as $_menu_list) {
                    $title_item_cat = isset($_menu_list['category']) ? $_menu_list['category'] : '';
                    $title_item = isset($_menu_list['title']) ? $_menu_list['title'] : '';
                    $price_item = isset($_menu_list['price']) ? $_menu_list['price'] : '';
                    $extras_item = isset($_menu_list['extras']) ? $_menu_list['extras'] : '';

                    $order_m_total += floatval($price_item);
                    $payment_list .= '
					<li  class="order-heading-titles">
					<div>
						<h4>' . $title_item_cat . '</h4>
						<h5>' . $title_item . '</h5>
						';
                    if (is_array($extras_item) && sizeof($extras_item) > 0) {
                        $payment_list .= '<ul>';
                        foreach ($extras_item as $extra_item) {
                            $heading_extra_item = isset($extra_item['heading']) ? $extra_item['heading'] : '';
                            $title_extra_item = isset($extra_item['title']) ? $extra_item['title'] : '';
                            $price_extra_item = isset($extra_item['price']) ? $extra_item['price'] : '';
                            if ($title_extra_item != '' || $price_extra_item > 0) {
                                $payment_list .= '<li>' . $heading_extra_item . ' - ' . $title_extra_item . ' : ' . $currency_sign . foodbakery_get_currency($price_extra_item, false, '', '', false) . '</li>';
                            }
                            $order_m_total += floatval($price_extra_item);
                        }
                        $payment_list .= '</ul>';
                    }
                    $payment_list .= '</div>';
                    $payment_list .= '<div>';
                    $payment_list .= '<span class="category-price">' . $currency_sign . foodbakery_get_currency($price_item, false, '', '', false) . '</span>';
                    $payment_list .= '</div>';
                    $payment_list .= '
					</li>';
                }
                $payment_list .= '
					</ul></div>';
            }
            $payment_list .= '</div>';

            echo force_balance_tags($payment_list);
        }

        public function order_price($order_id = '') {
            global $post;

            if ($order_id == '') {
                $order_id = $post->ID;
            }

            $menu_order_fee_type = get_post_meta($order_id, 'menu_order_fee_type', true);
            $menu_order_fee = get_post_meta($order_id, 'menu_order_fee', true);
            $currency_sign = get_post_meta($order_id, 'foodbakery_currency', true);
            $order_subtotal_price = get_post_meta($order_id, 'order_subtotal_price', true);

            $services_total_price = get_post_meta($order_id, 'services_total_price', true);
            $order_vat_percent = get_post_meta($order_id, 'order_vat_percent', true);
            $order_vat_cal_price = get_post_meta($order_id, 'order_vat_cal_price', true);


            $wooc_order_all_data = get_post_meta($order_id, 'foodbakery_wooc_order_data', true);

            if ($order_subtotal_price != '' || ($menu_order_fee_type != '' && $menu_order_fee != '') || $order_vat_cal_price != '' || $services_total_price != '') {
                ?>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-12">
                            <h3><?php echo esc_html__('Order Total', 'foodbakery'); ?></h3>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <ul class="order-detail-options order-total">
                                <?php

                                if ($order_subtotal_price != '') { ?>
                                    <li class="created-date">
                                        <strong><?php esc_html_e('Subtotal:', 'foodbakery'); ?></strong>
                                        <span><?php echo esc_html__($currency_sign) . foodbakery_get_currency($order_subtotal_price, false, '', '', false); ?></span>
                                    </li>
                                <?php } ?>
                                <?php
                                $check_delivery_fee = apply_filters('foodbakery_check_delivery_tax', false);
                                if(!$check_delivery_fee){
                                    if (!isset($wooc_order_all_data) || empty($wooc_order_all_data)) { ?>
                                    <?php if ($menu_order_fee_type != '' && $menu_order_fee != '') { ?>
                                        <li class="order-type">
                                            <strong>
                                                <?php
                                                if ($menu_order_fee_type == '') {
                                                    esc_html_e('Delivery Fee:', 'foodbakery');
                                                } else {
                                                    esc_html_e('Pick Up Fee:', 'foodbakery');
                                                }
                                                ?>
                                            </strong>
                                            <span><?php echo esc_html($currency_sign) . foodbakery_get_currency($menu_order_fee, false, '', '', false); ?></span>
                                        </li>
                                    <?php } ?>
                                    <?php if ($order_vat_cal_price != '') { ?>
                                        <li class="order-type">
                                            <strong><?php printf(esc_html__('VAT (%s&#37;)', 'foodbakery'), $order_vat_percent) ?></strong>
                                            <span><?php echo esc_html($currency_sign) . foodbakery_get_currency($order_vat_cal_price, false, '', '', false) ?></span>
                                        </li>
                                    <?php }
                                }
                                ?>
                                <?php if (isset($wooc_order_all_data) && !empty($wooc_order_all_data)) { ?>
                    <?php foreach ($wooc_order_all_data as $wooc_order_data) { ?>
                                        <li class="order-type">
                                            <strong><?php echo esc_html(strip_tags($wooc_order_data['label'])); ?></strong>
                                            <span><?php echo esc_html(strip_tags($wooc_order_data['value'])); ?></span>
                                        </li>
                                    <?php }
                                }
                                }else{
                                     do_action('foodbakery_add_delivery_taxes_in_buyer_order_details', $order_id, $currency_sign);
                                }
                                ?>


                <?php if ($services_total_price != '') { ?>
                                    <li class="order-type total-price">
                                        <strong><?php esc_html_e('Total:', 'foodbakery') ?></strong>
                                        <span><?php
                                            if($check_delivery_fee){
                                                echo esc_html($currency_sign) . foodbakery_get_currency($services_total_price, false, '', '', false);
                                        }else{
                                            echo  esc_html($currency_sign) .   restaurant_menu_price_calc('defined', $order_subtotal_price, $menu_order_fee, true, false, false);
                                        } ?></span>
                                    </li>
                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php
            }
        }

        public function order_status_color($order_name = 'Processing') {
            global $foodbakery_plugin_options;

            $orders_status = isset($foodbakery_plugin_options['orders_status']) ? $foodbakery_plugin_options['orders_status'] : '';
            $orders_color = isset($foodbakery_plugin_options['orders_color']) ? $foodbakery_plugin_options['orders_color'] : '';
            if (is_array($orders_status) && sizeof($orders_status) > 0) {
                foreach ($orders_status as $key => $lable) {
                    if (strtolower($lable) == strtolower($order_name)) {
                        return $order_color = isset($orders_color[$key]) ? $orders_color[$key] : '';
                        break;
                    }
                }
            }
        }

        public function foodbakery_update_order_status_callback() {
            $json = array();

            $order_id = foodbakery_get_input('order_id', NULL, 'STRING');
            $order_status = foodbakery_get_input('order_status', NULL, 'STRING');

            if ($order_id && $order_status) {
                update_post_meta($order_id, 'foodbakery_order_status', $order_status);
                $order_paytype = get_post_meta($order_id, 'foodbakery_order_paytype', true);
                $order_comision_charge = get_post_meta($order_id, 'order_amount_charged', true);

                $earning_subtract = get_post_meta($order_id, 'earning_subtract_to_publisher', true);
                echo ($earning_subtract);
                update_post_meta($order_id, 'earning_subtract_to_publisher', '');

                if ($earning_subtract != 'yes' && $order_status == 'Completed' && $order_paytype == 'cash' && $order_comision_charge > 0) {

                    $order_restaurant = get_post_meta($order_id, 'foodbakery_publisher_id', true);
                    $restaurant_subtracts = get_post_meta($order_restaurant, 'total_cash_subtracts', true);
                    if ($restaurant_subtracts < 0) {
                        $restaurant_subtracts = 0;
                    }
                    $restaurant_subtracts = $restaurant_subtracts + $order_comision_charge;

                    update_post_meta($order_restaurant, 'total_cash_subtracts', $restaurant_earnings);

                    $order_restaurantsss = get_post_meta($order_restaurant, 'total_cash_subtracts', true);
                    echo ($order_restaurantsss);

                    update_post_meta($order_id, 'earning_subtract_to_publisher', 'yes');
                }

                // Update order status email
                do_action('foodbakery_order_status_updated_email', $order_id);


                $json['type'] = "success";
                $json['msg'] = esc_html__("Order status has been changed.", "foodbakery");
                $json['status'] = esc_html($order_status);
            } else {
                $json['type'] = "error";
                $json['msg'] = esc_html__("Order status not changed.", "foodbakery");
                $json['status'] = '';
            }

            echo json_encode($json);
            exit();
        }

    }

    global $foodbakery_order_detail;
    $foodbakery_order_detail = new Foodbakery_Order_Detail();
}