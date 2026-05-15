/* Chillies SSO AI — Admin JavaScript */
/* global chilliesAdmin, jQuery */

(function ($) {
    'use strict';

    /* ── Utility ───────────────────────────────────────────── */

    function ajax(action, data, success, error) {
        $.ajax({
            url:    chilliesAdmin.ajax_url,
            method: 'POST',
            data:   Object.assign({ action: action, nonce: chilliesAdmin.nonce }, data),
            success: function (res) {
                if (res.success) {
                    success && success(res.data);
                } else {
                    error && error(res.data || 'Request failed.');
                }
            },
            error: function () {
                error && error('Network error.');
            }
        });
    }

    function spinner(btn, state) {
        if (state) {
            btn.data('orig', btn.html())
               .html('<span class="dashicons dashicons-update chillies-spin"></span> Working...')
               .prop('disabled', true);
        } else {
            btn.html(btn.data('orig')).prop('disabled', false);
        }
    }

    function badge(text, type) {
        return '<span class="chillies-status-badge status-' + type + '">' + text + '</span>';
    }

    /* ── Color picker ──────────────────────────────────────── */
    $('.chillies-color-picker').wpColorPicker();

    /* ── Dashboard: refresh news ────────────────────────────── */
    $(document).on('click', '#chillies-refresh-news', function () {
        var btn = $(this);
        spinner(btn, true);
        ajax('chillies_ai_request', { prompt: 'List 5 trending topics in technology and WordPress today. Keep it brief.' },
            function (data) {
                $('#chillies-news-widget').text(data.result);
                spinner(btn, false);
            },
            function (msg) {
                $('#chillies-news-widget').text('Error: ' + msg);
                spinner(btn, false);
            }
        );
    });

    /* ── AI Bug Detection ───────────────────────────────────── */
    $(document).on('click', '#chillies-bug-detect-btn', function () {
        var btn = $(this), out = $('#chillies-bug-detect-result');
        spinner(btn, true);
        out.text('Scanning...').show();
        ajax('chillies_ai_bug_detect', {},
            function (data) {
                out.text(data.report);
                spinner(btn, false);
            },
            function (msg) {
                out.text('Error: ' + msg);
                spinner(btn, false);
            }
        );
    });

    /* ── AI Auto Post ───────────────────────────────────────── */
    $(document).on('click', '#chillies-auto-post-btn', function () {
        var btn    = $(this),
            topic  = $('#chillies-auto-post-topic').val().trim(),
            result = $('#chillies-auto-post-result');
        if (!topic) { result.html('<span style="color:#ef4444">Please enter a topic.</span>'); return; }
        spinner(btn, true);
        result.html('<span class="chillies-status-badge status-checking"><span class="dashicons dashicons-update chillies-spin"></span> Generating post...</span>');
        ajax('chillies_ai_auto_post', { topic: topic },
            function (data) {
                result.html(
                    badge('Draft post created!', 'active') +
                    ' <strong>' + $('<div>').text(data.title).html() + '</strong>' +
                    ' &mdash; <a href="' + data.edit_link + '" target="_blank">Edit Draft</a>'
                );
                spinner(btn, false);
            },
            function (msg) {
                result.html(badge('Error: ' + msg, 'offline'));
                spinner(btn, false);
            }
        );
    });

    /* ── AI CSS ─────────────────────────────────────────────── */
    $(document).on('click', '#chillies-ai-css-btn', function () {
        var btn  = $(this),
            desc = $('#chillies-ai-css-prompt').val().trim(),
            wrap = $('#chillies-ai-css-result');
        if (!desc) return;
        spinner(btn, true);
        ajax('chillies_ai_css', { description: desc },
            function (data) {
                $('#chillies-ai-css-output').val(data.css);
                wrap.show();
                spinner(btn, false);
            },
            function (msg) {
                wrap.html('<span style="color:#ef4444">' + msg + '</span>').show();
                spinner(btn, false);
            }
        );
    });

    $(document).on('click', '#chillies-ai-css-apply', function () {
        var existing = $('textarea[name="custom_css"]').val();
        var newCss   = $('#chillies-ai-css-output').val();
        $('textarea[name="custom_css"]').val(existing + '\n\n/* AI Generated */\n' + newCss);
    });

    /* ── AI Template ────────────────────────────────────────── */
    $(document).on('click', '#chillies-ai-template-btn', function () {
        var btn    = $(this),
            prompt = $('#chillies-ai-template-prompt').val().trim(),
            wrap   = $('#chillies-ai-template-result');
        if (!prompt) return;
        spinner(btn, true);
        ajax('chillies_ai_template', { prompt: prompt },
            function (data) {
                $('#chillies-ai-template-output').val(data.template);
                wrap.show();
                spinner(btn, false);
            },
            function (msg) {
                alert(msg);
                spinner(btn, false);
            }
        );
    });

    /* ── AI Shortcode Generator ─────────────────────────────── */
    $(document).on('click', '#chillies-sc-gen-btn', function () {
        var btn  = $(this),
            desc = $('#chillies-sc-desc').val().trim(),
            wrap = $('#chillies-sc-gen-result');
        if (!desc) return;
        spinner(btn, true);
        ajax('chillies_ai_shortcode_gen', { description: desc },
            function (data) {
                $('#chillies-sc-gen-output').val(data.shortcode);
                wrap.show();
                spinner(btn, false);
            },
            function (msg) {
                alert(msg);
                spinner(btn, false);
            }
        );
    });

    /* ── Skills Manager ─────────────────────────────────────── */
    $(document).on('click', '#chillies-add-skill', function () {
        var btn = $(this);
        spinner(btn, true);
        ajax('chillies_add_skill', {
                name:        $('#skill-name').val(),
                description: $('#skill-desc').val(),
                shortcode:   $('#skill-shortcode').val(),
                token_cost:  $('#skill-token').val() || 'Unlimited'
            },
            function () {
                location.reload();
            },
            function (msg) {
                alert(msg);
                spinner(btn, false);
            }
        );
    });

    $(document).on('click', '.chillies-delete-skill', function () {
        var id  = $(this).data('id'),
            row = $('#skill-row-' + id);
        if (!confirm('Delete this skill?')) return;
        ajax('chillies_delete_skill', { id: id },
            function () { row.remove(); },
            function (msg) { alert(msg); }
        );
    });

    $(document).on('click', '.chillies-toggle-skill', function () {
        var id  = $(this).data('id'),
            btn = $(this);
        spinner(btn, true);
        ajax('chillies_toggle_skill', { id: id },
            function (data) {
                var badge = $('#skill-row-' + id + ' .chillies-status-badge');
                badge.removeClass('status-active status-inactive')
                     .addClass(data.new_status ? 'status-active' : 'status-inactive')
                     .text(data.new_status ? 'Active' : 'Inactive');
                spinner(btn, false);
            },
            function (msg) { alert(msg); spinner(btn, false); }
        );
    });

    $(document).on('click', '#chillies-ai-suggest-skills', function () {
        var btn = $(this);
        spinner(btn, true);
        ajax('chillies_ai_suggest_skills', {},
            function (data) {
                $('#chillies-ai-suggestions-content').text(data.suggestions);
                $('#chillies-ai-suggestions').show();
                spinner(btn, false);
            },
            function (msg) { alert(msg); spinner(btn, false); }
        );
    });

    /* ── Superadmin / Subdomains ─────────────────────────────── */
    $(document).on('click', '#chillies-add-subdomain-btn', function () {
        var domain = $('#chillies-new-subdomain').val().trim();
        if (!domain) return;
        ajax('chillies_add_subdomain', { domain: domain },
            function () { location.reload(); },
            function (msg) { alert(msg); }
        );
    });

    $(document).on('click', '.chillies-remove-subdomain', function () {
        var domain = $(this).data('domain');
        if (!confirm('Remove ' + domain + '?')) return;
        ajax('chillies_remove_subdomain', { domain: domain },
            function () { location.reload(); },
            function (msg) { alert(msg); }
        );
    });

    // Auto-check status for each subdomain row
    $('.sd-status').each(function () {
        var cell   = $(this),
            domain = cell.data('domain');
        ajax('chillies_check_subdomain', { domain: domain },
            function (data) {
                var cls = data.status === 'online' ? 'status-online' : 'status-offline';
                cell.html(badge(data.status.charAt(0).toUpperCase() + data.status.slice(1), cls));
            },
            function () {
                cell.html(badge('Offline', 'status-offline'));
            }
        );
    });

    $(document).on('click', '#chillies-push-all-btn', function () {
        var btn = $(this), result = $('#chillies-push-all-result');
        spinner(btn, true);
        ajax('chillies_push_settings_all', {},
            function (data) {
                var html = '<ul style="margin:0;padding-left:20px;">';
                $.each(data.results, function (domain, r) {
                    html += '<li>' + $('<div>').text(domain).html() + ' — ' +
                            badge(r.success ? 'OK' : 'Failed', r.success ? 'active' : 'offline') + '</li>';
                });
                html += '</ul>';
                result.html(html);
                spinner(btn, false);
            },
            function (msg) { result.html(badge('Error: ' + msg, 'offline')); spinner(btn, false); }
        );
    });

    /* ── Cross-posting ──────────────────────────────────────── */
    $(document).on('click', '#chillies-cp-submit', function () {
        var btn        = $(this),
            post_id    = $('#chillies-cp-post-select').val(),
            api_key    = $('#chillies-cp-apikey').val(),
            subdomains = [],
            result     = $('#chillies-cp-result');

        $('.chillies-cp-subdomain:checked').each(function () {
            subdomains.push($(this).val());
        });
        if (!subdomains.length) {
            result.html(badge('Select at least one subdomain.', 'checking'));
            return;
        }
        spinner(btn, true);
        ajax('chillies_cross_post', { post_id: post_id, subdomains: subdomains, api_key: api_key },
            function (data) {
                var html = '<ul style="margin:0;padding-left:20px;">';
                $.each(data.results, function (sd, r) {
                    html += '<li>' + $('<div>').text(sd).html() + ' — ' +
                            badge(r.success ? 'Success' : 'Failed', r.success ? 'active' : 'offline') +
                            ' <small>' + $('<div>').text(r.message).html() + '</small></li>';
                });
                html += '</ul>';
                result.html(html);
                spinner(btn, false);
            },
            function (msg) { result.html(badge(msg, 'offline')); spinner(btn, false); }
        );
    });

    /* ── API Key Manager ────────────────────────────────────── */
    $(document).on('click', '#chillies-gen-apikey', function () {
        var btn   = $(this),
            label = $('#chillies-apikey-label').val(),
            rate  = $('#chillies-apikey-rate').val();
        spinner(btn, true);
        ajax('chillies_gen_api_key', { label: label, rate_limit: rate },
            function (data) {
                $('#chillies-new-key-value').text(data.key);
                $('#chillies-new-key-display').show();
                spinner(btn, false);
                // Add to table
                $('#chillies-api-keys-table tbody tr:last').before(
                    '<tr><td>' + $('<d>').text(label).html() + '</td>' +
                    '<td><code>' + data.key.substring(0, 12) + '...</code></td>' +
                    '<td>0</td><td>' + rate + '</td><td>Just now</td>' +
                    '<td><button class="chillies-btn-sm chillies-btn-danger chillies-revoke-apikey" data-id="new">' +
                    '<span class="dashicons dashicons-trash"></span> Revoke</button></td></tr>'
                );
            },
            function (msg) { alert(msg); spinner(btn, false); }
        );
    });

    $(document).on('click', '.chillies-revoke-apikey', function () {
        var id  = $(this).data('id'),
            row = $(this).closest('tr');
        if (!confirm('Revoke this API key?')) return;
        ajax('chillies_revoke_api_key', { key_id: id },
            function () { row.remove(); },
            function (msg) { alert(msg); }
        );
    });

    /* ── URL Rewriter: add custom rule row ───────────────────── */
    $(document).on('click', '#chillies-add-url-rule', function () {
        var row = '<tr>' +
                  '<td><input type="text" name="rule_orig[]" placeholder="original-slug"></td>' +
                  '<td><input type="text" name="rule_custom[]" placeholder="new-slug"></td>' +
                  '</tr>';
        $('#chillies-url-rules-table tbody').append(row);
    });

})(jQuery);
