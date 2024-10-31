<?php
global $wpdb;
global $post;
$settings = get_option('rcb_comments_settings');

if (!isset($settings['widget_id']) && empty($settings['widget_id'])) {
    $errorText = 'Загрузка виджета не возможна, не указан ID в настройках';
} else {

    $sso = 0;
    if (isset($settings['sso'])) {
        $sso = $settings['sso'];

        $api_key = '';
        if (isset($settings['api_key']) && !empty($settings['api_key'])) {
            $api_key = $settings['api_key'];
        }
    }
    $sync = 0;
    if (isset($settings['sync'])) {
        $sync = $settings['sync'];
    }

	$host = $_SERVER['HTTP_HOST'];
	$host = str_replace('www.', '', $host);
	$hosts = explode('.', $host);
	if(count($hosts) > 2) {
        $prefix = [];
        for($i = 0; $i < (count($hosts) - 2); $i++) {
            $prefix[] = $hosts[$i];
        }
        $postId = implode('_', $prefix) . '_' . $post->ID;
    } else {
        $postId = $post->ID;
    }

    $channesTableName = $wpdb->get_blog_prefix() . 'recobox_channels';
    $recoboxChannel = $wpdb->get_row($wpdb->prepare(
        "
                        SELECT *
                        FROM $channesTableName
                          WHERE post_id = %d
                          ", $postId));
    $htmlRating = '';
    if (!empty($recoboxChannel)) {

        if(!empty($recoboxChannel->rating)) {
            $rating = (array)json_decode($recoboxChannel->rating);
            switch ($rating['type']) {
                case 'stars':
                    $ratingValue = $rating['rating_avg'];
                    $ratingCount = $rating['rating_count'];
                    break;
                case 'like_dislike':
                    $ratingValue = $rating['likes'] - $rating['dislikes'];
                    if($ratingValue < 0)
                        $ratingValue = 0;
                    else
                        $ratingValue = 5;
                    $ratingCount = $rating['likes'] + $rating['dislikes'];
                    break;
            }

            $htmlRating = '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
                    Рейтинг <span itemprop="ratingValue">' . $ratingValue . '</span> из <span itemprop="bestRating">5</span>. 
                    Проголосовало: <span itemprop="ratingCount">' . $ratingCount . '</span></div>';
        }

    }


    do_action('comment_form_before');
	
	if(comments_open()) {
    ?>
    <div id="rcb-comments-area">
        <div id="rcb-comments">
            <?php
            echo $htmlRating;
            if ($sync) { ?>
                <ul id="recobox-comments">
                    <?php
                    wp_list_comments();
					//array(), get_comments(array('orderby' => 'comment_date', 'order' => 'DESC', 'number' => 20))
                    ?>
                </ul>
            <?php } ?>
        </div>
    </div>
	<?php do_action('comment_form_after'); ?>

    <script type="text/javascript">
        (function () {
            RCB = window.RCB || [];
            data = {
                widget: "Comments",
                id: <?php echo $settings['widget_id'] ?>,
                cname: '<?php echo $postId ?>',
                count_container: '#rcb-counter-<?php echo $post->ID ?>'
            };
            <?php
            if ($sso && strlen($api_key) > 0) {
            if (is_user_logged_in()) {
            $user = wp_get_current_user();

            $userArr = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar' => rcbCommentsGetAvatarPath($user->ID)
            );
            $siteApiKey = $api_key;
            $user_data = base64_encode(json_encode($userArr));
            $timestamp = time();
            $sign = md5($user_data . $siteApiKey . $timestamp);
            ?>
            data.sso = {};
            data.sso.auth = <?php echo "'$user_data $sign $timestamp'" ?>;
            <?php
            }
            }
            ?>
            RCB.push(data);
            var RCBScript = document.createElement("script");
            RCBScript.type = "text/javascript";
            RCBScript.async = true;
            RCBScript.src = "https://recobox.ru/widget/widget.js";
            var s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(RCBScript, s.nextSibling);
        })();
    </script>
    <?php
	}
}

function rcbCommentsGetAvatarPath($id)
{
    $avatar_path = get_avatar($id);
    $avatar_path = str_replace("&#038;", "&", $avatar_path);
    preg_match("/src=(\'|\")(.*)(\'|\")/Uis", $avatar_path, $matches);
    $avatar_src = substr(trim($matches[0]), 5, strlen($matches[0]) - 6);
    if (strpos($avatar_src, 'http') === false) {
        $avatar_src = get_option('siteurl') . $avatar_src;
    }
    return $avatar_src;
}

?>