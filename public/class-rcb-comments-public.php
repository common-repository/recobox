<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Rcb_Comments
 * @subpackage Rcb_Comments/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Rcb_Comments
 * @subpackage Rcb_Comments/public
 * @author     Your Name <email@example.com>
 */
class Rcb_Comments_Public
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

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
    }

    public function comments_template()
    {
        return dirname(__FILE__) . '/partials/rcb-comment-template.php';
    }

    public function get_data_from_recobox()
    {
        register_rest_route('rcb-comments/v1', 'data', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'get_data_from_recobox_func'],
            ]
        ]);
    }

    public function get_data_from_recobox_func($request)
    {
        //$test_wr = fopen('testlog.txt', 'a+');
        //fwrite($test_wr, print_r($request, true));
        //fclose($test_wr);

        $type = $request->get_param('type');

        if ($type) {
            switch ($type) {
                case 'new':
                    $this->newComment($request->get_param('comment'));
                    break;
                case 'edit':
                    $this->editComment($request->get_param('comment'));
                    break;
                case 'migrate':
                    $this->migrateComment($request->get_param('migrate'));
                    break;
                case 'change_status':
                    $this->changeStatusComment($request->get_param('new_status'), $request->get_param('ids'));
                    break;
            }
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

    public function changeStatusComment($status, $ids)
    {
        global $wpdb;

        $comments = get_comments([
            'meta_query' => [
                [
                    'key' => 'recobox_comment_id',
                    'value' => $ids,
                    'compare' => 'IN'
                ]
            ]
        ]);

        $idsInDb = [];
        foreach ($comments as $comment) {
            $idsInDb[] = $comment->comment_ID;
        }

        if (count($idsInDb) > 0) {
            $wpdb->query($wpdb->prepare(
                "
                        UPDATE $wpdb->comments
                          SET comment_approved = %s
                          WHERE comment_ID IN (" . implode(',', $idsInDb) . ")
                          ", $this->commentStatusFormat($status)));
        }
    }

    public function migrateComment($data)
    {
        global $wpdb;
        if (isset($data['from_channel']) && isset($data['to_channel'])) {

            $channesTableName = $wpdb->get_blog_prefix() . 'recobox_channels';
            $recoboxChannelFrom = $wpdb->get_row($wpdb->prepare(
                "
                        SELECT *
                        FROM $channesTableName
                          WHERE recobox_id = %d
                          ", $data['from_channel']));

            if (empty($recoboxChannelFrom))
                return;

            $recoboxChannelTo = $wpdb->get_row($wpdb->prepare(
                "
                        SELECT *
                        FROM $channesTableName
                          WHERE recobox_id = %d
                          ", $data['to_channel']));

            if (empty($recoboxChannelTo))
                return;

            $wpdb->update($wpdb->comments,
                array('comment_post_ID' => $recoboxChannelTo->post_id),
                array('comment_post_ID' => $recoboxChannelFrom->post_id)
            );
        }
    }

    public function editComment($comment)
    {
        global $wpdb;
        if ($comment) {

            $commentId = $wpdb->get_var($wpdb->prepare(
                "
                        SELECT *
                        FROM $wpdb->commentmeta
                          WHERE meta_value >= %d
                              AND meta_key = %s
                          ", $comment['id'], 'recobox_comment_id'), 1);

            if ($commentId) {
                $dateTime = new DateTime($comment['created_at']);
                wp_update_comment([
                    'comment_ID' => $commentId,
                    'comment_content' => isset($comment['text']) ? $comment['text'] : null,
                    'comment_date' => strftime("%Y-%m-%d %H:%M:%S", $dateTime->getTimestamp() + (get_option('gmt_offset') * 3600)),
                    'comment_date_gmt' => strftime("%Y-%m-%d %H:%M:%S", $dateTime->getTimestamp()),
                ]);
            }
        }
    }

    public function newComment($comment)
    {
        global $wpdb;

        if ($comment) {
            $parentId = 0;

            if (isset($comment['parent_id']) && $comment['parent_id'] > 0) {
                $parentDBId = $wpdb->get_var($wpdb->prepare(
                    "
                        SELECT *
                        FROM $wpdb->commentmeta
                          WHERE meta_value >= %d
                              AND meta_key = %s
                          ", $comment['parent_id'], 'recobox_comment_id'), 1);

                if ($parentDBId)
                    $parentId = $parentDBId;
            }

            $dateTime = new DateTime($comment['created_at']);
            $data = array(
                'comment_post_ID' => $comment['channel']['name'],
                'comment_author' => $comment['user']['name'],
                'comment_author_email' => isset($comment['user']['email']) ? $comment['user']['email'] : null,
                'comment_author_url' => isset($comment['user']['social_page']) ? $comment['user']['social_page'] : null,
                'comment_content' => isset($comment['text']) ? $comment['text'] : null,
                'comment_type' => '',
                'comment_parent' => $parentId,
                'user_id' => isset($comment['user']['sso_id']) ? $comment['user']['sso_id'] : 0,
                'comment_author_IP' => $comment['ip'],
                'comment_date' => strftime("%Y-%m-%d %H:%M:%S", $dateTime->getTimestamp() + (get_option('gmt_offset') * 3600)),
                'comment_date_gmt' => strftime("%Y-%m-%d %H:%M:%S", $dateTime->getTimestamp()),
                'comment_approved' => $this->commentStatusFormat($comment['status']),
                'comment_meta' => [
                    'recobox_comment_id' => $comment['id']
                ]
            );

            wp_insert_comment(wp_slash($data));

            $channesTableName = $wpdb->get_blog_prefix() . 'recobox_channels';
            $recoboxChannel = $wpdb->get_row($wpdb->prepare(
                "
                        SELECT *
                        FROM $channesTableName
                          WHERE recobox_id = %d
                          ", $comment['channel']['id']));

            if (empty($recoboxChannel)) {

                $wpdb->insert(
                    $wpdb->get_blog_prefix() . 'recobox_channels',
                    [
                        'post_id' => $comment['channel']['name'],
                        'recobox_id' => $comment['channel']['id'],
                        'rating' => json_encode($comment['channel']['rating'])
                    ],
                    ['%s', '%s', '%s']
                );

            } else {

                $wpdb->update(
                    $wpdb->get_blog_prefix() . 'recobox_channels',
                    [
                        'rating' => json_encode($comment['channel']['rating'])
                    ],
                    [
                        'post_id' => $comment['channel']['name'],
                        'recobox_id' => $comment['channel']['id'],
                    ],
                    ['%s'],
                    ['%s', '%s']
                );

            }
        }
    }

    public function add_comment_counter_script()
    {
        if (is_single() || is_page()) {
        } else {
            require_once(dirname(__FILE__) . '/partials/rcb-counter.php');
        }
    }

}
