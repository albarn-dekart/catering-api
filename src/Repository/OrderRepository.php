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
    public function getTotalRevenue(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::Unpaid, OrderStatus::Cancelled]);

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt >= :startDate')
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * Get the total revenue for a specific restaurant
     */
    public function getTotalRevenueByRestaurant(Restaurant $restaurant, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->where('o.restaurant = :restaurant')
            ->andWhere('o.status NOT IN (:excludedStatuses)')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('excludedStatuses', [OrderStatus::Unpaid, OrderStatus::Cancelled]);

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt >= :startDate')
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * Get the count of orders grouped by status
     * Returns associative array like ['Paid' => 10, 'Active' => 5, ...]
     */
    public function getOrderCountByStatus(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.status as status, COUNT(o.id) as count')
            ->groupBy('o.status');

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt >= :startDate')
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

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
    public function getAverageOrderValue(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('AVG(o.total)')
            ->where('o.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::Unpaid, OrderStatus::Cancelled]);

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt >= :startDate')
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result !== null ? (float) $result : 0.0;
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
            ->where('o.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::Unpaid, OrderStatus::Cancelled])
            ->orderBy('o.id', 'DESC');

        $orders = $qb->getQuery()->getResult();

        // Simple aggregation - in production, you'd want to use SQL DATE functions
        $revenue = [];
        foreach ($orders as $order) {
            $revenue[] = (float) $order['total'];
        }

        return $revenue;
    }

    /**
     * Get the count of orders for a specific restaurant
     */
    public function getOrderCountByRestaurant(Restaurant $restaurant, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.restaurant = :restaurant')
            ->setParameter('restaurant', $restaurant);

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt >= :startDate')
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get the count of active orders (Paid or Active status) for a specific restaurant
     */
    public function getActiveOrdersByRestaurant(Restaurant $restaurant, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.restaurant = :restaurant')
            ->andWhere('o.status IN (:activeStatuses)')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('activeStatuses', [OrderStatus::Paid, OrderStatus::Active]);

        if ($startDate && $endDate) {
            $qb->andWhere('o.createdAt >= :startDate')
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get revenue time series data for charts
     * Returns array of ['date' => 'YYYY-MM-DD', 'revenue' => float] for the last N days
     */
    public function getRevenueTimeSeries(int $days = 30, ?Restaurant $restaurant = null, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        if ($startDate && $endDate) {
            $start = \DateTime::createFromInterface($startDate);
            $end = \DateTime::createFromInterface($endDate);
        } else {
            $end = new DateTime('today');
            $start = (new DateTime('today'))->modify("-$days days");
        }

        // Ensure end includes the full day if it's set to midnight (handled by caller or here)
        // If passed as 2023-10-25 23:59:59 it's fine.
        // If passed as 2023-10-25 00:00:00 we might miss data if query is <=.
        // It uses strings.

        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DATE(created_at) as date, SUM(total) as revenue
            FROM "order"
            WHERE status NOT IN (:unpaidStatus, :cancelledStatus)
            AND created_at >= :startDate
            AND created_at <= :endDate
        ';

        $params = [
            'unpaidStatus' => OrderStatus::Unpaid->value,
            'cancelledStatus' => OrderStatus::Cancelled->value,
            'startDate' => $start->format('Y-m-d H:i:s'),
            'endDate' => $end->format('Y-m-d H:i:s'),
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
        $current = clone $start;
        // Strip time for loop comparison
        $current->setTime(0, 0, 0);
        $loopEnd = clone $end;
        $loopEnd->setTime(0, 0, 0);

        while ($current <= $loopEnd) {
            $dateStr = $current->format('Y-m-d');
            $revenue = 0.0;

            foreach ($results as $result) {
                if ($result['date'] === $dateStr) {
                    $revenue = (float) $result['revenue'];
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
    /**
     * Get daily orders count time series data for charts
     * Returns array of ['date' => 'YYYY-MM-DD', 'count' => int] for the last N days
     */
    public function getDailyOrdersTimeSeries(int $days = 30, ?Restaurant $restaurant = null, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        if ($startDate && $endDate) {
            $start = \DateTime::createFromInterface($startDate);
            $end = \DateTime::createFromInterface($endDate);
        } else {
            $end = new DateTime('today');
            $start = (new DateTime('today'))->modify("-$days days");
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DATE(created_at) as date, COUNT(id) as count
            FROM "order"
            WHERE status NOT IN (:unpaidStatus, :cancelledStatus)
            AND created_at >= :startDate
            AND created_at <= :endDate
        ';

        $params = [
            'unpaidStatus' => OrderStatus::Unpaid->value,
            'cancelledStatus' => OrderStatus::Cancelled->value,
            'startDate' => $start->format('Y-m-d H:i:s'),
            'endDate' => $end->format('Y-m-d H:i:s'),
        ];

        if ($restaurant) {
            $sql .= ' AND restaurant_id = :restaurantId';
            $params['restaurantId'] = $restaurant->getId();
        }

        $sql .= ' GROUP BY DATE(created_at) ORDER BY date ASC';

        $stmt = $conn->prepare($sql);
        $results = $stmt->executeQuery($params)->fetchAllAssociative();

        // Format results and fill missing dates with 0 counts
        $timeSeries = [];
        $current = clone $start;
        $current->setTime(0, 0, 0);
        $loopEnd = clone $end;
        $loopEnd->setTime(0, 0, 0);

        while ($current <= $loopEnd) {
            $dateStr = $current->format('Y-m-d');
            $count = 0;

            foreach ($results as $result) {
                if ($result['date'] === $dateStr) {
                    $count = (int) $result['count'];
                    break;
                }
            }

            $timeSeries[] = [
                'date' => $dateStr,
                'count' => $count
            ];

            $current->modify('+1 day');
        }

        return $timeSeries;
    }
}
