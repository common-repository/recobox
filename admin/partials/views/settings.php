<?php
//Load Data
$widget_id = '';
if ( isset( $this->settings['widget_id'] ) && ! empty( $this->settings['widget_id'] ) ) {
    $widget_id = $this->settings['widget_id'];
}
$api_key = '';
if ( isset( $this->settings['api_key'] ) && ! empty( $this->settings['api_key'] ) ) {
    $api_key = $this->settings['api_key'];
}
$sso = 0;
if ( isset( $this->settings['sso'] ) ) {
    $sso = $this->settings['sso'];
}
$counter = 0;
if ( isset( $this->settings['counter'] ) ) {
    $counter = $this->settings['counter'];
}
$sync = 0;
if ( isset( $this->settings['sync'] ) ) {
    $sync = $this->settings['sync'];
}
?>
<form id="rcb-comments-general-settings-form" class="" action="" method="post" data-rcb-comments-validate="true">
    <?php wp_nonce_field( 'verify_rcb_comments_settings_nonce', 'rcb_comments_settings_nonce' ); ?>
    <table class="form-table">
        <tr>
            <th><label>Widget ID</label></th>
            <td>
                <input type="text" id="widget_id" name="widget_id" class="regular-text" value="<?php echo esc_attr($widget_id); ?>"/>
                <p class="description">ID виджета можно найти в настройках виджета на первой странице в личном кабинете Recobox.</p>
            </td>
        </tr>
        <tr>
            <th><label>API ключ</label></th>
            <td>
                <input type="text" id="api_key" name="api_key" class="regular-text" value="<?php echo esc_attr($api_key); ?>"/>
                <p class="description">Api ключ виджета можно найти в настройках виджета на вкладке "Уведомления" в личном кабинете Recobox.</p>
            </td>
        </tr>
        <tr>
            <th><label>Счетчик комментариев</label></th>
            <td>
                <input type="checkbox" id="counter" name="counter" value="1" <?php echo ($counter == 1 ? 'checked' : '') ?> />
                <p class="description">На страницах со списками записей выводит кол-во комментариев. Если ваш шаблон не выводит кол-во комментариев к записи, то рекомендуется отключить эту опцию.</p>
            </td>
        </tr>
        <tr>
            <th><label>Единая авторизация</label></th>
            <td>
                <input type="checkbox" id="sso" name="sso" value="1" <?php echo ($sso == 1 ? 'checked' : '') ?> />
                <p class="description">При включенной настройке, пользователи будут автоматически авторизованы в виджете.</p>
            </td>
        </tr>
        <tr>
            <th><label>Синхронизация</label></th>
            <td>
                <input type="checkbox" id="sync" name="sync" value="1" <?php echo ($sync == 1 ? 'checked' : '') ?> />
                <p class="description">Синхронизация комментариев с вашей базой данных сайта. Каждый новый комментарий будет добавлен в вашу базу. Так же комментарии будут выводится на странице перед инициализацией виджета что позволит поисковым системам их индексировать.</p>
            </td>
        </tr>
        <tr>
            <th><label>HTTP уведомления</label></th>
            <td>
                <?php
                $response_url = get_site_url() . '/wp-json/rcb-comments/v1/data';
                ?>
                В настройках виджета на вкладке "Уведомления" укажите этот адрес: <code><?php echo $response_url; ?></code><br />
                На этот адрес Recobox будет отправлять различные уведомления связанные с добавлением/изменениеми/удалением комментариев, а плагин автоматически будет их обрабатывать.<br />
                Эта схема будет работать если у вас включена опция выше "Синхронизация".
            </td>
        </tr>
    </table>

    <p class="submit">
        <input type="submit" class="button button-primary" value="Сохранить"/>
    </p>
</form>