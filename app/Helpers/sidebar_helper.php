<?php

if (! function_exists('get_sidebar_badge')) {
    function get_sidebar_badge($type) {
        $cache = \Config\Services::cache();
        $cacheKey = 'sidebar_badge_rollup_' . $type;
        
        if ($cachedCount = $cache->get($cacheKey)) {
            return $cachedCount;
        }

        $db = \Config\Database::connect();
        
        $count = 0;
        if ($type === 'persetujuan') {
            if ($db->tableExists('courses')) {
                $count = $db->table('courses')->where('status', 'draft')->where('deleted_at IS NULL')->countAllResults();
            }
        } elseif ($type === 'transaksi') {
            // Placeholder for transaction count
            $count = 0; 
        }

        $cache->save($cacheKey, $count, 300);
        return $count;
    }
}
