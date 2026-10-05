$db = \Config\Database::connect();
$dt = new \App\Libraries\FluentDatatables();
$dt->of("orders")
   ->select("orders.*, users.name as user_name, users.email as user_email, courses.title as course_title, courses.price")
   ->join("users", "users.id = orders.user_id")
   ->join("courses", "courses.id = orders.course_id")
   ->whereIn("orders.status", ["pending", "draft"]);
echo json_encode($dt->generate());
