<?php
namespace App\Controllers;
use CodeIgniter\Controller;
class TestController extends Controller {
    public function index() {
        $db = \Config\Database::connect();
        echo "Hostname: " . $db->hostname . "<br>";
        echo "Database: " . $db->database . "<br>";
        $query = $db->query("SELECT * FROM courses");
        echo "<pre>";
        print_r($query->getResultArray());
        echo "</pre>";
    }
}
