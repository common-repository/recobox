<div class="wrap">
    <h1>Настройка системы комментирования Recobox</h1>

    <div class="updated smsru-plugin-documentation-nag">
        <p>
            Если на сайте установлены и активированны плагины других систем комментирования то рекомендуется их отключить, т.к. возможет конфликт приоритетов замены стандарной формы комментариев на форму системы Recobox.
        </p>
    </div>
    <div class="updated smsru-plugin-documentation-nag">
        <p>
            Для модерации комментариев перейдите в административную панель сервиса <a href="https://recobox.ru">Recobox</a> и выберите Ваш виджет.
        </p>
    </div>

    <h2 class="nav-tab-wrapper">
        <?php

        foreach ( $this->tabs as $key => $value ) {

            if ( ! isset( $_GET['tab'] ) ) {
                if ( 'general' === $key ) {
                    $active_class = 'nav-tab-active';
                } else {
                    $active_class = '';
                }

            } else {
                if ( $_GET['tab'] === $key ) {
                    $active_class = 'nav-tab-active';
                } else {
                    $active_class = '';
                }
            }

            ?>

            <a href="<?php echo add_query_arg( array(
                'page' => 'rcb-recobox',
                'tab'  => $key
            ), admin_url( 'admin.php' ) ); ?>" class="nav-tab <?php echo $active_class; ?>">
                <?php printf('%s', $value ); ?>
            </a>

            <?php
        }
        ?>

    </h2>
    <div class="message">
        <?php
        $message = self::get_message();
        if ( isset( $message ) && ! empty( $message ) ) {
            echo $message;
        }
        ?>
    </div>
    <?php
    $current_tab = ( isset ( $_GET['tab'] ) ) ? sanitize_title( $_GET['tab'] ) : 'general';
    do_action( 'rcb_comments_settings_' . $current_tab );
    ?>
</div>