<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>

<style>
    /* ==================== LAPORAN PAGE STYLES ==================== */
    .metric-card {
        background: #ffffff; border: 1px solid #E2E8F0; border-radius: 12px;
        padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .metric-title { font-size: 14px; color: #64748B; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .metric-value { font-size: 28px; font-weight: 700; color: #0F172A; }
    .metric-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px; }
    .icon-gross { background: #E0E7FF; color: #4F46E5; }
    .icon-platform { background: #DCFCE7; color: #16A34A; }
    .icon-mentor { background: #FEF3C7; color: #D97706; }
    
    .btn-gradient { background: linear-gradient(135deg, #4F46E5 0%, #3B82F6 100%); color: white; border: none; padding: 10px 20px; border-radius: 100px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-gradient:hover { background: linear-gradient(135deg, #4338CA 0%, #2563EB 100%); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4); transform: translateY(-1px); color: white; }
    .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    
    /* === DRIBBBLE EDITABLE TABLE GRID (.agy-table) === */
    .agy-table { width: 100%; border-collapse: collapse !important; border-spacing: 0; font-family: 'Inter', 'Plus Jakarta Sans', sans-serif; }
    .agy-table thead { background-color: #F8FAFC; }
    .agy-table th { padding: 12px 16px; font-weight: 600 !important; font-size: 13px !important; color: #64748B !important; border: 1px solid #E2E8F0 !important; text-align: left; text-transform: capitalize !important; }
    .agy-table th i { margin-right: 6px; font-size: 13px; color: #94A3B8; }
    .agy-table td { padding: 12px 16px; vertical-align: middle; border: 1px solid #E2E8F0 !important; color: #334155; font-size: 13px; background: #FFFFFF; transition: box-shadow 0.1s, background-color 0.1s; }
    .agy-table tbody tr:hover td { background: #FAFAFA !important; }
    .agy-table tbody td:hover { box-shadow: inset 0 0 0 2px #7C3AED; z-index: 10; position: relative; }

    /* Library Footer */
    .dt-footer { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid #F1F5F9; flex-wrap: wrap; gap: 10px; }
    .dt-footer-info { font-size: 13px; color: #64748B; }
    .dt-footer-info strong { color: #1E293B; font-weight: 700; }
    .dt-footer-pager .dt-pager-nav { display: flex; align-items: center; gap: 4px; }
    .dt-pager-btn, .dt-pager-link { min-width: 32px; height: 32px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 7px; border: none; background: transparent; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
    .dt-pager-btn:hover:not(:disabled):not(.dt-pager-active), .dt-pager-link:hover { background: #F1F5F9; }
    .dt-pager-btn.dt-pager-active, .dt-pager-link.active { background: #6366F1; border-color: #6366F1; color: #fff; font-weight: 700; }
    .dt-pager-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .dt-pager-numbers { list-style: none; padding: 0; margin: 0; display: flex; gap: 4px; align-items: center; }
    .dt-pager-item { display: inline-block; }
    .dt-pager-ellipsis { padding: 0 8px; color: #94A3B8; font-weight: 500; }
    
    /* Search Wrapper */
    .dt-search-wrapper { position: relative; }
    .dt-search-wrapper i.fa-search { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 13px; pointer-events: none; }
    .dt-search-input { padding-left: 36px !important; padding-right: 32px !important; height: 36px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px; box-shadow: none !important; width: 100%; }
    .dt-search-input:focus { border-color: #6366F1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.1) !important; }
    .dt-search-clear-icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 13px; cursor: pointer; }
    .dt-search-clear-icon:hover { color: #475569; }
</style>

<link rel="stylesheet" href="/css/jquery.dataTables.min.css">

<div class="container-fluid p-0">
    <div class="card-header-flex mt-4 px-4">
        <div>
            <h1 style="font-size:28px;font-weight:800;color:#0F172A;margin-bottom:4px;letter-spacing:-.5px;">Laporan & Keuangan</h1>
            <p style="font-size:13.5px;color:#64748B;margin:0;">Laporan penjualan, bagi hasil, dan riwayat transaksi sukses.</p>
        </div>
        <a href="<?= base_url('report/export') ?>" class="btn-gradient">
            <i class="fas fa-file-export"></i> Ekspor CSV
        </a>
    </div>

    <!-- Metrics Row -->
    <div class="row mt-4 px-4">
        <div class="col-md-4 mb-4">
            <div class="metric-card">
                <div class="metric-icon icon-gross"><i class="fas fa-wallet"></i></div>
                <div class="metric-title">Total Penerimaan Kotor</div>
                <div class="metric-value">Rp <?= number_format($earningsData['total_gross'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="metric-card">
                <div class="metric-icon icon-platform"><i class="fas fa-building"></i></div>
                <div class="metric-title">Komisi Platform (30%)</div>
                <div class="metric-value">Rp <?= number_format($earningsData['total_platform_fee'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="metric-card">
                <div class="metric-icon icon-mentor"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="metric-title">Hak Mentor (70%)</div>
                <div class="metric-value">Rp <?= number_format($earningsData['total_net_mentor'], 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <!-- Table Row -->
    <div class="px-4">
        <div class="card border-0" style="border-radius:14px; border: 1px solid #E2E8F0; box-shadow: none;">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center" style="background:#fff;">
                <h5 class="font-weight-bold m-0" style="color: #0F172A;">Rekapitulasi Transaksi Berhasil</h5>
                <div class="dt-search-wrapper" style="width:240px;">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control dt-search-input" id="dtSearchInput" placeholder="Cari laporan...">
                </div>
            </div>
            
            <div class="table-responsive" style="overflow-x: auto;">
                <table id="laporanTable" class="agy-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="border-right: 1px solid #E2E8F0;"><i class="fas fa-hashtag"></i> Kode Pesanan</th>
                            <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-calendar"></i> Tanggal Pembayaran</th>
                            <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-folder"></i> Kursus</th>
                            <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-user"></i> Siswa</th>
                            <th style="border-right: 1px solid #E2E8F0;"><i class="fas fa-dollar-sign"></i> Total Tagihan</th>
                            <th style="border-right: 1px solid #E2E8F0;"><i class="far fa-credit-card"></i> Metode</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="/js/jquery.min.js"></script>
<script src="/js/jquery.dataTables.min.js"></script>
<script src="/js/server-datatables.js"></script>

<script>
$(document).ready(function() {
    var columns = [
        { data: 'col_kode', name: 'orders.order_code' },
        { data: 'col_tanggal', name: 'orders.payment_date' },
        { data: 'col_kursus', name: 'courses.title' },
        { data: 'col_siswa', name: 'users.name' },
        { data: 'col_total', name: 'orders.total' },
        { data: 'col_metode', name: 'orders.payment_method' }
    ];

    var dtInstance = initServerDataTable('#laporanTable', '/report/get-data', columns, {
        order: [[1, 'desc']],
        ajax: {
            url: '/report/get-data',
            type: 'POST',
            data: function (d) {
                // Add CSRF Token
                var csrfName = $('meta[name="csrf-token-name"]').attr('content');
                var csrfHash = $('meta[name="csrf-token-hash"]').attr('content');
                if (csrfName) {
                    d[csrfName] = csrfHash;
                }
            }
        }
    });

    $('#dtSearchInput').on('input', debounce(function() {
        dtInstance.search(this.value).draw();
    }, 400));
});
</script>

<?= $this->endSection() ?>


