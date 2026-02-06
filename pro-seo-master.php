<?php
/**
 * Plugin Name: Pro SEO Master
 * Plugin URI: https://example.com/pro-seo-master
 * Description: A comprehensive SEO plugin with advanced content analysis and Google autocomplete for focus keyphrases. Optimize your content for search engines with real-time analysis, snippet preview, XML sitemaps, breadcrumbs, and schema markup.
 * Version: 1.4.0
 * Author: Prosanjit Dhar
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pro-seo-master
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PSM_VERSION', '1.4.0');
define('PSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PSM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Pro_SEO_Master {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        
        // Meta box
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'));
        
        // Output meta tags in head
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        
        // AJAX handlers
        add_action('wp_ajax_psm_google_suggest', array($this, 'ajax_google_suggest'));
        add_action('wp_ajax_psm_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_psm_get_scores', array($this, 'ajax_get_scores'));
        
        // REST API for Gutenberg
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Sitemap
        add_action('init', array($this, 'register_sitemap_routes'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        
        // Breadcrumbs
        add_shortcode('psm_breadcrumbs', array($this, 'breadcrumbs_shortcode'));
    }
    
    /**
     * Activation
     */
    public function activate() {
        // Flush rewrite rules for sitemap
        flush_rewrite_rules();
        
        // Set default options
        if (!get_option('psm_settings')) {
            add_option('psm_settings', array(
                'homepage_title' => get_bloginfo('name') . ' - ' . get_bloginfo('description'),
                'homepage_description' => get_bloginfo('description'),
                'separator' => '-',
                'company_name' => get_bloginfo('name'),
                'company_logo' => '',
            ));
        }
    }
    
    /**
     * Deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Translation loading is handled automatically by WordPress 4.6+
        // No need to call load_plugin_textdomain() manually
    }
    
    /**
     * Admin Initialize
     */
    public function admin_init() {
        register_setting('psm_settings_group', 'psm_settings', array($this, 'sanitize_settings'));
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Pro SEO Master', 'pro-seo-master'),
            __('SEO', 'pro-seo-master'),
            'manage_options',
            'pro-seo-master',
            array($this, 'settings_page'),
            'dashicons-search',
            60
        );
    }
    
    /**
     * Settings Page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = get_option('psm_settings', array());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('psm_settings_group');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="homepage_title"><?php _e('Homepage Title', 'pro-seo-master'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="homepage_title" name="psm_settings[homepage_title]" 
                                   value="<?php echo esc_attr($settings['homepage_title'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('The SEO title for your homepage.', 'pro-seo-master'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="homepage_description"><?php _e('Homepage Meta Description', 'pro-seo-master'); ?></label>
                        </th>
                        <td>
                            <textarea id="homepage_description" name="psm_settings[homepage_description]" 
                                      rows="3" class="large-text"><?php echo esc_textarea($settings['homepage_description'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('The meta description for your homepage.', 'pro-seo-master'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="separator"><?php _e('Title Separator', 'pro-seo-master'); ?></label>
                        </th>
                        <td>
                            <select id="separator" name="psm_settings[separator]">
                                <option value="-" <?php selected($settings['separator'] ?? '-', '-'); ?>>-</option>
                                <option value="|" <?php selected($settings['separator'] ?? '-', '|'); ?>>|</option>
                                <option value="–" <?php selected($settings['separator'] ?? '-', '–'); ?>>–</option>
                                <option value="»" <?php selected($settings['separator'] ?? '-', '»'); ?>>»</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_name"><?php _e('Company Name', 'pro-seo-master'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="company_name" name="psm_settings[company_name]" 
                                   value="<?php echo esc_attr($settings['company_name'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Used for schema.org Organization markup.', 'pro-seo-master'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <hr>
            <h2><?php _e('Useful Links', 'pro-seo-master'); ?></h2>
            <ul>
                <li><a href="<?php echo home_url('/sitemap_index.xml'); ?>" target="_blank"><?php _e('View XML Sitemap', 'pro-seo-master'); ?></a></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Sanitize Settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['homepage_title'])) {
            $sanitized['homepage_title'] = sanitize_text_field($input['homepage_title']);
        }
        
        if (isset($input['homepage_description'])) {
            $sanitized['homepage_description'] = sanitize_textarea_field($input['homepage_description']);
        }
        
        if (isset($input['separator'])) {
            $sanitized['separator'] = sanitize_text_field($input['separator']);
        }
        
        if (isset($input['company_name'])) {
            $sanitized['company_name'] = sanitize_text_field($input['company_name']);
        }
        
        return $sanitized;
    }
    
    /**
     * Enqueue Admin Assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on post edit screens
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_style(
            'psm-admin-css',
            PSM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PSM_VERSION
        );
        
        wp_enqueue_script(
            'psm-admin-js',
            PSM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PSM_VERSION,
            true
        );
        
        wp_localize_script('psm-admin-js', 'psmData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psm_nonce'),
            'post_id' => get_the_ID(),
        ));
    }
    
    /**
     * Enqueue Block Editor Assets (Gutenberg Sidebar)
     */
    public function enqueue_block_editor_assets() {
        // Enqueue the Gutenberg sidebar script
        wp_enqueue_script(
            'psm-gutenberg-sidebar',
            PSM_PLUGIN_URL . 'assets/js/gutenberg-sidebar.js',
            array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose'),
            PSM_VERSION,
            true
        );
        
        // Enqueue sidebar styles
        wp_enqueue_style(
            'psm-gutenberg-sidebar-css',
            PSM_PLUGIN_URL . 'assets/css/gutenberg-sidebar.css',
            array('wp-edit-post'),
            PSM_VERSION
        );
        
        // Pass data to script
        wp_localize_script('psm-gutenberg-sidebar', 'psmGutenberg', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('psm/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'post_id' => get_the_ID(),
        ));
    }
    
    /**
     * Register REST API Routes
     */
    public function register_rest_routes() {
        register_rest_route('psm/v1', '/scores/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_scores'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
    }
    
    /**
     * REST API: Get Scores
     */
    public function rest_get_scores($request) {
        $post_id = $request['id'];
        
        return array(
            'seo_score' => get_post_meta($post_id, '_psm_seo_score', true) ?: 0,
            'readability_score' => get_post_meta($post_id, '_psm_readability_score', true) ?: 0,
            'focus_keyphrase' => get_post_meta($post_id, '_psm_focus_keyphrase', true) ?: '',
        );
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'psm_seo_metabox',
                __('Pro SEO Master', 'pro-seo-master'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Render Meta Box
     */
    public function render_meta_box($post) {
        wp_nonce_field('psm_save_meta_box', 'psm_meta_box_nonce');
        
        // Get existing values
        $focus_keyphrase = get_post_meta($post->ID, '_psm_focus_keyphrase', true);
        $seo_title = get_post_meta($post->ID, '_psm_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_psm_meta_description', true);
        $canonical_url = get_post_meta($post->ID, '_psm_canonical_url', true);
        $robots_index = get_post_meta($post->ID, '_psm_robots_index', true);
        $robots_follow = get_post_meta($post->ID, '_psm_robots_follow', true);
        $og_title = get_post_meta($post->ID, '_psm_og_title', true);
        $og_description = get_post_meta($post->ID, '_psm_og_description', true);
        $twitter_title = get_post_meta($post->ID, '_psm_twitter_title', true);
        $twitter_description = get_post_meta($post->ID, '_psm_twitter_description', true);
        
        // Schema values
        $schema_type = get_post_meta($post->ID, '_psm_schema_type', true) ?: 'Article';
        $schema_headline = get_post_meta($post->ID, '_psm_schema_headline', true);
        $schema_description = get_post_meta($post->ID, '_psm_schema_description', true);
        $schema_author_name = get_post_meta($post->ID, '_psm_schema_author_name', true);
        $schema_publisher = get_post_meta($post->ID, '_psm_schema_publisher', true);
        $schema_date_published = get_post_meta($post->ID, '_psm_schema_date_published', true);
        $schema_date_modified = get_post_meta($post->ID, '_psm_schema_date_modified', true);
        
        // Default values
        if (empty($seo_title)) {
            $seo_title = '%%title%% %%sep%% %%sitename%%';
        }
        
        ?>
        <div class="psm-metabox">
            <div class="psm-tabs">
                <div class="psm-tab-buttons">
                    <button type="button" class="psm-tab-button active" data-tab="seo"><?php _e('SEO', 'pro-seo-master'); ?></button>
                    <button type="button" class="psm-tab-button" data-tab="social"><?php _e('Social', 'pro-seo-master'); ?></button>
                    <button type="button" class="psm-tab-button" data-tab="schema"><?php _e('Schema', 'pro-seo-master'); ?></button>
                    <button type="button" class="psm-tab-button" data-tab="advanced"><?php _e('Advanced', 'pro-seo-master'); ?></button>
                </div>
                
                <!-- SEO Tab -->
                <div class="psm-tab-content active" data-tab="seo">
                    <!-- Focus Keyphrase with Google Autocomplete -->
                    <div class="psm-field">
                        <label for="psm_focus_keyphrase">
                            <strong><?php _e('Focus Keyphrase', 'pro-seo-master'); ?></strong>
                        </label>
                        <div class="psm-autocomplete-wrapper">
                            <input type="text" id="psm_focus_keyphrase" name="psm_focus_keyphrase" 
                                   value="<?php echo esc_attr($focus_keyphrase); ?>" class="regular-text" 
                                   placeholder="<?php _e('Enter your focus keyphrase...', 'pro-seo-master'); ?>" 
                                   autocomplete="off" />
                            <div id="psm-suggestions" class="psm-suggestions"></div>
                        </div>
                        <p class="description"><?php _e('Start typing to see Google keyword suggestions!', 'pro-seo-master'); ?></p>
                    </div>
                    
                    <!-- Google Snippet Preview -->
                    <div class="psm-field">
                        <label><strong><?php _e('Google Preview', 'pro-seo-master'); ?></strong></label>
                        <div class="psm-snippet-preview">
                            <div class="psm-snippet-toggle">
                                <button type="button" class="psm-preview-btn active" data-device="desktop">
                                    <span class="dashicons dashicons-desktop"></span> <?php _e('Desktop', 'pro-seo-master'); ?>
                                </button>
                                <button type="button" class="psm-preview-btn" data-device="mobile">
                                    <span class="dashicons dashicons-smartphone"></span> <?php _e('Mobile', 'pro-seo-master'); ?>
                                </button>
                            </div>
                            <div class="psm-snippet desktop">
                                <div class="psm-snippet-url"><?php echo esc_html(get_permalink($post->ID)); ?></div>
                                <div class="psm-snippet-title"></div>
                                <div class="psm-snippet-description"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Title -->
                    <div class="psm-field">
                        <label for="psm_seo_title">
                            <strong><?php _e('SEO Title', 'pro-seo-master'); ?></strong>
                        </label>
                        <input type="text" id="psm_seo_title" name="psm_seo_title" 
                               value="<?php echo esc_attr($seo_title); ?>" class="large-text" />
                        <p class="description">
                            <?php _e('Variables: %%title%%, %%sitename%%, %%sep%%', 'pro-seo-master'); ?>
                            <span class="psm-title-length"></span>
                        </p>
                    </div>
                    
                    <!-- Meta Description -->
                    <div class="psm-field">
                        <label for="psm_meta_description">
                            <strong><?php _e('Meta Description', 'pro-seo-master'); ?></strong>
                        </label>
                        <textarea id="psm_meta_description" name="psm_meta_description" 
                                  rows="3" class="large-text"><?php echo esc_textarea($meta_description); ?></textarea>
                        <p class="description">
                            <span class="psm-desc-length"></span>
                        </p>
                    </div>
                    
                    <!-- SEO Analysis -->
                    <div class="psm-field">
                        <label>
                            <strong><?php _e('SEO Analysis', 'pro-seo-master'); ?></strong>
                            <span class="psm-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('SEO Analysis checks how well your content is optimized for search engines. It evaluates factors like keyphrase usage, title optimization, meta descriptions, links, and more. Aim for 80%+ (green) for best results.', 'pro-seo-master'); ?>"></span>
                        </label>
                        <div id="psm-seo-analysis" class="psm-analysis">
                            <div class="psm-score">
                                <span class="psm-score-indicator psm-score-none"></span>
                                <span><?php _e('SEO Score: Not analyzed yet', 'pro-seo-master'); ?></span>
                            </div>
                            <ul id="psm-seo-checks" class="psm-checks"></ul>
                        </div>
                    </div>
                    
                    <!-- Readability Analysis -->
                    <div class="psm-field">
                        <label>
                            <strong><?php _e('Readability Analysis', 'pro-seo-master'); ?></strong>
                            <span class="psm-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Readability Analysis measures how easy your content is to read and understand. It checks sentence length, paragraph length, transition words, passive voice, word complexity, and Flesch reading ease. Aim for 60%+ for good readability.', 'pro-seo-master'); ?>"></span>
                        </label>
                        <div id="psm-readability-analysis" class="psm-analysis">
                            <div class="psm-score">
                                <span class="psm-score-indicator psm-score-none"></span>
                                <span><?php _e('Readability Score: Not analyzed yet', 'pro-seo-master'); ?></span>
                            </div>
                            <ul id="psm-readability-checks" class="psm-checks"></ul>
                        </div>
                    </div>
                    
                    <button type="button" id="psm-analyze-btn" class="button button-primary">
                        <?php _e('Analyze Content', 'pro-seo-master'); ?>
                    </button>
                </div>
                
                <!-- Social Tab -->
                <div class="psm-tab-content" data-tab="social">
                    <h3><?php _e('Open Graph (Facebook)', 'pro-seo-master'); ?></h3>
                    <div class="psm-field">
                        <label for="psm_og_title"><?php _e('OG Title', 'pro-seo-master'); ?></label>
                        <input type="text" id="psm_og_title" name="psm_og_title" 
                               value="<?php echo esc_attr($og_title); ?>" class="large-text" />
                    </div>
                    <div class="psm-field">
                        <label for="psm_og_description"><?php _e('OG Description', 'pro-seo-master'); ?></label>
                        <textarea id="psm_og_description" name="psm_og_description" 
                                  rows="3" class="large-text"><?php echo esc_textarea($og_description); ?></textarea>
                    </div>
                    
                    <h3><?php _e('Twitter Cards', 'pro-seo-master'); ?></h3>
                    <div class="psm-field">
                        <label for="psm_twitter_title"><?php _e('Twitter Title', 'pro-seo-master'); ?></label>
                        <input type="text" id="psm_twitter_title" name="psm_twitter_title" 
                               value="<?php echo esc_attr($twitter_title); ?>" class="large-text" />
                    </div>
                    <div class="psm-field">
                        <label for="psm_twitter_description"><?php _e('Twitter Description', 'pro-seo-master'); ?></label>
                        <textarea id="psm_twitter_description" name="psm_twitter_description" 
                                  rows="3" class="large-text"><?php echo esc_textarea($twitter_description); ?></textarea>
                    </div>
                </div>
                
                <!-- Schema Tab -->
                <div class="psm-tab-content" data-tab="schema">
                    <p class="description" style="margin-top: 0;">
                        <?php _e('Configure structured data (Schema.org) to help search engines better understand your content. This appears as rich snippets in search results.', 'pro-seo-master'); ?>
                    </p>
                    
                    <div class="psm-field">
                        <label for="psm_schema_type">
                            <strong><?php _e('Schema Type', 'pro-seo-master'); ?></strong>
                            <span class="psm-tooltip dashicons dashicons-info" data-tooltip="<?php esc_attr_e('Choose the type of content. Article is best for blog posts, Review for product reviews, Recipe for cooking instructions, etc.', 'pro-seo-master'); ?>"></span>
                        </label>
                        <select id="psm_schema_type" name="psm_schema_type" class="regular-text">
                            <option value="Article" <?php selected($schema_type, 'Article'); ?>><?php _e('Article (Default - Blog Posts)', 'pro-seo-master'); ?></option>
                            <option value="BlogPosting" <?php selected($schema_type, 'BlogPosting'); ?>><?php _e('Blog Posting', 'pro-seo-master'); ?></option>
                            <option value="NewsArticle" <?php selected($schema_type, 'NewsArticle'); ?>><?php _e('News Article', 'pro-seo-master'); ?></option>
                            <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>><?php _e('Web Page (Standard Pages)', 'pro-seo-master'); ?></option>
                            <option value="Product" <?php selected($schema_type, 'Product'); ?>><?php _e('Product (E-commerce)', 'pro-seo-master'); ?></option>
                            <option value="Review" <?php selected($schema_type, 'Review'); ?>><?php _e('Review', 'pro-seo-master'); ?></option>
                            <option value="Recipe" <?php selected($schema_type, 'Recipe'); ?>><?php _e('Recipe (Cooking)', 'pro-seo-master'); ?></option>
                            <option value="Event" <?php selected($schema_type, 'Event'); ?>><?php _e('Event', 'pro-seo-master'); ?></option>
                            <option value="VideoObject" <?php selected($schema_type, 'VideoObject'); ?>><?php _e('Video', 'pro-seo-master'); ?></option>
                            <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>><?php _e('FAQ Page', 'pro-seo-master'); ?></option>
                            <option value="HowTo" <?php selected($schema_type, 'HowTo'); ?>><?php _e('How-To Guide', 'pro-seo-master'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Select the most appropriate schema type for your content. Different types show different rich snippets in search results.', 'pro-seo-master'); ?>
                        </p>
                    </div>
                    
                    <div class="psm-field">
                        <label for="psm_schema_headline"><?php _e('Headline (Optional)', 'pro-seo-master'); ?></label>
                        <input type="text" id="psm_schema_headline" name="psm_schema_headline" 
                               value="<?php echo esc_attr($schema_headline); ?>" class="large-text" 
                               placeholder="<?php _e('Leave empty to use post title', 'pro-seo-master'); ?>" />
                        <p class="description"><?php _e('The headline shown in rich snippets. Defaults to post title if empty.', 'pro-seo-master'); ?></p>
                    </div>
                    
                    <div class="psm-field">
                        <label for="psm_schema_description"><?php _e('Description (Optional)', 'pro-seo-master'); ?></label>
                        <textarea id="psm_schema_description" name="psm_schema_description" 
                                  rows="3" class="large-text" 
                                  placeholder="<?php _e('Leave empty to use meta description', 'pro-seo-master'); ?>"><?php echo esc_textarea($schema_description); ?></textarea>
                        <p class="description"><?php _e('The description for structured data. Defaults to meta description if empty.', 'pro-seo-master'); ?></p>
                    </div>
                    
                    <div class="psm-field">
                        <label for="psm_schema_author_name"><?php _e('Author Name (Optional)', 'pro-seo-master'); ?></label>
                        <input type="text" id="psm_schema_author_name" name="psm_schema_author_name" 
                               value="<?php echo esc_attr($schema_author_name); ?>" class="large-text" 
                               placeholder="<?php _e('Leave empty to use post author', 'pro-seo-master'); ?>" />
                        <p class="description"><?php _e('Author displayed in rich snippets. Defaults to post author if empty.', 'pro-seo-master'); ?></p>
                    </div>
                    
                    <div class="psm-field">
                        <label for="psm_schema_publisher"><?php _e('Publisher/Organization (Optional)', 'pro-seo-master'); ?></label>
                        <input type="text" id="psm_schema_publisher" name="psm_schema_publisher" 
                               value="<?php echo esc_attr($schema_publisher); ?>" class="large-text" 
                               placeholder="<?php _e('Leave empty to use site name', 'pro-seo-master'); ?>" />
                        <p class="description"><?php _e('Publishing organization. Defaults to site name if empty.', 'pro-seo-master'); ?></p>
                    </div>
                    
                    <div class="psm-field">
                        <p><strong><?php _e('Dates', 'pro-seo-master'); ?></strong></p>
                        <p class="description">
                            <?php _e('Published and modified dates are automatically pulled from your post. They update automatically when you publish or update.', 'pro-seo-master'); ?>
                        </p>
                    </div>
                    
                    <div class="psm-field" style="background: #f0f6fc; padding: 15px; border-left: 3px solid #0073aa; border-radius: 3px;">
                        <p style="margin: 0 0 10px 0;"><strong><?php _e('💡 Schema Benefits:', 'pro-seo-master'); ?></strong></p>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li><?php _e('Appear as rich snippets in Google search results', 'pro-seo-master'); ?></li>
                            <li><?php _e('Increase click-through rates with enhanced display', 'pro-seo-master'); ?></li>
                            <li><?php _e('Help search engines understand your content better', 'pro-seo-master'); ?></li>
                            <li><?php _e('Eligible for special features (recipes cards, event listings, etc.)', 'pro-seo-master'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="psm-field">
                        <p>
                            <a href="https://search.google.com/test/rich-results" target="_blank" class="button">
                                <?php _e('Test with Google Rich Results Test', 'pro-seo-master'); ?>
                            </a>
                        </p>
                        <p class="description">
                            <?php _e('After publishing, test your page to see how it appears in Google search results.', 'pro-seo-master'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div class="psm-tab-content" data-tab="advanced">
                    <div class="psm-field">
                        <label for="psm_canonical_url"><?php _e('Canonical URL', 'pro-seo-master'); ?></label>
                        <input type="url" id="psm_canonical_url" name="psm_canonical_url" 
                               value="<?php echo esc_url($canonical_url); ?>" class="large-text" />
                        <p class="description"><?php _e('Leave empty to use default permalink.', 'pro-seo-master'); ?></p>
                    </div>
                    
                    <div class="psm-field">
                        <label><?php _e('Meta Robots', 'pro-seo-master'); ?></label>
                        <p>
                            <label>
                                <input type="checkbox" name="psm_robots_index" value="noindex" 
                                       <?php checked($robots_index, 'noindex'); ?> />
                                <?php _e('No Index (hide from search engines)', 'pro-seo-master'); ?>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="psm_robots_follow" value="nofollow" 
                                       <?php checked($robots_follow, 'nofollow'); ?> />
                                <?php _e('No Follow (don\'t follow links)', 'pro-seo-master'); ?>
                            </label>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save Meta Box
     */
    public function save_meta_box($post_id) {
        // Verify nonce
        if (!isset($_POST['psm_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['psm_meta_box_nonce'], 'psm_save_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save fields
        $fields = array(
            'psm_focus_keyphrase',
            'psm_seo_title',
            'psm_meta_description',
            'psm_canonical_url',
            'psm_og_title',
            'psm_og_description',
            'psm_twitter_title',
            'psm_twitter_description',
            'psm_schema_type',
            'psm_schema_headline',
            'psm_schema_description',
            'psm_schema_author_name',
            'psm_schema_publisher',
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save checkboxes
        update_post_meta($post_id, '_psm_robots_index', 
            isset($_POST['psm_robots_index']) ? 'noindex' : '');
        update_post_meta($post_id, '_psm_robots_follow', 
            isset($_POST['psm_robots_follow']) ? 'nofollow' : '');
    }
    
    /**
     * AJAX: Google Suggest
     */
    public function ajax_google_suggest() {
        check_ajax_referer('psm_nonce', 'nonce');
        
        if (!isset($_POST['query']) || empty($_POST['query'])) {
            wp_send_json_error('No query provided');
        }
        
        $query = sanitize_text_field($_POST['query']);
        
        // Fetch suggestions from Google
        $url = 'http://suggestqueries.google.com/complete/search?client=chrome&hl=en&q=' . urlencode($query);
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch suggestions');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data[1]) && is_array($data[1])) {
            wp_send_json_success(array_slice($data[1], 0, 10));
        } else {
            wp_send_json_error('No suggestions found');
        }
    }
    
    /**
     * AJAX: Analyze Content
     */
    public function ajax_analyze_content() {
        check_ajax_referer('psm_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $keyphrase = isset($_POST['keyphrase']) ? sanitize_text_field($_POST['keyphrase']) : '';
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
        
        // Temporarily set global $post for analysis
        global $post;
        $old_post = $post;
        $post = get_post($post_id);
        
        $seo_analysis = $this->analyze_seo($content, $title, $keyphrase, $description);
        $readability_analysis = $this->analyze_readability($content);
        
        // Restore old post
        $post = $old_post;
        
        // Save scores to post meta for Gutenberg sidebar
        if ($post_id > 0) {
            update_post_meta($post_id, '_psm_seo_score', $seo_analysis['score']);
            update_post_meta($post_id, '_psm_readability_score', $readability_analysis['score']);
            update_post_meta($post_id, '_psm_seo_rating', $seo_analysis['rating']);
            update_post_meta($post_id, '_psm_readability_rating', $readability_analysis['rating']);
        }
        
        wp_send_json_success(array(
            'seo' => $seo_analysis,
            'readability' => $readability_analysis,
        ));
    }
    
    /**
     * AJAX: Get Scores (for Gutenberg sidebar refresh)
     */
    public function ajax_get_scores() {
        check_ajax_referer('psm_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if ($post_id <= 0) {
            wp_send_json_error('Invalid post ID');
        }
        
        $seo_score = get_post_meta($post_id, '_psm_seo_score', true) ?: 0;
        $readability_score = get_post_meta($post_id, '_psm_readability_score', true) ?: 0;
        $seo_rating = get_post_meta($post_id, '_psm_seo_rating', true) ?: 'none';
        $readability_rating = get_post_meta($post_id, '_psm_readability_rating', true) ?: 'none';
        $focus_keyphrase = get_post_meta($post_id, '_psm_focus_keyphrase', true) ?: '';
        
        wp_send_json_success(array(
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'seo_rating' => $seo_rating,
            'readability_rating' => $readability_rating,
            'focus_keyphrase' => $focus_keyphrase,
        ));
    }
    
    /**
     * Analyze SEO (Enhanced with 16+ checks)
     */
    private function analyze_seo($content, $title, $keyphrase, $description) {
        global $post;
        $checks = array();
        $problems = array(); // Critical issues
        $improvements = array(); // Suggestions for improvement
        $good = array(); // What's working well
        $score = 0;
        $max_score = 0;
        
        if (empty($keyphrase)) {
            return array(
                'score' => 0,
                'rating' => 'none',
                'checks' => array(
                    array('status' => 'info', 'text' => __('Enter a focus keyphrase to see analysis.', 'pro-seo-master'))
                ),
                'problems' => array(),
                'improvements' => array(),
                'good' => array(),
            );
        }
        
        $keyphrase_lower = strtolower($keyphrase);
        $content_lower = strtolower(strip_tags($content));
        $title_lower = strtolower($title);
        $word_count = str_word_count($content_lower);
        $keyphrase_count = substr_count($content_lower, $keyphrase_lower);
        
        // ============================================
        // CHECK 1: Keyphrase in SEO title + Position
        // ============================================
        $max_score += 10;
        $title_pos = strpos($title_lower, $keyphrase_lower);
        if ($title_pos !== false) {
            // Check if it's at the beginning (first 20 chars is good)
            if ($title_pos <= 20) {
                $good[] = array('status' => 'good', 'text' => __('Focus keyphrase appears at the beginning of SEO title.', 'pro-seo-master'));
                $score += 10;
            } else {
                $improvements[] = array('status' => 'warning', 'text' => __('Focus keyphrase appears in SEO title, but not at the beginning.', 'pro-seo-master'));
                $score += 7;
            }
        } else {
            $problems[] = array('status' => 'bad', 'text' => __('Focus keyphrase doesn\'t appear in SEO title. Add it for better rankings.', 'pro-seo-master'));
        }
        
        // ============================================
        // CHECK 2: SEO Title Pixel Width (Google limit: ~600px)
        // ============================================
        $max_score += 10;
        $title_pixel_width = $this->calculate_pixel_width($title);
        if ($title_pixel_width >= 400 && $title_pixel_width <= 580) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('SEO title width is %dpx (optimal: 400-580px).', 'pro-seo-master'), $title_pixel_width));
            $score += 10;
        } elseif ($title_pixel_width > 580) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('SEO title is %dpx wide. Google may truncate it (limit: 580px).', 'pro-seo-master'), $title_pixel_width));
            $score += 5;
        } else {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('SEO title is only %dpx wide. Consider making it longer.', 'pro-seo-master'), $title_pixel_width));
            $score += 7;
        }
        
        // ============================================
        // CHECK 3: Meta Description with Keyphrase
        // ============================================
        $max_score += 10;
        if (!empty($description)) {
            if (strpos(strtolower($description), $keyphrase_lower) !== false) {
                $good[] = array('status' => 'good', 'text' => __('Focus keyphrase appears in meta description.', 'pro-seo-master'));
                $score += 10;
            } else {
                $improvements[] = array('status' => 'warning', 'text' => __('Focus keyphrase doesn\'t appear in meta description.', 'pro-seo-master'));
                $score += 5;
            }
        } else {
            $problems[] = array('status' => 'bad', 'text' => __('No meta description set. Add one to improve click-through rates.', 'pro-seo-master'));
        }
        
        // ============================================
        // CHECK 4: Meta Description Length
        // ============================================
        $max_score += 10;
        $desc_length = mb_strlen($description);
        if ($desc_length >= 120 && $desc_length <= 160) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('Meta description length is optimal (%d characters).', 'pro-seo-master'), $desc_length));
            $score += 10;
        } elseif ($desc_length > 160) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Meta description is %d characters. Google may truncate it (limit: 160).', 'pro-seo-master'), $desc_length));
            $score += 6;
        } elseif ($desc_length > 0) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Meta description is %d characters. Add more content (optimal: 120-160).', 'pro-seo-master'), $desc_length));
            $score += 6;
        }
        
        // ============================================
        // CHECK 5: Keyphrase in Introduction (First Paragraph)
        // ============================================
        $max_score += 10;
        // Extract first paragraph more accurately
        $paragraphs = preg_split('/\n\n+|<\/p>|<br\s*\/?>\s*<br\s*\/?>/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $first_para = !empty($paragraphs) ? strtolower(strip_tags($paragraphs[0])) : substr($content_lower, 0, 500);
        
        if (strpos($first_para, $keyphrase_lower) !== false) {
            $good[] = array('status' => 'good', 'text' => __('Focus keyphrase appears in the introduction (first paragraph).', 'pro-seo-master'));
            $score += 10;
        } else {
            $problems[] = array('status' => 'bad', 'text' => __('Focus keyphrase doesn\'t appear in the introduction. Add it to the first paragraph.', 'pro-seo-master'));
        }
        
        // ============================================
        // CHECK 6: Keyphrase in Subheadings (H2 & H3)
        // ============================================
        $max_score += 10;
        preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/is', $content, $heading_matches);
        $all_headings = isset($heading_matches[2]) ? $heading_matches[2] : array();
        $headings_with_keyphrase = 0;
        
        foreach ($all_headings as $heading) {
            if (strpos(strtolower(strip_tags($heading)), $keyphrase_lower) !== false) {
                $headings_with_keyphrase++;
            }
        }
        
        if ($headings_with_keyphrase > 0) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('Focus keyphrase appears in %d subheading(s) (H2/H3).', 'pro-seo-master'), $headings_with_keyphrase));
            $score += 10;
        } else {
            if (count($all_headings) > 0) {
                $improvements[] = array('status' => 'warning', 'text' => __('Focus keyphrase doesn\'t appear in any subheadings. Add it to at least one H2 or H3.', 'pro-seo-master'));
                $score += 3;
            } else {
                $improvements[] = array('status' => 'warning', 'text' => __('No subheadings found. Add H2/H3 headings with your focus keyphrase.', 'pro-seo-master'));
                $score += 3;
            }
        }
        
        // ============================================
        // CHECK 7: Single H1 Validation
        // ============================================
        $max_score += 5;
        preg_match_all('/<h1[^>]*>/i', $content, $h1_matches);
        $h1_count = count($h1_matches[0]);
        
        if ($h1_count === 0) {
            $good[] = array('status' => 'good', 'text' => __('No H1 in content (good - your post title is the H1).', 'pro-seo-master'));
            $score += 5;
        } elseif ($h1_count === 1) {
            $improvements[] = array('status' => 'warning', 'text' => __('One H1 found in content. Remove it - use H2 instead (title is already H1).', 'pro-seo-master'));
            $score += 2;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('%d H1 tags found. Use only H2-H6 in content (title is the H1).', 'pro-seo-master'), $h1_count));
        }
        
        // ============================================
        // CHECK 8: Keyphrase Density
        // ============================================
        $max_score += 10;
        $density = $word_count > 0 ? ($keyphrase_count / $word_count) * 100 : 0;
        
        if ($density >= 0.5 && $density <= 2.5) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('Keyphrase density is %.2f%% (optimal: 0.5-2.5%%).', 'pro-seo-master'), $density));
            $score += 10;
        } elseif ($density > 0 && $density < 0.5) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Keyphrase density is %.2f%% (a bit low). Try to use it more naturally.', 'pro-seo-master'), $density));
            $score += 6;
        } elseif ($density > 2.5) {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('Keyphrase density is %.2f%% (too high - keyword stuffing). Remove some instances.', 'pro-seo-master'), $density));
            $score += 3;
        } else {
            $problems[] = array('status' => 'bad', 'text' => __('Keyphrase doesn\'t appear in the content.', 'pro-seo-master'));
        }
        
        // ============================================
        // CHECK 9: Exact Keyphrase Count
        // ============================================
        $improvements[] = array('status' => 'info', 'text' => sprintf(__('Focus keyphrase found %d time(s) in content.', 'pro-seo-master'), $keyphrase_count));
        
        // ============================================
        // CHECK 10: Content Length
        // ============================================
        $max_score += 10;
        if ($word_count >= 300) {
            if ($word_count >= 1000) {
                $good[] = array('status' => 'good', 'text' => sprintf(__('Text contains %d words (excellent length for SEO).', 'pro-seo-master'), $word_count));
                $score += 10;
            } else {
                $good[] = array('status' => 'good', 'text' => sprintf(__('Text contains %d words (good length).', 'pro-seo-master'), $word_count));
                $score += 10;
            }
        } elseif ($word_count >= 150) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Text contains %d words. Add more content (minimum: 300 words).', 'pro-seo-master'), $word_count));
            $score += 5;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('Text contains only %d words (too short). Aim for at least 300 words.', 'pro-seo-master'), $word_count));
        }
        
        // ============================================
        // CHECK 11: Image Alt Text Analysis
        // ============================================
        $max_score += 10;
        preg_match_all('/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $content, $alt_matches);
        preg_match_all('/<img[^>]*>/i', $content, $all_img_matches);
        $total_images = count($all_img_matches[0]);
        $alts_with_keyphrase = 0;
        $images_with_alt = count($alt_matches[1]);
        
        if ($total_images > 0) {
            foreach ($alt_matches[1] as $alt_text) {
                if (strpos(strtolower($alt_text), $keyphrase_lower) !== false) {
                    $alts_with_keyphrase++;
                }
            }
            
            if ($alts_with_keyphrase > 0) {
                $good[] = array('status' => 'good', 'text' => sprintf(__('Focus keyphrase found in %d image alt attribute(s).', 'pro-seo-master'), $alts_with_keyphrase));
                $score += 10;
            } else {
                $improvements[] = array('status' => 'warning', 'text' => sprintf(__('No image alt attributes contain the focus keyphrase. Add it to at least one image (total: %d).', 'pro-seo-master'), $total_images));
                $score += 4;
            }
            
            // Check if all images have alt text
            if ($images_with_alt < $total_images) {
                $improvements[] = array('status' => 'warning', 'text' => sprintf(__('%d of %d images are missing alt attributes.', 'pro-seo-master'), ($total_images - $images_with_alt), $total_images));
            }
        } else {
            $improvements[] = array('status' => 'info', 'text' => __('No images found in content.', 'pro-seo-master'));
        }
        
        // ============================================
        // CHECK 12: Internal Links
        // ============================================
        $max_score += 5;
        $site_url = get_site_url();
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $link_matches);
        $all_links = isset($link_matches[1]) ? $link_matches[1] : array();
        $internal_links = 0;
        
        foreach ($all_links as $link) {
            if (strpos($link, $site_url) !== false || strpos($link, '/') === 0 && strpos($link, '//') !== 0) {
                $internal_links++;
            }
        }
        
        if ($internal_links >= 2) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('%d internal link(s) found (good for SEO).', 'pro-seo-master'), $internal_links));
            $score += 5;
        } elseif ($internal_links === 1) {
            $improvements[] = array('status' => 'warning', 'text' => __('Only 1 internal link found. Add more internal links to related content.', 'pro-seo-master'));
            $score += 3;
        } else {
            $improvements[] = array('status' => 'warning', 'text' => __('No internal links found. Add links to related content on your site.', 'pro-seo-master'));
        }
        
        // ============================================
        // CHECK 13: Outbound Links (Follow vs Nofollow)
        // ============================================
        $max_score += 5;
        $external_links = 0;
        $nofollow_links = 0;
        
        foreach ($all_links as $index => $link) {
            if (strpos($link, $site_url) === false && (strpos($link, 'http') === 0 || strpos($link, '//') === 0)) {
                $external_links++;
                // Check if nofollow
                if (isset($link_matches[0][$index]) && strpos($link_matches[0][$index], 'nofollow') !== false) {
                    $nofollow_links++;
                }
            }
        }
        
        if ($external_links > 0) {
            $follow_links = $external_links - $nofollow_links;
            $good[] = array('status' => 'good', 'text' => sprintf(__('%d outbound link(s) found (%d follow, %d nofollow).', 'pro-seo-master'), $external_links, $follow_links, $nofollow_links));
            $score += 5;
        } else {
            $improvements[] = array('status' => 'info', 'text' => __('No outbound links found. Consider linking to quality external sources.', 'pro-seo-master'));
            $score += 3;
        }
        
        // ============================================
        // CHECK 14: Keyphrase in URL Slug
        // ============================================
        $max_score += 10;
        if ($post && isset($post->post_name)) {
            $slug = $post->post_name;
            $slug_lower = strtolower($slug);
            $keyphrase_slug = str_replace(' ', '-', $keyphrase_lower);
            
            if (strpos($slug_lower, $keyphrase_slug) !== false || strpos($slug_lower, str_replace(' ', '', $keyphrase_lower)) !== false) {
                $good[] = array('status' => 'good', 'text' => __('Focus keyphrase appears in URL slug.', 'pro-seo-master'));
                $score += 10;
            } else {
                // Check if any word from keyphrase is in slug
                $keyphrase_words = explode(' ', $keyphrase_lower);
                $words_in_slug = 0;
                foreach ($keyphrase_words as $word) {
                    if (strlen($word) > 3 && strpos($slug_lower, $word) !== false) {
                        $words_in_slug++;
                    }
                }
                
                if ($words_in_slug > 0) {
                    $improvements[] = array('status' => 'warning', 'text' => __('Part of focus keyphrase appears in URL. Consider using the full keyphrase.', 'pro-seo-master'));
                    $score += 5;
                } else {
                    $problems[] = array('status' => 'bad', 'text' => __('Focus keyphrase doesn\'t appear in URL slug. Edit permalink to include it.', 'pro-seo-master'));
                }
            }
        }
        
        // ============================================
        // CHECK 15: Previously Used Keyphrase (Check Database)
        // ============================================
        $max_score += 5;
        if ($post) {
            global $wpdb;
            $duplicate_check = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_psm_focus_keyphrase' 
                AND meta_value = %s 
                AND post_id != %d",
                $keyphrase,
                $post->ID
            ));
            
            if ($duplicate_check > 0) {
                $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Focus keyphrase "%s" has been used in %d other post(s). Consider using a unique keyphrase.', 'pro-seo-master'), $keyphrase, $duplicate_check));
                $score += 2;
            } else {
                $good[] = array('status' => 'good', 'text' => __('Focus keyphrase is unique (not used in other posts).', 'pro-seo-master'));
                $score += 5;
            }
        }
        
        // Combine all checks in order: Problems, Improvements, Good
        $checks = array_merge($problems, $improvements, $good);
        
        // Calculate rating
        $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
        $rating = 'bad';
        if ($percentage >= 80) {
            $rating = 'good';
        } elseif ($percentage >= 50) {
            $rating = 'ok';
        }
        
        return array(
            'score' => round($percentage),
            'rating' => $rating,
            'checks' => $checks,
            'problems' => $problems,
            'improvements' => $improvements,
            'good' => $good,
        );
    }
    
    /**
     * Calculate Pixel Width for SEO Title
     * Approximate pixel width based on character widths
     */
    private function calculate_pixel_width($text) {
        $width = 0;
        $char_widths = array(
            // Narrow characters (4px)
            'i' => 4, 'l' => 4, 'I' => 4, 'j' => 4, 't' => 4, 'f' => 4, 'r' => 4,
            // Medium characters (7-8px)
            'a' => 7, 'b' => 7, 'c' => 7, 'd' => 7, 'e' => 7, 'g' => 7, 'h' => 7, 
            'k' => 7, 'n' => 7, 'o' => 7, 'p' => 7, 'q' => 7, 's' => 7, 'u' => 7,
            'v' => 7, 'x' => 7, 'y' => 7, 'z' => 7,
            'A' => 8, 'B' => 8, 'C' => 8, 'D' => 8, 'E' => 8, 'F' => 8, 'G' => 8,
            'H' => 8, 'J' => 8, 'K' => 8, 'L' => 8, 'N' => 8, 'O' => 8, 'P' => 8,
            'Q' => 8, 'R' => 8, 'S' => 8, 'T' => 8, 'U' => 8, 'V' => 8, 'X' => 8,
            'Y' => 8, 'Z' => 8,
            // Wide characters (9-10px)
            'm' => 10, 'w' => 10, 'M' => 11, 'W' => 11,
            // Numbers (7px)
            '0' => 7, '1' => 7, '2' => 7, '3' => 7, '4' => 7, 
            '5' => 7, '6' => 7, '7' => 7, '8' => 7, '9' => 7,
            // Special (4-6px)
            ' ' => 4, '.' => 4, ',' => 4, ':' => 4, ';' => 4, 
            '-' => 5, '_' => 6, '|' => 4,
        );
        
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $char) {
            if (isset($char_widths[$char])) {
                $width += $char_widths[$char];
            } else {
                // Default width for unknown characters
                $width += 7;
            }
        }
        
        return $width;
    }
    
    /**
     * Analyze Readability (Enhanced with 12+ checks)
     */
    private function analyze_readability($content) {
        $problems = array();
        $improvements = array();
        $good = array();
        $score = 0;
        $max_score = 0;
        
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_filter(array_map('trim', $sentences));
        $sentence_count = count($sentences);
        $word_count = str_word_count($text);
        
        if ($word_count < 50) {
            return array(
                'score' => 0,
                'rating' => 'none',
                'checks' => array(
                    array('status' => 'info', 'text' => __('Not enough content to analyze readability (minimum 50 words).', 'pro-seo-master'))
                ),
                'problems' => array(),
                'improvements' => array(),
                'good' => array(),
            );
        }
        
        // ============================================
        // CHECK 1: Average Sentence Length
        // ============================================
        $max_score += 10;
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        if ($avg_sentence_length <= 20) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('Average sentence length is %.1f words (good).', 'pro-seo-master'), $avg_sentence_length));
            $score += 10;
        } elseif ($avg_sentence_length <= 25) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Average sentence length is %.1f words (a bit long - aim for under 20).', 'pro-seo-master'), $avg_sentence_length));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('Average sentence length is %.1f words (too long - aim for under 20).', 'pro-seo-master'), $avg_sentence_length));
            $score += 2;
        }
        
        // ============================================
        // CHECK 2: Individual Sentence Length Analysis
        // ============================================
        $max_score += 10;
        $long_sentences = 0;
        $very_long_sentences = 0;
        
        foreach ($sentences as $sentence) {
            $sentence_words = str_word_count($sentence);
            if ($sentence_words > 25) {
                $long_sentences++;
            }
            if ($sentence_words > 35) {
                $very_long_sentences++;
            }
        }
        
        if ($very_long_sentences === 0 && $long_sentences <= 2) {
            $good[] = array('status' => 'good', 'text' => __('Sentence length variation is excellent.', 'pro-seo-master'));
            $score += 10;
        } elseif ($very_long_sentences === 0) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('%d sentence(s) are too long (over 25 words). Try breaking them up.', 'pro-seo-master'), $long_sentences));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('%d sentence(s) are very long (over 35 words). Break them into shorter sentences.', 'pro-seo-master'), $very_long_sentences));
            $score += 3;
        }
        
        // ============================================
        // CHECK 3: Consecutive Sentences Starting Same Way
        // ============================================
        $max_score += 10;
        $consecutive_count = 0;
        $max_consecutive = 0;
        $prev_start = '';
        
        foreach ($sentences as $sentence) {
            $words = str_word_count($sentence, 1);
            if (!empty($words)) {
                $first_word = strtolower($words[0]);
                if ($first_word === $prev_start && strlen($first_word) > 3) {
                    $consecutive_count++;
                    $max_consecutive = max($max_consecutive, $consecutive_count);
                } else {
                    $consecutive_count = 1;
                }
                $prev_start = $first_word;
            }
        }
        
        if ($max_consecutive <= 2) {
            $good[] = array('status' => 'good', 'text' => __('Good sentence variation - no repetitive sentence beginnings.', 'pro-seo-master'));
            $score += 10;
        } elseif ($max_consecutive === 3) {
            $improvements[] = array('status' => 'warning', 'text' => __('3 consecutive sentences start with the same word. Vary your sentence beginnings.', 'pro-seo-master'));
            $score += 5;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('%d consecutive sentences start the same way. Vary sentence structures.', 'pro-seo-master'), $max_consecutive));
            $score += 2;
        }
        
        // ============================================
        // CHECK 4: Paragraph Length
        // ============================================
        $max_score += 10;
        $paragraphs = preg_split('/\n\n+|<\/p>/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $para_count = count($paragraphs);
        $long_paragraphs = 0;
        
        foreach ($paragraphs as $para) {
            $para_words = str_word_count(strip_tags($para));
            if ($para_words > 150) {
                $long_paragraphs++;
            }
        }
        
        if ($long_paragraphs === 0) {
            $good[] = array('status' => 'good', 'text' => __('Paragraph length is good (all under 150 words).', 'pro-seo-master'));
            $score += 10;
        } elseif ($long_paragraphs <= 2) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('%d paragraph(s) are quite long. Break them up for better readability.', 'pro-seo-master'), $long_paragraphs));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('%d paragraphs exceed 150 words. Use shorter paragraphs.', 'pro-seo-master'), $long_paragraphs));
            $score += 3;
        }
        
        // ============================================
        // CHECK 5: Subheading Distribution
        // ============================================
        $max_score += 10;
        preg_match_all('/<h[2-6][^>]*>/i', $content, $subheading_matches);
        $subheading_count = count($subheading_matches[0]);
        $words_per_subheading = $subheading_count > 0 ? $word_count / $subheading_count : $word_count;
        
        if ($word_count < 300) {
            // Short content doesn't need many subheadings
            if ($subheading_count >= 1) {
                $good[] = array('status' => 'good', 'text' => sprintf(__('%d subheading(s) for %d words (good distribution).', 'pro-seo-master'), $subheading_count, $word_count));
                $score += 10;
            } else {
                $improvements[] = array('status' => 'warning', 'text' => __('Add at least one subheading to break up your content.', 'pro-seo-master'));
                $score += 5;
            }
        } else {
            // Longer content needs subheadings every 300 words
            if ($words_per_subheading <= 300) {
                $good[] = array('status' => 'good', 'text' => sprintf(__('%d subheading(s) for %d words (excellent distribution).', 'pro-seo-master'), $subheading_count, $word_count));
                $score += 10;
            } elseif ($words_per_subheading <= 450) {
                $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Add more subheadings (%.0f words per subheading - aim for 300).', 'pro-seo-master'), $words_per_subheading));
                $score += 6;
            } else {
                $problems[] = array('status' => 'bad', 'text' => sprintf(__('Too few subheadings (%.0f words per subheading - aim for 300).', 'pro-seo-master'), $words_per_subheading));
                $score += 3;
            }
        }
        
        // ============================================
        // CHECK 6: Transition Words Percentage
        // ============================================
        $max_score += 10;
        $transitions = array(
            'however', 'therefore', 'moreover', 'furthermore', 'additionally', 
            'consequently', 'meanwhile', 'finally', 'first', 'second', 'third',
            'also', 'because', 'although', 'though', 'while', 'since', 'unless',
            'despite', 'nevertheless', 'nonetheless', 'thus', 'hence', 'accordingly',
            'besides', 'indeed', 'instead', 'likewise', 'otherwise', 'similarly',
            'subsequently', 'for example', 'for instance', 'in addition', 'in contrast',
            'in fact', 'in other words', 'on the other hand', 'as a result'
        );
        
        $text_lower = strtolower($text);
        $sentences_with_transitions = 0;
        
        foreach ($sentences as $sentence) {
            $sentence_lower = strtolower($sentence);
            foreach ($transitions as $transition) {
                if (strpos($sentence_lower, $transition) !== false) {
                    $sentences_with_transitions++;
                    break;
                }
            }
        }
        
        $transition_percentage = $sentence_count > 0 ? ($sentences_with_transitions / $sentence_count) * 100 : 0;
        
        if ($transition_percentage >= 30) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('%.1f%% of sentences contain transition words (excellent).', 'pro-seo-master'), $transition_percentage));
            $score += 10;
        } elseif ($transition_percentage >= 20) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('%.1f%% of sentences contain transition words (aim for 30%%).', 'pro-seo-master'), $transition_percentage));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('Only %.1f%% of sentences contain transition words (aim for 30%%).', 'pro-seo-master'), $transition_percentage));
            $score += 3;
        }
        
        // ============================================
        // CHECK 7: Passive Voice Detection
        // ============================================
        $max_score += 10;
        $passive_indicators = array(
            'was ', 'were ', 'is ', 'are ', 'been ', 'be ', 'being ',
            'am ', 'was being', 'were being', 'has been', 'have been',
            'had been', 'will be', 'will have been'
        );
        
        $passive_endings = array('ed ', 'en ', 'ed.', 'en.', 'ed,', 'en,');
        $passive_count = 0;
        
        foreach ($sentences as $sentence) {
            $sentence_lower = strtolower($sentence);
            foreach ($passive_indicators as $indicator) {
                if (strpos($sentence_lower, $indicator) !== false) {
                    // Check if followed by past participle
                    foreach ($passive_endings as $ending) {
                        if (preg_match('/' . preg_quote($indicator) . '\w+' . preg_quote($ending) . '/i', $sentence_lower)) {
                            $passive_count++;
                            break 2;
                        }
                    }
                }
            }
        }
        
        $passive_percentage = $sentence_count > 0 ? ($passive_count / $sentence_count) * 100 : 0;
        
        if ($passive_percentage <= 10) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('%.1f%% passive voice (good - under 10%%).', 'pro-seo-master'), $passive_percentage));
            $score += 10;
        } elseif ($passive_percentage <= 20) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('%.1f%% passive voice (try to use more active voice).', 'pro-seo-master'), $passive_percentage));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('%.1f%% passive voice (too high - use active voice).', 'pro-seo-master'), $passive_percentage));
            $score += 3;
        }
        
        // ============================================
        // CHECK 8: Word Complexity (Long Words)
        // ============================================
        $max_score += 10;
        $words = str_word_count($text, 1);
        $complex_words = 0;
        
        foreach ($words as $word) {
            $syllables = $this->count_word_syllables($word);
            if ($syllables >= 4) {
                $complex_words++;
            }
        }
        
        $complex_percentage = $word_count > 0 ? ($complex_words / $word_count) * 100 : 0;
        
        if ($complex_percentage <= 10) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('%.1f%% complex words (good - easy to understand).', 'pro-seo-master'), $complex_percentage));
            $score += 10;
        } elseif ($complex_percentage <= 20) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('%.1f%% complex words (consider simpler alternatives).', 'pro-seo-master'), $complex_percentage));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('%.1f%% complex words (too many - simplify your language).', 'pro-seo-master'), $complex_percentage));
            $score += 3;
        }
        
        // ============================================
        // CHECK 9: Flesch Reading Ease
        // ============================================
        $max_score += 10;
        $syllable_count = $this->count_syllables($text);
        $flesch = 206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * ($syllable_count / $word_count);
        
        if ($flesch >= 60) {
            $good[] = array('status' => 'good', 'text' => sprintf(__('Flesch reading ease: %.1f (easy to read).', 'pro-seo-master'), $flesch));
            $score += 10;
        } elseif ($flesch >= 30) {
            $improvements[] = array('status' => 'warning', 'text' => sprintf(__('Flesch reading ease: %.1f (fairly difficult - simplify).', 'pro-seo-master'), $flesch));
            $score += 6;
        } else {
            $problems[] = array('status' => 'bad', 'text' => sprintf(__('Flesch reading ease: %.1f (very difficult - needs simplification).', 'pro-seo-master'), $flesch));
            $score += 3;
        }
        
        // ============================================
        // CHECK 10: Subheadings Usage Confirmation
        // ============================================
        if ($subheading_count > 0) {
            $good[] = array('status' => 'good', 'text' => __('Content uses subheadings effectively.', 'pro-seo-master'));
        }
        
        // Combine all checks: Problems, Improvements, Good
        $checks = array_merge($problems, $improvements, $good);
        
        // Calculate rating
        $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
        $rating = 'bad';
        if ($percentage >= 80) {
            $rating = 'good';
        } elseif ($percentage >= 60) {
            $rating = 'ok';
        }
        
        return array(
            'score' => round($percentage),
            'rating' => $rating,
            'checks' => $checks,
            'problems' => $problems,
            'improvements' => $improvements,
            'good' => $good,
        );
    }
    
    /**
     * Count syllables (simplified)
     */
    private function count_syllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $syllable_count = 0;
        
        foreach ($words as $word) {
            $syllable_count += $this->count_word_syllables($word);
        }
        
        return $syllable_count;
    }
    
    /**
     * Count syllables in a word
     */
    private function count_word_syllables($word) {
        $word = strtolower(preg_replace('/[^a-z]/', '', $word));
        $vowels = array('a', 'e', 'i', 'o', 'u', 'y');
        $syllable_count = 0;
        $previous_was_vowel = false;
        
        for ($i = 0; $i < strlen($word); $i++) {
            $is_vowel = in_array($word[$i], $vowels);
            if ($is_vowel && !$previous_was_vowel) {
                $syllable_count++;
            }
            $previous_was_vowel = $is_vowel;
        }
        
        // Adjust for silent e
        if (substr($word, -1) == 'e') {
            $syllable_count--;
        }
        
        return max(1, $syllable_count);
    }
    
    /**
     * Output Meta Tags
     */
    public function output_meta_tags() {
        if (is_singular()) {
            global $post;
            
            $seo_title = get_post_meta($post->ID, '_psm_seo_title', true);
            $meta_description = get_post_meta($post->ID, '_psm_meta_description', true);
            $canonical_url = get_post_meta($post->ID, '_psm_canonical_url', true);
            $robots_index = get_post_meta($post->ID, '_psm_robots_index', true);
            $robots_follow = get_post_meta($post->ID, '_psm_robots_follow', true);
            
            // Process title
            $title = $this->process_title($seo_title ?: '%%title%% %%sep%% %%sitename%%', $post);
            
            // Output title
            echo '<title>' . esc_html($title) . '</title>' . "\n";
            
            // Meta description
            if (!empty($meta_description)) {
                echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
            }
            
            // Canonical
            $canonical = !empty($canonical_url) ? $canonical_url : get_permalink($post->ID);
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
            
            // Robots
            $robots = array();
            if ($robots_index == 'noindex') $robots[] = 'noindex';
            if ($robots_follow == 'nofollow') $robots[] = 'nofollow';
            if (!empty($robots)) {
                echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '">' . "\n";
            }
            
            // Open Graph
            $og_title = get_post_meta($post->ID, '_psm_og_title', true) ?: $title;
            $og_description = get_post_meta($post->ID, '_psm_og_description', true) ?: $meta_description;
            
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
            if (!empty($og_description)) {
                echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
            }
            if (has_post_thumbnail($post->ID)) {
                echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url($post->ID, 'large')) . '">' . "\n";
            }
            
            // Twitter Cards
            $twitter_title = get_post_meta($post->ID, '_psm_twitter_title', true) ?: $title;
            $twitter_description = get_post_meta($post->ID, '_psm_twitter_description', true) ?: $meta_description;
            
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '">' . "\n";
            if (!empty($twitter_description)) {
                echo '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '">' . "\n";
            }
            if (has_post_thumbnail($post->ID)) {
                echo '<meta name="twitter:image" content="' . esc_url(get_the_post_thumbnail_url($post->ID, 'large')) . '">' . "\n";
            }
            
            // Schema.org
            $this->output_schema($post);
            
        } elseif (is_front_page()) {
            $settings = get_option('psm_settings', array());
            $title = $settings['homepage_title'] ?? get_bloginfo('name');
            $description = $settings['homepage_description'] ?? get_bloginfo('description');
            
            echo '<title>' . esc_html($title) . '</title>' . "\n";
            if (!empty($description)) {
                echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            }
            echo '<link rel="canonical" href="' . esc_url(home_url('/')) . '">' . "\n";
            
            // Organization schema
            $this->output_organization_schema();
        }
    }
    
    /**
     * Process Title with Variables
     */
    private function process_title($title, $post = null) {
        $settings = get_option('psm_settings', array());
        $separator = $settings['separator'] ?? '-';
        
        $title = str_replace('%%sep%%', $separator, $title);
        $title = str_replace('%%sitename%%', get_bloginfo('name'), $title);
        
        if ($post) {
            $title = str_replace('%%title%%', get_the_title($post->ID), $title);
        }
        
        return $title;
    }
    
    /**
     * Output Schema.org Markup (Enhanced with custom schema types)
     */
    private function output_schema($post) {
        // Get custom schema settings
        $schema_type = get_post_meta($post->ID, '_psm_schema_type', true) ?: 'Article';
        $schema_headline = get_post_meta($post->ID, '_psm_schema_headline', true);
        $schema_description = get_post_meta($post->ID, '_psm_schema_description', true);
        $schema_author_name = get_post_meta($post->ID, '_psm_schema_author_name', true);
        $schema_publisher = get_post_meta($post->ID, '_psm_schema_publisher', true);
        
        // Build base schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'headline' => !empty($schema_headline) ? $schema_headline : get_the_title($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
        );
        
        // Add description if available
        if (!empty($schema_description)) {
            $schema['description'] = $schema_description;
        } elseif ($meta_desc = get_post_meta($post->ID, '_psm_meta_description', true)) {
            $schema['description'] = $meta_desc;
        }
        
        // Add author
        $author_name = !empty($schema_author_name) ? $schema_author_name : get_the_author_meta('display_name', $post->post_author);
        
        if (in_array($schema_type, array('Article', 'BlogPosting', 'NewsArticle', 'Review'))) {
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => $author_name,
            );
        }
        
        // Add image if available
        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'large');
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image_url,
            );
        }
        
        // Add publisher for article types
        if (in_array($schema_type, array('Article', 'BlogPosting', 'NewsArticle'))) {
            $settings = get_option('psm_settings', array());
            $publisher_name = !empty($schema_publisher) ? $schema_publisher : ($settings['company_name'] ?? get_bloginfo('name'));
            
            $schema['publisher'] = array(
                '@type' => 'Organization',
                'name' => $publisher_name,
            );
            
            // Add logo if available
            if (!empty($settings['company_logo'])) {
                $schema['publisher']['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $settings['company_logo'],
                );
            }
        }
        
        // Add main entity of page
        $schema['mainEntityOfPage'] = array(
            '@type' => 'WebPage',
            '@id' => get_permalink($post->ID),
        );
        
        // Type-specific properties
        switch ($schema_type) {
            case 'Product':
                // Basic product schema (can be extended with price, availability, etc.)
                $schema['name'] = $schema['headline'];
                unset($schema['headline']);
                break;
                
            case 'Recipe':
                // Basic recipe schema
                $schema['name'] = $schema['headline'];
                $schema['recipeCategory'] = 'General';
                unset($schema['headline']);
                break;
                
            case 'Event':
                // Basic event schema
                $schema['name'] = $schema['headline'];
                $schema['startDate'] = get_the_date('c', $post->ID);
                unset($schema['headline']);
                break;
                
            case 'VideoObject':
                // Basic video schema
                $schema['name'] = $schema['headline'];
                $schema['uploadDate'] = get_the_date('c', $post->ID);
                unset($schema['headline']);
                break;
        }
        
        // Output JSON-LD
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    /**
     * Output Organization Schema
     */
    private function output_organization_schema() {
        $settings = get_option('psm_settings', array());
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings['company_name'] ?? get_bloginfo('name'),
            'url' => home_url('/'),
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    /**
     * Register Sitemap Routes
     */
    public function register_sitemap_routes() {
        add_rewrite_rule('^sitemap_index\.xml$', 'index.php?psm_sitemap=index', 'top');
        add_rewrite_rule('^([^/]+)-sitemap\.xml$', 'index.php?psm_sitemap=$matches[1]', 'top');
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'psm_sitemap';
            return $vars;
        });
    }
    
    /**
     * Handle Sitemap Request
     */
    public function handle_sitemap_request() {
        $sitemap = get_query_var('psm_sitemap');
        
        if (empty($sitemap)) {
            return;
        }
        
        header('Content-Type: application/xml; charset=utf-8');
        
        if ($sitemap === 'index') {
            $this->generate_sitemap_index();
        } else {
            $this->generate_sitemap($sitemap);
        }
        
        exit;
    }
    
    /**
     * Generate Sitemap Index
     */
    private function generate_sitemap_index() {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false));
        
        foreach ($post_types as $post_type) {
            echo '  <sitemap>' . "\n";
            echo '    <loc>' . esc_url(home_url('/' . $post_type . '-sitemap.xml')) . '</loc>' . "\n";
            echo '    <lastmod>' . date('c') . '</lastmod>' . "\n";
            echo '  </sitemap>' . "\n";
        }
        
        echo '</sitemapindex>';
    }
    
    /**
     * Generate Sitemap for Post Type
     */
    private function generate_sitemap($post_type) {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 500,
            'orderby' => 'modified',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                // Skip if noindex
                $robots_index = get_post_meta(get_the_ID(), '_psm_robots_index', true);
                if ($robots_index == 'noindex') {
                    continue;
                }
                
                echo '  <url>' . "\n";
                echo '    <loc>' . esc_url(get_permalink()) . '</loc>' . "\n";
                echo '    <lastmod>' . get_the_modified_date('c') . '</lastmod>' . "\n";
                echo '  </url>' . "\n";
            }
        }
        
        wp_reset_postdata();
        
        echo '</urlset>';
    }
    
    /**
     * Breadcrumbs Shortcode
     */
    public function breadcrumbs_shortcode($atts) {
        return $this->get_breadcrumbs();
    }
    
    /**
     * Get Breadcrumbs
     */
    public function get_breadcrumbs() {
        if (is_front_page()) {
            return '';
        }
        
        $breadcrumbs = array();
        $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . __('Home', 'pro-seo-master') . '</a>';
        
        if (is_single()) {
            $categories = get_the_category();
            if (!empty($categories)) {
                $breadcrumbs[] = '<a href="' . esc_url(get_category_link($categories[0]->term_id)) . '">' . 
                                esc_html($categories[0]->name) . '</a>';
            }
            $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
        } elseif (is_page()) {
            $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
        } elseif (is_category()) {
            $breadcrumbs[] = '<span>' . single_cat_title('', false) . '</span>';
        } elseif (is_archive()) {
            $breadcrumbs[] = '<span>' . get_the_archive_title() . '</span>';
        }
        
        $output = '<nav class="psm-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'pro-seo-master') . '">';
        $output .= implode(' &raquo; ', $breadcrumbs);
        $output .= '</nav>';
        
        return $output;
    }
}

// Initialize plugin
function psm_init() {
    return Pro_SEO_Master::get_instance();
}
add_action('plugins_loaded', 'psm_init');

// Template function for breadcrumbs
function psm_breadcrumbs() {
    $plugin = Pro_SEO_Master::get_instance();
    echo $plugin->get_breadcrumbs();
}
