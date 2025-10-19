<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ApiResponseTrait
{
    /**
     * Generate standardized success response
     */
    protected function successResponse($data = null, int $httpStatus = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $data,
            'trace' => [
                'request_id' => $this->generateRequestId(),
                'ts' => now()->toISOString(),
            ]
        ], $httpStatus);
    }

    /**
     * Generate standardized error response
     */
    protected function errorResponse(
        string $code,
        string $message,
        ?string $hint = null,
        array $details = [],
        int $httpStatus = 400
    ): JsonResponse {
        return response()->json([
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'hint' => $hint,
                'details' => $details,
            ],
            'trace' => [
                'request_id' => $this->generateRequestId(),
                'ts' => now()->toISOString(),
            ]
        ], $httpStatus);
    }

    /**
     * Generate request ID for tracing
     */
    protected function generateRequestId(): string
    {
        return 'req_' . bin2hex(random_bytes(8)) . '_' . time();
    }

    /**
     * Map TikTok API errors to standardized error codes
     */
    protected function mapTikTokError(int $httpStatus, ?string $errorCode = null, ?string $errorMessage = null): array
    {
        switch ($httpStatus) {
            case 400:
                if (in_array($errorCode, ['invalid_grant', 'invalid_code'])) {
                    return [
                        'code' => 'OAUTH_EXCHANGE_FAILED',
                        'message' => 'Không đổi được mã ủy quyền sang token.',
                        'hint' => 'Kiểm tra Redirect URL và quyền app trên console.',
                        'details' => [
                            'provider_http_status' => $httpStatus,
                            'provider_error_code' => $errorCode,
                            'provider_error_msg' => $errorMessage,
                        ]
                    ];
                }
                return [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Tham số không hợp lệ.',
                    'hint' => 'Kiểm tra lại các tham số gửi lên.',
                    'details' => [
                        'provider_http_status' => $httpStatus,
                        'provider_error_code' => $errorCode,
                        'provider_error_msg' => $errorMessage,
                    ]
                ];

            case 401:
                return [
                    'code' => 'TOKEN_REFRESH_FAILED',
                    'message' => 'Token không hợp lệ hoặc đã hết hạn.',
                    'hint' => 'Yêu cầu người bán ủy quyền lại.',
                    'details' => [
                        'provider_http_status' => $httpStatus,
                        'provider_error_code' => $errorCode,
                        'provider_error_msg' => $errorMessage,
                    ]
                ];

            case 403:
                if ($errorCode === 'insufficient_scope') {
                    return [
                        'code' => 'OAUTH_SCOPE_FILTERED',
                        'message' => 'Quyền truy cập bị TikTok lọc khi ủy quyền.',
                        'hint' => 'Hoàn tất review package liên quan trong Developer Console.',
                        'details' => [
                            'provider_http_status' => $httpStatus,
                            'provider_error_code' => $errorCode,
                            'provider_error_msg' => $errorMessage,
                        ]
                    ];
                }
                return [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Không có quyền truy cập.',
                    'hint' => 'Kiểm tra lại thông tin xác thực.',
                    'details' => [
                        'provider_http_status' => $httpStatus,
                        'provider_error_code' => $errorCode,
                        'provider_error_msg' => $errorMessage,
                    ]
                ];

            case 429:
                return [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Bị giới hạn tần suất truy cập.',
                    'hint' => 'Vui lòng thử lại sau.',
                    'details' => [
                        'provider_http_status' => $httpStatus,
                        'provider_error_code' => $errorCode,
                        'provider_error_msg' => $errorMessage,
                    ]
                ];

            case 500:
            case 502:
            case 503:
            case 504:
                return [
                    'code' => 'PROVIDER_API_ERROR',
                    'message' => 'TikTok Shop API trả lỗi.',
                    'hint' => 'Xem chi tiết trong log và trace.request_id.',
                    'details' => [
                        'provider_http_status' => $httpStatus,
                        'provider_error_code' => $errorCode,
                        'provider_error_msg' => $errorMessage,
                    ]
                ];

            default:
                return [
                    'code' => 'PROVIDER_API_ERROR',
                    'message' => 'Lỗi không xác định từ TikTok Shop API.',
                    'hint' => 'Xem chi tiết trong log và trace.request_id.',
                    'details' => [
                        'provider_http_status' => $httpStatus,
                        'provider_error_code' => $errorCode,
                        'provider_error_msg' => $errorMessage,
                    ]
                ];
        }
    }

    /**
     * Handle TikTok API response and return appropriate error
     */
    protected function handleTikTokError(int $httpStatus, array $responseData = []): JsonResponse
    {
        $errorCode = $responseData['error'] ?? $responseData['error_code'] ?? null;
        $errorMessage = $responseData['error_description'] ?? $responseData['error_message'] ?? $responseData['message'] ?? null;

        $errorMapping = $this->mapTikTokError($httpStatus, $errorCode, $errorMessage);

        return $this->errorResponse(
            $errorMapping['code'],
            $errorMapping['message'],
            $errorMapping['hint'],
            $errorMapping['details'],
            $httpStatus >= 500 ? 500 : 400
        );
    }
}
