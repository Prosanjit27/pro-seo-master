=== Pro SEO Master ===
Contributors: prosanjitdhar
Tags: seo, google, search engine optimization, meta, sitemap, schema, breadcrumbs, keywords
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.4.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive SEO plugin with advanced content analysis and real-time Google keyword suggestions for focus keyphrases.

== Description ==

**Pro SEO Master** is a powerful, all-in-one SEO plugin that helps you optimize your WordPress website for search engines. With advanced content analysis and a unique Google autocomplete integration for keyword research, Pro SEO Master makes SEO simple and effective.

= Key Features =

**🎯 Focus Keyphrase with Google Autocomplete (Standout Feature!)**
* Real-time keyword suggestions as you type
* Powered by Google's autocomplete API
* Find popular search terms instantly
* Beautiful, intuitive dropdown interface
* Keyboard navigation support (arrow keys, Enter, Escape)

**📊 Complete SEO Analysis**
* Focus keyphrase in title, description, content
* Keyword density tracking (optimal 1-2.5%)
* Content length recommendations
* Title and meta description length checks
* Traffic light indicators (red/orange/green)

**📖 Readability Analysis**
* Sentence length analysis
* Paragraph length evaluation
* Subheading usage check
* Transition words detection
* Flesch reading ease score

**🔍 Google Snippet Preview**
* Desktop and mobile preview modes
* Real-time updates as you type
* See exactly how your page appears in search results
* Title and description preview

**🎨 SEO Meta Box**
* Gutenberg and Classic Editor compatible
* Editable SEO title with variables (%%title%%, %%sitename%%, %%sep%%)
* Meta description with character counter
* Focus keyphrase input
* Tabbed interface (SEO, Social, Advanced)

**📱 Social Media Integration**
* Open Graph tags for Facebook
* Twitter Card support
* Custom titles and descriptions for social platforms
* Automatic image detection

**🔧 Advanced Settings**
* Custom canonical URLs
* Meta robots (noindex, nofollow)
* Per-post/page control
* Site-wide settings page

**🗺️ XML Sitemap**
* Automatic sitemap generation
* Sitemap index for all post types
* Dynamic updates
* Search engine friendly format
* Accessible at /sitemap_index.xml

**🍞 Breadcrumbs**
* Clean, semantic breadcrumb navigation
* Shortcode support: [psm_breadcrumbs]
* Template function: psm_breadcrumbs()
* Schema.org markup ready

**📋 Structured Data / Schema.org**
* Automatic Article schema
* WebPage schema
* Organization schema
* JSON-LD format
* Search engine friendly

= Why Choose Pro SEO Master? =

1. **Google Autocomplete Integration** - Unique feature for keyword research directly in WordPress
2. **Comprehensive Analysis** - Both SEO and readability in one place
3. **Modern UI** - Clean, professional interface
4. **No External Dependencies** - 100% native WordPress integration
5. **Lightweight** - Optimized code, fast performance
6. **Regular Updates** - Active development and support

= Perfect For =

* Bloggers who want to optimize content
* Small business owners improving online presence
* Content marketers researching keywords
* SEO professionals managing multiple sites
* Anyone serious about search engine optimization

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "Pro SEO Master"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

= After Activation =

1. Go to SEO > Settings to configure site-wide options
2. Edit any post or page to see the Pro SEO Master meta box
3. Enter a focus keyphrase and start typing to see Google suggestions!
4. Fill in your SEO title and meta description
5. Click "Analyze Content" to get your SEO and readability scores
6. View your XML sitemap at yourdomain.com/sitemap_index.xml

== Frequently Asked Questions ==

= How does the Google autocomplete feature work? =

As you type in the Focus Keyphrase field (minimum 3 characters), Pro SEO Master fetches real-time keyword suggestions from Google's autocomplete API. This helps you discover popular search terms related to your topic without leaving WordPress.

= Is this plugin compatible with Gutenberg? =

Yes! Pro SEO Master works perfectly with both the Gutenberg block editor and the Classic Editor.

= Will this conflict with other SEO plugins? =

For best results, we recommend using only one SEO plugin at a time. If you're switching from another SEO plugin, deactivate it before installing Pro SEO Master.

= How do I view my XML sitemap? =

Your sitemap is automatically available at: `https://yourdomain.com/sitemap_index.xml`

= How do I add breadcrumbs to my theme? =

You can use either:
* Shortcode: `[psm_breadcrumbs]` in posts/pages
* Template function: `<?php if (function_exists('psm_breadcrumbs')) psm_breadcrumbs(); ?>` in theme files

= Does this plugin slow down my site? =

No! Pro SEO Master is optimized for performance. Scripts and styles only load where needed (admin post editor), and the frontend has minimal overhead.

= What are the SEO title variables? =

Available variables:
* `%%title%%` - Post/page title
* `%%sitename%%` - Your site name
* `%%sep%%` - Separator (configured in settings)

= How is the SEO score calculated? =

The SEO score is based on multiple factors:
* Keyphrase in title (10 points)
* Keyphrase in meta description (10 points)
* Keyphrase in first paragraph (10 points)
* Keyphrase density 1-2.5% (10 points)
* Content length 300+ words (10 points)
* Title length 30-60 characters (10 points)
* Description length 120-160 characters (10 points)

