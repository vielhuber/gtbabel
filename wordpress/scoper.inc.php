<?php
declare(strict_types=1);

/* this is a string of functions that php scoper should not prefix */
/* it must consist of all global functions outside of php scopers folder (like native wp functions) */
/* globally declared helpers that are hotloaded via composer.json must also be included! */
/* globally used helpers of your dependencies that are hotloaded must NOT be included! */
$functions = [
    /* globally functions that the plugin provides must not be prefixed */
    'helpers' => [
        'gtbabel_current_lng',
        'gtbabel_source_lng',
        'gtbabel_referer_lng',
        'gtbabel_language_label',
        'gtbabel_languages',
        'gtbabel_default_language_codes',
        'gtbabel_default_languages',
        'gtbabel_default_settings',
        'gtbabel_languagepicker',
        'gtbabel__',
        'gtbabel_alt_lng',
        'ICL_LANGUAGE_CODE'
    ],
    /* native wp functions must not be prefixed (https://codex.wordpress.org/Function_Reference, https://github.com/humbug/php-scoper/issues/303) */
    'wordpress' => [
        'DB_HOST',
        'DB_USER',
        'DB_PASSWORD',
        'DB_NAME',
        'WP_Query',
        'WP_Scripts',
        'WP_Term_Query',
        'WP_Widget',
        'Walker_Nav_Menu_Checklist',
        'WP_PLUGIN_DIR',
        'url_to_postid',
        'get_home_url',
        'get_file_data',
        '__',
        '_admin_notice_multisite_activate_plugins_page',
        '_e',
        '_ex',
        '_n',
        '_ngettext',
        '_nx',
        '_x',
        'absint',
        'add_action',
        'add_blog_option',
        'add_cap',
        'add_comment_meta',
        'add_comments_page',
        'add_contextual_help',
        'add_custom_background',
        'add_custom_image_header',
        'add_dashboard_page',
        'add_editor_style',
        'add_existing_user_to_blog',
        'add_filter',
        'add_group',
        'add_image_size',
        'add_links_page',
        'add_magic_quotes',
        'add_management_page',
        'add_media_page',
        'add_menu_page',
        'add_meta_box',
        'add_new_user_to_blog',
        'add_node',
        'add_object_page',
        'add_option',
        'add_options_page',
        'add_pages_page',
        'add_ping',
        'add_plugins_page',
        'add_post_meta',
        'add_post_type_support',
        'add_posts_page',
        'add_query_arg',
        'add_rewrite_rule',
        'add_role',
        'add_settings_error',
        'add_settings_field',
        'add_settings_section',
        'add_shortcode',
        'add_site_option',
        'add_submenu_page',
        'add_theme_page',
        'add_theme_support',
        'add_user_meta',
        'add_user_to_blog',
        'add_users_page',
        'add_utility_page',
        'addslashes_gpc',
        'admin_notice_feed',
        'admin_url',
        'antispambot',
        'apply_filters_ref_array',
        'apply_filters',
        'attribute_escape',
        'auth_redirect',
        'author_can',
        'avoid_blog_page_permalink_collision',
        'backslashit',
        'balanceTags',
        'bloginfo_rss',
        'body_class',
        'bool_from_yn',
        'cache_javascript_headers',
        'capital_P_dangit',
        'cat_is_ancestor_of',
        'check_admin_referer',
        'check_ajax_referer',
        'check_comment',
        'check_import_new_users',
        'check_upload_mimes',
        'check_upload_size',
        'checked',
        'choose_primary_blog',
        'clean_blog_cache',
        'clean_pre',
        'clean_url',
        'comment_author_rss',
        'comment_author',
        'comment_class',
        'comment_date',
        'comment_form',
        'comment_ID',
        'comment_link',
        'comment_text_rss',
        'comment_text',
        'comment_time',
        'comments_number',
        'comments_open',
        'comments_template',
        'confirm_delete_users',
        'content_url',
        'convert_chars',
        'convert_smilies',
        'count_many_users_posts',
        'count_user_posts',
        'count_users',
        'create_empty_blog',
        'current_filter',
        'current_theme_supports',
        'current_time',
        'current_user_can_for_blog',
        'current_user_can',
        'date_i18n',
        'delete_blog_option',
        'delete_comment_meta',
        'delete_option',
        'delete_post_meta',
        'delete_site_option',
        'delete_site_transient',
        'delete_transient',
        'delete_user_meta',
        'did_action',
        'disabled',
        'discover_pingback_server_uri',
        'display_space_usage',
        'do_action_ref_array',
        'do_action',
        'do_all_pings',
        'do_enclose',
        'do_feed_atom',
        'do_feed_rdf',
        'do_feed_rss',
        'do_feed_rss2',
        'do_feed',
        'do_robots',
        'do_settings_fields',
        'do_settings_sections',
        'do_shortcode_tag',
        'do_shortcode',
        'do_trackbacks',
        'domain_exists',
        'dynamic_sidebar',
        'email_exists',
        'ent2ncr',
        'esc_attr__',
        'esc_attr_e',
        'esc_attr',
        'esc_html__',
        'esc_html_e',
        'esc_html',
        'esc_js',
        'esc_sql',
        'esc_textarea',
        'esc_url_raw',
        'esc_url',
        'fetch_feed',
        'fetch_rss',
        'filter_SSL',
        'fix_import_form_size',
        'fix_phpmailer_messageid',
        'flush_rewrite_rules',
        'force_balance_tags',
        'force_ssl_content',
        'form_option',
        'format_code_lang',
        'format_to_edit',
        'format_to_post',
        'funky_javascript_fix',
        'generic_ping',
        'get_404_template',
        'get_active_blog_for_user',
        'get_adjacent_post',
        'get_admin_page_title',
        'get_admin_url',
        'get_admin_users_for_domain',
        'get_all_category_ids',
        'get_all_page_ids',
        'get_alloptions',
        'get_ancestors',
        'get_ancestors',
        'get_approved_comments',
        'get_archive_template',
        'get_attached_file',
        'get_attachment_template',
        'get_author_feed_link',
        'get_author_posts_url',
        'get_author_template',
        'get_avatar',
        'get_blog_count',
        'get_blog_details',
        'get_blog_id_from_url',
        'get_blog_option',
        'get_blog_permalink',
        'get_blog_post',
        'get_blog_status',
        'get_blogaddress_by_domain',
        'get_blogaddress_by_id',
        'get_blogaddress_by_name',
        'get_bloginfo_rss',
        'get_bloginfo',
        'get_blogs_of_user',
        'get_body_class',
        'get_bookmark',
        'get_bookmarks',
        'get_boundary_post',
        'get_calendar',
        'get_cat_ID',
        'get_cat_name',
        'get_categories',
        'get_category_by_path',
        'get_category_by_slug',
        'get_category_feed_link',
        'get_category_link',
        'get_category_parents',
        'get_category_template',
        'get_category',
        'get_children',
        'get_comment_author_rss',
        'get_comment_author',
        'get_comment_date',
        'get_comment_link',
        'get_comment_meta',
        'get_comment_pages_count',
        'get_comment_text',
        'get_comment_time',
        'get_comment',
        'get_comments_popup_template',
        'get_comments',
        'get_current_site_name',
        'get_current_site',
        'get_current_theme',
        'get_current_user_id',
        'get_currentuserinfo',
        'get_dashboard_blog',
        'get_date_from_gmt',
        'get_date_template',
        'get_day_link',
        'get_delete_post_link',
        'get_dirsize',
        'get_edit_post_link',
        'get_edit_term_link',
        'get_editable_roles',
        'get_enclosed',
        'get_extended',
        'get_footer',
        'get_gmt_from_date',
        'get_header_image',
        'get_header_textcolor',
        'get_header',
        'get_home_template',
        'get_id_from_blogname',
        'get_last_updated',
        'get_lastcommentmodified',
        'get_lastpostdate',
        'get_lastpostmodified',
        'get_locale_stylesheet_uri',
        'get_locale',
        'get_meta_sql',
        'get_month_link',
        'get_most_recent_post_of_user',
        'get_next_post',
        'get_next_posts_link',
        'get_node',
        'get_nodes',
        'get_num_queries',
        'get_object_taxonomies',
        'get_option',
        'get_page_by_path',
        'get_page_by_title',
        'get_page_children',
        'get_page_hierarchy',
        'get_page_link',
        'get_page_template',
        'get_page_uri',
        'get_page',
        'get_paged_template',
        'get_pages',
        'get_permalink',
        'get_plugin_data',
        'get_plugins',
        'get_post_ancestors',
        'get_post_class',
        'get_post_comments_feed_link',
        'get_post_custom_keys',
        'get_post_custom_values',
        'get_post_custom',
        'get_post_field',
        'get_post_format',
        'get_post_meta',
        'get_post_mime_type',
        'get_post_stati',
        'get_post_status',
        'get_post_statuses',
        'get_post_type_archive_link',
        'get_post_type_capabilities',
        'get_post_type_labels',
        'get_post_type_object',
        'get_post_type',
        'get_post_types',
        'get_post',
        'get_posts_by_author_sql',
        'get_posts',
        'get_previous_post',
        'get_previous_posts_link',
        'get_profile',
        'get_pung',
        'get_query_template',
        'get_query_var',
        'get_registered_nav_menus',
        'get_role',
        'get_rss',
        'get_search_comments_feed_link',
        'get_search_feed_link',
        'get_search_form',
        'get_search_template',
        'get_settings_errors',
        'get_shortcode_regex',
        'get_sidebar',
        'get_single_template',
        'get_site_allowed_themes',
        'get_site_option',
        'get_site_transient',
        'get_site_url',
        'get_sitestats',
        'get_space_allowed',
        'get_space_used',
        'get_stylesheet_directory_uri',
        'get_stylesheet_directory',
        'get_stylesheet_uri',
        'get_stylesheet',
        'get_submit_button',
        'get_super_admins',
        'get_tag_link',
        'get_tag_template',
        'get_tag',
        'get_tags',
        'get_tax_sql',
        'get_taxonomies',
        'get_taxonomy_template',
        'get_taxonomy',
        'get_template_directory_uri',
        'get_template_directory',
        'get_template_part',
        'get_template',
        'get_term_by',
        'get_term_children',
        'get_term_link',
        'get_term',
        'get_terms',
        'get_the_author_meta',
        'get_the_author_posts',
        'get_the_author',
        'get_the_category_by_ID',
        'get_the_category_list',
        'get_the_category_rss',
        'get_the_category',
        'get_the_content',
        'get_the_date',
        'get_the_excerpt',
        'get_the_ID',
        'get_the_ID',
        'get_the_modified_author',
        'get_the_modified_time',
        'get_the_post_thumbnail',
        'get_the_tag_list',
        'get_the_tags',
        'get_the_term_list',
        'get_the_terms',
        'get_the_time',
        'get_the_title_rss',
        'get_the_title',
        'get_theme_data',
        'get_theme_file_path',
        'get_theme_file_uri',
        'get_theme_mod',
        'get_theme_mods',
        'get_theme_root_uri',
        'get_theme_root',
        'get_theme_roots',
        'get_theme_support',
        'get_theme',
        'get_themes',
        'get_to_ping',
        'get_transient',
        'get_upload_space_available',
        'get_user_by',
        'get_user_count',
        'get_user_id_from_string',
        'get_user_meta',
        'get_user_option',
        'get_userdata',
        'get_usernumposts',
        'get_users',
        'get_weekstartend',
        'get_year_link',
        'global_terms',
        'grant_super_admin',
        'has_action',
        'has_excerpt',
        'has_filter',
        'has_header_image',
        'has_nav_menu',
        'has_post_format',
        'has_post_thumbnail',
        'has_tag',
        'has_tag',
        'has_term',
        'have_comments',
        'have_posts',
        'header_image',
        'header_textcolor',
        'home_url',
        'htmlentities2',
        'human_time_diff',
        'image_edit_before_change',
        'image_resize',
        'in_category',
        'in_category',
        'in_the_loop',
        'includes_url',
        'insert_blog',
        'install_blog_defaults',
        'install_blog',
        'is_404',
        'is_active_sidebar',
        'is_active_widget',
        'is_admin_bar_showing',
        'is_admin',
        'is_archive',
        'is_archived',
        'is_attachment',
        'is_attachment',
        'is_author',
        'is_blog_installed',
        'is_blog_user',
        'is_category',
        'is_category',
        'is_child_theme',
        'is_comments_popup',
        'is_customize_preview',
        'is_customize_preview',
        'is_date',
        'is_day',
        'is_dynamic_sidebar',
        'is_email_address_unsafe',
        'is_email',
        'is_feed',
        'is_front_page',
        'is_home',
        'is_local_attachment',
        'is_main_query',
        'is_main_site',
        'is_month',
        'is_multi_author',
        'is_multisite',
        'is_new_day',
        'is_object_in_term',
        'is_page_template',
        'is_page',
        'is_page',
        'is_paged',
        'is_plugin_active_for_network',
        'is_plugin_active',
        'is_plugin_inactive',
        'is_plugin_page',
        'is_post_type_archive',
        'is_post_type_hierarchical',
        'is_post',
        'is_preview',
        'is_rtl',
        'is_search',
        'is_serialized_string',
        'is_serialized',
        'is_single',
        'is_single',
        'is_singular',
        'is_ssl',
        'is_sticky',
        'is_sticky',
        'is_subdomain_install',
        'is_super_admin',
        'is_tag',
        'is_tag',
        'is_tax',
        'is_tax',
        'is_taxonomy_hierarchical',
        'is_taxonomy',
        'is_term',
        'is_time',
        'is_trackback',
        'is_trackback',
        'is_upload_space_available',
        'is_user_logged_in',
        'is_user_member_of_blog',
        'is_user_option_local',
        'is_user_spammy',
        'is_wp_error',
        'is_year',
        'iso8601_timezone_to_offset',
        'iso8601_to_datetime',
        'js_escape',
        'language_attributes',
        'load_default_textdomain',
        'load_muplugin_textdomain',
        'load_plugin_textdomain',
        'load_template',
        'load_textdomain',
        'load_theme_textdomain',
        'locale_stylesheet',
        'locate_template',
        'log_app',
        'make_clickable',
        'make_url_footnote',
        'map_meta_cap',
        'maybe_add_existing_user_to_blog',
        'maybe_redirect_404',
        'maybe_serialize',
        'maybe_unserialize',
        'menu_page_url',
        'merge_filters',
        'ms_cookie_constants',
        'ms_deprecated_blogs_file',
        'ms_file_constants',
        'ms_not_installed',
        'ms_site_check',
        'ms_subdomain_constants',
        'ms_upload_constants',
        'mu_dropdown_languages',
        'mysql2date',
        'network_admin_url',
        'network_home_url',
        'network_site_url',
        'new_user_email_admin_notice',
        'newblog_notify_siteadmin',
        'newuser_notify_siteadmin',
        'next_comments_link',
        'next_posts_link',
        'nocache_headers',
        'page_uri_index',
        'paginate_comments_links',
        'paginate_links',
        'permalink_single_rss',
        'pingback',
        'pings_open',
        'plugin_basename',
        'plugin_dir_path',
        'plugin_dir_url',
        'plugins_url',
        'popuplinks',
        'post_class',
        'post_comments_feed_link',
        'post_submit_meta_box',
        'post_type_archive_title',
        'post_type_exists',
        'post_type_supports',
        'preview_theme_ob_filter_callback',
        'preview_theme_ob_filter',
        'preview_theme',
        'previous_comments_link',
        'previous_posts_link',
        'privacy_ping_filter',
        'query_posts',
        'recurse_dirsize',
        'redirect_mu_dashboard',
        'redirect_this_site',
        'redirect_user_to_blog',
        'refresh_blog_details',
        'refresh_user_details',
        'register_activation_hook',
        'register_deactivation_hook',
        'register_meta',
        'register_nav_menu',
        'register_nav_menus',
        'register_post_status',
        'register_post_type',
        'register_setting',
        'register_sidebar',
        'register_sidebars',
        'register_taxonomy_for_object_type',
        'register_taxonomy',
        'register_theme_directory',
        'register_widget',
        'remove_accents',
        'remove_action',
        'remove_all_actions',
        'remove_all_filters',
        'remove_all_shortcodes',
        'remove_cap',
        'remove_filter',
        'remove_menu_page',
        'remove_meta_box',
        'remove_node',
        'remove_post_type_support',
        'remove_query_arg',
        'remove_role',
        'remove_shortcode',
        'remove_submenu_page',
        'remove_theme_mod',
        'remove_theme_mods',
        'remove_theme_support',
        'remove_user_from_blog',
        'require_if_theme_supports',
        'restore_current_blog',
        'revoke_super_admin',
        'rewind_posts',
        'rss_enclosure',
        'sanitize_comment_cookies',
        'sanitize_email',
        'sanitize_file_name',
        'sanitize_html_class',
        'sanitize_key',
        'sanitize_mime_type',
        'sanitize_option',
        'sanitize_sql_orderby',
        'sanitize_text_field',
        'sanitize_textarea_field',
        'sanitize_title_for_query',
        'sanitize_title_with_dashes',
        'sanitize_title',
        'sanitize_user',
        'search_theme_directories',
        'secret_salt_warning',
        'seems_utf8',
        'selected',
        'send_confirmation_on_profile_email',
        'set_current_user',
        'set_post_format',
        'set_post_thumbnail',
        'set_post_type',
        'set_site_transient',
        'set_theme_mod',
        'set_transient',
        'settings_errors',
        'settings_fields',
        'setup_postdata',
        'shortcode_atts',
        'shortcode_parse_atts',
        'show_post_thumbnail_warning',
        'signup_nonce_check',
        'signup_nonce_fields',
        'single_cat_title',
        'single_tag_title',
        'site_admin_notice',
        'site_url',
        'spawn_cron',
        'status_header',
        'strip_shortcodes',
        'stripslashes_deep',
        'submit_button',
        'switch_theme',
        'switch_to_blog',
        'sync_category_tag_slugs',
        'tag_description',
        'taxonomy_exists',
        'term_exists',
        'the_author',
        'the_category_rss',
        'the_category',
        'the_content_rss',
        'the_content',
        'the_date',
        'the_excerpt_rss',
        'the_excerpt',
        'the_ID',
        'the_ID',
        'the_modified_time',
        'the_permalink',
        'the_post',
        'the_tags',
        'the_terms',
        'the_time',
        'the_title_attribute',
        'the_title_rss',
        'the_title',
        'the_widget',
        'trackback_url_list',
        'trackback_url',
        'trackback',
        'trailingslashit',
        'unregister_nav_menu',
        'unregister_setting',
        'unregister_sidebar',
        'unregister_widget',
        'untrailingslashit',
        'unzip_file',
        'update_archived',
        'update_attached_file',
        'update_blog_details',
        'update_blog_option',
        'update_blog_public',
        'update_blog_status',
        'update_comment_meta',
        'update_option_new_admin_email',
        'update_option',
        'update_post_meta',
        'update_posts_count',
        'update_site_option',
        'update_user_meta',
        'update_user_option',
        'update_user_status',
        'upload_is_file_too_big',
        'upload_is_user_over_quota',
        'upload_is_user_over_quote',
        'upload_size_limit_filter',
        'upload_space_setting',
        'url_shorten',
        'urlencode_deep',
        'user_can',
        'user_pass_ok',
        'user_pass_ok',
        'username_exists',
        'users_can_register_signup_filter',
        'utf8_uri_encode',
        'validate_current_theme',
        'validate_file_to_edit',
        'validate_file',
        'validate_username',
        'walk_nav_menu_tree',
        'weblog_ping',
        'welcome_user_msg_filter',
        'wordpressmu_wp_mail_from',
        'wp_add_dashboard_widget',
        'wp_add_inline_style',
        'wp_allow_comment',
        'wp_attachment_is_image',
        'wp_authenticate',
        'wp_cache_get',
        'wp_cache_reset',
        'wp_cache_set',
        'wp_category_checklist',
        'wp_check_filetype',
        'wp_check_for_changed_slugs',
        'wp_clean_themes_cache',
        'wp_clear_scheduled_hook',
        'wp_clearcookie',
        'wp_convert_widget_settings',
        'wp_count_comments',
        'wp_count_posts',
        'wp_count_terms',
        'wp_create_category',
        'wp_create_nav_menu',
        'wp_create_nonce',
        'wp_create_thumbnail',
        'wp_create_user',
        'wp_cron',
        'wp_dashboard_quota',
        'wp_delete_attachment',
        'wp_delete_category',
        'wp_delete_comment',
        'wp_delete_post',
        'wp_delete_term',
        'wp_delete_user',
        'wp_dequeue_script',
        'wp_dequeue_style',
        'wp_deregister_script',
        'wp_deregister_style',
        'wp_die',
        'wp_dropdown_categories',
        'wp_dropdown_pages',
        'wp_dropdown_users',
        'wp_editor',
        'wp_enqueue_script',
        'wp_enqueue_style',
        'wp_explain_nonce',
        'wp_filter_comment',
        'wp_filter_kses',
        'wp_filter_nohtml_kses',
        'wp_filter_post_kses',
        'wp_footer',
        'wp_generate_attachment_metadata',
        'wp_generate_tag_cloud',
        'wp_get_archives',
        'wp_get_attachment_image_src',
        'wp_get_attachment_image',
        'wp_get_attachment_link',
        'wp_get_attachment_metadata',
        'wp_get_attachment_thumb_file',
        'wp_get_attachment_thumb_url',
        'wp_get_attachment_url',
        'wp_get_comment_status',
        'wp_get_cookie_login',
        'wp_get_current_commenter',
        'wp_get_current_user',
        'wp_get_http_headers',
        'wp_get_image_editor',
        'wp_get_installed_translations',
        'wp_get_mime_types',
        'wp_get_nav_menu_items',
        'wp_get_object_terms',
        'wp_get_original_referer',
        'wp_get_post_categories',
        'wp_get_post_revision',
        'wp_get_post_revisions',
        'wp_get_post_tags',
        'wp_get_post_terms',
        'wp_get_recent_posts',
        'wp_get_referer',
        'wp_get_schedule',
        'wp_get_schedules',
        'wp_get_sidebars_widgets',
        'wp_get_single_post',
        'wp_get_sites',
        'wp_get_theme',
        'wp_get_themes',
        'wp_get_widget_defaults',
        'wp_handle_sideload',
        'wp_hash',
        'wp_head',
        'wp_insert_attachment',
        'wp_insert_category',
        'wp_insert_comment',
        'wp_insert_post',
        'wp_insert_term',
        'wp_insert_user',
        'wp_install_defaults',
        'wp_is_mobile',
        'wp_is_post_revision',
        'wp_iso_descrambler',
        'wp_kses_array_lc',
        'wp_kses_attr',
        'wp_kses_bad_protocol_once',
        'wp_kses_bad_protocol_once2',
        'wp_kses_bad_protocol',
        'wp_kses_check_attr_val',
        'wp_kses_decode_entities',
        'wp_kses_hair',
        'wp_kses_hook',
        'wp_kses_html_error',
        'wp_kses_js_entities',
        'wp_kses_no_null',
        'wp_kses_normalize_entities',
        'wp_kses_normalize_entities2',
        'wp_kses_post',
        'wp_kses_split',
        'wp_kses_split2',
        'wp_kses_stripslashes',
        'wp_kses_version',
        'wp_kses',
        'wp_link_pages',
        'wp_list_bookmarks',
        'wp_list_categories',
        'wp_list_comments',
        'wp_list_pages',
        'wp_list_pluck',
        'wp_load_alloptions',
        'wp_localize_script',
        'wp_login_form',
        'wp_loginout',
        'wp_logout_url',
        'wp_logout',
        'wp_mail',
        'wp_make_link_relative',
        'wp_max_upload_size',
        'wp_mime_type_icon',
        'wp_mkdir_p',
        'wp_nav_menu',
        'wp_new_comment',
        'wp_new_user_notification',
        'wp_next_scheduled',
        'wp_nonce_ays',
        'wp_nonce_field',
        'wp_nonce_url',
        'wp_normalize_path',
        'wp_notify_moderator',
        'wp_notify_postauthor',
        'wp_oembed_remove_provider',
        'wp_original_referer_field',
        'wp_page_menu',
        'wp_page_menu',
        'wp_parse_args',
        'wp_password_change_notification',
        'wp_prepare_attachment_for_js',
        'wp_publish_post',
        'wp_redirect',
        'wp_referer_field',
        'wp_register_script',
        'wp_register_sidebar_widget',
        'wp_register_style',
        'wp_register_widget_control',
        'wp_rel_nofollow',
        'wp_remote_fopen',
        'wp_remote_get',
        'wp_remote_retrieve_body',
        'wp_remove_object_terms',
        'wp_reschedule_event',
        'wp_reset_postdata',
        'wp_reset_query',
        'wp_richedit_pre',
        'wp_rss',
        'wp_safe_redirect',
        'wp_salt',
        'wp_schedule_event',
        'wp_schedule_single_event',
        'wp_script_is',
        'wp_send_json_error',
        'wp_send_json_success',
        'wp_send_json',
        'wp_set_auth_cookie',
        'wp_set_comment_status',
        'wp_set_current_user',
        'wp_set_object_terms',
        'wp_set_password',
        'wp_set_post_categories',
        'wp_set_post_tags',
        'wp_set_post_terms',
        'wp_set_sidebars_widgets',
        'wp_signon',
        'wp_specialchars',
        'wp_strip_all_tags',
        'wp_style_is',
        'wp_tag_cloud',
        'wp_terms_checklist',
        'wp_text_diff',
        'wp_throttle_comment_flood',
        'wp_title',
        'wp_trash_post',
        'wp_trim_excerpt',
        'wp_trim_words',
        'wp_unregister_sidebar_widget',
        'wp_unregister_widget_control',
        'wp_unschedule_event',
        'wp_update_attachment_metadata',
        'wp_update_comment_count_now',
        'wp_update_comment_count',
        'wp_update_comment',
        'wp_update_post',
        'wp_update_term',
        'wp_update_user',
        'wp_upload_bits',
        'wp_upload_dir',
        'wp_verify_nonce',
        'wp_widget_description',
        'wp',
        'wpautop',
        'wpmu_activate_signup',
        'wpmu_admin_redirect_add_updated_param',
        'wpmu_create_blog',
        'wpmu_create_user',
        'wpmu_current_site',
        'wpmu_delete_blog',
        'wpmu_delete_user',
        'wpmu_get_blog_allowedthemes',
        'wpmu_log_new_registrations',
        'wpmu_signup_blog_notification',
        'wpmu_signup_blog',
        'wpmu_signup_user_notification',
        'wpmu_signup_user',
        'wpmu_update_blogs_date',
        'wpmu_validate_blog_signup',
        'wpmu_validate_user_signup',
        'wpmu_welcome_notification',
        'wpmu_welcome_user_notification',
        'wptexturize',
        'xmlrpc_getpostcategory',
        'xmlrpc_getposttitle',
        'xmlrpc_removepostdata',
        'zeroise',
        'wp_slash_strings_only',
        'addslashes_strings_only'
    ]
];

