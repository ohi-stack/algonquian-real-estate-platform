import http from 'node:http';
import { randomUUID } from 'node:crypto';

const PORT = Number.parseInt(process.env.PORT || '3000', 10);
const HOST = process.env.HOST || '0.0.0.0';
const NODE_ENV = process.env.NODE_ENV || 'development';
const API_VERSION = 'v1';
const SERVICE_NAME = 'Algonquian Real Estate API Gateway';
const SERVICE_VERSION = '0.1.0';
const startedAt = new Date().toISOString();

const allowedOrigins = new Set(
  (process.env.ARE_ALLOWED_ORIGINS || '')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean),
);

function setSecurityHeaders(res, origin) {
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'DENY');
  res.setHeader('Referrer-Policy', 'no-referrer');
  res.setHeader('Cache-Control', 'no-store');
  res.setHeader('Content-Type', 'application/json; charset=utf-8');

  if (origin && allowedOrigins.has(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
    res.setHeader('Vary', 'Origin');
    res.setHeader('Access-Control-Allow-Credentials', 'false');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-ARE-Key-ID, X-ARE-Timestamp, X-ARE-Nonce, X-ARE-Request-ID, X-ARE-Correlation-ID, Idempotency-Key, X-ARE-Signature');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
  }
}

function json(res, status, payload) {
  res.statusCode = status;
  res.end(JSON.stringify(payload));
}

const server = http.createServer((req, res) => {
  const requestId = req.headers['x-are-request-id'] || randomUUID();
  const correlationId = req.headers['x-are-correlation-id'] || requestId;
  const origin = req.headers.origin;

  setSecurityHeaders(res, origin);
  res.setHeader('X-ARE-Request-ID', String(requestId));
  res.setHeader('X-ARE-Correlation-ID', String(correlationId));

  if (req.method === 'OPTIONS') {
    res.statusCode = origin && allowedOrigins.has(origin) ? 204 : 403;
    return res.end();
  }

  const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);

  if (req.method === 'GET' && url.pathname === '/') {
    return json(res, 200, {
      service: SERVICE_NAME,
      version: SERVICE_VERSION,
      api: `/${API_VERSION}`,
      health: `/${API_VERSION}/health`,
      environment: NODE_ENV,
      status: 'online',
    });
  }

  if (req.method === 'GET' && url.pathname === `/${API_VERSION}/health`) {
    return json(res, 200, {
      service: SERVICE_NAME,
      version: SERVICE_VERSION,
      api_version: API_VERSION,
      environment: NODE_ENV,
      status: 'healthy',
      started_at: startedAt,
      wordpress_bridge_configured: Boolean(process.env.ARE_WORDPRESS_BRIDGE_URL),
      request_id: requestId,
      correlation_id: correlationId,
    });
  }

  if (url.pathname.startsWith(`/${API_VERSION}/`)) {
    return json(res, 501, {
      error: 'route_not_implemented',
      message: 'This ARE API route is reserved but is not operational until its authoritative WordPress service and API Bridge mapping are connected.',
      request_id: requestId,
      correlation_id: correlationId,
    });
  }

  return json(res, 404, {
    error: 'not_found',
    message: 'Route not found.',
    request_id: requestId,
    correlation_id: correlationId,
  });
});

server.listen(PORT, HOST, () => {
  console.log(`${SERVICE_NAME} ${SERVICE_VERSION} listening on ${HOST}:${PORT}`);
});

function shutdown(signal) {
  console.log(`${signal} received; shutting down.`);
  server.close(() => process.exit(0));
  setTimeout(() => process.exit(1), 10_000).unref();
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
