/**
 * Pro SEO Master - Gutenberg Sidebar
 * Displays SEO and Readability scores in the block editor sidebar
 */

(function(wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, PanelRow, Button, TextControl } = wp.components;
    const { createElement: el, Fragment, Component } = wp.element;
    const { withSelect, withDispatch } = wp.data;
    const { compose } = wp.compose;

    class ProSEOMasterSidebar extends Component {
        constructor(props) {
            super(props);
            this.state = {
                seoScore: 0,
                readabilityScore: 0,
                seoRating: 'none',
                readabilityRating: 'none',
                focusKeyphrase: '',
                isAnalyzing: false,
                expandedSection: null,
            };
            this.loadScores = this.loadScores.bind(this);
            this.analyzeContent = this.analyzeContent.bind(this);
            this.toggleSection = this.toggleSection.bind(this);
        }

        componentDidMount() {
            this.loadScores();
            // Refresh scores when post is saved
            this.unsubscribe = wp.data.subscribe(() => {
                const isSaving = wp.data.select('core/editor').isSavingPost();
                const isAutosaving = wp.data.select('core/editor').isAutosavingPost();
                
                if (!isSaving && !isAutosaving && this.wasSaving) {
                    this.loadScores();
                }
                
                this.wasSaving = isSaving;
            });
        }

        componentWillUnmount() {
            if (this.unsubscribe) {
                this.unsubscribe();
            }
        }

        loadScores() {
            const postId = psmGutenberg.post_id;
            
            jQuery.ajax({
                url: psmGutenberg.ajax_url,
                type: 'POST',
                data: {
                    action: 'psm_get_scores',
                    nonce: psmGutenberg.nonce,
                    post_id: postId,
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.setState({
                            seoScore: parseInt(response.data.seo_score) || 0,
                            readabilityScore: parseInt(response.data.readability_score) || 0,
                            seoRating: response.data.seo_rating || 'none',
                            readabilityRating: response.data.readability_rating || 'none',
                            focusKeyphrase: response.data.focus_keyphrase || '',
                        });
                    }
                },
            });
        }

        analyzeContent() {
            this.setState({ isAnalyzing: true });

            const content = wp.data.select('core/editor').getEditedPostContent();
            const title = wp.data.select('core/editor').getEditedPostAttribute('title');
            const postId = psmGutenberg.post_id;

            // Get meta values
            const meta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
            const keyphrase = meta._psm_focus_keyphrase || '';
            const description = meta._psm_meta_description || '';

            jQuery.ajax({
                url: psmGutenberg.ajax_url,
                type: 'POST',
                data: {
                    action: 'psm_analyze_content',
                    nonce: psmGutenberg.nonce,
                    post_id: postId,
                    content: content,
                    title: title,
                    keyphrase: keyphrase,
                    description: description,
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.setState({
                            seoScore: response.data.seo.score,
                            readabilityScore: response.data.readability.score,
                            seoRating: response.data.seo.rating,
                            readabilityRating: response.data.readability.rating,
                            isAnalyzing: false,
                        });
                    } else {
                        this.setState({ isAnalyzing: false });
                    }
                },
                error: () => {
                    this.setState({ isAnalyzing: false });
                },
            });
        }

        toggleSection(section) {
            this.setState({
                expandedSection: this.state.expandedSection === section ? null : section
            });
        }

        getScoreColor(rating) {
            switch(rating) {
                case 'good': return '#7ad03a';
                case 'ok': return '#ffba00';
                case 'bad': return '#dc3232';
                default: return '#a7aaad';
            }
        }

        getScoreText(score, rating) {
            if (rating === 'none' || score === 0) {
                return 'Not analyzed';
            }
            return score + '%';
        }

        render() {
            const { seoScore, readabilityScore, seoRating, readabilityRating, focusKeyphrase, isAnalyzing, expandedSection } = this.state;

            return el(
                Fragment,
                {},
                el(
                    PluginSidebarMoreMenuItem,
                    { target: 'psm-sidebar', icon: 'search' },
                    'SEO'
                ),
                el(
                    PluginSidebar,
                    {
                        name: 'psm-sidebar',
                        title: 'Pro SEO Master',
                        icon: 'search',
                    },
                    // Focus Keyphrase Display
                    focusKeyphrase && el(
                        'div',
                        {
                            style: {
                                padding: '16px',
                                borderBottom: '1px solid #ddd',
                                fontSize: '13px',
                                color: '#1e1e1e',
                            }
                        },
                        el(
                            'div',
                            { style: { marginBottom: '4px', fontWeight: '500' } },
                            'Focus keyphrase: ' + focusKeyphrase
                        )
                    ),
                    
                    // SEO Score
                    el(
                        'div',
                        {
                            style: {
                                padding: '16px',
                                borderBottom: '1px solid #ddd',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                            }
                        },
                        el('span', { style: { fontSize: '14px', fontWeight: '500' } }, 'SEO score:'),
                        el('span', {
                            style: {
                                width: '16px',
                                height: '16px',
                                borderRadius: '50%',
                                backgroundColor: this.getScoreColor(seoRating),
                                display: 'inline-block',
                            }
                        })
                    ),
                    
                    // Readability Score
                    el(
                        'div',
                        {
                            style: {
                                padding: '16px',
                                borderBottom: '1px solid #ddd',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                            }
                        },
                        el('span', { style: { fontSize: '14px', fontWeight: '500' } }, 'Readability:'),
                        el('span', {
                            style: {
                                width: '16px',
                                height: '16px',
                                borderRadius: '50%',
                                backgroundColor: this.getScoreColor(readabilityRating),
                                display: 'inline-block',
                            }
                        })
                    ),
                    
                    // Analyze this page section
                    el(
                        'div',
                        {
                            style: {
                                borderBottom: '1px solid #ddd',
                            }
                        },
                        el(
                            'button',
                            {
                                onClick: () => this.toggleSection('analyze'),
                                style: {
                                    width: '100%',
                                    padding: '16px',
                                    background: 'transparent',
                                    border: 'none',
                                    textAlign: 'left',
                                    cursor: 'pointer',
                                    fontSize: '14px',
                                    fontWeight: '500',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                }
                            },
                            'Analyze this page',
                            el('span', {}, expandedSection === 'analyze' ? '▲' : '▶')
                        ),
                        expandedSection === 'analyze' && el(
                            'div',
                            { style: { padding: '0 16px 16px 16px' } },
                            el(
                                Button,
                                {
                                    isPrimary: true,
                                    onClick: this.analyzeContent,
                                    disabled: isAnalyzing,
                                    style: { width: '100%', justifyContent: 'center' }
                                },
                                isAnalyzing ? 'Analyzing...' : 'Run Analysis'
                            ),
                            el(
                                'p',
                                {
                                    style: {
                                        fontSize: '12px',
                                        color: '#757575',
                                        marginTop: '12px',
                                        lineHeight: '1.5',
                                    }
                                },
                                'Click to analyze your content for SEO and readability. Results appear in the meta box below.'
                            )
                        )
                    ),
                    
                    // SEO Tools section
                    el(
                        'div',
                        {
                            style: {
                                borderBottom: '1px solid #ddd',
                            }
                        },
                        el(
                            'button',
                            {
                                onClick: () => this.toggleSection('tools'),
                                style: {
                                    width: '100%',
                                    padding: '16px',
                                    background: 'transparent',
                                    border: 'none',
                                    textAlign: 'left',
                                    cursor: 'pointer',
                                    fontSize: '14px',
                                    fontWeight: '500',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                }
                            },
                            'SEO Tools',
                            el('span', {}, expandedSection === 'tools' ? '▲' : '▶')
                        ),
                        expandedSection === 'tools' && el(
                            'div',
                            { style: { padding: '0 16px 16px 16px' } },
                            el(
                                'ul',
                                { style: { margin: 0, paddingLeft: '20px', fontSize: '13px', lineHeight: '2' } },
                                el('li', {}, el('a', { href: 'https://search.google.com/search-console', target: '_blank' }, 'Google Search Console')),
                                el('li', {}, el('a', { href: 'https://search.google.com/test/rich-results', target: '_blank' }, 'Rich Results Test')),
                                el('li', {}, el('a', { href: 'https://developers.google.com/speed/pagespeed/insights/', target: '_blank' }, 'PageSpeed Insights')),
                                el('li', {}, el('a', { href: 'https://www.bing.com/webmasters', target: '_blank' }, 'Bing Webmaster Tools'))
                            )
                        )
                    ),
                    
                    // How to section
                    el(
                        'div',
                        {
                            style: {
                                borderBottom: '1px solid #ddd',
                            }
                        },
                        el(
                            'button',
                            {
                                onClick: () => this.toggleSection('howto'),
                                style: {
                                    width: '100%',
                                    padding: '16px',
                                    background: 'transparent',
                                    border: 'none',
                                    textAlign: 'left',
                                    cursor: 'pointer',
                                    fontSize: '14px',
                                    fontWeight: '500',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                }
                            },
                            'How to',
                            el('span', {}, expandedSection === 'howto' ? '▲' : '▶')
                        ),
                        expandedSection === 'howto' && el(
                            'div',
                            { style: { padding: '0 16px 16px 16px' } },
                            el(
                                'ul',
                                { style: { margin: 0, paddingLeft: '20px', fontSize: '13px', lineHeight: '2' } },
                                el('li', {}, 'Enter a focus keyphrase for your content'),
                                el('li', {}, 'Use Google autocomplete for keyword ideas'),
                                el('li', {}, 'Write compelling title and meta description'),
                                el('li', {}, 'Include keyphrase in first paragraph'),
                                el('li', {}, 'Add internal and external links'),
                                el('li', {}, 'Use clear subheadings (H2, H3)'),
                                el('li', {}, 'Keep sentences short and readable'),
                                el('li', {}, 'Click "Analyze" to check your score')
                            )
                        )
                    ),
                    
                    // Help section
                    el(
                        'div',
                        {
                            style: {
                                borderBottom: '1px solid #ddd',
                            }
                        },
                        el(
                            'button',
                            {
                                onClick: () => this.toggleSection('help'),
                                style: {
                                    width: '100%',
                                    padding: '16px',
                                    background: 'transparent',
                                    border: 'none',
                                    textAlign: 'left',
                                    cursor: 'pointer',
                                    fontSize: '14px',
                                    fontWeight: '500',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                }
                            },
                            'Help',
                            el('span', {}, expandedSection === 'help' ? '▲' : '▶')
                        ),
                        expandedSection === 'help' && el(
                            'div',
                            { style: { padding: '0 16px 16px 16px' } },
                            el(
                                'div',
                                { style: { fontSize: '13px', lineHeight: '1.6' } },
                                el('p', { style: { marginTop: 0 } }, el('strong', {}, 'Need help?')),
                                el('p', {}, 'Check the meta box below the editor for detailed SEO settings and analysis.'),
                                el('p', {}, el('strong', {}, 'Score Colors:')),
                                el('ul', { style: { paddingLeft: '20px' } },
                                    el('li', {}, '🟢 Green (80%+) = Excellent'),
                                    el('li', {}, '🟠 Orange (50-79%) = Good, can improve'),
                                    el('li', {}, '🔴 Red (0-49%) = Needs work')
                                ),
                                el('p', {}, el('strong', {}, 'Pro Tip:')),
                                el('p', {}, 'Scroll down to the Pro SEO Master meta box for the full experience including Google autocomplete, snippet preview, and detailed recommendations.')
                            )
                        )
                    ),
                    
                    // Bottom note
                    el(
                        'div',
                        {
                            style: {
                                padding: '16px',
                                fontSize: '12px',
                                color: '#757575',
                                textAlign: 'center',
                                lineHeight: '1.5',
                            }
                        },
                        'For complete SEO settings, scroll to the Pro SEO Master meta box below the editor.'
                    )
                )
            );
        }
    }

    // Register the plugin
    registerPlugin('pro-seo-master-sidebar', {
        render: ProSEOMasterSidebar,
        icon: 'search',
    });

})(window.wp);