return [
    'prefix' => 'ScopedGtbabel',
    'whitelist-global-functions' => false,
    'whitelist-global-constants' => false,
    'whitelist-global-classes' => false,
    'patchers' => [
        function (string $filePath, string $prefix, string $content) use ($functions): string {
            // remove prefix
            foreach ($functions as $functions__value) {
                foreach ($functions__value as $functions__value__value) {
                    $content = str_replace(
                        '\\' . $prefix . '\\' . $functions__value__value,
                        '\\' . $functions__value__value,
                        $content
                    ); // "\PREFIX\foo()", or "foo extends nativeClass"
                    $content = str_replace(
                        $prefix . '\\\\' . $functions__value__value,
                        $functions__value__value,
                        $content
                    ); // "if( function_exists('PREFIX\\foo') )"
                }
            }
            // remove the namespace from file that define global functions that should be provided outside
            // don't use "files-whitelist" here, because it could be the case, that global functions are mixed with prefixed ones
            if (strpos($filePath, 'helpers.php') !== false) {
                $content = str_replace('namespace ' . $prefix . ';', '', $content);
            }
            return $content;
        }
    ]
    // this is not needed anymore
    /*
    'whitelist' => [
        'GtbabelWordPress\*', // all global/native/class based functions in the wordpress plugin class (you must add a namespace "namespace GtbabelWordPress;" inside the file before!)
        'vielhuber\gtbabel\*', // all global/native/class based functions in the main wordpress class
    ],
    'files-whitelist' => [
        'uninstall.php', // the uninstall file
        'helpers.php', // all hotloaded global functions by the composer package itself
        'vendor/vielhuber/stringhelper/stringhelper.php', // all libraries with global functions that are hotloaded
    ],
    // all global functions/classes should NOT be prefixed
    'files-whitelist' => [
        //'helpers.php', // all hotloaded global functions by the composer package itself should not get a namespace(!)
        //'vendor/vielhuber/stringhelper/stringhelper.php', // all dependencies with global functions that are hotloaded and used should not get a namespace(!)
    ],
    */
];
