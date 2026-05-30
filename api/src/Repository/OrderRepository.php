<?php
// ============================================================
//  src/Repository/OrderRepository.php
//  All SQL queries for the dashboard — one method per endpoint
// ============================================================
namespace App\Repository;

use App\Database\Connection;
use PDO;

class OrderRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    // ----------------------------------------------------------
    // SCORECARDS
    // ----------------------------------------------------------

    /**
     * Returns top-level KPI numbers.
     *
     * @return array<string, float|int>
     */
    public function getScoreCards(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*)                                            AS total_orders,
                COALESCE(SUM(amount), 0)                           AS total_revenue,
                COALESCE(AVG(amount), 0)                           AS avg_order_value,
                ROUND(
                    SUM(CASE WHEN courier_status = 'Shipped'
                             THEN 1 ELSE 0 END) / COUNT(*) * 100
                , 1)                                               AS shipped_rate,
                ROUND(
                    SUM(CASE WHEN `status` LIKE '%Cancelled%'
                             THEN 1 ELSE 0 END) / COUNT(*) * 100
                , 1)                                               AS cancelled_rate,
                COUNT(DISTINCT ship_state)                         AS states_reached,
                COUNT(DISTINCT category)                           AS categories_active
            FROM amazon_orders
        ")->fetch();

        return [
            'total_orders'      => (int)   $row['total_orders'],
            'total_revenue'     => (float) $row['total_revenue'],
            'avg_order_value'   => (float) round($row['avg_order_value'], 2),
            'shipped_rate'      => (float) $row['shipped_rate'],
            'cancelled_rate'    => (float) $row['cancelled_rate'],
            'states_reached'    => (int)   $row['states_reached'],
            'categories_active' => (int)   $row['categories_active'],
        ];
    }

    // ----------------------------------------------------------
    // CHARTS — CATEGORICAL
    // ----------------------------------------------------------

    /**
     * Order count grouped by product category.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getByCategory(int $limit = 8): array
    {
        $stmt = $this->db->prepare("
            SELECT category AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE category IS NOT NULL AND category != ''
            GROUP BY category
            ORDER BY value DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->toIntValues($stmt->fetchAll());
    }

    /**
     * Order count grouped by courier/delivery status.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getByCourierStatus(): array
    {
        $rows = $this->db->query("
            SELECT
                CASE
                    WHEN courier_status IS NULL OR courier_status = ''
                    THEN 'Unknown'
                    ELSE courier_status
                END AS label,
                COUNT(*) AS value
            FROM amazon_orders
            GROUP BY label
            ORDER BY value DESC
        ")->fetchAll();
        return $this->toIntValues($rows);
    }

    /**
     * Order count grouped by order status.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getByOrderStatus(): array
    {
        $rows = $this->db->query("
            SELECT `status` AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE `status` IS NOT NULL
            GROUP BY `status`
            ORDER BY value DESC
        ")->fetchAll();
        return $this->toIntValues($rows);
    }

    /**
     * Order count grouped by garment size.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getBySize(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT size AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE size IS NOT NULL AND size != ''
            GROUP BY size
            ORDER BY value DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->toIntValues($stmt->fetchAll());
    }

    /**
     * Order count grouped by fulfilment type (Merchant / Amazon).
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getByFulfilment(): array
    {
        $rows = $this->db->query("
            SELECT fulfilment AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE fulfilment IS NOT NULL
            GROUP BY fulfilment
            ORDER BY value DESC
        ")->fetchAll();
        return $this->toIntValues($rows);
    }

    /**
     * Order count grouped by sales channel.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getBySalesChannel(): array
    {
        $rows = $this->db->query("
            SELECT sales_channel AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE sales_channel IS NOT NULL
            GROUP BY sales_channel
            ORDER BY value DESC
        ")->fetchAll();
        return $this->toIntValues($rows);
    }

    /**
     * Order count grouped by B2B flag.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getByB2B(): array
    {
        $rows = $this->db->query("
            SELECT
                CASE WHEN b2b = 1 THEN 'B2B' ELSE 'B2C' END AS label,
                COUNT(*) AS value
            FROM amazon_orders
            GROUP BY b2b
            ORDER BY value DESC
        ")->fetchAll();
        return $this->toIntValues($rows);
    }

    // ----------------------------------------------------------
    // CHARTS — GEOGRAPHIC
    // ----------------------------------------------------------

    /**
     * Top N shipping states by order volume.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getTopStates(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT ship_state AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE ship_state IS NOT NULL AND ship_state != ''
            GROUP BY ship_state
            ORDER BY value DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->toIntValues($stmt->fetchAll());
    }

    /**
     * Top N shipping cities by order volume.
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function getTopCities(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT ship_city AS label, COUNT(*) AS value
            FROM amazon_orders
            WHERE ship_city IS NOT NULL AND ship_city != ''
            GROUP BY ship_city
            ORDER BY value DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->toIntValues($stmt->fetchAll());
    }

    // ----------------------------------------------------------
    // CHARTS — TIME SERIES
    // ----------------------------------------------------------

    /**
     * Monthly revenue trend.
     *
     * @param int $months  How many past months to return
     * @return array<int, array{label: string, value: float, year: int, month: int}>
     */
    public function getRevenueTrend(int $months = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(order_date, '%b %Y') AS label,
                YEAR(order_date)                 AS year,
                MONTH(order_date)                AS month,
                ROUND(SUM(amount), 2)            AS value,
                COUNT(*)                         AS order_count
            FROM amazon_orders
            WHERE order_date IS NOT NULL
            GROUP BY YEAR(order_date), MONTH(order_date)
            ORDER BY YEAR(order_date) ASC, MONTH(order_date) ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $months, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'label'       => $row['label'],
                'year'        => (int)   $row['year'],
                'month'       => (int)   $row['month'],
                'value'       => (float) $row['value'],
                'order_count' => (int)   $row['order_count'],
            ];
        }, $stmt->fetchAll());
    }

    /**
     * Daily order count for the last N days (sparkline).
     *
     * @param int $days
     * @return array<int, array{label: string, value: int}>
     */
    public function getDailyOrderCount(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE_FORMAT(order_date, '%d %b') AS label,
                COUNT(*)                          AS value
            FROM amazon_orders
            WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY order_date
            ORDER BY order_date ASC
        ");
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $this->toIntValues($stmt->fetchAll());
    }

    // ----------------------------------------------------------
    // ORDERS — PAGINATED LIST
    // ----------------------------------------------------------

    /**
     * Paginated list of raw orders with optional filters.
     *
     * @param int    $page
     * @param int    $limit
     * @param array<string, string> $filters  keys: status, category, size, state
     * @return array{data: array<int, array<string,mixed>>, total: int, page: int, limit: int, pages: int}
     */
    public function getOrders(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]            = 'courier_status = :status';
            $params[':status']  = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $where[]              = 'category = :category';
            $params[':category']  = $filters['category'];
        }
        if (!empty($filters['size'])) {
            $where[]          = 'size = :size';
            $params[':size']  = $filters['size'];
        }
        if (!empty($filters['state'])) {
            $where[]           = 'ship_state = :state';
            $params[':state']  = $filters['state'];
        }

        $whereSQL = implode(' AND ', $where);
        $offset   = ($page - 1) * $limit;

        // Total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM amazon_orders WHERE $whereSQL");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Data
        $dataStmt = $this->db->prepare("
            SELECT
                order_id, order_date, `status`, courier_status,
                category, size, amount, currency,
                ship_city, ship_state, fulfilled_by
            FROM amazon_orders
            WHERE $whereSQL
            ORDER BY order_date DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $val) {
            $dataStmt->bindValue($key, $val);
        }
        $dataStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'data'  => $dataStmt->fetchAll(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ];
    }

    // ----------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------

    /**
     * Cast 'value' column to int in a result set.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function toIntValues(array $rows): array
    {
        return array_map(static function (array $row): array {
            $row['value'] = (int) $row['value'];
            return $row;
        }, $rows);
    }
}