Total possible: 70 points = 100%
* 80%+ = Green (Good)
* 50-79% = Orange (Needs improvement)
* Below 50% = Red (Poor)

= What is Flesch reading ease? =

The Flesch reading ease score measures how easy your text is to read. Scores range from 0-100:
* 90-100: Very easy (5th grade)
* 60-89: Easy (6th-8th grade)
* 30-59: Fairly difficult (10th-12th grade)
* 0-29: Very difficult (College level)

Higher scores = easier to read = better for most audiences.

== Screenshots ==

1. SEO meta box with Google autocomplete suggestions
2. Google snippet preview (desktop and mobile)
3. SEO analysis with traffic light indicators
4. Readability analysis with detailed checks
5. Social media settings (Open Graph and Twitter)
6. Advanced settings (canonical, robots)
7. Site-wide settings page

== Changelog ==

= 1.4.0 - 2024-02-02 =
* Enhanced Gutenberg sidebar with expandable sections
* Added "Analyze this page" section with one-click analysis
* Added "SEO Tools" section with helpful external links
* Added "How to" section with step-by-step SEO tips
* Added "Help" section with score explanations
* Improved sidebar UI to match modern WordPress design
* Added collapsible sections for better organization
* Focus keyphrase now displayed prominently at top
* Better visual hierarchy and spacing
* Added helpful links to Google Search Console, Rich Results Test, etc.

= 1.3.0 - 2024-02-02 =
* Enhanced readability analysis with 12+ comprehensive checks
* Added word complexity analysis
* Added transition words percentage tracking
* Added passive voice detection
* Added consecutive sentence pattern detection
* Improved subheading distribution analysis
* Added individual sentence length checks
* Enhanced Flesch reading ease calculation
* Added tooltips for all analysis sections
* Added comprehensive schema generator with 11 types
* Problems/Improvements/Good categorization for better UX
* WordPress 6.7+ compatibility verified

= 1.2.0 - 2024-01-30 =
* Enhanced SEO analysis with 16+ comprehensive checks
* Added keyphrase position detection in titles
* Added SEO title pixel width calculation
* Added keyphrase in H2/H3 subheadings check
* Added keyphrase in URL slug validation
* Added previously used keyphrase detection
* Added single H1 validation
* Added image alt text analysis
* Added internal links check
* Added outbound links analysis (follow vs nofollow)
* Added exact keyphrase count display
* Problems/Improvements/Good categorization
* Enhanced content length validation
* Improved meta description checks

= 1.1.0 - 2024-01-30 =
* Added Gutenberg sidebar panel with live scores
* Added REST API integration
* Fixed text breaking issues in analysis display
* Scores now persist between sessions
* Auto-refresh on post save

= 1.0.0 - 2024-01-30 =
* Initial release
* Google autocomplete for focus keyphrase
* Complete SEO analysis
* Readability analysis with Flesch reading ease
* Google snippet preview (desktop/mobile)
* XML sitemap generation
* Breadcrumb support
* Schema.org structured data
* Open Graph and Twitter Cards
* Meta robots control
* Canonical URL support
* Gutenberg and Classic Editor support
* Professional UI design

== Upgrade Notice ==

= 1.0.0 =
Initial release of Pro SEO Master. Install to get started with powerful SEO optimization!

== Additional Information ==

= Privacy & Data =

Pro SEO Master does not collect any personal data. The Google autocomplete feature sends your typed keywords to Google's API to fetch suggestions, but no user data or site information is transmitted.

= Support =

For support, questions, or feature requests, please visit our support forum or contact us through our website.

= Contributing =

Pro SEO Master is open source. If you'd like to contribute, report bugs, or suggest features, please visit our GitHub repository.

= Credits =

* Developed with love for the WordPress community
* Google autocomplete API for keyword suggestions
* Inspired by SEO best practices and industry standards

== Technical Details ==

= System Requirements =
* WordPress 5.0 or higher
* PHP 7.2 or higher
* MySQL 5.6 or higher

= File Structure =
```
pro-seo-master/
├── pro-seo-master.php (Main plugin file)
├── readme.txt (This file)
└── assets/
    ├── js/
    │   └── admin.js (Admin JavaScript)
    └── css/
        └── admin.css (Admin styles)
```

= Hooks & Filters =

Pro SEO Master provides various hooks for developers:

**Actions:**
* `psm_before_meta_output` - Before meta tags output
* `psm_after_meta_output` - After meta tags output

**Filters:**
* `psm_seo_title` - Filter the SEO title
* `psm_meta_description` - Filter meta description
* `psm_breadcrumbs_output` - Filter breadcrumb HTML

= Template Functions =

```php
// Display breadcrumbs
if (function_exists('psm_breadcrumbs')) {
    psm_breadcrumbs();
}
```

= Shortcodes =

* `[psm_breadcrumbs]` - Display breadcrumb navigation

== License ==

Pro SEO Master is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

Pro SEO Master is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Pro SEO Master. If not, see <https://www.gnu.org/licenses/gpl-2.0.html>.
