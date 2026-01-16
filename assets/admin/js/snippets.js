/**
 * WP Slatan Theme - Snippets Admin JavaScript
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Snippets Admin namespace
    window.WPSLTSnippets = window.WPSLTSnippets || {};

    /**
     * Initialize CodeMirror editor with linting
     */
    WPSLTSnippets.initEditor = function () {
        var $textarea = $('#snippet_code');
        if (!$textarea.length) {
            return;
        }

        var mode = $textarea.data('mode') || 'css';
        var $scope = $('#snippet_scope');

        /**
         * Get CodeMirror mode from scope value
         */
        function getModeFromScope(scope) {
            if (scope.indexOf('-css') !== -1) {
                return 'css';
            } else if (scope.indexOf('-js') !== -1) {
                return 'javascript';
            } else if (scope.indexOf('php-') !== -1) {
                return 'php';
            }
            return 'css';
        }

        /**
         * Basic PHP linter for CodeMirror
         * Checks for common syntax errors without server-side validation
         */
        function basicPHPLinter(text) {
            var errors = [];
            var lines = text.split('\n');
            var braceStack = [];
            var parenStack = [];
            var bracketStack = [];
            var inString = false;
            var stringChar = '';
            var inComment = false;
            var inMultiComment = false;

            lines.forEach(function (line, lineNum) {
                var trimmed = line.trim();

                // Skip empty lines and pure comments
                if (!trimmed || trimmed.startsWith('//')) {
                    return;
                }

                // Track multi-line comments
                if (trimmed.indexOf('/*') !== -1) {
                    inMultiComment = true;
                }
                if (trimmed.indexOf('*/') !== -1) {
                    inMultiComment = false;
                    return;
                }
                if (inMultiComment) {
                    return;
                }

                // Check for unmatched brackets
                for (var i = 0; i < line.length; i++) {
                    var char = line[i];
                    var prevChar = i > 0 ? line[i - 1] : '';

                    // Handle strings
                    if ((char === '"' || char === "'") && prevChar !== '\\') {
                        if (!inString) {
                            inString = true;
                            stringChar = char;
                        } else if (char === stringChar) {
                            inString = false;
                            stringChar = '';
                        }
                    }

                    if (inString) continue;

                    // Track brackets
                    if (char === '{') braceStack.push({ line: lineNum, col: i });
                    if (char === '}') {
                        if (braceStack.length === 0) {
                            errors.push({
                                message: 'Unexpected closing brace }',
                                severity: 'error',
                                from: CodeMirror.Pos(lineNum, i),
                                to: CodeMirror.Pos(lineNum, i + 1)
                            });
                        } else {
                            braceStack.pop();
                        }
                    }

                    if (char === '(') parenStack.push({ line: lineNum, col: i });
                    if (char === ')') {
                        if (parenStack.length === 0) {
                            errors.push({
                                message: 'Unexpected closing parenthesis )',
                                severity: 'error',
                                from: CodeMirror.Pos(lineNum, i),
                                to: CodeMirror.Pos(lineNum, i + 1)
                            });
                        } else {
                            parenStack.pop();
                        }
                    }

                    if (char === '[') bracketStack.push({ line: lineNum, col: i });
                    if (char === ']') {
                        if (bracketStack.length === 0) {
                            errors.push({
                                message: 'Unexpected closing bracket ]',
                                severity: 'error',
                                from: CodeMirror.Pos(lineNum, i),
                                to: CodeMirror.Pos(lineNum, i + 1)
                            });
                        } else {
                            bracketStack.pop();
                        }
                    }
                }

                // Check for missing semicolons (basic check)
                if (trimmed.length > 0 &&
                    !trimmed.endsWith('{') &&
                    !trimmed.endsWith('}') &&
                    !trimmed.endsWith(':') &&
                    !trimmed.endsWith(',') &&
                    !trimmed.endsWith(';') &&
                    !trimmed.endsWith('(') &&
                    !trimmed.startsWith('if') &&
                    !trimmed.startsWith('else') &&
                    !trimmed.startsWith('foreach') &&
                    !trimmed.startsWith('for') &&
                    !trimmed.startsWith('while') &&
                    !trimmed.startsWith('function') &&
                    !trimmed.startsWith('class') &&
                    !trimmed.startsWith('//') &&
                    !trimmed.startsWith('/*') &&
                    !trimmed.startsWith('*') &&
                    trimmed.indexOf('function') === -1 &&
                    trimmed.indexOf('=>') === -1) {
                    // This might be a missing semicolon
                    errors.push({
                        message: 'Possible missing semicolon',
                        severity: 'warning',
                        from: CodeMirror.Pos(lineNum, line.length - 1),
                        to: CodeMirror.Pos(lineNum, line.length)
                    });
                }
            });

            // Report unclosed brackets at end
            braceStack.forEach(function (item) {
                errors.push({
                    message: 'Unclosed brace {',
                    severity: 'error',
                    from: CodeMirror.Pos(item.line, item.col),
                    to: CodeMirror.Pos(item.line, item.col + 1)
                });
            });
            parenStack.forEach(function (item) {
                errors.push({
                    message: 'Unclosed parenthesis (',
                    severity: 'error',
                    from: CodeMirror.Pos(item.line, item.col),
                    to: CodeMirror.Pos(item.line, item.col + 1)
                });
            });
            bracketStack.forEach(function (item) {
                errors.push({
                    message: 'Unclosed bracket [',
                    severity: 'error',
                    from: CodeMirror.Pos(item.line, item.col),
                    to: CodeMirror.Pos(item.line, item.col + 1)
                });
            });

            return errors;
        }

        // Register PHP linter with CodeMirror
        if (typeof CodeMirror !== 'undefined') {
            CodeMirror.registerHelper('lint', 'php', function (text) {
                return basicPHPLinter(text);
            });
        }

        /**
         * Check if linting should be enabled for this mode
         */
        function shouldEnableLinting(mode) {
            // All modes now support linting (PHP has basic linter)
            return true;
        }

        // Initialize CodeMirror with linting
        if (typeof wp !== 'undefined' && wp.codeEditor) {
            var editorSettings = wp.codeEditor.defaultSettings ?
                _.clone(wp.codeEditor.defaultSettings) : {};

            var currentMode = getModeFromScope($scope.val() || mode);

            editorSettings.codemirror = _.extend(
                {},
                editorSettings.codemirror,
                {
                    mode: currentMode === 'php' ? 'application/x-httpd-php' : currentMode,
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 2,
                    tabSize: 2,
                    gutters: ['CodeMirror-lint-markers'],
                    lint: shouldEnableLinting(currentMode)
                }
            );

            var editor = wp.codeEditor.initialize($textarea, editorSettings);
            WPSLTSnippets.editor = editor;

            // Update mode and linting when scope changes
            $scope.on('change', function () {
                var newScope = $(this).val();
                var newMode = getModeFromScope(newScope);
                var cmMode = newMode === 'php' ? 'application/x-httpd-php' : newMode;

                editor.codemirror.setOption('mode', cmMode);

                // Re-enable linting for new mode (except PHP)
                editor.codemirror.setOption('lint', false);
                if (shouldEnableLinting(newMode)) {
                    setTimeout(function () {
                        editor.codemirror.setOption('lint', true);
                    }, 100);
                }
            });
        }
    };

    /**
     * Initialize scope selector enhancements
     */
    WPSLTSnippets.initScopeSelector = function () {
        var $scope = $('#snippet_scope');
        if (!$scope.length) {
            return;
        }

        // Add visual indicator when scope changes
        $scope.on('change', function () {
            var scope = $(this).val();
            var isPHP = scope.indexOf('php-') !== -1;
            var isJS = scope.indexOf('-js') !== -1;

            // Update code label
            var codeLabel;
            if (isPHP) {
                codeLabel = wpsltSnippets.i18n?.phpLabel || 'PHP Code';
            } else if (isJS) {
                codeLabel = wpsltSnippets.i18n?.jsLabel || 'JavaScript Code';
            } else {
                codeLabel = wpsltSnippets.i18n?.cssLabel || 'CSS Code';
            }

            $('label[for="snippet_code"]').text(codeLabel);
        });
    };

    /**
     * Initialize delete confirmation
     */
    WPSLTSnippets.initDeleteConfirm = function () {
        $('.submitdelete').on('click', function (e) {
            if (!confirm(wpsltSnippets.i18n?.deleteConfirm || 'Are you sure?')) {
                e.preventDefault();
                return false;
            }
        });
    };

    /**
     * Initialize bulk actions functionality
     */
    WPSLTSnippets.initBulkActions = function () {
        var $selectAll = $('#cb-select-all');
        var $checkboxes = $('.snippet-checkbox');
        var $countDisplay = $('.wpslt-selected-count');
        var $countNumber = $countDisplay.find('strong');
        var $form = $('#wpslt-bulk-form');

        if (!$selectAll.length) {
            return;
        }

        // Update selected count display
        function updateSelectedCount() {
            var count = $checkboxes.filter(':checked').length;
            if (count > 0) {
                $countNumber.text(count);
                $countDisplay.show();
            } else {
                $countDisplay.hide();
            }
        }

        // Select all checkbox handler
        $selectAll.on('change', function () {
            var isChecked = $(this).prop('checked');
            $checkboxes.prop('checked', isChecked);
            updateSelectedCount();
        });

        // Individual checkbox handler
        $checkboxes.on('change', function () {
            var total = $checkboxes.length;
            var checked = $checkboxes.filter(':checked').length;

            // Update select all checkbox state
            $selectAll.prop('checked', checked === total);
            $selectAll.prop('indeterminate', checked > 0 && checked < total);

            updateSelectedCount();
        });

        // Confirm bulk delete
        $form.on('submit', function (e) {
            var action = $('#bulk-action-selector').val();
            var checked = $checkboxes.filter(':checked').length;

            if (checked === 0) {
                alert('Please select at least one snippet.');
                e.preventDefault();
                return false;
            }

            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete ' + checked + ' snippet(s)?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    };

    /**
     * Display admin notice
     * 
     * @param {string} message Notice message
     * @param {string} type Notice type: success, error, warning, info
     */
    WPSLTSnippets.displayNotice = function (message, type) {
        type = type || 'success';
        var noticeClass = 'notice-' + type;

        // Remove existing Ajax notices
        $('.wpslt-ajax-notice').remove();

        var $notice = $('<div>')
            .addClass('notice ' + noticeClass + ' is-dismissible wpslt-ajax-notice')
            .html('<p>' + message + '</p>');

        // Add dismiss button
        var $dismissBtn = $('<button>')
            .attr('type', 'button')
            .addClass('notice-dismiss')
            .html('<span class="screen-reader-text">Dismiss this notice.</span>')
            .on('click', function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            });

        $notice.append($dismissBtn);

        // Insert notice at the top of the page
        $('.wpslt-snippets-wrap').prepend($notice);

        // Auto-dismiss success messages after 3 seconds
        if (type === 'success') {
            setTimeout(function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 3000);
        }

        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 50
        }, 300);
    };

    /**
     * Dismiss notice manually
     */
    WPSLTSnippets.dismissNotice = function () {
        $('.wpslt-ajax-notice').fadeOut(function () {
            $(this).remove();
        });
    };

    /**
     * Initialize Ajax save functionality
     */
    WPSLTSnippets.initAjaxSave = function () {
        var $form = $('#wpslt-snippet-form');
        if (!$form.length) {
            return;
        }

        var $submitBtn = $form.find('.button-primary');
        var originalBtnText = $submitBtn.text();

        $form.on('submit', function (e) {
            e.preventDefault();

            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true).text('Saving...');

            // Get CodeMirror value
            var code = '';
            if (WPSLTSnippets.editor && WPSLTSnippets.editor.codemirror) {
                code = WPSLTSnippets.editor.codemirror.getValue();
            } else {
                code = $('#snippet_code').val();
            }

            // Prepare form data
            var formData = {
                action: 'wpslt_save_snippet',
                wpslt_snippet_nonce: $form.find('[name="wpslt_snippet_nonce"]').val(),
                snippet_id: $form.find('[name="snippet_id"]').val() || 0,
                snippet_name: $form.find('[name="snippet_name"]').val(),
                snippet_description: $form.find('[name="snippet_description"]').val(),
                snippet_code: code,
                snippet_scope: $form.find('[name="snippet_scope"]').val(),
                snippet_priority: $form.find('[name="snippet_priority"]').val(),
                snippet_active: $form.find('[name="snippet_active"]').is(':checked') ? 1 : 0
            };

            // Send Ajax request
            $.ajax({
                url: wpsltSnippets.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalBtnText);

                    if (response.success) {
                        var data = response.data;
                        var message = data.message || 'Snippet saved successfully!';

                        // Show success message
                        WPSLTSnippets.displayNotice(message, 'success');

                        // Show warnings if any
                        if (data.warnings && data.warnings.length > 0) {
                            var warningHtml = '<strong>Warnings:</strong><ul style="margin-top:5px;">';
                            data.warnings.forEach(function (warning) {
                                warningHtml += '<li>' + warning + '</li>';
                            });
                            warningHtml += '</ul>';
                            WPSLTSnippets.displayNotice(warningHtml, 'warning');
                        }

                        // Update URL with snippet ID without redirecting (for new snippets)
                        if (data.redirect_url) {
                            history.replaceState(null, '', data.redirect_url);
                            // Update the hidden snippet_id field for subsequent saves
                            $form.find('[name="snippet_id"]').val(data.snippet.id);
                        }

                    } else {
                        // Error response
                        var errorMessage = response.data.message || 'Failed to save snippet.';

                        // Handle collision errors
                        if (response.data.collisions && response.data.collisions.length > 0) {
                            errorMessage += '<ul style="margin-top:5px;">';
                            response.data.collisions.forEach(function (collision) {
                                errorMessage += '<li>' + collision + '</li>';
                            });
                            errorMessage += '</ul>';
                        }

                        WPSLTSnippets.displayNotice(errorMessage, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalBtnText);

                    // Show error message
                    var errorMessage = 'An error occurred while saving the snippet. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }

                    WPSLTSnippets.displayNotice(errorMessage, 'error');
                }
            });
        });
    };

    /**
     * Initialize all functionality
     */
    WPSLTSnippets.init = function () {
        this.initEditor();
        this.initScopeSelector();
        this.initDeleteConfirm();
        this.initBulkActions();
        this.initAjaxSave();
    };

    // Initialize on document ready
    $(document).ready(function () {
        WPSLTSnippets.init();
    });

})(jQuery);
