<?php
$settings = get_option('rcb_comments_settings');
if (isset($settings['counter']) && $settings['counter'] == 1) {
    if (isset($settings['widget_id']) && !empty($settings['widget_id'])) {

        ?>
        <script type="text/javascript">
            // <![CDATA[
            var nodes = document.getElementsByTagName('span');
            for (var i = 0, url; i < nodes.length; i++) {
                if (nodes[i].className.indexOf('rcb-span-container') != -1) {
                    var c_id = nodes[i].getAttribute('id').split('rcb-counter-');
                    nodes[i].parentNode.setAttribute('data-rcb-channel-name', c_id[1]);
                    nodes[i].parentNode.classList.add('rcb-count-container');
                    url = nodes[i].parentNode.href.split('#', 1);
                    if (url.length == 1) url = url[0];
                    else url = url[1]
                    nodes[i].parentNode.href = url + '#rcb-comments';
                }
            }


            RCB = window.RCB || [];
            RCB.push({
                widget: 'CommentCount',
                id: <?php echo $settings['widget_id'] ?>,
            });
            (function () {
                var mc = document.createElement('script');
                mc.type = 'text/javascript';
                mc.async = true;
                mc.src = 'https://recobox.ru/widget/widget.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(mc, s.nextSibling);
            })();
            //]]>
        </script>
        <?php
    }
}
?>