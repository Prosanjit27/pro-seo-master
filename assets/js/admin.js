/**
 * Pro SEO Master - Admin JavaScript
 * Handles Google autocomplete, snippet preview, content analysis, and UI interactions
 */

(function($) {
    'use strict';
    
    var PSM = {
        
        // Configuration
        config: {
            debounceDelay: 300,
            minCharsForSuggest: 3,
        },
        
        // State
        state: {
            currentSuggestionIndex: -1,
            suggestions: [],
            debounceTimer: null,
        },
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.updateSnippetPreview();
            this.updateCharacterCounts();
        },
        
        /**
         * Bind Events
         */
        bindEvents: function() {
            var self = this;
            
            // Tab switching
            $('.psm-tab-button').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Snippet preview device toggle
            $('.psm-preview-btn').on('click', function(e) {
                e.preventDefault();
                var device = $(this).data('device');
                self.toggleSnippetDevice(device);
            });
            
            // Focus keyphrase - Google Autocomplete
            $('#psm_focus_keyphrase').on('input', function() {
                self.handleKeyphraseInput($(this).val());
            });
            
            // Focus keyphrase - keyboard navigation
            $('#psm_focus_keyphrase').on('keydown', function(e) {
                if ($('#psm-suggestions').is(':visible')) {
                    self.handleSuggestionNavigation(e);
                }
            });
            
            // Click outside to close suggestions
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.psm-autocomplete-wrapper').length) {
                    $('#psm-suggestions').hide();
                }
            });
            
            // SEO Title and Meta Description - Update preview and counts
            $('#psm_seo_title, #psm_meta_description, #psm_focus_keyphrase').on('input', function() {
                self.updateSnippetPreview();
                self.updateCharacterCounts();
            });
            
            // Also update on WordPress editor content change (Classic and Gutenberg)
            if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
                // Gutenberg
                var editor = wp.data.select('core/editor');
                if (editor) {
                    wp.data.subscribe(function() {
                        setTimeout(function() {
                            self.updateSnippetPreview();
                        }, 100);
                    });
                }
            }
            
            // Classic Editor
            if (typeof tinymce !== 'undefined') {
                $(document).on('tinymce-editor-init', function(event, editor) {
                    editor.on('input change', function() {
                        self.updateSnippetPreview();
                    });
                });
            }
            
            // Analyze content button
            $('#psm-analyze-btn').on('click', function(e) {
                e.preventDefault();
                self.analyzeContent();
            });
        },
        
        /**
         * Switch Tab
         */
        switchTab: function(tab) {
            $('.psm-tab-button').removeClass('active');
            $('.psm-tab-button[data-tab="' + tab + '"]').addClass('active');
            
            $('.psm-tab-content').removeClass('active');
            $('.psm-tab-content[data-tab="' + tab + '"]').addClass('active');
        },
        
        /**
         * Toggle Snippet Preview Device
         */
        toggleSnippetDevice: function(device) {
            $('.psm-preview-btn').removeClass('active');
            $('.psm-preview-btn[data-device="' + device + '"]').addClass('active');
            
            $('.psm-snippet').removeClass('desktop mobile').addClass(device);
        },
        
        /**
         * Handle Keyphrase Input (Google Autocomplete)
         */
        handleKeyphraseInput: function(query) {
            var self = this;
            
            // Clear previous timer
            clearTimeout(this.state.debounceTimer);
            
            // Hide suggestions if query is too short
            if (query.length < this.config.minCharsForSuggest) {
                $('#psm-suggestions').hide();
                return;
            }
            
            // Debounce the API call
            this.state.debounceTimer = setTimeout(function() {
                self.fetchGoogleSuggestions(query);
            }, this.config.debounceDelay);
        },
        
        /**
         * Fetch Google Suggestions via AJAX
         */
        fetchGoogleSuggestions: function(query) {
            var self = this;
            
            $.ajax({
                url: psmData.ajax_url,
                type: 'POST',
                data: {
                    action: 'psm_google_suggest',
                    nonce: psmData.nonce,
                    query: query,
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.displaySuggestions(response.data, query);
                    } else {
                        $('#psm-suggestions').hide();
                    }
                },
                error: function() {
                    $('#psm-suggestions').hide();
                }
            });
        },
        
        /**
         * Display Suggestions
         */
        displaySuggestions: function(suggestions, query) {
            var self = this;
            this.state.suggestions = suggestions;
            this.state.currentSuggestionIndex = -1;
            
            var $container = $('#psm-suggestions');
            $container.empty();
            
            if (suggestions.length === 0) {
                $container.hide();
                return;
            }
            
            var queryLower = query.toLowerCase();
            
            $.each(suggestions, function(index, suggestion) {
                var $item = $('<div class="psm-suggestion-item"></div>');
                
                // Highlight matching text
                var suggestionLower = suggestion.toLowerCase();
                var matchIndex = suggestionLower.indexOf(queryLower);
                
                if (matchIndex !== -1) {
                    var before = suggestion.substring(0, matchIndex);
                    var match = suggestion.substring(matchIndex, matchIndex + query.length);
                    var after = suggestion.substring(matchIndex + query.length);
                    
                    $item.html(
                        $('<span>').text(before).html() +
                        '<strong>' + $('<span>').text(match).html() + '</strong>' +
                        $('<span>').text(after).html()
                    );
                } else {
                    $item.text(suggestion);
                }
                
                // Click handler
                $item.on('click', function() {
                    self.selectSuggestion(suggestion);
                });
                
                // Hover handler
                $item.on('mouseenter', function() {
                    $('.psm-suggestion-item').removeClass('active');
                    $(this).addClass('active');
                    self.state.currentSuggestionIndex = index;
                });
                
                $container.append($item);
            });
            
            $container.show();
        },
        
        /**
         * Handle Suggestion Navigation (Arrow keys, Enter)
         */
        handleSuggestionNavigation: function(e) {
            var $suggestions = $('.psm-suggestion-item');
            var totalSuggestions = $suggestions.length;
            
            if (totalSuggestions === 0) return;
            
            switch(e.keyCode) {
                case 38: // Up arrow
                    e.preventDefault();
                    this.state.currentSuggestionIndex--;
                    if (this.state.currentSuggestionIndex < 0) {
                        this.state.currentSuggestionIndex = totalSuggestions - 1;
                    }
                    this.highlightSuggestion();
                    break;
                    
                case 40: // Down arrow
                    e.preventDefault();
                    this.state.currentSuggestionIndex++;
                    if (this.state.currentSuggestionIndex >= totalSuggestions) {
                        this.state.currentSuggestionIndex = 0;
                    }
                    this.highlightSuggestion();
                    break;
                    
                case 13: // Enter
                    e.preventDefault();
                    if (this.state.currentSuggestionIndex >= 0) {
                        var suggestion = this.state.suggestions[this.state.currentSuggestionIndex];
                        this.selectSuggestion(suggestion);
                    }
                    break;
                    
                case 27: // Escape
                    e.preventDefault();
                    $('#psm-suggestions').hide();
                    break;
            }
        },
        
        /**
         * Highlight Suggestion
         */
        highlightSuggestion: function() {
            $('.psm-suggestion-item').removeClass('active');
            $('.psm-suggestion-item').eq(this.state.currentSuggestionIndex).addClass('active');
        },
        
        /**
         * Select Suggestion
         */
        selectSuggestion: function(suggestion) {
            $('#psm_focus_keyphrase').val(suggestion);
            $('#psm-suggestions').hide();
            this.updateSnippetPreview();
        },
        
        /**
         * Update Snippet Preview
         */
        updateSnippetPreview: function() {
            var title = $('#psm_seo_title').val() || '';
            var description = $('#psm_meta_description').val() || '';
            
            // Process title variables
            var postTitle = this.getPostTitle();
            var siteName = this.getSiteName();
            var separator = '-'; // Default separator
            
            title = title.replace(/%%title%%/g, postTitle);
            title = title.replace(/%%sitename%%/g, siteName);
            title = title.replace(/%%sep%%/g, separator);
            
            // If description is empty, use excerpt
            if (!description) {
                description = this.getPostExcerpt();
            }
            
            // Update preview
            $('.psm-snippet-title').text(title || postTitle);
            $('.psm-snippet-description').text(description || 'Add a meta description to see how your page will appear in search results.');
        },
        
        /**
         * Get Post Title
         */
        getPostTitle: function() {
            // Try Gutenberg first
            if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                var editor = wp.data.select('core/editor');
                if (editor && editor.getEditedPostAttribute) {
                    var title = editor.getEditedPostAttribute('title');
                    if (title) return title;
                }
            }
            
            // Try Classic Editor
            var $titleField = $('#title');
            if ($titleField.length) {
                return $titleField.val();
            }
            
            return 'Your Page Title';
        },
        
        /**
         * Get Site Name
         */
        getSiteName: function() {
            // This should be passed from PHP, but for now use a default
            return $('title').text().split('-').pop().trim() || 'Your Site';
        },
        
        /**
         * Get Post Excerpt
         */
        getPostExcerpt: function() {
            var content = this.getPostContent();
            if (!content) return '';
            
            // Strip HTML and get first 160 characters
            var text = $('<div>').html(content).text();
            return text.substring(0, 160).trim() + (text.length > 160 ? '...' : '');
        },
        
        /**
         * Get Post Content
         */
        getPostContent: function() {
            // Try Gutenberg first
            if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                var editor = wp.data.select('core/editor');
                if (editor && editor.getEditedPostContent) {
                    return editor.getEditedPostContent();
                }
            }
            
            // Try Classic Editor
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                return tinymce.activeEditor.getContent();
            }
            
            // Fallback to textarea
            var $content = $('#content');
            if ($content.length) {
                return $content.val();
            }
            
            return '';
        },
        
        /**
         * Update Character Counts
         */
        updateCharacterCounts: function() {
            var title = $('#psm_seo_title').val() || '';
            var description = $('#psm_meta_description').val() || '';
            
            // Process title to get actual length
            var postTitle = this.getPostTitle();
            var siteName = this.getSiteName();
            var processedTitle = title.replace(/%%title%%/g, postTitle)
                                     .replace(/%%sitename%%/g, siteName)
                                     .replace(/%%sep%%/g, '-');
            
            var titleLength = processedTitle.length;
            var descLength = description.length;
            
            // Title length indicator
            var titleStatus = '';
            var titleClass = '';
            if (titleLength === 0) {
                titleStatus = 'No title set';
                titleClass = 'psm-count-bad';
            } else if (titleLength < 30) {
                titleStatus = titleLength + ' characters (too short)';
                titleClass = 'psm-count-bad';
            } else if (titleLength > 60) {
                titleStatus = titleLength + ' characters (too long)';
                titleClass = 'psm-count-warning';
            } else {
                titleStatus = titleLength + ' characters (good)';
                titleClass = 'psm-count-good';
            }
            
            $('.psm-title-length').text(titleStatus).attr('class', 'psm-title-length ' + titleClass);
            
            // Description length indicator
            var descStatus = '';
            var descClass = '';
            if (descLength === 0) {
                descStatus = 'No description';
                descClass = 'psm-count-bad';
            } else if (descLength < 120) {
                descStatus = descLength + ' characters (too short)';
                descClass = 'psm-count-bad';
            } else if (descLength > 160) {
                descStatus = descLength + ' characters (too long)';
                descClass = 'psm-count-warning';
            } else {
                descStatus = descLength + ' characters (good)';
                descClass = 'psm-count-good';
            }
            
            $('.psm-desc-length').text(descStatus).attr('class', 'psm-desc-length ' + descClass);
        },
        
        /**
         * Analyze Content
         */
        analyzeContent: function() {
            var self = this;
            var $button = $('#psm-analyze-btn');
            
            // Get content
            var content = this.getPostContent();
            var title = $('#psm_seo_title').val() || this.getPostTitle();
            var keyphrase = $('#psm_focus_keyphrase').val();
            var description = $('#psm_meta_description').val();
            
            // Disable button
            $button.prop('disabled', true).text('Analyzing...');
            
            $.ajax({
                url: psmData.ajax_url,
                type: 'POST',
                data: {
                    action: 'psm_analyze_content',
                    nonce: psmData.nonce,
                    post_id: psmData.post_id,
                    content: content,
                    title: title,
                    keyphrase: keyphrase,
                    description: description,
                },
                success: function(response) {
                    if (response.success) {
                        self.displayAnalysis(response.data);
                    }
                    $button.prop('disabled', false).text('Analyze Content');
                },
                error: function() {
                    alert('Analysis failed. Please try again.');
                    $button.prop('disabled', false).text('Analyze Content');
                }
            });
        },
        
        /**
         * Display Analysis Results
         */
        displayAnalysis: function(data) {
            // SEO Analysis
            var seoScore = data.seo.score;
            var seoRating = data.seo.rating;
            var seoChecks = data.seo.checks;
            
            var seoIndicatorClass = 'psm-score-' + seoRating;
            var seoText = 'SEO Score: ' + seoScore + '%';
            
            $('#psm-seo-analysis .psm-score-indicator')
                .attr('class', 'psm-score-indicator ' + seoIndicatorClass);
            $('#psm-seo-analysis .psm-score span:last-child').text(seoText);
            
            var $seoChecks = $('#psm-seo-checks');
            $seoChecks.empty();
            
            $.each(seoChecks, function(index, check) {
                var $li = $('<li class="psm-check-' + check.status + '"></li>');
                $li.append('<span class="psm-check-bullet"></span>');
                $li.append('<span>' + $('<div>').text(check.text).html() + '</span>');
                $seoChecks.append($li);
            });
            
            // Readability Analysis
            var readScore = data.readability.score;
            var readRating = data.readability.rating;
            var readChecks = data.readability.checks;
            
            var readIndicatorClass = 'psm-score-' + readRating;
            var readText = 'Readability Score: ' + readScore + '%';
            
            $('#psm-readability-analysis .psm-score-indicator')
                .attr('class', 'psm-score-indicator ' + readIndicatorClass);
            $('#psm-readability-analysis .psm-score span:last-child').text(readText);
            
            var $readChecks = $('#psm-readability-checks');
            $readChecks.empty();
            
            $.each(readChecks, function(index, check) {
                var $li = $('<li class="psm-check-' + check.status + '"></li>');
                $li.append('<span class="psm-check-bullet"></span>');
                $li.append('<span>' + $('<div>').text(check.text).html() + '</span>');
                $readChecks.append($li);
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PSM.init();
    });
    
})(jQuery);
