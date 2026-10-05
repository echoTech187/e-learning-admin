<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class LaporanController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Rekapitulasi Penjualan Harian/Bulanan (Total Penjualan 30 Hari Terakhir)
        $salesQuery = $db->query("
            SELECT DATE(payment_date) as date, SUM(total) as daily_sales
            FROM orders
            WHERE status = 'paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(payment_date)
            ORDER BY DATE(payment_date) ASC
        ");
        $salesData = $salesQuery->getResultArray();
        
        // 2. Pembagian Pemasukan (Revenue Split)
        $earningsQuery = $db->query("
            SELECT 
                SUM(gross_amount) as total_gross,
                SUM(platform_fee) as total_platform_fee,
                SUM(net_amount) as total_net_mentor
            FROM mentor_earnings
        ");
        $earningsData = $earningsQuery->getRowArray();
        
        if (empty($earningsData['total_gross'])) {
            $fallbackQuery = $db->query("
                SELECT SUM(total) as total_gross 
                FROM orders 
                WHERE status = 'paid'
            ");
            $fallbackData = $fallbackQuery->getRowArray();
            $totalGross = $fallbackData['total_gross'] ?? 0;
            $earningsData = [
                'total_gross' => $totalGross,
                'total_platform_fee' => $totalGross * 0.30,
                'total_net_mentor' => $totalGross * 0.70
            ];
        }

        // 3. Data Transaksi Terbaru
        $transactionsQuery = $db->query("
            SELECT o.*, c.title as course_title, u.name as user_name
            FROM orders o
            JOIN courses c ON c.id = o.course_id
            JOIN users u ON u.id = o.user_id
            WHERE o.status = 'paid'
            ORDER BY o.payment_date DESC
            LIMIT 100
        ");
        $transactions = $transactionsQuery->getResultArray();

        $data = [
            'title' => 'Laporan Penjualan & Keuangan',
            'salesData' => $salesData,
            'earningsData' => $earningsData,
            'transactions' => $transactions,
            'uri' => 'report'
        ];
        
        return view('admin/laporan', $data);
    }

    public function export()
    {
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT o.order_code, o.payment_date, c.title as course_title, u.name as user_name, o.total, o.payment_method
            FROM orders o
            JOIN courses c ON c.id = o.course_id
            JOIN users u ON u.id = o.user_id
            WHERE o.status = 'paid'
            ORDER BY o.payment_date DESC
        ");
        $transactions = $query->getResultArray();

        $filename = 'laporan_transaksi_' . date('Ymd') . '.csv';
        
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Type: application/csv; "); 
        
        $file = fopen('php://output', 'w');
        
        $header = array("Kode Pesanan", "Tanggal Pembayaran", "Nama Kursus", "Nama Siswa", "Total (Rp)", "Metode Pembayaran");
        fputcsv($file, $header);
        
        foreach ($transactions as $line){
            fputcsv($file, array($line['order_code'], $line['payment_date'], $line['course_title'], $line['user_name'], $line['total'], $line['payment_method']));
        }
        
        fclose($file);
        exit;
    }

    public function getReportData()
    {
        $dt = new \App\Libraries\FluentDatatables();
        
        $dt->of('orders')
           ->select('orders.*, users.name as user_name, courses.title as course_title')
           ->join('users', 'users.id = orders.user_id')
           ->join('courses', 'courses.id = orders.course_id')
           ->where('orders.status', 'paid')
           ->set_column_search(['orders.order_code', 'users.name', 'courses.title'])
           ->set_column_order(['orders.order_code', 'orders.payment_date', 'courses.title', 'users.name', 'orders.total', 'orders.payment_method']);

        $dt->addColumn('col_kode', function($row) {
            return '<span class="font-weight-bold text-primary">#' . esc($row->order_code) . '</span>';
        });
        
        $dt->addColumn('col_tanggal', function($row) {
            return date('d M Y, H:i', strtotime($row->payment_date));
        });
        
        $dt->addColumn('col_kursus', function($row) {
            return esc($row->course_title);
        });

        $dt->addColumn('col_siswa', function($row) {
            return esc($row->user_name);
        });

        $dt->addColumn('col_total', function($row) {
            return '<span class="font-weight-bold">Rp ' . number_format($row->total, 0, ',', '.') . '</span>';
        });

        $dt->addColumn('col_metode', function($row) {
            $methodStr = !empty($row->payment_method) ? strtoupper(esc($row->payment_method)) : 'MANUAL';
            return '<span class="badge badge-light" style="font-size: 12px; padding: 6px 12px; border-radius: 100px; border: 1px solid #E2E8F0;">' . $methodStr . '</span>';
        });

        return $dt->make(TRUE);
    }
}