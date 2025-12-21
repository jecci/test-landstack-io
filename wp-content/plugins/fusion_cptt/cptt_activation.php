<?php
//Plugin activation 

function fusion_cptt_avada_cpt_activation()
{
    set_transient('cptt-admin-notice-show', true, 100);
}

add_action('admin_notices', 'fusion_cptt_admin_notice_run');

function fusion_cptt_admin_notice_run()
{
    if (get_transient('cptt-admin-notice-show')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>Add the new Avada Builder elements in Avada Dashboard->Options->Builder Options (<strong>Blog
                    CPT, Recent Posts CPT and Portfolio CPT</strong>).</p>
        </div>
        <?php
        delete_transient('cptt-admin-notice-show');
    }
}

register_activation_hook(plugin_dir_path(__FILE__) . 'fusion_cptt.php', 'fusion_cptt_avada_cpt_activation');
?>