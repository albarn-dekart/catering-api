<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\OrderStatus;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function createCustomerOrdersQueryBuilder(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customer = :user')
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC');
    }

    /**
     * Get the total revenue from all orders (excluding unpaid orders)
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.status != :unpaidStatus')
            ->setParameter('unpaidStatus', OrderStatus::Unpaid)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result / 100 : 0.0; // Convert cents to euros
    }

    /**
     * Get the total revenue for a specific restaurant
     */
    public function getTotalRevenueByRestaurant(Restaurant $restaurant): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.restaurant = :restaurant')
            ->andWhere('o.status != :unpaidStatus')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('unpaidStatus', OrderStatus::Unpaid)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result / 100 : 0.0; // Convert cents to euros
    }

    /**
     * Get the count of orders grouped by status
     * Returns associative array like ['Paid' => 10, 'Active' => 5, ...]
     */
    public function getOrderCountByStatus(): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.status as status, COUNT(o.id) as count')
            ->groupBy('o.status');

        $results = $qb->getQuery()->getResult();

        $statusCounts = [];
        foreach ($results as $result) {
            $status = $result['status'];
            if ($status instanceof OrderStatus) {
                $statusCounts[$status->value] = (int) $result['count'];
            }
        }

        return $statusCounts;
    }

    /**
     * Get the average order value (excluding unpaid orders)
     */
    public function getAverageOrderValue(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('AVG(o.total)')
            ->where('o.status != :unpaidStatus')
            ->setParameter('unpaidStatus', OrderStatus::Unpaid)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result / 100 : 0.0; // Convert cents to euros
    }

    /**
     * Get the number of orders in a specific time period
     * Period can be: 'today', 'week', 'month', 'year'
     */
    public function getOrdersInPeriod(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.id >= :start')
            ->andWhere('o.id <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get revenue grouped by month for the current year
     * Returns array of ['month' => 'January', 'revenue' => 1234.56]
     */
    public function getRevenueByPeriod(string $period = 'month'): array
    {
        // For simplicity, we'll return the revenue for the last 12 months
        $qb = $this->createQueryBuilder('o')
            ->select('o.total')
            ->where('o.status != :unpaidStatus')
            ->setParameter('unpaidStatus', OrderStatus::Unpaid)
            ->orderBy('o.id', 'DESC');

        $orders = $qb->getQuery()->getResult();

        // Simple aggregation - in production, you'd want to use SQL DATE functions
        $revenue = [];
        foreach ($orders as $order) {
            $revenue[] = $order['total'] / 100; // Convert cents to euros
        }

        return $revenue;
    }

    /**
     * Get the count of orders for a specific restaurant
     */
    public function getOrderCountByRestaurant(Restaurant $restaurant): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.restaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get the count of active orders (Paid or Active status) for a specific restaurant
     */
    public function getActiveOrdersByRestaurant(Restaurant $restaurant): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.restaurant = :restaurant')
            ->andWhere('o.status IN (:activeStatuses)')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('activeStatuses', [OrderStatus::Paid, OrderStatus::Active])
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get revenue time series data for charts
     * Returns array of ['date' => 'YYYY-MM-DD', 'revenue' => float] for the last N days
     */
    public function getRevenueTimeSeries(int $days = 30, ?Restaurant $restaurant = null): array
    {
        $endDate = new DateTime('today');
        $startDate = (new DateTime('today'))->modify("-$days days");

        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DATE(created_at) as date, SUM(total) as revenue
            FROM "order"
            WHERE status != :unpaidStatus
            AND created_at >= :startDate
            AND created_at <= :endDate
        ';

        $params = [
            'unpaidStatus' => OrderStatus::Unpaid->value,
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d 23:59:59'),
        ];

        if ($restaurant) {
            $sql .= ' AND restaurant_id = :restaurantId';
            $params['restaurantId'] = $restaurant->getId();
        }

        $sql .= ' GROUP BY DATE(created_at) ORDER BY date ASC';

        $stmt = $conn->prepare($sql);
        $results = $stmt->executeQuery($params)->fetchAllAssociative();

        // Format results and fill missing dates with 0 revenue
        $timeSeries = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $revenue = 0.0;

            foreach ($results as $result) {
                if ($result['date'] === $dateStr) {
                    $revenue = (float) $result['revenue'] / 100; // Convert cents to euros
                    break;
                }
            }

            $timeSeries[] = [
                'date' => $dateStr,
                'revenue' => $revenue
            ];

            $current->modify('+1 day');
        }

        return $timeSeries;
    }
}
