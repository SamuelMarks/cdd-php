<?php

/**
 * Emits the ServerRunner CLI class for generated server projects.
 *
 * @package Cdd\ServerCli
 */

declare(strict_types=1);

namespace Cdd\ServerCli;

/**
 * Emits the Server CLI runner class.
 *
 * @param string $namespace Optional PHP namespace for the generated ServerRunner class.
 * @return string The generated ServerRunner class.
 */
function emit(string $namespace = 'Api'): string
{
    $bs = chr(92);
    $out = "<?php

/**
 * Auto-generated Server Runner, Middlewares, Identity Provider, and Webhook Dispatcher.
 *
 * @package Api
 */

declare(strict_types=1);

";
    if ($namespace !== '') {
        $out .= "namespace {$namespace};\n\n";
        $out .= "use Api{$bs}Database{$bs}DatabaseConnection;\n";
        $out .= "use Api{$bs}Seeder{$bs}DatabaseSeeder;\n";
        $out .= "use Api{$bs}Daos{$bs}DaoFactory;\n\n";
    }
    $out .= "/**
 * Global CORS middleware with permissive defaults for local UI development.
 */
class CorsMiddleware {
    /**
     * Handles incoming HTTP requests and attaches permissive CORS headers.
     *
     * @param string \$method HTTP request method.
     * @param array<string, string> \$requestHeaders Incoming HTTP request headers.
     * @return array{status: int, preflight: bool, headers: array<string, string>, body: string} CORS response descriptor.
     */
    public static function handle(string \$method = 'GET', array \$requestHeaders = []): array {
        \$headers = [
            'Access-Control-Allow-Origin' => \$requestHeaders['Origin'] ?? '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Api-Key, X-Mock-Scopes',
            'Access-Control-Max-Age' => '86400',
        ];
        if (strtoupper(\$method) === 'OPTIONS') {
            return [
                'status' => 204,
                'preflight' => true,
                'headers' => \$headers,
                'body' => '',
            ];
        }
        return [
            'status' => 200,
            'preflight' => false,
            'headers' => \$headers,
            'body' => '',
        ];
    }
}

/**
 * Strict request validation middleware that validates incoming payloads against OpenAPI/MCP schemas.
 */
class ValidationMiddleware {
    /**
     * Validates an incoming request payload against an OpenAPI schema definition.
     *
     * @param array<string, mixed> \$payload Request body payload.
     * @param array<string, mixed> \$schema OpenAPI schema definition.
     * @param bool \$strict Whether strict validation mode is enabled.
     * @return array{valid: bool, status: int, error?: string, errors: array<int, string>} Validation result.
     */
    public static function validateRequest(array \$payload, array \$schema, bool \$strict = true): array {
        if (!\$strict) {
            return ['valid' => true, 'status' => 200, 'errors' => []];
        }
        \$errors = [];
        \$required = \$schema['required'] ?? [];
        if (is_array(\$required)) {
            foreach (\$required as \$reqField) {
                \$fieldStr = (string) \$reqField;
                if (!array_key_exists(\$fieldStr, \$payload)) {
                    \$errors[] = \"Field '{\$fieldStr}' is required\";
                }
            }
        }
        \$properties = \$schema['properties'] ?? [];
        if (is_array(\$properties)) {
            foreach (\$properties as \$propName => \$propSpec) {
                if (!array_key_exists(\$propName, \$payload) || !is_array(\$propSpec)) {
                    continue;
                }
                \$val = \$payload[\$propName];
                \$expectedType = (string) (\$propSpec['type'] ?? '');
                if (\$expectedType === 'string' && !is_string(\$val)) {
                    \$errors[] = \"Field '{\$propName}' must be of type 'string'\";
                    continue;
                }
                if (\$expectedType === 'integer' && !is_int(\$val)) {
                    \$errors[] = \"Field '{\$propName}' must be of type 'integer'\";
                    continue;
                }
                if (\$expectedType === 'number' && !is_int(\$val) && !is_float(\$val)) {
                    \$errors[] = \"Field '{\$propName}' must be of type 'number'\";
                    continue;
                }
                if (\$expectedType === 'boolean' && !is_bool(\$val)) {
                    \$errors[] = \"Field '{\$propName}' must be of type 'boolean'\";
                    continue;
                }
                if (\$expectedType === 'array' && !is_array(\$val)) {
                    \$errors[] = \"Field '{\$propName}' must be of type 'array'\";
                    continue;
                }
                if (is_string(\$val)) {
                    \$format = (string) (\$propSpec['format'] ?? '');
                    if (\$format === 'email' && filter_var(\$val, FILTER_VALIDATE_EMAIL) === false) {
                        \$errors[] = \"Field '{\$propName}' must match format 'email'\";
                    } elseif (\$format === 'uri' && filter_var(\$val, FILTER_VALIDATE_URL) === false) {
                        \$errors[] = \"Field '{\$propName}' must match format 'uri'\";
                    }
                    if (isset(\$propSpec['maxLength']) && strlen(\$val) > (int) \$propSpec['maxLength']) {
                        \$errors[] = \"Field '{\$propName}' exceeds maximum length\";
                    }
                    if (isset(\$propSpec['minLength']) && strlen(\$val) < (int) \$propSpec['minLength']) {
                        \$errors[] = \"Field '{\$propName}' is shorter than minimum length\";
                    }
                }
                if (is_array(\$val)) {
                    if (isset(\$propSpec['maxItems']) && count(\$val) > (int) \$propSpec['maxItems']) {
                        \$errors[] = \"Array '{\$propName}' exceeds maximum length\";
                    }
                    if (isset(\$propSpec['minItems']) && count(\$val) < (int) \$propSpec['minItems']) {
                        \$errors[] = \"Array '{\$propName}' has fewer than minimum items\";
                    }
                }
                if ((is_int(\$val) || is_float(\$val))) {
                    if (isset(\$propSpec['maximum']) && \$val > \$propSpec['maximum']) {
                        \$errors[] = \"Field '{\$propName}' exceeds maximum\";
                    }
                    if (isset(\$propSpec['minimum']) && \$val < \$propSpec['minimum']) {
                        \$errors[] = \"Field '{\$propName}' is less than minimum\";
                    }
                }
                if (isset(\$propSpec['enum']) && is_array(\$propSpec['enum']) && !in_array(\$val, \$propSpec['enum'], true)) {
                    \$errors[] = \"Field '{\$propName}' must be one of allowed enum values\";
                }
            }
        }
        if (!empty(\$errors)) {
            return [
                'valid' => false,
                'status' => 400,
                'error' => 'Bad Request',
                'errors' => \$errors,
            ];
        }
        return ['valid' => true, 'status' => 200, 'errors' => []];
    }
}

/**
 * Integrated Identity Provider (IdP) sharing the main server DAO/ORM architecture.
 */
class IdentityProvider {
    /** @var string Schema model name used for User persistence. */
    private string \$userSchema;

    /** @var bool Whether to use an ephemeral database DAO. */
    private bool \$isEphemeral;

    /** @var array<string, array{user_id: int|string, username: string, password_hash: string}> Registered credentials index. */
    private array \$credentials = [];

    /** @var array<string, int|string> Active access tokens mapped to user IDs. */
    private array \$accessTokens = [];

    /** @var array<string, int|string> Active refresh tokens mapped to user IDs. */
    private array \$refreshTokens = [];

    /**
     * Initializes the Integrated Identity Provider.
     *
     * @param string \$userSchema The domain model schema name (e.g. 'User').
     * @param bool \$isEphemeral Whether ephemeral DB mode is active.
     */
    public function __construct(string \$userSchema = 'User', bool \$isEphemeral = true) {
        \$this->userSchema = \$userSchema;
        \$this->isEphemeral = \$isEphemeral;
    }

    /**
     * Resolves the shared domain DAO instance for user records.
     *
     * @return object The resolved DAO instance.
     * @throws {$bs}RuntimeException If DaoFactory is not available.
     */
    public function getDao(): object {
        if (class_exists(DaoFactory::class)) {
            return DaoFactory::create(\$this->userSchema, \$this->isEphemeral);
        }
        if (class_exists('DaoFactory')) {
            return {$bs}DaoFactory::create(\$this->userSchema, \$this->isEphemeral);
        }
        throw new {$bs}RuntimeException('DaoFactory not available');
    }

    /**
     * Registers a new user via the shared DAO (`POST /auth/register`).
     *
     * @param array<string, mixed> \$payload Registration request payload.
     * @return array{status: int, data: array<string, mixed>} Registration response.
     */
    public function register(array \$payload): array {
        \$username = (string) (\$payload['username'] ?? (\$payload['name'] ?? (\$payload['email'] ?? '')));
        \$password = (string) (\$payload['password'] ?? '');
        if (\$username === '' || \$password === '') {
            return ['status' => 400, 'data' => ['error' => 'Username and password are required']];
        }
        \$dao = \$this->getDao();
        \$daoData = \$payload;
        unset(\$daoData['password']);
        if (!isset(\$daoData['name'])) {
            \$daoData['name'] = \$username;
        }
        \$record = \$dao->create(\$daoData);
        \$userId = \$record->id ?? count(\$this->credentials) + 1;
        \$this->credentials[\$username] = [
            'user_id' => \$userId,
            'username' => \$username,
            'password_hash' => password_hash(\$password, PASSWORD_BCRYPT),
        ];
        \$accessToken = 'idp-token-' . bin2hex(random_bytes(8));
        \$refreshToken = 'idp-refresh-' . bin2hex(random_bytes(8));
        \$this->accessTokens[\$accessToken] = \$userId;
        \$this->refreshTokens[\$refreshToken] = \$userId;
        return [
            'status' => 201,
            'data' => [
                'id' => \$userId,
                'username' => \$username,
                'access_token' => \$accessToken,
                'refresh_token' => \$refreshToken,
                'token_type' => 'Bearer',
            ],
        ];
    }

    /**
     * Authenticates a user against the shared DAO and issues tokens (`POST /auth/login`).
     *
     * @param array<string, mixed> \$payload Login request payload.
     * @return array{status: int, data: array<string, mixed>} Login response.
     */
    public function login(array \$payload): array {
        \$username = (string) (\$payload['username'] ?? (\$payload['email'] ?? ''));
        \$password = (string) (\$payload['password'] ?? '');
        if (!isset(\$this->credentials[\$username]) || !password_verify(\$password, \$this->credentials[\$username]['password_hash'])) {
            return ['status' => 401, 'data' => ['error' => 'Invalid username or password']];
        }
        \$userId = \$this->credentials[\$username]['user_id'];
        \$dao = \$this->getDao();
        \$userRecord = \$dao->getById(\$userId);
        if (\$userRecord === null) {
            return ['status' => 401, 'data' => ['error' => 'User record no longer exists in DAO']];
        }
        \$accessToken = 'idp-token-' . bin2hex(random_bytes(8));
        \$refreshToken = 'idp-refresh-' . bin2hex(random_bytes(8));
        \$this->accessTokens[\$accessToken] = \$userId;
        \$this->refreshTokens[\$refreshToken] = \$userId;
        return [
            'status' => 200,
            'data' => [
                'access_token' => \$accessToken,
                'refresh_token' => \$refreshToken,
                'token_type' => 'Bearer',
                'user_id' => \$userId,
            ],
        ];
    }

    /**
     * Exchanges a valid refresh token for a new access and refresh token pair (`POST /auth/refresh`).
     *
     * @param array<string, mixed> \$payload Refresh request payload.
     * @return array{status: int, data: array<string, mixed>} Refresh response.
     */
    public function refresh(array \$payload): array {
        \$refreshToken = (string) (\$payload['refresh_token'] ?? '');
        if (\$refreshToken === '' || !isset(\$this->refreshTokens[\$refreshToken])) {
            return ['status' => 401, 'data' => ['error' => 'Invalid refresh token']];
        }
        \$userId = \$this->refreshTokens[\$refreshToken];
        unset(\$this->refreshTokens[\$refreshToken]);
        \$newAccess = 'idp-token-' . bin2hex(random_bytes(8));
        \$newRefresh = 'idp-refresh-' . bin2hex(random_bytes(8));
        \$this->accessTokens[\$newAccess] = \$userId;
        \$this->refreshTokens[\$newRefresh] = \$userId;
        return [
            'status' => 200,
            'data' => [
                'access_token' => \$newAccess,
                'refresh_token' => \$newRefresh,
                'token_type' => 'Bearer',
            ],
        ];
    }

    /**
     * Revokes an active access token (`POST /auth/logout`).
     *
     * @param string \$accessToken The Bearer access token to invalidate.
     * @return array{status: int, data: array<string, mixed>} Logout response.
     */
    public function logout(string \$accessToken): array {
        \$clean = preg_replace('/^Bearer\s+/i', '', trim(\$accessToken)) ?? '';
        unset(\$this->accessTokens[\$clean]);
        return [
            'status' => 200,
            'data' => ['logged_out' => true],
        ];
    }

    /**
     * Validates an access token and resolves the backing user entity from the Concrete DAO.
     *
     * @param string \$token Raw or Bearer-prefixed token string.
     * @return object|null The backing user entity from the DAO, or null if invalid.
     */
    public function validateToken(string \$token): ?object {
        \$clean = preg_replace('/^Bearer\s+/i', '', trim(\$token)) ?? '';
        if (!isset(\$this->accessTokens[\$clean])) {
            return null;
        }
        \$userId = \$this->accessTokens[\$clean];
        return \$this->getDao()->getById(\$userId);
    }
}

/**
 * Hybrid Authentication Middleware supporting both Mock Mode and Production Mode (Stateful ORM/DAO integration).
 */
class AuthMiddleware {
    /**
     * Validates incoming request credentials against OpenAPI security requirements.
     *
     * @param array<string, string> \$headers HTTP request headers.
     * @param array<string, mixed> \$queryParams Query string parameters.
     * @param array<int, array<string, array<int, string>>> \$securityRequirements Operation security requirements.
     * @param array<string, array<string, mixed>> \$securitySchemes Specification securitySchemes definitions.
     * @param bool \$enforceAuth Whether authentication enforcement is enabled (`--enforce-auth`).
     * @param bool \$isEphemeral Whether ephemeral/mock mode is active (`--ephemeral`).
     * @param IdentityProvider|null \$idp Optional integrated IdentityProvider instance.
     * @param string \$userSchema Schema name used for Production Mode Concrete DAO lookup.
     * @return array{authenticated: bool, status: int, mode?: string, error?: string, user?: object|null} Authentication result.
     */
    public static function authenticate(
        array \$headers,
        array \$queryParams = [],
        array \$securityRequirements = [],
        array \$securitySchemes = [],
        bool \$enforceAuth = false,
        bool \$isEphemeral = true,
        ?IdentityProvider \$idp = null,
        string \$userSchema = 'User'
    ): array {
        if (!\$enforceAuth || empty(\$securityRequirements)) {
            return ['authenticated' => true, 'status' => 200, 'mode' => 'bypass'];
        }

        \$normHeaders = [];
        foreach (\$headers as \$k => \$v) {
            \$normHeaders[strtolower((string) \$k)] = (string) \$v;
        }

        \$extractedToken = '';
        \$requiredScopes = [];
        \$foundCredential = false;

        foreach (\$securityRequirements as \$req) {
            foreach (\$req as \$schemeName => \$scopes) {
                if (is_array(\$scopes)) {
                    \$requiredScopes = array_merge(\$requiredScopes, \$scopes);
                }
                \$scheme = \$securitySchemes[\$schemeName] ?? ['type' => 'http', 'scheme' => 'bearer'];
                \$type = strtolower((string) (\$scheme['type'] ?? 'http'));
                if (\$type === 'apikey') {
                    \$keyName = (string) (\$scheme['name'] ?? 'X-Api-Key');
                    \$in = strtolower((string) (\$scheme['in'] ?? 'header'));
                    if (\$in === 'header' && isset(\$normHeaders[strtolower(\$keyName)]) && \$normHeaders[strtolower(\$keyName)] !== '') {
                        \$extractedToken = \$normHeaders[strtolower(\$keyName)];
                        \$foundCredential = true;
                    } elseif (\$in === 'query' && isset(\$queryParams[\$keyName]) && (string) \$queryParams[\$keyName] !== '') {
                        \$extractedToken = (string) \$queryParams[\$keyName];
                        \$foundCredential = true;
                    }
                } else {
                    \$authHeader = \$normHeaders['authorization'] ?? '';
                    if (preg_match('/^Bearer\s+(.+)$/i', \$authHeader, \$m)) {
                        \$extractedToken = trim(\$m[1]);
                        \$foundCredential = true;
                    }
                }
            }
        }

        if (!\$foundCredential || \$extractedToken === '') {
            return [
                'authenticated' => false,
                'status' => 401,
                'error' => 'Unauthorized: Missing credentials',
            ];
        }

        \$matchedUser = null;
        \$authMode = 'mock';

        if (\$idp !== null) {
            \$idpUser = \$idp->validateToken(\$extractedToken);
            if (\$idpUser !== null) {
                \$matchedUser = \$idpUser;
                \$authMode = \$isEphemeral ? 'mock-idp' : 'production-idp';
            }
        }

        if (\$matchedUser === null) {
            \$dbUrl = getenv('DATABASE_URL');
            \$isProductionMode = !\$isEphemeral && is_string(\$dbUrl) && \$dbUrl !== '';

            if (!\$isProductionMode) {
                \$validMockTokens = ['mock-token-123', 'mock-api-key-123', 'special-key'];
                if (!in_array(\$extractedToken, \$validMockTokens, true)) {
                    return [
                        'authenticated' => false,
                        'status' => 401,
                        'error' => 'Unauthorized: Invalid mock credentials',
                    ];
                }
                \$authMode = 'mock';
            } else {
                // Production Mode: Stateful ORM / Concrete DAO integration
                \$dao = class_exists(DaoFactory::class)
                    ? DaoFactory::create(\$userSchema, false)
                    : {$bs}DaoFactory::create(\$userSchema, false);
                \$records = \$dao->getAll();
                foreach (\$records as \$userRecord) {
                    \$recId = (string) (\$userRecord->id ?? '');
                    if (\$extractedToken === 'user-session-' . \$recId || \$extractedToken === (string) (\$userRecord->api_token ?? '')) {
                        \$matchedUser = \$userRecord;
                        \$authMode = 'production';
                        break;
                    }
                }
                if (\$matchedUser === null) {
                    return [
                        'authenticated' => false,
                        'status' => 401,
                        'error' => 'Unauthorized: Session or token not found in Production DAO',
                    ];
                }
            }
        }

        if (!empty(\$requiredScopes)) {
            \$providedScopesRaw = \$normHeaders['x-mock-scopes'] ?? '';
            \$providedScopes = \$providedScopesRaw !== '' ? array_map('trim', explode(',', \$providedScopesRaw)) : [];
            foreach (\$requiredScopes as \$reqScope) {
                if (!in_array(\$reqScope, \$providedScopes, true)) {
                    return [
                        'authenticated' => false,
                        'status' => 403,
                        'error' => \"Forbidden: Insufficient scope '{\$reqScope}'\",
                    ];
                }
            }
        }

        return [
            'authenticated' => true,
            'status' => 200,
            'mode' => \$authMode,
            'user' => \$matchedUser,
        ];
    }
}

/**
 * Outgoing Webhook and Callback Dispatcher for administrative trigger endpoints (`POST /_mock/trigger-webhook/{webhook_name}`).
 */
class WebhookDispatcher {
    /** @var array<string, mixed> Registered webhooks and callbacks from the OpenAPI specification. */
    private array \$webhooks;

    /** @var callable|null Optional custom HTTP sender callback for isolated testing. */
    private mixed \$httpSender;

    /**
     * Initializes the WebhookDispatcher.
     *
     * @param array<string, mixed> \$webhooks Registered webhook definitions.
     * @param callable|null \$httpSender Optional HTTP transport callback `fn(string \$url, array \$payload, array \$headers): array{status: int, body: string}`.
     */
    public function __construct(array \$webhooks = [], ?callable \$httpSender = null) {
        \$this->webhooks = \$webhooks;
        \$this->httpSender = \$httpSender;
    }

    /**
     * Triggers an outgoing webhook and dispatches its payload to the target URL.
     *
     * @param string \$webhookName The name of the webhook or callback to trigger.
     * @param string \$targetUrl The destination URL to receive the webhook payload.
     * @param array<string, mixed> \$payload The JSON payload to dispatch.
     * @return array{status: int, webhook?: string, target_url?: string, dispatched_payload?: array<string, mixed>, delivery_status?: int, delivery_response?: mixed, error?: string} Dispatch result.
     */
    public function trigger(string \$webhookName, string \$targetUrl, array \$payload = []): array {
        if (!empty(\$this->webhooks) && !isset(\$this->webhooks[\$webhookName])) {
            return [
                'status' => 404,
                'error' => \"Unknown webhook: {\$webhookName}\",
            ];
        }
        if (\$targetUrl === '') {
            return [
                'status' => 400,
                'error' => 'target_url is required to dispatch webhook',
            ];
        }

        \$headers = [
            'Content-Type: application/json',
            'X-Webhook-Event: ' . \$webhookName,
        ];

        if (is_callable(\$this->httpSender)) {
            \$res = (\$this->httpSender)(\$targetUrl, \$payload, \$headers);
            \$deliveryStatus = (int) (\$res['status'] ?? 200);
            \$deliveryBody = json_decode((string) (\$res['body'] ?? '{}'), true) ?? (\$res['body'] ?? '');
        } else {
            \$ch = curl_init(\$targetUrl);
            curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt(\$ch, CURLOPT_POSTFIELDS, (string) json_encode(\$payload));
            curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
            curl_setopt(\$ch, CURLOPT_TIMEOUT, 5);
            \$rawResp = curl_exec(\$ch);
            \$deliveryStatus = (int) curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
            \$deliveryBody = is_string(\$rawResp) ? (json_decode(\$rawResp, true) ?? \$rawResp) : '';
        }

        return [
            'status' => 200,
            'webhook' => \$webhookName,
            'target_url' => \$targetUrl,
            'dispatched_payload' => \$payload,
            'delivery_status' => \$deliveryStatus,
            'delivery_response' => \$deliveryBody,
        ];
    }
}

/**
 * CLI Server Runner managing orthogonal database, seeding, validation, and authentication flags.
 */
class ServerRunner {
    /** @var bool Whether `--ephemeral` mode is enabled. */
    public bool \$isEphemeral = false;

    /** @var bool Whether `--seed` mode is enabled. */
    public bool \$shouldSeed = false;

    /** @var bool Whether the server is running in stateless Stub DAO mode. */
    public bool \$isStubMode = false;

    /** @var bool Whether `--strict-validation` mode is enabled. */
    public bool \$strictValidation = false;

    /** @var bool Whether `--enforce-auth` mode is enabled. */
    public bool \$enforceAuth = false;

    /** @var bool Whether `--start-auth-server` mode is enabled. */
    public bool \$startAuthServer = false;

    /** @var bool Whether `mcp` subcommand mode is enabled. */
    public bool \$isMcpMode = false;

    /**
     * Parses CLI arguments and determines orthogonal server modes.
     * --ephemeral: Uses an in-memory database
     * --seed: Runs the data seeder
     * --strict-validation: Enforces strict OpenAPI request schema validation
     * --enforce-auth: Enforces mock or production DAO authentication
     * --start-auth-server: Mounts the integrated Identity Provider (/auth/*) endpoints
     * mcp: Starts the MCP server handler
     *
     * @param array<int, string> \$argv CLI arguments
     * @return void
     */
    public function parseArgs(array \$argv): void {
        \$this->isEphemeral = in_array('--ephemeral', \$argv, true);
        \$this->shouldSeed = in_array('--seed', \$argv, true);
        \$this->strictValidation = in_array('--strict-validation', \$argv, true);
        \$this->enforceAuth = in_array('--enforce-auth', \$argv, true);
        \$this->startAuthServer = in_array('--start-auth-server', \$argv, true);
        \$this->isMcpMode = in_array('mcp', \$argv, true);
        \$dbUrl = getenv('DATABASE_URL');
        \$this->isStubMode = !\$this->isEphemeral && !\$dbUrl;
        putenv('CDD_STRICT_VALIDATION=' . (\$this->strictValidation ? 'true' : 'false'));
        putenv('CDD_ENFORCE_AUTH=' . (\$this->enforceAuth ? 'true' : 'false'));
        putenv('CDD_START_AUTH_SERVER=' . (\$this->startAuthServer ? 'true' : 'false'));
    }

    /**
     * Handles an incoming MCP JSON-RPC request by dynamically proxying tool calls to backend route handlers
     * and enforcing root boundaries on resource URIs.
     *
     * @param array<string, mixed> \$req Incoming MCP JSON-RPC request.
     * @param array<string, callable> \$routeHandlers Map of operationId to backend route handler callables.
     * @param string|null \$rootBoundary Optional root directory boundary for `file://` URIs.
     * @return array<string, mixed> JSON-RPC response envelope.
     */
    public function handleMcpRequest(array \$req, array \$routeHandlers = [], ?string \$rootBoundary = null): array {
        \$res = ['jsonrpc' => '2.0', 'id' => \$req['id'] ?? null];
        \$method = (string) (\$req['method'] ?? '');
        if (\$method === 'tools/call') {
            \$toolName = (string) (\$req['params']['name'] ?? '');
            \$args = (array) (\$req['params']['arguments'] ?? []);
            if (isset(\$routeHandlers[\$toolName]) && is_callable(\$routeHandlers[\$toolName])) {
                \$handlerOut = (\$routeHandlers[\$toolName])(\$args);
                \$res['result'] = [
                    'content' => [['type' => 'text', 'text' => is_string(\$handlerOut) ? \$handlerOut : (string) json_encode(\$handlerOut)]],
                ];
            } else {
                \$res['result'] = [
                    'isError' => true,
                    'content' => [['type' => 'text', 'text' => \"Unknown tool: {\$toolName}\"]],
                ];
            }
        } elseif (\$method === 'resources/read') {
            \$uri = (string) (\$req['params']['uri'] ?? '');
            if (\$uri === 'api://status') {
                \$res['result'] = ['contents' => [['uri' => \$uri, 'mimeType' => 'application/json', 'text' => (string) json_encode(['status' => 'ok'])]]];
            } elseif (str_starts_with(\$uri, 'file://')) {
                \$realTarget = realpath(substr(\$uri, 7));
                \$realRoot = realpath(\$rootBoundary ?? (string) getcwd());
                \$rootPrefix = \$realRoot !== false ? rtrim(\$realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
                if (\$realTarget !== false && \$realRoot !== false && (str_starts_with(\$realTarget, \$rootPrefix) || \$realTarget === \$realRoot) && is_file(\$realTarget)) {
                    \$res['result'] = ['contents' => [['uri' => \$uri, 'mimeType' => 'text/plain', 'text' => (string) file_get_contents(\$realTarget)]]];
                } else {
                    \$res['error'] = ['code' => -32602, 'message' => 'Access denied: Path is outside root boundary or invalid'];
                }
            } else {
                \$res['error'] = ['code' => -32602, 'message' => 'Invalid URI'];
            }
        } else {
            \$res['result'] = ['status' => 'ready'];
        }
        return \$res;
    }

    /**
     * Executes the server startup lifecycle.
     *
     * @param array<int, string> \$argv CLI arguments
     * @return void
     */
    public function run(array \$argv): void {
        \$this->parseArgs(\$argv);
        if (!\$this->isStubMode) {
            if (class_exists(DatabaseConnection::class)) {
                DatabaseConnection::connect(\$this->isEphemeral);
                DatabaseConnection::migrate();
            } elseif (class_exists('DatabaseConnection')) {
                {$bs}DatabaseConnection::connect(\$this->isEphemeral);
                {$bs}DatabaseConnection::migrate();
            }
            if (\$this->shouldSeed) {
                if (class_exists(DatabaseSeeder::class)) {
                    \$seeder = new DatabaseSeeder();
                    \$seeder->seed();
                } elseif (class_exists('DatabaseSeeder')) {
                    \$seeder = new {$bs}DatabaseSeeder();
                    \$seeder->seed();
                }
            }
        }
    }
}
";

    return $out;
}
