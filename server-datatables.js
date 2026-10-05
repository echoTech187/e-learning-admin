/**
 * Reusable Server-Side DataTable Initialization
 * 
 * @param {string} tableId - The ID of the table element (e.g., '#myTable')
 * @param {string} ajaxUrl - The URL for the AJAX request
 * @param {array} columns - Array of column definitions
 * @param {object} additionalOptions - Any additional DataTables options to override defaults
 */
function initServerDataTable(tableId, ajaxUrl, columns, additionalOptions = {}) {
    var mobileLazyState = {
        isAppending: false,
        isLoading: false,
        accumulatedNodes: []
    };

    var defaultOptions = {
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": ajaxUrl,
            "type": "POST",
            "timeout": 10000,
            "data": function (d) {
                // Automatically append CSRF token if present
                var csrfName = $('meta[name="csrf-token-name"]').attr('content');
                var csrfHash = $('meta[name="csrf-token-hash"]').attr('content');
                if (csrfName && csrfHash) {
                    d[csrfName] = csrfHash;
                }
            },
            "error": function (xhr, error, thrown) {
                console.error("DataTables Error:", error, thrown);
                // Remove custom processing state
                var target = $(tableId).closest('.table-responsive');
                if (!target.length) target = $(".table-responsive").first();
                target.removeClass("dt-processing-active");
                
                // Hide DataTables built-in processing indicator
                $(tableId + '_processing').hide();
                
                // Show empty data state manually without triggering .draw() (which causes infinite loop in serverSide mode)
                if ($.fn.DataTable.isDataTable(tableId)) {
                    var api = $(tableId).DataTable();
                    var colspan = api.columns(':visible').count();
                    var emptyStateHtml = '<div class="py-5 text-center"><div class="mb-3"><svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><circle cx="9" cy="13" r="1.5" fill="currentColor" stroke="none"></circle><circle cx="15" cy="13" r="1.5" fill="currentColor" stroke="none"></circle><path d="M11 16h2" stroke="currentColor" stroke-width="1.5"></path></svg></div><div style="font-weight: 700; font-size: 16px;" class="mb-2">Nothing to display yet</div><p class="text-muted small mx-auto mb-0" style="max-width: 300px; font-size: 13px;">As information is registered, it will be displayed here.</p></div>';
                    $(api.table().body()).html('<tr class="dt-empty-row"><td colspan="' + colspan + '">' + emptyStateHtml + '</td></tr>');
                    // Reset pagination info
                    var $info = $(api.table().container()).find('.dt-footer-info');
                    if ($info.length) $info.html('Showing <strong>0</strong> of <strong>0</strong> results');
                    // Reset pager with disabled prev/next buttons
                    var $pager = $(api.table().container()).find('.dt-footer-pager');
                    if ($pager.length) {
                        $pager.html('<div class="dt-pager-nav">' + 
                            '<button class="dt-pager-btn dt-prev-btn" disabled><i class="fas fa-chevron-left mr-md-2"></i><span class="dt-pager-btn-txt">Previous</span></button>' +
                            '<button class="dt-pager-btn dt-next-btn" disabled><span class="dt-pager-btn-txt">Next</span><i class="fas fa-chevron-right ml-md-2"></i></button>' +
                        '</div>');
                    }
                }
            }
        },
        "columns": columns,
        "ordering": false,
        "language": {
            "processing": '<div class="dt-loader-inner"><div class="spinner-border text-primary" role="status"></div><div class="small font-weight-bold ml-2">PROCESSING DATA...</div></div>',
            "info": "Showing _START_ – _END_ of _TOTAL_ results",
            "infoEmpty": "No results to show",
            "infoFiltered": "",
            "zeroRecords": '<div class="py-5 text-center"><div class="mb-3"><svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><circle cx="9" cy="13" r="1.5" fill="currentColor" stroke="none"></circle><circle cx="15" cy="13" r="1.5" fill="currentColor" stroke="none"></circle><path d="M11 16h2" stroke="currentColor" stroke-width="1.5"></path></svg></div><div style="font-weight: 700; font-size: 16px;" class="mb-2">Nothing to display yet</div><p class="text-muted small mx-auto mb-0" style="max-width: 300px; font-size: 13px;">As information is registered, it will be displayed here.</p></div>'
        },
        "dom": 'rt<"dt-footer"<"dt-footer-info"i><"dt-footer-pager">>',
        "createdRow": function(row, data, dataIndex) {
            // Automatically add data-label for mobile stacking based on <thead> text
            var $table = $(this);
            var $headers = $table.find('thead th');
            $('td', row).each(function(colIndex) {
                var headerText = $($headers[colIndex]).text().trim();
                $(this).attr('data-label', headerText);
            });
        },
        "drawCallback": function(settings) {
            var api        = this.api();
            var info       = api.page.info();
            var $container = $(api.table().container());
            var $pager     = $container.find('.dt-footer-pager');
            var $info      = $container.find('.dt-footer-info');
            var $tbody     = $(api.table().body());

            var isMobile = window.innerWidth < 992;

            if (isMobile) {
                // Hide desktop pager
                $pager.css('display', 'none');

                var currentNodes = $tbody.children('tr').toArray();
                if (mobileLazyState.isAppending && mobileLazyState.accumulatedNodes.length > 0) {
                    var combinedNodes = mobileLazyState.accumulatedNodes.concat(currentNodes);
                    $tbody.empty().append(combinedNodes);
                    mobileLazyState.accumulatedNodes = combinedNodes;

                    // Instantly restore scroll position using requestAnimationFrame
                    var targetScroll = mobileLazyState.savedScrollTop || 0;
                    if (targetScroll > 0) {
                        requestAnimationFrame(function() {
                            $(window).scrollTop(targetScroll);
                            $('body').scrollTop(targetScroll);
                            $('#content-wrapper').scrollTop(targetScroll);
                        });
                    }
                } else {
                    mobileLazyState.accumulatedNodes = currentNodes;
                    $tbody.css('min-height', '');
                    $container.css('min-height', '');
                }

                // Update min-height to match accumulated content height
                var newHeight = $tbody.outerHeight();
                if (newHeight > 0) {
                    $tbody.css('min-height', newHeight + 'px');
                }

                mobileLazyState.isLoading = false;
                mobileLazyState.isAppending = false;

                // Render / Update Mobile Lazy Footer
                var $lazyFooter = $container.find('.dt-mobile-lazy-footer');
                if (!$lazyFooter.length) {
                    $lazyFooter = $('<div class="dt-mobile-lazy-footer text-center py-3"></div>');
                    $container.find('.dt-footer').append($lazyFooter);
                }
                $lazyFooter.css('display', 'block');

                var total = info.recordsDisplay;
                if (total === 0) {
                    $lazyFooter.html('<span class="text-muted small font-weight-bold">No results found</span>');
                    if ($info.length) $info.html('Showing <strong>0</strong> of <strong>0</strong> results');
                } else {
                    var countLoaded = mobileLazyState.accumulatedNodes.length;
                    if ($info.length) {
                        $info.html('Showing <strong>1 – ' + countLoaded + '</strong> of <strong>' + number_format(total) + '</strong> results');
                    }
                    if (info.page < info.pages - 1) {
                        $lazyFooter.html('<span class="text-muted small"><i class="fas fa-circle-notch fa-spin text-primary mr-2"></i> Scroll to load more (' + countLoaded + ' of ' + number_format(total) + ')</span>');
                    } else {
                        $lazyFooter.html('<span class="text-muted small font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;"><i class="fas fa-check-circle text-success mr-1"></i> All ' + number_format(total) + ' items loaded</span>');
                    }
                }
            } else {
                // Desktop / Notebook Mode (>= 992px)
                $pager.css('display', '');
                $container.find('.dt-mobile-lazy-footer').css('display', 'none');
                mobileLazyState.accumulatedNodes = [];
                mobileLazyState.isAppending = false;
                mobileLazyState.isLoading = false;

                var currPage   = info.page + 1;
                var totalPages = info.pages || 1;

                // Render Info text with better formatting
                if ($info.length) {
                    var total = info.recordsDisplay;
                    if (total === 0) {
                        $info.html('Showing <strong>0</strong> of <strong>0</strong> results');
                    } else {
                        var start = info.start + 1;
                        var end = info.end;
                        $info.html('Showing <strong>' + start + ' – ' + end + '</strong> of <strong>' + number_format(total) + '</strong> results');
                    }
                }
                var total = info.recordsDisplay;
                // Render Pager
                var pagerHtml = '<div class="dt-pager-nav">';
                
                // Previous Button
                pagerHtml += '<button class="dt-pager-btn dt-prev-btn" ' + (info.page === 0 ? 'disabled' : '') + '>';
                pagerHtml += '<i class="fas fa-chevron-left mr-md-2"></i><span class="dt-pager-btn-txt">Previous</span>';
                pagerHtml += '</button>';
                if (total > 0) {
                    // Page Numbers
                    pagerHtml += '<ul class="dt-pager-numbers d-none d-md-flex">';
                    pagerHtml += generatePagerNumbers(info);
                    pagerHtml += '</ul>';
                }

                // Next Button
                pagerHtml += '<button class="dt-pager-btn dt-next-btn" ' + (info.page >= totalPages - 1 ? 'disabled' : '') + '>';
                pagerHtml += '<span class="dt-pager-btn-txt">Next</span><i class="fas fa-chevron-right ml-md-2"></i>';
                pagerHtml += '</button>';

                pagerHtml += '</div>';

                $pager.html(pagerHtml);

                // Bind Events
                $pager.find('.dt-prev-btn').off('click').on('click', function() {
                    if (!$(this).prop('disabled')) { api.page('previous').draw('page'); }
                });
                $pager.find('.dt-next-btn').off('click').on('click', function() {
                    if (!$(this).prop('disabled')) { api.page('next').draw('page'); }
                });
                $pager.find('.dt-pager-link').off('click').on('click', function() {
                    var page = $(this).data('page');
                    if (page !== undefined) { api.page(page).draw('page'); }
                });
            }
        }
    };

    /**
     * Internal Helper: Generate numeric page links with ellipses
     */
    function generatePagerNumbers(info) {
        var current = info.page;
        var last = info.pages - 1;
        var delta = 1; // Number of pages back and forth from current
        var left = current - delta;
        var right = current + delta + 1;
        var range = [];
        var rangeWithDots = [];
        var l;

        if (info.pages <= 1) return '<li class="dt-pager-item"><button class="dt-pager-link active">1</button></li>';

        for (var i = 0; i <= last; i++) {
            if (i == 0 || i == last || (i >= left && i < right)) {
                range.push(i);
            }
        }

        for (var i of range) {
            if (l !== undefined) {
                if (i - l === 2) {
                    rangeWithDots.push(l + 1);
                } else if (i - l !== 1) {
                    rangeWithDots.push('...');
                }
            }
            rangeWithDots.push(i);
            l = i;
        }

        var html = '';
        for (var i of rangeWithDots) {
            if (i === '...') {
                html += '<li class="dt-pager-ellipsis">...</li>';
            } else {
                html += '<li class="dt-pager-item">';
                html += '<button class="dt-pager-link ' + (i === current ? 'active' : '') + '" data-page="' + i + '">' + (i + 1) + '</button>';
                html += '</li>';
            }
        }
        return html;
    }

    // Merge default options with any additional options provided
    var finalOptions = $.extend(true, {}, defaultOptions, additionalOptions);
    var table = $(tableId).DataTable(finalOptions);
    var target = $(tableId).closest('.table-responsive');
    if (!target.length) target = $(".table-responsive").first();

    // Show loading state on init AND on every subsequent request (search, paginate)
    target.addClass("dt-processing-active");
    table.on('preXhr.dt', function(e, settings, data) {
        if (!mobileLazyState.isAppending) {
            target.addClass("dt-processing-active");
            mobileLazyState.accumulatedNodes = [];
        }
        if (data && data.search && data.search.value) {
            data.search.value = data.search.value.trim();
        }
    });
    table.on('xhr.dt', function (e, settings, json) {
        target.removeClass("dt-processing-active");
    });

    function triggerNextPage() {
        if (window.innerWidth >= 992) return;
        if (!$.fn.DataTable.isDataTable(tableId)) return;
        var info = table.page.info();
        if (info.page < info.pages - 1 && !mobileLazyState.isLoading) {
            mobileLazyState.isAppending = true;
            mobileLazyState.isLoading = true;

            // Record exact scroll position BEFORE triggering page draw
            var currentScroll = $(window).scrollTop() || $('body').scrollTop() || $('#content-wrapper').scrollTop() || 0;
            mobileLazyState.savedScrollTop = currentScroll;

            // Lock min-height of table body to prevent DOM collapse during fetch
            var $tbody = $(table.table().body());
            var currentHeight = $tbody.outerHeight();
            if (currentHeight > 0) {
                $tbody.css('min-height', currentHeight + 'px');
            }
            
            var $lazyFooter = $(table.table().container()).find('.dt-mobile-lazy-footer');
            if ($lazyFooter.length) {
                $lazyFooter.html('<span class="text-muted small font-weight-bold"><i class="fas fa-circle-notch fa-spin text-primary mr-2"></i> Loading next page...</span>');
            }
            table.page('next').draw('page');
        }
    }

    // Direct Tap / Click Event on Mobile Lazy Footer
    $(document).off('click.dtLazy_' + cleanTableId, '.dt-mobile-lazy-footer').on('click.dtLazy_' + cleanTableId, '.dt-mobile-lazy-footer', function(e) {
        e.preventDefault();
        triggerNextPage();
    });

    // Window & Container Scroll Listener for Mobile Infinite Scroll
    var cleanTableId = tableId.replace(/[^a-zA-Z0-9]/g, '');
    $(window).add('body').add('#content-wrapper').off('scroll.dtLazy_' + cleanTableId).on('scroll.dtLazy_' + cleanTableId, debounce(function() {
        if (window.innerWidth >= 992) return;
        if (!$.fn.DataTable.isDataTable(tableId)) return;

        var info = table.page.info();
        if (info.page < info.pages - 1 && !mobileLazyState.isLoading) {
            var winScroll = $(window).scrollTop() || $('body').scrollTop() || $('#content-wrapper').scrollTop() || 0;
            var docHeight = Math.max($(document).height(), $('body').height(), $('#content-wrapper').prop('scrollHeight') || 0);
            var winHeight = $(window).height();

            if (winScroll + winHeight >= docHeight - 350) {
                triggerNextPage();
            }
        }
    }, 100));
    
    // Prevent default DataTables error alert and render inline empty state error
    $.fn.dataTable.ext.errMode = 'none';
    table.on('error.dt', function (e, settings, techNote, message) {
        target.removeClass("dt-processing-active");
        if ($.fn.DataTable.isDataTable(tableId)) {
            var api = $(tableId).DataTable();
            var colspan = api.columns(':visible').count();
            var errorStateHtml = '<div class="py-5 text-center"><div class="mb-3"><svg width="100" height="100" viewBox="0 0 24 24" fill="#f8fafc" stroke="#ef4444" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div><h5 style="color: #ef4444; font-weight: 700; font-size: 16px;" class="mb-2">Gangguan Koneksi</h5><p class="text-muted small mx-auto mb-0" style="max-width: 300px; color: #64748b !important; font-size: 13px;">Sistem terlalu lama merespons atau server sedang sibuk. Silakan coba lagi.</p></div>';
            $(api.table().body()).html('<tr class="dt-empty-row"><td colspan="' + colspan + '">' + errorStateHtml + '</td></tr>');
            
            // Reset pagination info
            var $info = $(api.table().container()).find('.dt-footer-info');
            if ($info.length) $info.html('Showing <strong>0</strong> of <strong>0</strong> results');
            // Reset pager
            var $pager = $(api.table().container()).find('.dt-footer-pager');
            if ($pager.length) {
                $pager.html('<div class="dt-pager-nav">' + 
                    '<button class="dt-pager-btn dt-prev-btn" disabled><i class="fas fa-chevron-left mr-md-2"></i><span class="dt-pager-btn-txt">Previous</span></button>' +
                    '<button class="dt-pager-btn dt-next-btn" disabled><span class="dt-pager-btn-txt">Next</span><i class="fas fa-chevron-right ml-md-2"></i></button>' +
                '</div>');
            }
        }
    });

    // Initialize and return the DataTable instance
    return table;
}

