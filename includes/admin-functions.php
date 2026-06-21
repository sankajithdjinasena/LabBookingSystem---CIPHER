<?php
/**
 * SURAS — admin & faculty helper functions
 * User management, resource CRUD, booking approval/rejection, and
 * the analytics queries behind admin/reports.php.
 */

if (defined('SURAS_ADMIN_FUNCTIONS_LOADED')) {
    return;
}
define('SURAS_ADMIN_FUNCTIONS_LOADED', true);

require_once __DIR__ . '/functions.php';

/* =====================================================================
   Overview stats (admin dashboard)
   ===================================================================== */

function admin_overview_stats(): array
{
    $pdo = get_db_connection();

    $users     = $pdo->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];
    $resources = $pdo->query("SELECT COUNT(*) AS c FROM resources")->fetch()['c'];
    $pending   = $pdo->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'pending'")->fetch()['c'];
    $today     = $pdo->query("SELECT COUNT(*) AS c FROM bookings WHERE status IN ('approved','pending') AND DATE(start_time) = CURDATE()")->fetch()['c'];
    $waitlist  = $pdo->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'waitlist'")->fetch()['c'];
    $available = $pdo->query("SELECT COUNT(*) AS c FROM resources WHERE status = 'available'")->fetch()['c'];

    return [
        'total_users'      => (int) $users,
        'total_resources'  => (int) $resources,
        'available_resources' => (int) $available,
        'pending_requests' => (int) $pending,
        'today_bookings'   => (int) $today,
        'waitlisted'       => (int) $waitlist,
    ];
}

function recent_activity(int $limit = 8): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'SELECT b.id, b.status, b.created_at, b.start_time, u.full_name, r.name AS resource_name
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN resources r ON r.id = b.resource_id
         ORDER BY b.created_at DESC LIMIT :limit'
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* =====================================================================
   User management
   ===================================================================== */

function get_all_users(string $search = '', string $role = '', string $status = ''): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT * FROM users WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (full_name LIKE :search OR email LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }
    if ($role !== '' && $role !== 'all') {
        $sql .= ' AND role = :role';
        $params['role'] = $role;
    }
    if ($status !== '' && $status !== 'all') {
        $sql .= ' AND status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function update_user(int $userId, string $role, string $status): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE users SET role = :role, status = :status WHERE id = :id');
    return $stmt->execute(['role' => $role, 'status' => $status, 'id' => $userId]);
}

function delete_user(int $userId): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    return $stmt->execute(['id' => $userId]);
}

/* =====================================================================
   Resource management (CRUD)
   ===================================================================== */

function create_resource(string $name, string $category, ?string $location, ?int $capacity, ?string $description, string $status): int
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'INSERT INTO resources (name, category, location, capacity, description, status)
         VALUES (:name, :category, :location, :capacity, :description, :status)'
    );
    $stmt->execute([
        'name' => $name, 'category' => $category, 'location' => $location,
        'capacity' => $capacity, 'description' => $description, 'status' => $status,
    ]);
    return (int) $pdo->lastInsertId();
}

function update_resource(int $id, string $name, string $category, ?string $location, ?int $capacity, ?string $description, string $status): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'UPDATE resources SET name = :name, category = :category, location = :location,
         capacity = :capacity, description = :description, status = :status WHERE id = :id'
    );
    return $stmt->execute([
        'name' => $name, 'category' => $category, 'location' => $location,
        'capacity' => $capacity, 'description' => $description, 'status' => $status, 'id' => $id,
    ]);
}

function delete_resource(int $id): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM resources WHERE id = :id');
    return $stmt->execute(['id' => $id]);
}

/* =====================================================================
   Booking review (admin + faculty)
   ===================================================================== */

function get_bookings_for_review(string $status = 'pending', string $department = ''): array
{
    $pdo = get_db_connection();
    $sql = 'SELECT b.*, u.full_name, u.email, u.department AS user_department, u.role AS user_role,
                   r.name AS resource_name, r.category AS resource_category
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN resources r ON r.id = b.resource_id
            WHERE 1=1';
    $params = [];

    if ($status !== '' && $status !== 'all') {
        $sql .= ' AND b.status = :status';
        $params['status'] = $status;
    }
    if ($department !== '') {
        $sql .= ' AND u.department = :department';
        $params['department'] = $department;
    }
    $sql .= ' ORDER BY b.priority_score DESC, b.created_at ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function approve_booking_admin(int $bookingId): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT user_id FROM bookings WHERE id = :id');
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();
    if (!$booking) return false;

    $update = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
    $update->execute(['id' => $bookingId]);

    create_notification((int) $booking['user_id'], $bookingId, 'approval', 'Your booking request has been approved.');
    return true;
}

function reject_booking_admin(int $bookingId, string $reason = ''): bool
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT user_id FROM bookings WHERE id = :id');
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();
    if (!$booking) return false;

    $update = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = :id");
    $update->execute(['id' => $bookingId]);

    $message = 'Your booking request was rejected.' . ($reason !== '' ? ' Reason: ' . $reason : '');
    create_notification((int) $booking['user_id'], $bookingId, 'rejection', $message);
    return true;
}

/* =====================================================================
   Reports & analytics
   ===================================================================== */

function bookings_per_day(int $days = 14): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "SELECT DATE(start_time) AS day, COUNT(*) AS total
         FROM bookings
         WHERE start_time >= (CURDATE() - INTERVAL :days DAY)
         GROUP BY DATE(start_time)
         ORDER BY day ASC"
    );
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function most_used_resources(int $limit = 6): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "SELECT r.name, r.category, COUNT(b.id) AS total
         FROM bookings b
         JOIN resources r ON r.id = b.resource_id
         WHERE b.status IN ('approved','completed')
         GROUP BY b.resource_id
         ORDER BY total DESC
         LIMIT :limit"
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function peak_booking_hours(): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->query(
        "SELECT HOUR(start_time) AS hour, COUNT(*) AS total
         FROM bookings
         WHERE status IN ('approved','completed')
         GROUP BY HOUR(start_time)
         ORDER BY hour ASC"
    );
    return $stmt->fetchAll();
}

function department_usage(): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->query(
        "SELECT COALESCE(u.department, 'Unspecified') AS department, COUNT(b.id) AS total
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         WHERE b.status IN ('approved','completed')
         GROUP BY u.department
         ORDER BY total DESC"
    );
    return $stmt->fetchAll();
}

function cancellation_stats(): array
{
    $pdo = get_db_connection();
    $row = $pdo->query(
        "SELECT
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = 'rejected'  THEN 1 ELSE 0 END) AS rejected,
            COUNT(*) AS total
         FROM bookings"
    )->fetch();

    $total = (int) ($row['total'] ?? 0);
    $cancelled = (int) ($row['cancelled'] ?? 0);
    $rejected  = (int) ($row['rejected'] ?? 0);

    return [
        'cancelled' => $cancelled,
        'rejected'  => $rejected,
        'total'     => $total,
        'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0,
    ];
}

function resource_utilization_rate(): float
{
    $pdo = get_db_connection();
    $total = (int) $pdo->query("SELECT COUNT(*) AS c FROM resources")->fetch()['c'];
    $inUse = (int) $pdo->query(
        "SELECT COUNT(DISTINCT resource_id) AS c FROM bookings WHERE status IN ('approved','completed')"
    )->fetch()['c'];

    return $total > 0 ? round(($inUse / $total) * 100, 1) : 0.0;
}
