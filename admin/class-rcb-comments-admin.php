<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Rcb_Comments
 * @subpackage Rcb_Comments/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rcb_Comments
 * @subpackage Rcb_Comments/admin
 * @author     Your Name <email@example.com>
 */
class Rcb_Comments_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;
    public $tabs = array();
    public static $message = '';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        $screen = get_current_screen();

        if ('comments_page_rcb-recobox' == $screen->id) {
            wp_enqueue_script('jquery');
        }

    }

    public function add_submenu_page()
    {
        global $submenu, $menu;
        unset($submenu['edit-comments.php'][0]);
        add_submenu_page(
            'edit-comments.php',
            'Recobox настройки',
            'Recobox настройки',
            'edit_others_posts',
            'rcb-recobox',
            array($this, 'generate_admin_page')
        );
        foreach( $menu as $key => $value ){
            if( $menu[$key][2] == 'edit-comments.php' ){
                $menu[$key][0] = __('All Comments');
                break;
            }
        }
    }

    public function generate_admin_page()
    {
        $this->get_settings_page();
        $this->save_settings();
        $this->tabs = apply_filters('rcb_comments_settings_tabs_array', $this->tabs);
        require_once(plugin_dir_path(__FILE__) . 'partials/views/admin.php');
    }

    public function save_settings()
    {
        $current_tab = (isset($_GET['tab'])) ? sanitize_title($_GET['tab']) : 'general';
        do_action('rcb_comments_save_' . $current_tab);

    }

    public function get_settings_page()
    {
        require_once(plugin_dir_path(__FILE__) . 'partials/rcb-comments-admin-settings.php');
        require_once(plugin_dir_path(__FILE__) . 'partials/rcb-comments-admin-export.php');
        require_once(plugin_dir_path(__FILE__) . 'partials/rcb-comments-admin-import.php');
    }

    public static function get_message()
    {
        return self::$message;
    }

    public static function set_message($class, $message)
    {
        self::$message = '<div class=' . $class . '><p>' . $message . '</p></div>';
    }

    public function checkDataApi($settings)
    {
        if (!isset($settings['widget_id']) && empty($settings['widget_id'])) {
            echo json_encode([
                'result' => 'error',
                'text' => 'Загрузка не возможна, не указан ID виджета.'
            ]);
            wp_die();
        }

        if (!isset($settings['api_key']) && empty($settings['api_key'])) {
            echo json_encode([
                'result' => 'error',
                'text' => 'Загрузка не возможна, не указан API ключ виджета.'
            ]);
            wp_die();
        }
    }

    public function import_channels()
    {
        global $wpdb;
        $tr = [];

        $settings = get_option('rcb_comments_settings');

        $this->checkDataApi($settings);

        $widgetId = $settings['widget_id'];
        $apiKey = $settings['api_key'];

        $offset = (int)$_POST['offset'];

        $toRecoboxArray = [
            'widget_id' => $widgetId,
            'api_key' => $apiKey,
            'limit' => 200,
            'offset' => $offset
        ];

        $response = wp_remote_post(
            'https://recobox.ru/api/1.0/channels/lists',
            [
                'body' => $toRecoboxArray
            ]
        );
        $responseBody = json_decode(wp_remote_retrieve_body($response));

        if (!isset($responseBody->meta)) {
            echo json_encode([
                'result' => 'error',
                'text' => 'Вернулись не корректные данные.'
            ]);
            wp_die();
        }

        if (!isset($responseBody->meta->total) || !isset($responseBody->meta->offset)) {
            echo json_encode([
                'result' => 'error',
                'text' => 'Вернулись не корректные данные.'
            ]);
            wp_die();
        }

        $channesTableName = $wpdb->get_blog_prefix() . 'recobox_channels';
        $channelsInDB = $wpdb->get_col("SELECT * FROM $channesTableName", 2);

        foreach ($responseBody->data as $channel) {
            if (!in_array($channel->id, $channelsInDB)) {
                $wpdb->insert(
                    $wpdb->get_blog_prefix() . 'recobox_channels',
                    [
                        'post_id' => $channel->name,
                        'recobox_id' => $channel->id,
                        'rating' => json_encode($channel->rating)
                    ],
                    ['%s', '%s']
                );
            }
        }

        $tr[] = [
            'tag' => 'th',
            'text' => 'Загрузка списка каналов в базу сайта, загружено ' . count($responseBody->data) . ' из ' . $responseBody->meta->total
        ];
        if ($responseBody->meta->total > count($responseBody->data)) {
            echo json_encode([
                'offset' => $offset + $responseBody->meta->offset,
                'action' => 'import_channels',
                'tr' => $tr
            ]);
        } else {
            echo json_encode([
                'action' => 'import_comments',
                'offset' => 0,
                'recobox_id' => 0,
                'tr' => $tr
            ]);
        }

        wp_die();
    }

    public function import_comments()
    {
        global $wpdb;

        $settings = get_option('rcb_comments_settings');
        $tr = [];

        $this->checkDataApi($settings);

        $widgetId = $settings['widget_id'];
        $apiKey = $settings['api_key'];

        $offset = (int)$_POST['offset'];
        $recoboxId = (int)$_POST['recobox_id'];

        $channesTableName = $wpdb->get_blog_prefix() . 'recobox_channels';
        $recoboxChannelInDb = $wpdb->get_row($wpdb->prepare(
            "
                        SELECT *
                        FROM $channesTableName
                          WHERE recobox_id >= %d
                          ORDER BY recobox_id ASC
                          LIMIT 1
                          ", $recoboxId));

        if (empty($recoboxChannelInDb)) {
            echo json_encode([
                'result' => 'finish'
            ]);
            wp_die();
        }

        $toRecoboxArray = [
            'widget_id' => $widgetId,
            'api_key' => $apiKey,
            'limit' => 200,
            'offset' => $offset,
            'cid' => $recoboxChannelInDb->recobox_id
        ];

        $response = wp_remote_post(
            'https://recobox.ru/api/1.0/comments/lists',
            [
                'body' => $toRecoboxArray
            ]
        );
        $responseBody = json_decode(wp_remote_retrieve_body($response));

        if ($responseBody->result == 'error') {
            echo json_encode([
                'result' => 'error',
                'text' => $responseBody->text
            ]);
            wp_die();
        }

        if (!isset($responseBody->meta)) {
            echo json_encode([
                'result' => 'error',
                'text' => 'Вернулись не корректные данные.'
            ]);
            wp_die();
        }

        if (!isset($responseBody->meta->total) || !isset($responseBody->meta->offset)) {
            echo json_encode([
                'result' => 'error',
                'text' => 'Вернулись не корректные данные.'
            ]);
            wp_die();
        }

        if ($responseBody->meta->total > count($responseBody->data)) {
            $this->import_comments_in_db($responseBody, $recoboxChannelInDb);
            $tr[] = [
                'tag' => 'th',
                'text' => 'Загрузка комментариев с канала: ' . $recoboxChannelInDb->recobox_id . ', загружено ' . count($responseBody->data) . ' из ' . $responseBody->meta->total
            ];
            echo json_encode([
                'offset' => $offset + $responseBody->meta->offset,
                'action' => 'import_comments',
                'tr' => $tr
            ]);
        } else if (empty($responseBody->meta->channel_id) || $responseBody->meta->total > 0) {
            $rr = $this->import_comments_in_db($responseBody, $recoboxChannelInDb);
            $tr[] = [
                'tag' => 'th',
                'text' => 'Загрузка комментариев с канала: ' . $recoboxChannelInDb->recobox_id . ', загружено ' . count($responseBody->data) . ' из ' . $responseBody->meta->total
            ];
            echo json_encode([
                'action' => 'import_comments',
                'offset' => 0,
                'recobox_id' => ++$recoboxChannelInDb->recobox_id,
                'tr' => $tr,
                'data' => $rr,
                'channel' => $responseBody
            ]);
        } else if ($responseBody->meta->channel_id && $responseBody->meta->total == 0) {
            echo json_encode([
                'action' => 'import_comments',
                'offset' => 0,
                'recobox_id' => ++$recoboxChannelInDb->recobox_id,
            ]);
        } else {
            echo json_encode([
                'result' => 'finish',
                'fgh' => $responseBody
            ]);
        }

        wp_die();
    }

    public function import_comments_in_db($data, $postChannel)
    {
        global $wpdb;
        if (count($data->data) > 0) {
            $commentsInDb = $wpdb->get_results($wpdb->prepare(
                "
                    SELECT 
                      com.comment_ID as id,
                      mcom.meta_value as recobox_comment_id 
                    FROM $wpdb->comments as com
                    LEFT JOIN $wpdb->commentmeta as mcom
                      ON mcom.comment_id = com.comment_ID
                    WHERE com.comment_post_ID = %d
                      AND mcom.meta_value IS NOT NULL
                ", $postChannel->post_id
            ));

            $comIds = array_column($commentsInDb, 'id');
            $comRecoboxIds = array_column($commentsInDb, 'recobox_comment_id');

            foreach ($data->data as $comment) {
                if (in_array($comment->id, $comRecoboxIds))
                    continue;

                $parentId = 0;
                if($comment->parent_id > 0) {
                    $parentKey = array_search($comment->parent_id, $comRecoboxIds);
                    if ($parentKey !== false) {
                        $parentId = $comIds[$parentKey];
                    }
                }

                $dateTime = new DateTime($comment->created_at);
                $data = array(
                    'comment_post_ID' => $postChannel->post_id,
                    'comment_author' => $comment->user->name,
                    'comment_author_email' => $comment->user->email,
                    'comment_author_url' => $comment->user->social_page,
                    'comment_content' => $comment->text,
                    'comment_type' => '',
                    'comment_parent' => $parentId,
                    'user_id' => $comment->user->sso_id ? $comment->user->sso_id : 0,
                    'comment_author_IP' => $comment->ip,
                    'comment_date' => strftime("%Y-%m-%d %H:%M:%S", $dateTime->getTimestamp() + (get_option('gmt_offset') * 3600)),
                    'comment_date_gmt' => strftime("%Y-%m-%d %H:%M:%S", $dateTime->getTimestamp()),
                    'comment_approved' => $this->commentStatusFormat($comment->status),
                    'comment_meta' => [
                        'recobox_comment_id' => $comment->id
                    ]
                );

                wp_insert_comment( wp_slash($data) );
            }

            return [
                $commentsInDb, $comIds, $comRecoboxIds, array_search(1285, $comRecoboxIds)
            ];
        }
    }

    public function commentStatusFormat($status)
    {
        if ($status == "approved") {
            $st = 1;
        } elseif ($status == "pending") {
            $st = 0;
        } elseif ($status == "spam") {
            $st = "spam";
        } elseif ($status == "deleted") {
            $st = "trash";
        }
        return $st;
    }

    public function export_comments()
    {
        global $wpdb;

        $settings = get_option('rcb_comments_settings');

        $this->checkDataApi($settings);

        $widgetId = $settings['widget_id'];
        $apiKey = $settings['api_key'];

        $offset = (int)$_POST['offset'];
        $postId = (int)$_POST['post_id'];

        $post = $wpdb->get_row($wpdb->prepare(
            "
                        SELECT *
                        FROM $wpdb->posts
                          WHERE post_type != 'revision'
                            AND post_status = 'publish'
                            AND comment_count > 0
                            AND ID >= %d
                          ORDER BY ID ASC
                          LIMIT 1
                          ", $postId));

        if (empty($post)) {
            echo json_encode([
                'result' => 'finish'
            ]);
            wp_die();
        }

        $comments = get_comments([
            'post_id' => $post->ID,
            'number' => 200,
            'offset' => $offset,
            'orderby' => 'comment_date',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'recobox_comment_id',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        $toRecoboxArray = [
            'widget_id' => $widgetId,
            'api_key' => $apiKey,
            'channel' => [
                'name' => $post->ID,
                'url' => get_permalink($post->ID),
                'title' => $post->post_title
            ],
            'comments' => []
        ];

        $tr = [];

        if ($offset == 0) {
            $tr[] = [
                'tag' => 'th',
                'text' => 'Загрузка комментариев с канала: ' . $post->post_title . ' (' . $post->ID . ')'
            ];
        }

        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                $created = $comment->comment_date ? new DateTime($comment->comment_date) : new DateTime();
                $toRecoboxArray['comments'][] = [
                    'id' => $comment->comment_ID,
                    'text' => $comment->comment_content,
                    'ip' => $comment->comment_author_IP ? $comment->comment_author_IP : $_SERVER['SERVER_ADDR'],
                    'status' => $this->getStatusComment($comment->comment_approved),
                    'parent_id' => $comment->comment_parent,
                    'created_at' => $created->getTimestamp(),
                    'user' => [
                        'id' => $comment->user_id > 0 ? $comment->user_id : null,
                        'name' => $comment->comment_author,
                        'email' => $comment->comment_author_email ? $comment->comment_author_email : 'default@' . str_replace('www.', '', $_SERVER['HTTP_HOST']),
                    ]
                ];
            }
            if (count($comments) >= 200) {
                $offset = $offset + 200;
            } else {
                $offset = 0;
                $postId = $post->ID + 1;
            }
            $tr[] = [
                'tag' => 'td',
                'text' => ' - Загружено комментариев: ' . count($comments)
            ];
        } else {
            $postId = $post->ID + 1;
            $offset = 0;
        }

        /**
         *
         */
        if (count($toRecoboxArray['comments']) > 0) {
            $response = wp_remote_post(
                'https://recobox.ru/api/1.0/comments/add',
                [
                    'body' => $toRecoboxArray
                ]
            );
            $responseBody = json_decode(wp_remote_retrieve_body($response));
            //echo json_encode($responseBody);
            //wp_die();
        }


        echo json_encode([
            'post_id' => $postId,
            'offset' => $offset,
            'action' => 'export_comments',
            'tr' => $tr
        ]);

        wp_die();
    }

    public function getStatusComment($status)
    {
        if ($status == "1") {
            $status = "approved";
        } elseif ($status == "0") {
            $status = "pending";
        } elseif ($status == "spam") {
            $status = "spam";
        } elseif ($status == "trash") {
            $status = "deleted";
        }
        return $status;
    }

    function comments_number_post($comment_text) {
        global $post;
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
        return '<span class="rcb-span-container" id="rcb-counter-' . htmlspecialchars($postId) . '">' . $comment_text . '</span>';
    }
}