/**
 * JS Number Format Helper
 */
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + (Math.round(n * k) / k).toFixed(prec);
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

/**
 * Debounce Helper - Limits the rate at which a function can fire.
 */
function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * Global CSRF Injection for Legacy Forms
 */
$(document).ready(function () {
    var csrfName = $('meta[name="csrf-token-name"]').attr('content');
    var csrfHash = $('meta[name="csrf-token-hash"]').attr('content');

    if (csrfName && csrfHash) {
        // Inject CSRF into all POST forms that don't already have it
        $('form[method="post"]').each(function () {
            if ($(this).find('input[name="' + csrfName + '"]').length === 0) {
                $(this).append('<input type="hidden" name="' + csrfName + '" value="' + csrfHash + '">');
            }
        });
    }
});

/**
 * Global optimized search handling
 * Prevents full page reload when Enter is pressed in search inputs
 */
$(document).on('keypress', '.dt-search-input', function(e) {
    if (e.which == 13) { // Enter key
        e.preventDefault();
        // Trigger blur to ensure any pending changes are committed if needed
        $(this).blur().focus(); 
    }
});

/**
 * Global AJAX CSRF Injection
 * Ensures all jQuery AJAX POST requests include the security token required by CI3.
 */
$(document).ajaxSend(function(event, jqXHR, settings) {
    if (settings.type.toUpperCase() === 'POST') {
        var csrfName = $('meta[name="csrf-token-name"]').attr('content');
        var csrfHash = $('meta[name="csrf-token-hash"]').attr('content');
        
        if (csrfName && csrfHash) {
            // Handle string data (serialized)
            if (typeof settings.data === 'string') {
                if (settings.data.indexOf(csrfName + '=') === -1) {
                    settings.data += (settings.data ? '&' : '') + csrfName + '=' + csrfHash;
                }
            } 
            // Handle object data
            else if (typeof settings.data === 'object' && !(settings.data instanceof FormData)) {
                settings.data[csrfName] = csrfHash;
            }
        }
    }
});

