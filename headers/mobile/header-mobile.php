<h2>This is Heading</h2>

<div id="goal-header-mobile" class="header-mobile hidden-lg hidden-md clearfix">    
    <div class="container-fluid">
        <div class="row">
            <div class="flex-middle">
                <div class="col-xs-3">
                    <div class="box-left">
                        <a href="javascript:void(0);" class="btn btn-showmenu"><i class="ti-align-left"></i></a>
                    </div>
                </div>
                <div class="text-center col-xs-6">
                    <?php
                        $logo = lindo_get_config('media-mobile-logo');
                        $logo_url = '';
                        if ( !empty($logo['id']) ) {
                            $img = wp_get_attachment_image_src($logo['id'], 'full');
                            if ( !empty($img[0]) ) {
                                $logo_url = $img[0];
                            }
                        }
                    ?>
                    <?php if( isset($logo['url']) && !empty($logo['url']) ): ?>
                        <div class="logo">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" >
                                <img src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php bloginfo( 'name' ); ?>">
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="logo logo-theme">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" >
                                <img src="<?php echo esc_url_raw( get_template_directory_uri().'/images/logo.svg'); ?>" alt="<?php bloginfo( 'name' ); ?>">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-xs-3">
                    <?php if ( defined('LINDO_WOOCOMMERCE_ACTIVED') && lindo_get_config('show_cartbtn') && !lindo_get_config( 'enable_shop_catalog' ) ): ?>
                        <div class="box-right pull-right">
                            <!-- Setting -->
                            <div class="top-cart">
                                <?php get_template_part( 'woocommerce/cart/mini-cart-button' ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</div>
<?php if ( lindo_get_config('show_vertical_menu') && has_nav_menu( 'vertical-menu' ) ): ?>
    <div class="mobile-vertical-menu hidden-lg" style="display: none;">
        <div class="container">
            <nav class="navbar navbar-offcanvas navbar-static" role="navigation">
                <?php
                    $args = array(
                        'theme_location' => 'vertical-menu',
                        'container_class' => 'navbar-collapse navbar-offcanvas-collapse no-padding',
                        'menu_class' => 'nav navbar-nav',
                        'fallback_cb' => '',
                        'menu_id' => 'vertical-mobile-menu',
                        'walker' => new Lindo_Mobile_Vertical_Menu()
                    );
                    wp_nav_menu($args);
                ?>
            </nav>
        </div>
    </div>
<?php endif; ?>