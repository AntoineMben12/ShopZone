<?php
// api/payment.php — Payment gateway stub / webhook receiver
// Supports: Stripe, PayPal, Mobile Money (MTN/Orange)
// Replace the placeholder logic with real SDK calls for production.

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config/database.php';
require_once '../includes/auth.php';

// ── CORS (adjust origin for production) ─────────────────────
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Helper ───────────────────────────────────────────────────
function jsonResponse(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ── Route ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

match ($action) {
    'stripe_intent'    => handleStripeIntent(),
    'paypal_capture'   => handlePaypalCapture(),
    'mobile_pay'       => handleMobilePay(),
    'webhook_stripe'   => handleStripeWebhook(),
    'verify_order'     => verifyOrder(),
    default            => jsonResponse(400, ['error' => 'Unknown action']),
};

// ── Stripe: Create Payment Intent ────────────────────────────
function handleStripeIntent(): void {
    requireLogin();
    $db      = getDB();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $order   = getOrder($db, $orderId);

    if (!$order) jsonResponse(404, ['error' => 'Order not found']);

    /*
     * PRODUCTION: Replace with real Stripe SDK call:
     *
     * require_once '../vendor/autoload.php';
     * \Stripe\Stripe::setApiKey('sk_live_YOUR_SECRET_KEY');
     * $intent = \Stripe\PaymentIntent::create([
     *     'amount'   => (int)($order['total_price'] * 100),
     *     'currency' => 'usd',
     *     'metadata' => ['order_id' => $orderId],
     * ]);
     * jsonResponse(200, ['client_secret' => $intent->client_secret]);
     */

    // Demo response
    jsonResponse(200, [
        'client_secret' => 'pi_demo_' . bin2hex(random_bytes(16)) . '_secret_demo',
        'amount'        => (int)($order['total_price'] * 100),
        'currency'      => 'usd',
        'order_id'      => $orderId,
    ]);
}

// ── PayPal: Capture Order ─────────────────────────────────────
function handlePaypalCapture(): void {
    requireLogin();
    $db      = getDB();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $order   = getOrder($db, $orderId);

    if (!$order) jsonResponse(404, ['error' => 'Order not found']);

    /*
     * PRODUCTION: Replace with PayPal SDK:
     *
     * use PayPalCheckoutSdk\Core\PayPalHttpClient;
     * use PayPalCheckoutSdk\Core\SandboxEnvironment;
     * use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
     *
     * $environment = new SandboxEnvironment('CLIENT_ID', 'CLIENT_SECRET');
     * $client      = new PayPalHttpClient($environment);
     * $request     = new OrdersCaptureRequest($_POST['paypal_order_id']);
     * $response    = $client->execute($request);
     * if ($response->result->status === 'COMPLETED') { ... mark paid ... }
     */

    // Demo: mark as paid
    $db->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")
       ->execute([$orderId]);

    jsonResponse(200, ['status' => 'COMPLETED', 'order_id' => $orderId]);
}

// ── Mobile Money (MTN/Orange/Wave) ───────────────────────────
function handleMobilePay(): void {
    requireLogin();
    $db      = getDB();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $phone   = trim($_POST['phone'] ?? '');
    $network = trim($_POST['network'] ?? 'mtn');
    $order   = getOrder($db, $orderId);

    if (!$order)  jsonResponse(404, ['error' => 'Order not found']);
    if (!$phone)  jsonResponse(422, ['error' => 'Phone number required']);

    /*
     * PRODUCTION: Integrate with CinetPay, Fedapay, or MTN MoMo API:
     *
     * $response = Http::post('https://api.cinetpay.com/v2/payment', [
     *     'apikey'        => env('CINETPAY_API_KEY'),
     *     'site_id'       => env('CINETPAY_SITE_ID'),
     *     'transaction_id'=> 'TXN-' . $orderId,
     *     'amount'        => $order['total_price'],
     *     'currency'      => 'XAF',
     *     'customer_phone_number' => $phone,
     *     'payment_method'=> strtoupper($network),
     * ]);
     */

    // Demo: simulate pending → success flow
    $txRef = strtoupper($network) . '-' . date('Ymd') . '-' . $orderId . '-' . rand(1000,9999);

    jsonResponse(200, [
        'status'   => 'pending',
        'tx_ref'   => $txRef,
        'message'  => "A payment prompt has been sent to $phone. Please approve in your mobile money app.",
        'order_id' => $orderId,
    ]);
}

// ── Stripe Webhook ────────────────────────────────────────────
function handleStripeWebhook(): void {
    $payload   = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $secret    = 'whsec_YOUR_WEBHOOK_SECRET';   // ← replace in production

    /*
     * PRODUCTION:
     * \Stripe\Stripe::setApiKey('sk_live_...');
     * try {
     *     $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
     * } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
     *     jsonResponse(400, ['error' => 'Webhook error']);
     * }
     *
     * if ($event->type === 'payment_intent.succeeded') {
     *     $orderId = $event->data->object->metadata->order_id;
     *     getDB()->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")
     *            ->execute([$orderId]);
     * }
     */

    jsonResponse(200, ['received' => true]);
}

// ── Verify Order ──────────────────────────────────────────────
function verifyOrder(): void {
    requireLogin();
    $db      = getDB();
    $orderId = (int)($_GET['order_id'] ?? 0);
    $order   = getOrder($db, $orderId);

    if (!$order) jsonResponse(404, ['error' => 'Order not found']);

    jsonResponse(200, [
        'order_id'       => $order['id'],
        'status'         => $order['status'],
        'payment_status' => $order['payment_status'],
        'total'          => $order['total_price'],
    ]);
}

// ── Utilities ─────────────────────────────────────────────────
function getOrder(PDO $db, int $id): ?array {
    $st = $db->prepare("SELECT * FROM orders WHERE id=?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        jsonResponse(401, ['error' => 'Authentication required']);
    }
}