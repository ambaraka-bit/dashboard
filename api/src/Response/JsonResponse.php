<?php
// ============================================================
//  src/Response/JsonResponse.php
//  Standardised JSON output for every endpoint
// ============================================================
namespace App\Response;

class JsonResponse
{
    /**
     * Send a successful JSON response.
     *
     * @param mixed $data
     */
    public static function success(mixed $data, int $status = 200): never
    {
        self::send([
            'success'   => true,
            'timestamp' => date('c'),
            'data'      => $data,
        ], $status);
    }

    /**
     * Send an error JSON response.
     */
    public static function error(string $message, int $status = 400, mixed $debug = null): never
    {
        $body = [
            'success'   => false,
            'timestamp' => date('c'),
            'error'     => [
                'code'    => $status,
                'message' => $message,
            ],
        ];

        // Only expose debug info outside production
        if ($debug !== null && ($_ENV['APP_ENV'] ?? 'production') !== 'production') {
            $body['error']['debug'] = $debug;
        }

        self::send($body, $status);
    }

    /**
     * Send raw headers + body then exit.
     *
     * @param array<string, mixed> $body
     */
    private static function send(array $body, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
