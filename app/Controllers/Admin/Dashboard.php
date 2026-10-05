<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Fetch Latest Revenue (Latest Order)
        $settings = $db->table("platform_settings")->get()->getRowArray() ?: [];
        $latestOrder = $db->table("orders")
            ->select("orders.*, courses.title as course_title, courses.thumbnail as course_thumbnail")
            ->join("courses", "courses.id = orders.course_id", "left")
            ->orderBy("orders.created_at", "DESC")
            ->limit(1)
            ->get()
            ->getRowArray();
            
        // 2. Fetch Recent Activities (Mix of new users and orders)
        // Let us just fetch the 3 most recent orders for simplicity
        $recentOrders = $db->table("orders")
            ->select("orders.order_code, orders.created_at, orders.amount, users.name as user_name")
            ->join("users", "users.id = orders.user_id", "left")
            ->orderBy("orders.created_at", "DESC")
            ->limit(3)
            ->get()
            ->getResultArray();
            
        $recent_activities = [];
        foreach($recentOrders as $ro) {
            $recent_activities[] = [
                "icon" => "fas fa-shopping-cart",
                "icon_color" => "#10b981", // Emerald green
                "title" => "New Purchase by " . ($ro["user_name"] ?? "User"),
                "description" => "Order " . $ro["order_code"] . " - Rp " . number_format($ro["amount"], 0, ",", ".")
            ];
        }
        
        // If no data, use some fallback
        if (empty($recent_activities)) {
            $recent_activities = [
                [
                    "icon" => "fas fa-shopping-cart",
                    "icon_color" => "#10b981",
                    "title" => "No recent orders",
                    "description" => "Awaiting new transactions..."
                ]
            ];
        }

        $dashboardData = [
            "platform" => [
                "name" => $settings["platform_name"] ?? "EduNusa Platform",
                "phone" => $settings["phone_number"] ?? "+62 812-3456-7890",
                "website" => $settings["website_url"] ?? "edunusa.edu.id",
                "established" => $settings["established_date"] ?? "May 13, 2022",
                "last_update" => date("M d, Y"),
                "data_center" => $settings["data_center"] ?? "GCP Asia-Southeast2, Jakarta, Indonesia",
                "is_suspended" => $settings["is_suspended"] ?? 0,
                "is_muted" => $settings["is_muted"] ?? 0
            ],
            "action_buttons" => [
                "suspend" => $settings["btn_suspend"] ?? 1,
                "mute_alert" => $settings["btn_mute_alert"] ?? 1,
                "stop_monitoring" => $settings["btn_stop_monitoring"] ?? 1,
                "modules" => $settings["btn_modules"] ?? 1,
                "add_admin" => $settings["btn_add_admin"] ?? 1,
                "shut_down" => $settings["btn_shut_down"] ?? 1
            ],
            "latest_revenue" => [
                "course_name" => $latestOrder ? $latestOrder["course_title"] : "No Course Data",
                "course_thumbnail" => ($latestOrder && !empty($latestOrder["course_thumbnail"])) ? base_url($latestOrder["course_thumbnail"]) : "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=200&auto=format&fit=crop",
                "amount" => $latestOrder ? "Rp " . number_format($latestOrder["amount"] ?? $latestOrder["total"] ?? 0, 0, ",", ".") : "Rp 0",
                "trx_id" => $latestOrder ? $latestOrder["order_code"] : "TRX-NONE",
                "payment_method" => $latestOrder ? $latestOrder["payment_method"] : "-",
                "last_update" => $latestOrder ? date("M d, Y, h:i A", strtotime($latestOrder["created_at"])) : "today",
                "created_at" => $latestOrder ? date("M d", strtotime($latestOrder["created_at"])) : "-",
                "payment_date" => ($latestOrder && $latestOrder["payment_date"]) ? date("M d", strtotime($latestOrder["payment_date"])) : "-",
                "status" => $latestOrder ? $latestOrder["status"] : "pending",
            ],
            "recent_activities" => $recent_activities
        ];

        $role = session()->get('user_role') ?? 'superadmin';
        
        if ($role === 'content_manager') {
            // Data Khusus Content Manager
            $contentData = [
                "courses_published" => $db->table("courses")->where("status", "published")->countAllResults(),
                "courses_draft" => $db->table("courses")->where("status", "draft")->where("deleted_at IS NULL")->countAllResults(),
                "recent_courses" => $db->table("courses")->orderBy("created_at", "DESC")->limit(5)->get()->getResultArray()
            ];
            
            return view("admin/dashboard_content", [
                "title" => "Content Manager Dashboard",
                "data" => $contentData
            ]);
        }

        return view("admin/dashboard", [
            "title" => "Dashboard Admin",
            "data" => $dashboardData
        ]);
    }

    public function toggleSuspend()
    {
        $db = \Config\Database::connect();
        $settings = $db->table("platform_settings")->get()->getRow();
        if ($settings) {
            $newState = $settings->is_suspended ? 0 : 1;
            $db->table("platform_settings")->where("id", $settings->id)->update(["is_suspended" => $newState]);
            // Sync status file for Next.js portal
            $fresh = $db->table("platform_settings")->where("id", $settings->id)->get()->getRow();
            if ($fresh) { $this->writePlatformStatus(["is_suspended" => $fresh->is_suspended, "is_muted" => $fresh->is_muted]); }
            return $this->response->setJSON(["success" => true, "is_suspended" => $newState]);
        }
        return $this->response->setJSON(["success" => false]);
    }

    public function toggleMute()
    {
        $db = \Config\Database::connect();
        $settings = $db->table("platform_settings")->get()->getRow();
        if ($settings) {
            $newState = $settings->is_muted ? 0 : 1;
            $db->table("platform_settings")->where("id", $settings->id)->update(["is_muted" => $newState]);
            // Sync status file for Next.js portal
            $fresh = $db->table("platform_settings")->where("id", $settings->id)->get()->getRow();
            if ($fresh) { $this->writePlatformStatus(["is_suspended" => $fresh->is_suspended, "is_muted" => $fresh->is_muted]); }
            return $this->response->setJSON(["success" => true, "is_muted" => $newState]);
        }
        return $this->response->setJSON(["success" => false]);
    }

    public function platformStatus()
    {
        $this->response->setHeader("Access-Control-Allow-Origin", "*");
        $db = \Config\Database::connect();
        $settings = $db->table("platform_settings")->get()->getRow();
        if ($settings) {
            return $this->response->setJSON([
                "is_suspended" => (int) $settings->is_suspended,
                "is_muted" => (int) $settings->is_muted
            ]);
        }
        return $this->response->setJSON(["is_suspended" => 0, "is_muted" => 0]);
    }

    private function writePlatformStatus(array $settings): void
    {
        // Docker path: admin container is at /var/www/html, public is mounted at /var/www/html/../../../e-learning-public
        $paths = [
            "/var/www/html/../../../e-learning-public/public/platform-status.json",
            "C:/xampp82/htdocs/e-learning-public/public/platform-status.json",
        ];
        $statusFile = $paths[0];
        foreach ($paths as $p) { if (is_dir(dirname($p))) { $statusFile = $p; break; } }
        $data = json_encode([
            "is_suspended" => (int)$settings["is_suspended"],
            "is_muted"     => (int)$settings["is_muted"],
            "updated_at"   => date("c"),
        ]);
        @file_put_contents($statusFile, $data);
    }

    public function checkNewOrders()
    {
        $lastId = $this->request->getGet("last_id") ?? 0;
        
        $db = \Config\Database::connect();
        
        // Check platform settings for mute status
        $settings = $db->table("platform_settings")->get()->getRow();
        $is_muted = $settings ? (int) $settings->is_muted : 0;
        
        // Get new orders if any (simulate if orders table is empty)
        $newOrders = [];
        if ($db->tableExists("orders")) {
            $newOrders = $db->table("orders")
                            ->select("id, amount, status, created_at")
                            ->where("id >", $lastId)
                            ->orderBy("id", "ASC")
                            ->get()->getResultArray();
        }
        
        return $this->response->setJSON([
            "success" => true,
            "is_muted" => $is_muted,
            "new_orders" => $newOrders,
            "current_max_id" => empty($newOrders) ? $lastId : end($newOrders)["id"]
        ]);
    }

    public function simulateOrder()
    {
        $db = \Config\Database::connect();
        if ($db->tableExists("orders")) {
            $amount = rand(150000, 750000);
            $db->table("orders")->insert([
                "order_code" => "TRX-" . time() . rand(100, 999),
                "user_id" => 1,
                "course_id" => 1,
                "amount" => $amount,
                "total" => $amount,
                "status" => "paid",
                "payment_method" => "Bank Transfer",
                "created_at" => date("Y-m-d H:i:s")
            ]);
        }
        return $this->response->setJSON(["success" => true]);
    }
}