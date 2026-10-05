<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class TransaksiController extends BaseController
{
        public function index()
    {
        $status = $this->request->getGet('status');
        
        $db = \Config\Database::connect();
        $counts = [
            'all' => $db->table('orders')->countAllResults(),
            'pending' => $db->table('orders')->whereIn('status', ['pending', 'draft'])->countAllResults(),
            'paid' => $db->table('orders')->where('status', 'paid')->countAllResults(),
            'failed' => $db->table('orders')->whereIn('status', ['failed', 'expired', 'cancelled'])->countAllResults(),
        ];
        
        $data = [
            'title' => 'Transactions',
            'currentStatus' => $status,
            'counts' => $counts
        ];
        
        return view('admin/transaksi', $data);
    }
    
    public function getData()
    {
        $dt = new \App\Libraries\FluentDatatables();
        
        $status = $this->request->getPost('status_filter');

        $dt->of('orders')
           ->select('orders.*, users.name as user_name, users.email as user_email, courses.title as course_title, courses.price')
           ->join('users', 'users.id = orders.user_id', 'left')
           ->join('courses', 'courses.id = orders.course_id', 'left')
           ->set_column_search(['orders.id', 'users.name', 'users.email', 'courses.title'])
           ->set_column_order([null, 'orders.created_at', 'users.name', 'courses.title', 'orders.amount', 'orders.status', null]);

        if ($status === 'failed') {
            $dt->where_in('orders.status', ['expired', 'cancelled', 'failed']);
        } elseif ($status && $status === 'pending') {
            $dt->where_in('orders.status', ['pending', 'draft']);
        } elseif ($status === 'paid') {
            $dt->where('orders.status', 'paid');
        }

        $dateFrom = $this->request->getPost('date_from');
        $dateTo   = $this->request->getPost('date_to');
        if ($dateFrom) {
            $dt->where('orders.created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $dt->where('orders.created_at <=', $dateTo . ' 23:59:59');
        }

        

                $dt->addColumn('checkbox', function($row) {
            return '<input type="checkbox" class="agy-checkbox" value="' . $row->id . '">';
        });

        $dt->addColumn('date_formatted', function($row) {
            return '<div style="font-weight: 500; color: #1E293B;">' . date('M d, Y', strtotime($row->created_at)) . '</div><div style="font-size: 13px; color: #64748b;">' . date('H:i', strtotime($row->created_at)) . '</div>';
        });

        $dt->addColumn('student_html', function($row) {
            $initials = strtoupper(substr($row->user_name, 0, 2));
            return '
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: #F1F5F9; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #475569;">' . $initials . '</div>
                <div>
                    <div style="font-weight: 500; color: #1E293B; font-size: 14px;">' . esc($row->user_name) . '</div>
                    <div style="font-size: 13px; color: #64748b;">' . esc($row->user_email) . '</div>
                </div>
            </div>';
        });

        $dt->addColumn('course_html', function($row) {
            return '<div style="font-weight: 500; color: #1E293B; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . esc($row->course_title) . '</div><div style="font-size: 13px; color: #64748b; font-family: monospace;">Order: ' . substr($row->id, 0, 8) . '</div>';
        });

        $dt->addColumn('amount_formatted', function($row) {
            return '<div style="font-weight: 600; color: #0F172A;">Rp ' . number_format($row->amount, 0, ',', '.') . '</div>';
        });

        $dt->addColumn('status_badge', function($row) {
            $status = esc($row->status);
            $icon  = ''; $cls = 's-' . $status;
            if ($status === 'paid')                                           { $icon = 'fa-check-circle'; }
            elseif (in_array($status, ['pending', 'draft']))                  { $icon = 'fa-clock'; $cls = 's-pending'; $status = 'pending'; }
            elseif (in_array($status, ['failed','expired','cancelled']))  { $icon = 'fa-times-circle'; $cls = 's-failed'; }
            return '<span class="s-badge ' . $cls . '"><i class="fas ' . $icon . '"></i> ' . ucfirst($status) . '</span>';
        });

        $dt->addColumn('actions', function($row) {
            $html = '<div style="display: flex; gap: 4px;">';
            $html .= '<button class="btn-detail" onclick="viewDetail(\'' . $row->id . '\')" style="background: #F1F5F9; color: #475569; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 500; font-size: 12px; transition: all 0.2s;"><i class="fas fa-file-invoice" style="margin-right: 4px;"></i> Detail</button>';
            if (in_array($row->status, ['pending', 'draft'])) {
                $html .= '<button class="btn-approve" onclick="approveOrder(\'' . $row->id . '\')" style="background: #4F46E5; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 500; font-size: 12px; transition: all 0.2s;"><i class="fas fa-check" style="margin-right: 4px;"></i> Approve</button>';
            }
            $html .= '</div>';
            return $html;
        });

        

        return $dt->generate();
    }
    
    public function detail($id)
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')
            ->select('orders.*, users.name as user_name, users.email as user_email, users.role as user_role, courses.title as course_title, courses.price as course_price')
            ->join('users', 'users.id = orders.user_id', 'left')
            ->join('courses', 'courses.id = orders.course_id', 'left')
            ->where('orders.id', $id)
            ->get()->getRow();

        if (!$order) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.']);
        }

        return $this->response->setJSON(['status' => 'success', 'data' => $order]);
    }

    public function approve()
    {
        $request = \Config\Services::request();
        $orderId = $request->getPost('order_id');
        
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('id', $orderId)->get()->getRowArray();
        
        if (!$order) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Order not found']);
        }
        
        if ($order['status'] === 'paid') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Order is already paid']);
        }
        
        $db->transStart();
        
        // 1. Update order status
        $db->table('orders')->where('id', $orderId)->update(['status' => 'paid', 'updated_at' => date('Y-m-d H:i:s')]);
        
        // 2. Create enrollment
        // Generate UUIDv4 manually since we are using query builder
        $enrollmentId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        
        $db->table('enrollments')->insert([
            'id' => $enrollmentId,
            'user_id' => $order['user_id'],
            'course_id' => $order['course_id'],
            'progress' => 0,
            'enrolled_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->transComplete();
        
        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to approve transaction']);
        }
        
        return $this->response->setJSON(['status' => 'success', 'message' => 'Transaction approved and student enrolled!']);
    }
}
