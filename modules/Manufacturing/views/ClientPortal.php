<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'Api/V8/Middleware/AuthMiddleware.php';
require_once 'include/database/DBManager.php';

use Api\V8\Middleware\AuthMiddleware;

class ClientPortal
{
    private AuthMiddleware $authMiddleware;
    private DBManager $db;
    private array $currentUser;
    private string $clientAccountId;

    public function __construct()
    {
        $this->db = DBManager::getInstance();
        $this->authMiddleware = new AuthMiddleware($this->db->getConnection());
        
        // Authenticate and authorize
        $this->currentUser = $this->authMiddleware->protect('orders', 'READ', '/client-portal');
        
        // Verify user has client role
        if ($this->currentUser['primary_role'] !== 'client') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: Client role required']);
            exit;
        }
        
        // Get client's account ID
        $this->clientAccountId = $this->getClientAccountId();
    }

    /**
     * Main portal display
     */
    public function display()
    {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                $this->displayDashboard();
                break;
            case 'orders':
                $this->displayOrders();
                break;
            case 'order_details':
                $this->displayOrderDetails($_GET['order_id'] ?? '');
                break;
            case 'reorder':
                $this->handleReorder();
                break;
            case 'invoices':
                $this->displayInvoices();
                break;
            case 'download_invoice':
                $this->downloadInvoice($_GET['invoice_id'] ?? '');
                break;
            case 'account_info':
                $this->displayAccountInfo();
                break;
            case 'support':
                $this->displaySupport();
                break;
            default:
                $this->displayDashboard();
        }
    }

    /**
     * Display client dashboard
     */
    private function displayDashboard()
    {
        $dashboardData = [
            'account_info' => $this->getAccountInfo(),
            'recent_orders' => $this->getRecentOrders(5),
            'order_summary' => $this->getOrderSummary(),
            'quick_reorder' => $this->getQuickReorderItems(),
            'notifications' => $this->getClientNotifications(),
            'support_tickets' => $this->getRecentSupportTickets()
        ];
        
        $this->sendJsonResponse(['success' => true, 'data' => $dashboardData]);
    }

    /**
     * Display order history and tracking
     */
    private function displayOrders()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $orders = $this->getOrderHistory($limit, $offset);
        $totalOrders = $this->getOrderCount();
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalOrders / $limit),
                    'total_orders' => $totalOrders,
                    'per_page' => $limit
                ]
            ]
        ]);
    }

    /**
     * Display detailed order information
     */
    private function displayOrderDetails(string $orderId)
    {
        if (empty($orderId)) {
            $this->sendError('Order ID required', 400);
            return;
        }
        
        $orderDetails = $this->getOrderDetails($orderId);
        
        if (!$orderDetails) {
            $this->sendError('Order not found', 404);
            return;
        }
        
        // Verify order belongs to client
        if ($orderDetails['billing_account_id'] !== $this->clientAccountId) {
            $this->sendError('Access denied', 403);
            return;
        }
        
        $orderDetails['timeline'] = $this->getOrderTimeline($orderId);
        $orderDetails['items'] = $this->getOrderItems($orderId);
        $orderDetails['shipping_info'] = $this->getShippingInfo($orderId);
        
        $this->sendJsonResponse(['success' => true, 'data' => $orderDetails]);
    }

    /**
     * Handle reorder requests
     */
    private function handleReorder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('POST method required', 405);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['order_id'] ?? '';
        $items = $input['items'] ?? [];
        
        if (empty($orderId) && empty($items)) {
            $this->sendError('Order ID or items required', 400);
            return;
        }
        
        try {
            if (!empty($orderId)) {
                // Reorder entire previous order
                $reorderResult = $this->reorderFromOrder($orderId);
            } else {
                // Reorder specific items
                $reorderResult = $this->reorderItems($items);
            }
            
            $this->sendJsonResponse(['success' => true, 'data' => $reorderResult]);
            
        } catch (Exception $e) {
            $this->sendError('Reorder failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display invoices
     */
    private function displayInvoices()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $invoices = $this->getInvoiceHistory($limit, $offset);
        $totalInvoices = $this->getInvoiceCount();
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalInvoices / $limit),
                    'total_invoices' => $totalInvoices,
                    'per_page' => $limit
                ]
            ]
        ]);
    }

    /**
     * Download invoice PDF
     */
    private function downloadInvoice(string $invoiceId)
    {
        if (empty($invoiceId)) {
            $this->sendError('Invoice ID required', 400);
            return;
        }
        
        $invoice = $this->getInvoiceDetails($invoiceId);
        
        if (!$invoice || $invoice['billing_account_id'] !== $this->clientAccountId) {
            $this->sendError('Invoice not found or access denied', 404);
            return;
        }
        
        $pdfPath = $this->generateInvoicePDF($invoice);
        
        if (!$pdfPath || !file_exists($pdfPath)) {
            $this->sendError('Unable to generate PDF', 500);
            return;
        }
        
        // Send PDF file
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice_' . $invoice['invoice_number'] . '.pdf"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        
        // Clean up temporary file
        unlink($pdfPath);
    }

    /**
     * Display account information
     */
    private function displayAccountInfo()
    {
        $accountInfo = $this->getDetailedAccountInfo();
        $contractInfo = $this->getContractInfo();
        $pricingInfo = $this->getPricingTierInfo();
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => [
                'account' => $accountInfo,
                'contract' => $contractInfo,
                'pricing' => $pricingInfo
            ]
        ]);
    }

    /**
     * Display support section
     */
    private function displaySupport()
    {
        $tickets = $this->getSupportTickets();
        $faq = $this->getFAQItems();
        $contacts = $this->getSupportContacts();
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => [
                'tickets' => $tickets,
                'faq' => $faq,
                'contacts' => $contacts
            ]
        ]);
    }

    /**
     * Get client's account ID
     */
    private function getClientAccountId(): string
    {
        $userId = $this->currentUser['user_id'];
        
        // Look up account ID for client user
        $query = "
            SELECT account_id FROM mfg_client_users 
            WHERE user_id = ? AND deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['account_id'] ?? '';
    }

    /**
     * Get basic account information
     */
    private function getAccountInfo(): array
    {
        $query = "
            SELECT a.name, a.billing_address_street, a.billing_address_city,
                   a.billing_address_state, a.billing_address_postalcode,
                   a.phone_office, a.email1, mcc.pricing_tier
            FROM accounts a
            LEFT JOIN mfg_client_contracts mcc ON a.id = mcc.account_id
            WHERE a.id = ? AND a.deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get recent orders
     */
    private function getRecentOrders(int $limit = 10): array
    {
        $query = "
            SELECT op.id, op.order_number, op.stage_name, op.total_amount,
                   op.created_date, op.expected_delivery_date,
                   (SELECT COUNT(*) FROM mfg_order_items oi WHERE oi.order_id = op.id) as item_count
            FROM mfg_order_pipeline op
            WHERE op.billing_account_id = ? AND op.deleted = 0
            ORDER BY op.created_date DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId, $limit]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as &$order) {
            $order['status_badge'] = $this->getOrderStatusBadge($order['stage_name']);
        }
        
        return $orders;
    }

    /**
     * Get order summary statistics
     */
    private function getOrderSummary(): array
    {
        $query = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_spent,
                COUNT(CASE WHEN stage_name IN ('delivered', 'completed') THEN 1 END) as completed_orders,
                COUNT(CASE WHEN stage_name NOT IN ('delivered', 'completed', 'cancelled') THEN 1 END) as active_orders,
                AVG(total_amount) as avg_order_value
            FROM mfg_order_pipeline
            WHERE billing_account_id = ? AND deleted = 0
            AND created_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_orders' => (int)$summary['total_orders'],
            'total_spent' => (float)$summary['total_spent'],
            'completed_orders' => (int)$summary['completed_orders'],
            'active_orders' => (int)$summary['active_orders'],
            'avg_order_value' => round((float)$summary['avg_order_value'], 2)
        ];
    }

    /**
     * Get frequently ordered items for quick reorder
     */
    private function getQuickReorderItems(): array
    {
        $query = "
            SELECT p.id, p.sku, p.name, p.base_price,
                   COUNT(*) as order_frequency,
                   MAX(oi.created_date) as last_ordered,
                   AVG(oi.quantity) as avg_quantity
            FROM mfg_order_items oi
            JOIN mfg_products p ON oi.product_id = p.id
            JOIN mfg_order_pipeline op ON oi.order_id = op.id
            WHERE op.billing_account_id = ?
            AND oi.created_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            AND oi.deleted = 0 AND op.deleted = 0
            GROUP BY p.id
            ORDER BY order_frequency DESC, last_ordered DESC
            LIMIT 8
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get client notifications
     */
    private function getClientNotifications(): array
    {
        // Order status updates
        $orderUpdates = $this->getOrderStatusUpdates();
        
        // Payment reminders
        $paymentReminders = $this->getPaymentReminders();
        
        // Shipping notifications
        $shippingUpdates = $this->getShippingUpdates();
        
        return array_merge($orderUpdates, $paymentReminders, $shippingUpdates);
    }

    /**
     * Get complete order history
     */
    private function getOrderHistory(int $limit, int $offset): array
    {
        $query = "
            SELECT op.id, op.order_number, op.stage_name, op.total_amount,
                   op.created_date, op.expected_delivery_date,
                   (SELECT COUNT(*) FROM mfg_order_items oi WHERE oi.order_id = op.id) as item_count,
                   op.notes
            FROM mfg_order_pipeline op
            WHERE op.billing_account_id = ? AND op.deleted = 0
            ORDER BY op.created_date DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId, $limit, $offset]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as &$order) {
            $order['status_badge'] = $this->getOrderStatusBadge($order['stage_name']);
            $order['can_reorder'] = in_array($order['stage_name'], ['delivered', 'completed']);
        }
        
        return $orders;
    }

    /**
     * Get total order count
     */
    private function getOrderCount(): int
    {
        $query = "
            SELECT COUNT(*) as total
            FROM mfg_order_pipeline
            WHERE billing_account_id = ? AND deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    }

    /**
     * Get detailed order information
     */
    private function getOrderDetails(string $orderId): ?array
    {
        $query = "
            SELECT op.*, u.first_name, u.last_name
            FROM mfg_order_pipeline op
            LEFT JOIN users u ON op.assigned_user_id = u.id
            WHERE op.id = ? AND op.deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$orderId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get order timeline/history
     */
    private function getOrderTimeline(string $orderId): array
    {
        $query = "
            SELECT osh.stage_name, osh.previous_stage, osh.created_date,
                   osh.notes, u.first_name, u.last_name
            FROM mfg_order_stage_history osh
            LEFT JOIN users u ON osh.updated_by = u.id
            WHERE osh.order_id = ? AND osh.deleted = 0
            ORDER BY osh.created_date ASC
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get order items
     */
    private function getOrderItems(string $orderId): array
    {
        $query = "
            SELECT oi.*, p.name, p.sku, p.description
            FROM mfg_order_items oi
            JOIN mfg_products p ON oi.product_id = p.id
            WHERE oi.order_id = ? AND oi.deleted = 0
            ORDER BY oi.line_number
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reorder from previous order
     */
    private function reorderFromOrder(string $orderId): array
    {
        // Verify order belongs to client
        $order = $this->getOrderDetails($orderId);
        if (!$order || $order['billing_account_id'] !== $this->clientAccountId) {
            throw new Exception('Order not found or access denied');
        }
        
        $items = $this->getOrderItems($orderId);
        
        return $this->createReorder($items);
    }

    /**
     * Reorder specific items
     */
    private function reorderItems(array $items): array
    {
        // Validate items and add current pricing
        $validatedItems = [];
        
        foreach ($items as $item) {
            $product = $this->getProductForReorder($item['product_id'], $item['quantity']);
            if ($product) {
                $validatedItems[] = array_merge($item, $product);
            }
        }
        
        return $this->createReorder($validatedItems);
    }

    /**
     * Create reorder (quote or cart)
     */
    private function createReorder(array $items): array
    {
        if (empty($items)) {
            throw new Exception('No valid items for reorder');
        }
        
        // Create a quote/cart for the reorder
        $quoteId = $this->generateUUID();
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Create quote
            $insertQuote = "
                INSERT INTO mfg_quotes (id, quote_number, billing_account_id, created_date, status, client_name)
                VALUES (?, ?, ?, NOW(), 'draft', ?)
            ";
            
            $quoteNumber = 'RO-' . date('Ymd') . '-' . substr($quoteId, 0, 6);
            $accountInfo = $this->getAccountInfo();
            
            $stmt = $this->db->getConnection()->prepare($insertQuote);
            $stmt->execute([$quoteId, $quoteNumber, $this->clientAccountId, $accountInfo['name']]);
            
            // Add quote items
            $totalAmount = 0;
            foreach ($items as $index => $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;
                
                $insertItem = "
                    INSERT INTO mfg_quote_items (id, quote_id, product_id, quantity, unit_price, line_total, line_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ";
                
                $stmt = $this->db->getConnection()->prepare($insertItem);
                $stmt->execute([
                    $this->generateUUID(),
                    $quoteId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $lineTotal,
                    $index + 1
                ]);
            }
            
            // Update quote total
            $updateTotal = "UPDATE mfg_quotes SET total_amount = ? WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($updateTotal);
            $stmt->execute([$totalAmount, $quoteId]);
            
            $this->db->getConnection()->commit();
            
            return [
                'quote_id' => $quoteId,
                'quote_number' => $quoteNumber,
                'total_amount' => $totalAmount,
                'item_count' => count($items),
                'message' => 'Reorder created successfully. Your sales representative will contact you to confirm.'
            ];
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * Get invoice history
     */
    private function getInvoiceHistory(int $limit, int $offset): array
    {
        $query = "
            SELECT i.id, i.invoice_number, i.total_amount, i.created_date,
                   i.due_date, i.payment_status, i.payment_date,
                   op.order_number
            FROM mfg_invoices i
            LEFT JOIN mfg_order_pipeline op ON i.order_id = op.id
            WHERE i.billing_account_id = ? AND i.deleted = 0
            ORDER BY i.created_date DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$this->clientAccountId, $limit, $offset]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($invoices as &$invoice) {
            $invoice['status_badge'] = $this->getPaymentStatusBadge($invoice['payment_status']);
            $invoice['is_overdue'] = $this->isInvoiceOverdue($invoice['due_date'], $invoice['payment_status']);
        }
        
        return $invoices;
    }

    /**
     * Helper methods
     */
    
    private function getOrderStatusBadge(string $status): array
    {
        $badges = [
            'quote_sent' => ['color' => 'info', 'text' => 'Quote Sent'],
            'quote_accepted' => ['color' => 'success', 'text' => 'Quote Accepted'],
            'order_confirmed' => ['color' => 'primary', 'text' => 'Order Confirmed'],
            'in_production' => ['color' => 'warning', 'text' => 'In Production'],
            'ready_to_ship' => ['color' => 'info', 'text' => 'Ready to Ship'],
            'shipped' => ['color' => 'primary', 'text' => 'Shipped'],
            'delivered' => ['color' => 'success', 'text' => 'Delivered'],
            'completed' => ['color' => 'success', 'text' => 'Completed'],
            'cancelled' => ['color' => 'danger', 'text' => 'Cancelled']
        ];
        
        return $badges[$status] ?? ['color' => 'secondary', 'text' => ucfirst($status)];
    }

    private function getPaymentStatusBadge(string $status): array
    {
        $badges = [
            'pending' => ['color' => 'warning', 'text' => 'Pending'],
            'paid' => ['color' => 'success', 'text' => 'Paid'],
            'overdue' => ['color' => 'danger', 'text' => 'Overdue'],
            'cancelled' => ['color' => 'secondary', 'text' => 'Cancelled']
        ];
        
        return $badges[$status] ?? ['color' => 'secondary', 'text' => ucfirst($status)];
    }

    private function isInvoiceOverdue(string $dueDate, string $paymentStatus): bool
    {
        return $paymentStatus !== 'paid' && strtotime($dueDate) < time();
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function sendJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function sendError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['error' => $message, 'code' => $code]);
    }

    // Placeholder methods for additional functionality
    private function getShippingInfo(string $orderId): array { return []; }
    private function getOrderStatusUpdates(): array { return []; }
    private function getPaymentReminders(): array { return []; }
    private function getShippingUpdates(): array { return []; }
    private function getRecentSupportTickets(): array { return []; }
    private function getInvoiceCount(): int { return 0; }
    private function getInvoiceDetails(string $invoiceId): ?array { return null; }
    private function generateInvoicePDF(array $invoice): ?string { return null; }
    private function getDetailedAccountInfo(): array { return []; }
    private function getContractInfo(): array { return []; }
    private function getPricingTierInfo(): array { return []; }
    private function getSupportTickets(): array { return []; }
    private function getFAQItems(): array { return []; }
    private function getSupportContacts(): array { return []; }
    private function getProductForReorder(string $productId, int $quantity): ?array { return null; }
}

// Handle the request
$portal = new ClientPortal();
$portal->display();
