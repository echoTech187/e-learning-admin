<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>

<style>
    /* ==================== TRANSAKSI PAGE STYLES ==================== */
    .filter-tab {
          padding: 8px 18px;
          border-radius: 99px;
          font-size: 0.85rem;
          font-weight: 600;
          border: 1px solid #e2e8f0;
          background: #ffffff;
          color: #475569;
          text-decoration: none;
          transition: all 0.2s ease;
      }
      .filter-tab:hover {
          background: #f8fafc;
          color: #334155;
          border-color: #cbd5e1;
      }
      .filter-tab.active {
          background: #4f46e5;
          color: #ffffff;
          border-color: #4f46e5;
          box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
      }

    /* Toolbar Buttons */
    .toolbar-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: #64748B;
        border: none; background: transparent;
        cursor: pointer;
        transition: all 0.18s ease;
        position: relative;
        white-space: nowrap;
    }
    .toolbar-btn:hover { background: #F8FAFC; color: #374151; }
    .toolbar-btn.active { color: #4F46E5; font-weight: 600; }

    /* Search Input */
    .dt-search-wrapper { position: relative; }
    .dt-search-wrapper .fa-search { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 13px; z-index: 2; pointer-events: none; }
    .dt-search-input { padding-left: 36px !important; border-radius: 8px !important; background: #F8FAFC !important; padding-left: 36px !important; background: #fff; border: 1px solid #E2E8F0 !important; font-size: 13px !important; box-shadow: none !important; height: 38px; background: #F8FAFC; }
    .dt-search-input:focus { background: #fff !important; border-color: #6366F1 !important; box-shadow: 0 0 0 3px rgba(99,102,241,0.1) !important; }

    /* Dropdown Panels */
    .toolbar-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 280px;
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.10);
        z-index: 1050;
        padding: 16px;
        animation: dropdownIn 0.15s ease;
    }
    .toolbar-dropdown.show { display: block; }
    .sort-dropdown { width: 200px; }
    .more-dropdown { width: 180px; padding: 8px 0; }
    @keyframes dropdownIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .dropdown-label { font-size: 11px; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; }
    .filter-row { margin-bottom: 12px; }
    .filter-row label { font-size: 12px; font-weight: 500; color: #475569; margin-bottom: 5px; display: block; }
    .filter-row select, .filter-row input { width: 100%; padding: 7px 10px; border: 1px solid #E2E8F0; border-radius: 7px; font-size: 13px; color: #374151; outline: none; }
    .filter-row select:focus, .filter-row input:focus { border-color: #6366F1; box-shadow: 0 0 0 2px rgba(99,102,241,0.1); }
    .filter-actions { display: flex; gap: 8px; margin-top: 14px; }
    .filter-apply-btn { flex: 1; padding: 8px; border-radius: 8px; background: linear-gradient(135deg, #6366F1, #8B5CF6); color: #fff; border: none; font-size: 13px; font-weight: 600; cursor: pointer; }
    .filter-reset-btn { padding: 8px 12px; border-radius: 8px; background: #F1F5F9; color: #64748B; border: none; font-size: 13px; font-weight: 500; cursor: pointer; }
    .sort-option { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #374151; transition: background 0.15s; }
    .sort-option:hover { background: #F1F5F9; }
    .sort-option.selected { background: #EEF2FF; color: #4F46E5; font-weight: 600; }
    .sort-option i { width: 16px; text-align: center; color: #94A3B8; }
    .sort-option.selected i { color: #6366F1; }
    .more-option { display: flex; align-items: center; gap: 10px; padding: 10px 16px; cursor: pointer; font-size: 13px; color: #374151; transition: background 0.15s; }
    .more-option:hover { background: #F8FAFC; }
    .more-option i { width: 16px; text-align: center; color: #64748B; }
    .more-option.danger { color: #EF4444; }
    .more-option.danger i { color: #EF4444; }

    /* Bulk Action Bar */
    .bulk-action-bar {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 10px 20px;
        background: #EEF2FF;
        border-bottom: 1px solid #C7D2FE;
        font-size: 13px;
        font-weight: 500;
        color: #4338CA;
    }
    .bulk-action-bar.show { display: flex; }
    .bulk-action-bar .bulk-count { font-weight: 700; }
    .bulk-action-bar .bulk-btn { padding: 6px 14px; border-radius: 7px; border: 1px solid #C7D2FE; background: #fff; color: #4338CA; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
    .bulk-action-bar .bulk-btn:hover { background: #4338CA; color: #fff; }
    .bulk-action-bar .bulk-btn.danger { border-color: #FECACA; color: #DC2626; }
    .bulk-action-bar .bulk-btn.danger:hover { background: #DC2626; color: #fff; }

    /* Table */
    .dataTables_wrapper { padding: 0 !important; }
    table.dataTable { border-collapse: collapse !important; width: 100% !important; margin: 0 !important; }
    table.dataTable thead th, table.dataTable thead td { border-bottom: none !important; border-top: none !important; background-image: none !important; }
    table.dataTable tbody td { border-top: none !important; }
    table.dataTable.no-footer { border-bottom: none !important; }
    table.dataTable tbody tr.odd  { background-color: transparent !important; }
    table.dataTable tbody tr.even { background-color: transparent !important; }
    .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { display: none !important; }

    #transaksiTable thead th { background: #fff !important; font-size: 12.5px !important; font-weight: 600 !important; color: #64748B !important; text-transform: capitalize !important; letter-spacing: 0 !important; padding: 12px 16px !important; border-bottom: 1px solid #E2E8F0 !important;  white-space: nowrap; }
    #transaksiTable thead th:last-child { border-right: none !important; }
    #transaksiTable thead th:first-child { border-right: none !important; }
    #transaksiTable tbody td { padding: 14px 16px !important; border-bottom: 1px solid #F1F5F9 !important; vertical-align: middle !important; font-size: 13.5px !important; }
    #transaksiTable tbody tr:hover td { background: #F8FAFC !important; }
    #transaksiTable tbody tr:last-child td { border-bottom: none !important; }

    /* Checkbox */
    

    /* Status Badges */
    .s-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 600; }
    .s-paid    { background: #DCFCE7; color: #15803D; }
    .s-pending { background: #FEF9C3; color: #A16207; }
    .s-failed  { background: #FEE2E2; color: #B91C1C; }

    /* Action Buttons */
    .btn-approve {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 14px; border-radius: 8px;
        background: linear-gradient(135deg, #6366F1, #8B5CF6);
        color: #fff; border: none; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.18s;
    }
    .btn-approve:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-locked { padding: 6px 10px; border-radius: 8px; border: 1px solid #E2E8F0; background: #F8FAFC; color: #CBD5E1; font-size: 12px; cursor: not-allowed; }

    /* Library Footer */
    .dt-footer { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid #F1F5F9; flex-wrap: wrap; gap: 10px; }
    .dt-footer-info { font-size: 13px; color: #64748B; }
    .dt-footer-info strong { color: #1E293B; font-weight: 700; }
    .dt-footer-pager .dt-pager-nav { display: flex; align-items: center; gap: 4px; }
    .dt-pager-btn { min-width: 32px; height: 32px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 7px; border: none; background: transparent; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
    .dt-pager-btn:hover:not(:disabled):not(.dt-pager-active) { background: #F1F5F9; }
    .dt-pager-btn.dt-pager-active { background: #6366F1; border-color: #6366F1; color: #fff; font-weight: 700; }
    .dt-pager-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .dt-pager-btn-txt { font-size: 12px; }
    .dt-pager-numbers { list-style: none; padding: 0; margin: 0; display: flex; gap: 4px; align-items: center; }
    .dt-pager-item { display: inline-block; }
    .dt-pager-link { min-width: 32px; height: 32px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; border-radius: 7px; border: none; background: transparent; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; }
    .dt-pager-link:hover { background: #F1F5F9; }
    .dt-pager-link.active { background: #6366F1; color: #fff; font-weight: 700; }
    .dt-pager-ellipsis { padding: 0 8px; color: #94A3B8; font-weight: 500; }
    .btn-new-ticket {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        background: linear-gradient(135deg, #8B5CF6, #6366F1);
        color: #fff;
        border: none;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-new-ticket:hover { opacity: 0.9; }
    .agy-checkbox { width: 16px; height: 16px; accent-color: #6366F1; cursor: pointer; border-radius: 4px; }
    /* Dribbble Badge Overrides */
    .badge.bg-warning { background-color: #FEF3C7 !important; color: #92400E !important; font-weight: 600; padding: 6px 12px; border-radius: 6px; }
    .badge.bg-success { background-color: #DCFCE7 !important; color: #16A34A !important; font-weight: 600; padding: 6px 12px; border-radius: 6px; }
    .badge.bg-danger  { background-color: #FEE2E2 !important; color: #DC2626 !important; font-weight: 600; padding: 6px 12px; border-radius: 6px; }
    
    /* Table Header Text Color */
    table thead th { color: #64748B !important; font-weight: 600 !important; font-size: 13px !important; background: #F8FAFC !important; text-transform: capitalize !important; }
    
    /* Table Body Text */
    table tbody td { color: #334155; font-size: 13px; }
    
    /* Button Override Removed */
    /* === DRIBBBLE EDITABLE TABLE GRID (.agy-table) === */
    .agy-table { width: 100%; border-collapse: collapse !important; border-spacing: 0; font-family: 'Inter', 'Plus Jakarta Sans', sans-serif; }
    
    .agy-table thead { background-color: #F8FAFC; }
    .agy-table th { 
        padding: 12px 16px; font-weight: 600; font-size: 13px; color: #64748B; 
        border: 1px solid #E2E8F0 !important; text-align: left; 
    }
    .agy-table th i { margin-right: 6px; font-size: 13px; color: #94A3B8; }
    
    .agy-table td { 
        padding: 12px 16px; vertical-align: middle; 
        border: 1px solid #E2E8F0 !important; color: #334155; font-size: 13px; 
        background: #FFFFFF; transition: box-shadow 0.1s, background-color 0.1s;
    }
    
    /* Hover Outline effect simulating active editable cell */
    .agy-table tbody tr:hover td {
        background: #FAFAFA !important;
    }
    .agy-table tbody td:hover {
        box-shadow: inset 0 0 0 2px #7C3AED;
        z-index: 10;
        position: relative;
    }
</style>

<link rel="stylesheet" href="/css/jquery.dataTables.min.css">

<div class="container-fluid p-0">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-size:28px;font-weight:800;color:#0F172A;margin-bottom:4px;letter-spacing:-.5px;">Tickets &amp; Transactions</h1>
            <p style="font-size:13.5px;color:#64748B;margin:0;">Manage manual payments and verify student enrollments.</p>
        </div>
        <div>
            <button class="btn-new-ticket">
                <i class="fas fa-plus" style="font-size:12px;"></i> New Ticket <span style="border-left: 1px solid rgba(255,255,255,0.3); margin-left: 8px; padding-left: 8px;"><i class="fas fa-chevron-down" style="font-size:11px;"></i></span>
            </button>
        </div>
    </div>

    <div class="agy-card p-0" style="border-radius:14px; border: 1px solid #E2E8F0; box-shadow: none;">
        <!-- Tab Filter + Toolbar -->
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center" style="background:#fff; position: relative; z-index: 100;">
            <div style="display:inline-flex; gap:8px;">
                <a href="/transaksi"                class="filter-tab <?= empty($currentStatus) ? 'active' : '' ?>">Semua (<?= $counts['all'] ?? 0 ?>)</a>
                <a href="/transaksi?status=paid"    class="filter-tab <?= $currentStatus === 'paid'    ? 'active' : '' ?>">Berhasil (<?= $counts['paid'] ?? 0 ?>)</a>
                <a href="/transaksi?status=pending" class="filter-tab <?= $currentStatus === 'pending' ? 'active' : '' ?>">Menunggu Pembayaran (<?= $counts['pending'] ?? 0 ?>)</a>
                <a href="/transaksi?status=failed"  class="filter-tab <?= $currentStatus === 'failed'  ? 'active' : '' ?>">Gagal / Lainnya (<?= $counts['failed'] ?? 0 ?>)</a>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <!-- Search -->
                <div class="dt-search-wrapper" style="width:240px;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control dt-search-input" id="dtSearchInput" placeholder="Search transactions...">
                </div>
                <!-- Filter Button -->
                <div style="position:relative;">
                    <button class="toolbar-btn" id="btnFilterUi">
                        <i class="fas fa-filter"></i> Filter
                        <span id="filterActiveDot" style="display:none;width:7px;height:7px;background:#6366F1;border-radius:50%;margin-left:2px;"></span>
                    </button>
                    <div id="filterDropdown" class="toolbar-dropdown" style="right:0;">
                        <div class="dropdown-label">Filter Transaksi</div>
                        <div class="filter-row">
                            <label>Status Pembayaran</label>
                            <select id="filterStatus">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label>Dari Tanggal</label>
                            <input type="date" id="filterDateFrom">
                        </div>
                        <div class="filter-row">
                            <label>Sampai Tanggal</label>
                            <input type="date" id="filterDateTo">
                        </div>
                        <div class="filter-actions">
                            <button class="filter-reset-btn" id="btnFilterReset">Reset</button>
                            <button class="filter-apply-btn" id="btnFilterApply">Terapkan</button>
                        </div>
                    </div>
                </div>
                <!-- Sort Button -->
                <div style="position:relative;">
                    <button class="toolbar-btn" id="btnSortUi">
                        <i class="fas fa-sort"></i> Sort
                    </button>
                    <div id="sortDropdown" class="toolbar-dropdown sort-dropdown" style="right:0;">
                        <div class="dropdown-label">Urutkan Berdasarkan</div>
                        <div class="sort-option selected" data-col="1" data-dir="desc"><i class="far fa-calendar-alt"></i> Tanggal (Terbaru)</div>
                        <div class="sort-option" data-col="1" data-dir="asc"><i class="far fa-calendar"></i> Tanggal (Terlama)</div>
                        <div class="sort-option" data-col="2" data-dir="asc"><i class="fas fa-sort-alpha-down"></i> Nama A-Z</div>
                        <div class="sort-option" data-col="2" data-dir="desc"><i class="fas fa-sort-alpha-up"></i> Nama Z-A</div>
                        <div class="sort-option" data-col="4" data-dir="desc"><i class="fas fa-sort-amount-down"></i> Harga (Tertinggi)</div>
                        <div class="sort-option" data-col="4" data-dir="asc"><i class="fas fa-sort-amount-up"></i> Harga (Terendah)</div>
                    </div>
                </div>
                <!-- More Button -->
                <div style="position:relative;">
                    <button class="toolbar-btn" id="btnMoreUi" style="padding:8px 12px;">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div id="moreDropdown" class="toolbar-dropdown more-dropdown" style="right:0;">
                        <div class="more-option" id="btnExportCSV"><i class="fas fa-file-csv"></i> Export CSV</div>
                        <div class="more-option" id="btnCopyTable"><i class="fas fa-copy"></i> Copy Tabel</div>
                        <div style="height:1px;background:#F1F5F9;margin:4px 0;"></div>
                        <div class="more-option danger" id="btnBulkDelete"><i class="fas fa-trash"></i> Hapus Terpilih</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Action Bar -->
        <div class="bulk-action-bar" id="bulkActionBar">
            <i class="fas fa-check-square"></i>
            <span><span class="bulk-count" id="bulkCount">0</span> baris dipilih</span>
            <button class="bulk-btn" id="bulkApproveBtn"><i class="fas fa-check style="margin-right: 8px;""></i> Approve</button>
            <button class="bulk-btn danger" id="bulkDeselectBtn"><i class="fas fa-times style="margin-right: 8px;""></i> Batalkan Pilihan</button>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="transaksiTable" class="agy-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center;padding:12px 16px;" style="border-right: 1px solid #E2E8F0;">
                            <input type="checkbox" class="agy-checkbox" id="selectAllCb" title="Pilih Semua">
                        </th>
                        <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-calendar style="margin-right: 8px;""></i> Date</th>
                        <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-user style="margin-right: 8px;""></i> Student</th>
                        <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-folder style="margin-right: 8px;""></i> Course</th>
                        <th style="border-right: 1px solid #E2E8F0;"><i class="fas fa-dollar-sign style="margin-right: 8px;""></i> Amount</th>
                        <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-check-square style="margin-right: 8px;""></i> Status</th>
                        <th style="border-right: 1px solid #E2E8F0;"><i class="fas fa-cog style="margin-right: 8px;""></i> Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/js/jquery.min.js"></script>
<script src="/js/jquery.dataTables.min.js"></script>
<script src="/js/server-datatables.js"></script>

<script>
$(document).ready(function() {
    var currentStatus = '<?= esc($currentStatus) ?>';
    var activeFilters = { status: currentStatus, date_from: '', date_to: '' };
    var dtInstance    = null;

    // ==================== DATATABLE INIT ====================
    var columns = [
        { data: 'checkbox',         name: 'checkbox',         orderable: false, searchable: false },
        { data: 'date_formatted',   name: 'orders.created_at' },
        { data: 'student_html',     name: 'users.name' },
        { data: 'course_html',      name: 'courses.title' },
        { data: 'amount_formatted', name: 'orders.amount' },
        { data: 'status_badge',     name: 'orders.status' },
        { data: 'actions',          name: 'actions',          orderable: false, searchable: false }
    ];

    dtInstance = initServerDataTable('#transaksiTable', '/transaksi/get-data', columns, {
        order: [[1, 'desc']],
        ajax: {
            url: '/transaksi/get-data',
            type: 'POST',
            data: function(d) {
                d.status_filter = activeFilters.status;
                d.date_from     = activeFilters.date_from;
                d.date_to       = activeFilters.date_to;
                var csrfName = $('meta[name="csrf-token-name"]').attr('content');
                var csrfHash = $('meta[name="csrf-token-hash"]').attr('content');
                if (csrfName && csrfHash) d[csrfName] = csrfHash;
            }
        }
    });

    // ==================== DROPDOWN TOGGLE ====================
    function closeAllDropdowns() {
        $('#filterDropdown, #sortDropdown, #moreDropdown').removeClass('show');
        $('#btnFilterUi, #btnSortUi, #btnMoreUi').removeClass('active');
    }

    $('#btnFilterUi').on('click', function(e) {
        e.stopPropagation();
        var isOpen = $('#filterDropdown').hasClass('show');
        closeAllDropdowns();
        if (!isOpen) { $('#filterDropdown').addClass('show'); $(this).addClass('active'); }
    });
    $('#btnSortUi').on('click', function(e) {
        e.stopPropagation();
        var isOpen = $('#sortDropdown').hasClass('show');
        closeAllDropdowns();
        if (!isOpen) { $('#sortDropdown').addClass('show'); $(this).addClass('active'); }
    });
    $('#btnMoreUi').on('click', function(e) {
        e.stopPropagation();
        var isOpen = $('#moreDropdown').hasClass('show');
        closeAllDropdowns();
        if (!isOpen) { $('#moreDropdown').addClass('show'); $(this).addClass('active'); }
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#btnFilterUi, #filterDropdown, #btnSortUi, #sortDropdown, #btnMoreUi, #moreDropdown').length) {
            closeAllDropdowns();
        }
    });
    $('#filterDropdown, #sortDropdown, #moreDropdown').on('click', function(e) { e.stopPropagation(); });

    // ==================== FILTER ====================
    $('#btnFilterApply').on('click', function() {
        activeFilters.status    = $('#filterStatus').val();
        activeFilters.date_from = $('#filterDateFrom').val();
        activeFilters.date_to   = $('#filterDateTo').val();
        var hasFilter = activeFilters.status || activeFilters.date_from || activeFilters.date_to;
        $('#filterActiveDot').toggle(!!hasFilter);
        $('#btnFilterUi').toggleClass('active', !!hasFilter);
        closeAllDropdowns();
        dtInstance.ajax.reload();
        AgyToast.success('Filter berhasil diterapkan!');
    });
    $('#btnFilterReset').on('click', function() {
        $('#filterStatus').val(''); $('#filterDateFrom').val(''); $('#filterDateTo').val('');
        activeFilters = { status: currentStatus, date_from: '', date_to: '' };
        $('#filterActiveDot').hide();
        closeAllDropdowns();
        dtInstance.ajax.reload();
        AgyToast.info('Filter direset.');
    });

    // ==================== SORT ====================
    $(document).on('click', '#sortDropdown .sort-option', function() {
        var col = parseInt($(this).data('col'));
        var dir = $(this).data('dir');
        $('#sortDropdown .sort-option').removeClass('selected');
        $(this).addClass('selected');
        dtInstance.order([[col, dir]]).draw();
        closeAllDropdowns();
    });

    // ==================== SEARCH ====================
    var searchTimeout;
    $('#dtSearchInput').on('input', function() {
        var val = $(this).val();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() { dtInstance.search(val).draw(); }, 400);
    });

    // ==================== CHECKBOXES ====================
    $(document).on('change', '#selectAllCb', function() {
        $('#transaksiTable tbody .agy-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkBar();
    });
    $(document).on('change', '#transaksiTable tbody .agy-checkbox', function() {
        var total   = $('#transaksiTable tbody .agy-checkbox').length;
        var checked = $('#transaksiTable tbody .agy-checkbox:checked').length;
        $('#selectAllCb').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAllCb').prop('checked', total > 0 && checked === total);
        updateBulkBar();
    });
    $('#transaksiTable').on('draw.dt', function() {
        $('#selectAllCb').prop('checked', false).prop('indeterminate', false);
        updateBulkBar();
    });
    function updateBulkBar() {
        var count = $('#transaksiTable tbody .agy-checkbox:checked').length;
        $('#bulkCount').text(count);
        $('#bulkActionBar').toggleClass('show', count > 0);
    }
    $('#bulkDeselectBtn').on('click', function() {
        $('#selectAllCb').prop('checked', false).prop('indeterminate', false);
        $('#transaksiTable tbody .agy-checkbox').prop('checked', false);
        updateBulkBar();
    });

    // ==================== MORE ACTIONS ====================
    $('#btnExportCSV').on('click', function() { AgyToast.info('Mempersiapkan export CSV...'); closeAllDropdowns(); });
    $('#btnCopyTable').on('click', function() { AgyToast.info('Fitur copy tabel segera hadir.'); closeAllDropdowns(); });
    $('#btnBulkDelete').on('click', function() {
        var ids = [];
        $('#transaksiTable tbody .agy-checkbox:checked').each(function() { ids.push($(this).val()); });
        if (!ids.length) { AgyToast.warning('Tidak ada baris yang dipilih.'); return; }
        AgyToast.warning('Hapus ' + ids.length + ' transaksi - fitur segera hadir.');
        closeAllDropdowns();
    });
});

// ==================== APPROVE ====================
async function viewDetail(id) {
    AgyToast.info('Mengambil detail transaksi...');

    fetch(`/transaksi/detail/${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const order = res.data;
                const statusBadge = order.status === 'paid' ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span>' : (order.status === 'pending' ? '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>' : '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> ' + order.status + '</span>');
                
                const html = `
                    <div style="text-align: left; font-size: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                            <div>
                                <div style="font-weight: 700; font-size: 18px; color: #0F172A;">Invoice #${order.id.substring(0,8)}</div>
                                <div style="color: #64748B; font-size: 13px;">Tgl Transaksi: ${order.created_at}</div>
                            </div>
                            <div>
                                ${statusBadge}
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                            <div>
                                <div style="font-size: 12px; color: #64748B; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Informasi Siswa</div>
                                <div style="font-weight: 600; color: #1E293B;">${order.user_name || '-'}</div>
                                <div style="color: #475569;">${order.user_email || '-'}</div>
                                <div style="color: #64748B; font-size: 12px;">Akun: ${order.user_role || '-'}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; color: #64748B; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Pembayaran</div>
                                <div style="color: #475569;">Metode: <b>${(order.payment_method || 'Otomatis').replace('_', ' ')}</b></div>
                                <div style="color: #475569;">Tgl Bayar: ${order.payment_date || '-'}</div>
                            </div>
                        </div>

                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #E2E8F0; padding-bottom: 8px; margin-bottom: 8px;">
                                <span style="font-weight: 600; font-size: 12px; color: #64748B; text-transform: uppercase;">Item Kursus</span>
                                <span style="font-weight: 600; font-size: 12px; color: #64748B; text-transform: uppercase;">Harga</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-weight: 600; color: #1E293B;">${order.course_title || '-'}</div>
                                <div style="font-weight: 600; color: #1E293B;">Rp ${new Intl.NumberFormat('id-ID').format(order.amount)}</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end;">
                            <div style="width: 250px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: #64748B;">Subtotal</span>
                                    <span style="font-weight: 500; color: #1E293B;">Rp ${new Intl.NumberFormat('id-ID').format(order.amount)}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed #CBD5E1; margin-top: 4px;">
                                    <span style="font-weight: 700; color: #0F172A; font-size: 16px;">Total</span>
                                    <span style="font-weight: 700; color: #4F46E5; font-size: 16px;">Rp ${new Intl.NumberFormat('id-ID').format(order.amount)}</span>
                                </div>
                            </div>
                        </div>
                        
                        ${(order.midtrans_request || order.midtrans_response) ? `
                        <div style="margin-top: 24px; border-top: 1px solid #E2E8F0; padding-top: 16px;">
                            <div style="font-weight: 600; font-size: 14px; color: #334155; margin-bottom: 12px;"><i class="fas fa-bug" style="margin-right: 6px;"></i>System Audit Logs</div>
                            <div class="accordion accordion-flush" id="auditAccordion">
                              ${order.midtrans_request ? `
                              <div class="accordion-item" style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; margin-bottom: 8px;">
                                <h2 class="accordion-header" id="headingReq">
                                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReq" aria-expanded="false" aria-controls="collapseReq" style="padding: 10px 16px; background: #F8FAFC; font-size: 13px; font-weight: 600; color: #475569;">
                                    Midtrans Request Payload
                                  </button>
                                </h2>
                                <div id="collapseReq" class="accordion-collapse collapse" aria-labelledby="headingReq" data-bs-parent="#auditAccordion">
                                  <div class="accordion-body" style="padding: 0;">
                                    <pre style="margin: 0; padding: 16px; font-size: 11px; background: #1E293B; color: #A5B4FC; border-radius: 0; max-height: 250px; overflow-y: auto;"><code>${JSON.stringify(JSON.parse(order.midtrans_request), null, 2)}</code></pre>
                                  </div>
                                </div>
                              </div>
                              ` : ''}
                              ${order.midtrans_response ? `
                              <div class="accordion-item" style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden;">
                                <h2 class="accordion-header" id="headingRes">
                                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRes" aria-expanded="false" aria-controls="collapseRes" style="padding: 10px 16px; background: #F8FAFC; font-size: 13px; font-weight: 600; color: #475569;">
                                    Midtrans Response Payload
                                  </button>
                                </h2>
                                <div id="collapseRes" class="accordion-collapse collapse" aria-labelledby="headingRes" data-bs-parent="#auditAccordion">
                                  <div class="accordion-body" style="padding: 0;">
                                    <pre style="margin: 0; padding: 16px; font-size: 11px; background: #1E293B; color: #A5B4FC; border-radius: 0; max-height: 250px; overflow-y: auto;"><code>${JSON.stringify(JSON.parse(order.midtrans_response), null, 2)}</code></pre>
                                  </div>
                                </div>
                              </div>
                              ` : ''}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;

                let modalEl = document.getElementById('invoiceModal');
                if (!modalEl) {
                    modalEl = document.createElement('div');
                    modalEl.id = 'invoiceModal';
                    modalEl.className = 'modal fade';
                    modalEl.tabIndex = -1;
                    modalEl.innerHTML = `
                      <div class="modal-dialog modal-dialog-centered modal-md">
                        <div class="modal-content border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                          <div class="modal-header border-0 bg-light" style="border-bottom: 1px solid #E2E8F0 !important;">
                            <h5 class="modal-title fw-bold" style="font-size: 16px;"><i class="fas fa-file-invoice me-2" style="color: #4F46E5;"></i>Detail Transaksi</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body" id="invoiceModalBody" style="padding: 24px;">
                          </div>
                        </div>
                      </div>
                    `;
                    document.body.appendChild(modalEl);
                }
                
                document.getElementById('invoiceModalBody').innerHTML = html;
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                
            } else {
                AgyToast.error(res.message);
            }
        })
        .catch(err => {
            AgyToast.error('Gagal mengambil data detail');
        });
}
async function approveOrder(id) {
    const confirmed = await AgyConfirm.show(
        'Verifikasi Pembayaran?',
        'Setelah disetujui, murid akan otomatis terdaftar di dalam kelas. Tindakan ini tidak bisa dibatalkan.',
        'info', 'Ya, Approve'
    );
    if (!confirmed) return;
    try {
        const formData = new FormData();
        formData.append('order_id', id);
        const res    = await fetch('/transaksi/approve', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.status === 'success') {
            AgyToast.success(result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            AgyToast.error(result.message);
        }
    } catch (e) {
        AgyToast.error('Network error. Failed to communicate with the server.');
    }
}
</script>

<?= $this->endSection() ?>