/**
 * Global AJAX Error Handling for Session Timeouts and Permissions
 * Catches 401 Unauthorized and 403 Forbidden responses across all AJAX calls
 */
$(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
    if (jqXHR.status === 401 || jqXHR.status === 403) {
        try {
            var response = JSON.parse(jqXHR.responseText);
            if (response.redirect) {
                window.location.href = response.redirect;
            }
        } catch (e) {
            // Fallback if parsing fails
            window.location.href = '/auth/logout';
        }
    }
});


/**
 * Global Clear Button for dt-search-input
 * Automatically injects an 'X' button to clear search input, remove URL params, and trigger datatables.
 */
$(document).ready(function() {
    // Inject the clear button next to every dt-search-input
    $('.dt-search-input').each(function() {
        var $input = $(this);
        var $wrapper = $input.closest('.dt-search-wrapper');
        
        // Only inject if wrapper exists and it doesn't already have a clear button
        if ($wrapper.length && $wrapper.find('.dt-search-clear-icon').length === 0) {
            $wrapper.addClass('position-relative');
            var isVisible = $input.val().length > 0 ? 'block' : 'none';
            var $clearBtn = $('<span class="dt-search-clear-icon" style="display: ' + isVisible + '; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; z-index: 1050; font-size: 20px; line-height: 1; padding: 5px;">&times;</span>');
            $input.after($clearBtn);
        }
    });

    // Handle input event to show/hide the clear button
    $(document).on('input', '.dt-search-input', function() {
        var $input = $(this);
        var $wrapper = $input.closest('.dt-search-wrapper');
        var $clearBtn = $wrapper.find('.dt-search-clear-icon');
        
        if ($clearBtn.length) {
            if ($input.val().length > 0) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
                // Remove URL params when input is emptied manually
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            }
        }
    });

    // Handle clear button click
    $(document).on('click', '.dt-search-clear-icon', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.dt-search-wrapper');
        var $input = $wrapper.find('.dt-search-input');
        
        $input.val('');
        $btn.hide();
        
        // Remove URL params without reloading the page
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
        
        // Trigger input event to let debounce functions (if any) catch it and trigger search
        $input.trigger('input');
    });
});
