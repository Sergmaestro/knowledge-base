# Web Protocol Fundamentals

## Question 1: Explain the evolution of HTTP from version 1.0 to 3.0

**Answer:**

### HTTP/1.0 (1996)

The first standardized version. Simple request-response protocol with no persistent connections.

```
Request:
GET /index.html HTTP/1.0
Host: example.com

Response:
HTTP/1.0 200 OK
Content-Type: text/html
Content-Length: 1234

<html>...
```

**Limitations:**
- New TCP connection per request (3-way handshake overhead)
- No persistent connections (Connection: keep-alive was unofficial)
- No caching control beyond Expires header
- No virtual hosting support (Host header was optional)
- Headers are text-based and verbose
- No content negotiation

### HTTP/1.1 (1997, revised 1999 and 2014)

Major improvements over 1.0 — still the most widely used version.

```
GET /index.html HTTP/1.1
Host: example.com
Connection: keep-alive
Accept-Encoding: gzip, deflate
Cache-Control: max-age=3600
If-Modified-Since: Mon, 18 Jul 2024 02:00:00 GMT

HTTP/1.1 200 OK
Content-Type: text/html
Content-Length: 1234
Cache-Control: public, max-age=3600
ETag: "abc123"
Last-Modified: Mon, 18 Jul 2024 02:00:00 GMT
```

**Key improvements:**

| Feature | HTTP/1.0 | HTTP/1.1 |
|---------|----------|----------|
| Connections | Short-lived | Persistent by default |
| Host header | Optional | Required (virtual hosting) |
| Caching | Expires only | Cache-Control, ETag |
| Compression | Not standardized | Content-Encoding |
| Chunked transfer | Not supported | Supported (Transfer-Encoding) |
| Range requests | Not supported | Supported (Range header) |
| Methods | GET, POST, HEAD | PUT, DELETE, OPTIONS, PATCH, TRACE, CONNECT |
| Pipelining | Not supported | Supported (but buggy in practice) |

**Key features in detail:**

1. **Persistent connections** — Reuse TCP connection for multiple requests, reducing handshake overhead
2. **Pipelining** — Send multiple requests without waiting for responses (rarely used due to head-of-line blocking)
3. **Chunked transfer encoding** — Stream responses without knowing Content-Length upfront
4. **Caching improvements** — `Cache-Control`, `ETag`, `If-None-Match` for conditional requests
5. **Content negotiation** — `Accept`, `Accept-Language`, `Accept-Encoding` headers
6. **Range requests** — Resume downloads, partial content
7. **Host header** — Multiple domains on one IP

**Problems with HTTP/1.1:**
- Head-of-line (HOL) blocking — one slow response blocks all pipelined requests
- Verbose text headers — redundant headers in every request (cookies, user-agent)
- No multiplexing — browser workaround: multiple TCP connections (typically 6 per domain)
- No server push capability
- Large header overhead (compression not supported)

### HTTP/2 (2015, based on SPDY)

Binary protocol designed to address HTTP/1.1 performance issues.

```
Frame format (binary, not text):
+-----------------------------------------------+
| Length (24 bits)                               |
+---------------+---------------+---------------+
| Type (8 bits)  | Flags (8 bits)                |
+---------------+---------------+---------------+
| Stream Identifier (31 bits, reserved bit)      |
+-----------------------------------------------+
| Frame Payload (variable length)               |
+-----------------------------------------------+
```

**Key features:**

| Feature | Description |
|---------|-------------|
| **Binary protocol** | Frames and streams, not text — efficient parsing |
| **Multiplexing** | Multiple streams over single TCP connection |
| **Header compression** | HPACK — eliminates redundant headers |
| **Server push** | Server sends resources client hasn't requested |
| **Stream prioritization** | Client specifies resource importance |
| **Flow control** | Per-stream and connection-level |
| **Protocol negotiation** | ALPN (TLS extension) or h2c (cleartext) |

**Impact of multiplexing:**

```
HTTP/1.1 (6 connections, head-of-line blocking):
┌────────┐  ┌────────┐  ┌────────┐
│ REQ 1  │  │ REQ 2  │  │ REQ 3  │
│ → file │  │ → img  │  │ → css  │
│ ← wait │  │ ← wait │  │ ← wait │
└────────┘  └────────┘  └────────┘

HTTP/2 (1 connection, multiplexed streams):
┌──────────────────────────────────┐
│ stream1: file                    │
│ stream2: img   ← interleaved     │
│ stream3: css      responses      │
│ stream4: js                      │
│ stream5: font                    │
└──────────────────────────────────┘
```

**HPACK Header Compression:**

```
Before (HTTP/1.1) — 400+ bytes per request:
  :method: GET
  :path: /style.css
  :scheme: https
  :authority: example.com
  cookie: session=abc123; theme=dark
  user-agent: Mozilla/5.0 ...
  accept: text/css,...

After HPACK (HTTP/2) — ~8 bytes for static table match:
  (Static table indexed entries for common headers)
  (Dynamic table for cookie, user-agent after first request)
```

**HTTP/2 Performance Gains:**
- Single TCP connection instead of 6+
- No HOL blocking at application layer
- Up to 88% header size reduction
- 10-50% page load improvement depending on network conditions

**HTTP/2 Limitations:**

1. **TCP-level HOL blocking** — TCP packet loss blocks ALL streams (one lost packet stalls multiplexing)
2. **No encryption requirement** (though practically all implementations require TLS)
3. **Server push is complex** — often hurts performance if overused
4. **No real prioritization** — browsers set priorities poorly

### HTTP/3 (2022, based on QUIC)

Built on QUIC (Quick UDP Internet Connections), replacing TCP with UDP.

```
Stack comparison:

HTTP/1.1 & HTTP/2:           HTTP/3:
┌─────────────────┐          ┌─────────────────┐
│     HTTP/2      │          │     HTTP/3      │
├─────────────────┤          ├─────────────────┤
│     TLS 1.3     │          │    QUIC (TLS    │
├─────────────────┤          │     built-in)   │
│       TCP       │          ├─────────────────┤
├─────────────────┤          │       UDP       │
│       IP        │          ├─────────────────┤
└─────────────────┘          │       IP        │
                             └─────────────────┘
```

**Key features:**

| Feature | Description |
|---------|-------------|
| **UDP-based** | QUIC runs over UDP, avoiding TCP head-of-line blocking |
| **0-RTT handshake** | Reconnect to known server with zero round trips |
| **Connection migration** | Survives IP/network changes (e.g., WiFi → mobile) |
| **Per-stream HOL independence** | Packet loss on one stream doesn't block others |
| **Built-in encryption** | TLS 1.3 is mandatory and integrated into QUIC |
| **Improved flow control** | Stream and connection-level like HTTP/2, but better |

**Connection establishment:**

```
TCP + TLS 1.3 (HTTP/2):
  Client                          Server
    │                                │
    ├────────── SYN ────────────────►│  1 RTT
    │◄──────── SYN-ACK ─────────────┤
    ├────────── ACK ────────────────►│
    ├────────── ClientHello ────────►│
    │◄──────── ServerHello + Done ──┤  1 RTT
    ├────────── Finished ───────────►│
    │◄──────── HTTP Response ───────┤
    │                                │
    Total: 2 RTTs before data       │

QUIC (HTTP/3):
  Client                          Server
    │                                │
    ├────────── Initial ────────────►│
    │◄──────── Handshake + Done ────┤  1 RTT
    ├────────── HTTP Request ───────►│
    │◄──────── HTTP Response ───────┤
    │                                │
    Total: 1 RTT before data        │

0-RTT (resumed session):
  Client                          Server
    │                                │
    ├────────── HTTP Request ───────►│  0 RTT!
    │◄──────── HTTP Response ───────┤
    │                                │
    (Using cached connection parameters)
```

**Connection Migration:**

```
User switches from WiFi to mobile data:

HTTP/2 (TCP): Connection breaks → TCP timeout → New TCP handshake
            → New TLS handshake → Resume from scratch
            → Total: 2-3 RTTs + timeout delay

HTTP/3 (QUIC): Connection ID stays the same
             → Server continues sending to new IP
             → No interruption, no re-handshake
             → Total: 0 RTT
```

**Per-stream HOL blocking comparison:**

```
HTTP/2 (TCP — one lost packet blocks all):
Stream 1: ██████████░░░░░░░░░░  ← blocked waiting for retransmit
Stream 2: ██████░░░░░░░░░░░░░░  ← blocked by stream 1
Stream 3: ████████████░░░░░░░░  ← blocked
Stream 4: ██████████████████░░  ← blocked
          ↑ packet lost

HTTP/3 (QUIC — only affected stream):
Stream 1: ██████████░░░░░░░░░░  ← only stream 1 waits
Stream 2: ████████████████████  ← continues unaffected
Stream 3: ████████████████████  ← continues
Stream 4: ████████████████████  ← continues
          ↑ packet lost
```

### Version Comparison Summary

| Feature | HTTP/1.0 | HTTP/1.1 | HTTP/2 | HTTP/3 |
|---------|----------|----------|--------|--------|
| Year | 1996 | 1997/2014 | 2015 | 2022 |
| Transport | TCP | TCP | TCP | QUIC (UDP) |
| Protocol format | Text | Text | Binary | Binary |
| Persistent connections | No | Yes | Yes | Yes |
| Multiplexing | No | No | Yes | Yes |
| HOL blocking | Connection-level | Connection-level | Stream-level (TCP) | None |
| Header compression | No | No | HPACK | QPACK |
| Server push | No | No | Yes | Yes |
| Encryption | Optional | Optional | Optional (TLS) | Required (TLS 1.3) |
| Connection migration | No | No | No | Yes |
| 0-RTT handshake | No | No | No | Yes |
| Flow control | No | No | Yes | Yes (better) |
| Standard | RFC 1945 | RFC 7230-7235 | RFC 7540 | RFC 9114 |

**Follow-up:**
- What problems does QUIC solve that TCP couldn't?
- How does HPACK/QPACK header compression work?
- What is the role of ALPN in HTTP/2 and HTTP/3 negotiation?
- Why did HTTP/2 multiplexing still suffer from head-of-line blocking?
- What are the main challenges of deploying HTTP/3 on the server side?

**Key Points:**
- HTTP/1.0 → 1.1: persistent connections, host header, caching, chunked transfer
- HTTP/1.1 → 2: binary protocol, multiplexing, HPACK, server push (solves app-layer HOL)
- HTTP/2 → 3: UDP-based QUIC, eliminates TCP HOL blocking, 0-RTT, connection migration
- HTTP/3 uses QUIC which has TLS 1.3 built-in
- Each version is backward compatible in semantics (methods, status codes, headers)

---

## Question 2: How do HTTP methods and status codes work across protocol versions?

**Answer:**

HTTP method semantics are consistent across all versions. Status codes are also version-independent.

### HTTP Methods

| Method | Safe | Idempotent | Cacheable | Body |
|--------|------|------------|-----------|------|
| GET | Yes | Yes | Yes | No |
| HEAD | Yes | Yes | Yes | No |
| OPTIONS | Yes | Yes | No | No |
| TRACE | Yes | Yes | No | No |
| POST | No | No | Yes* | Yes |
| PUT | No | Yes | No | Yes |
| DELETE | No | Yes | No | No |
| PATCH | No | No | No | Yes |
| CONNECT | No | No | No | No |

*\* POST responses are cacheable only with explicit freshness information*

```php
// Laravel route examples for each method
Route::get('/posts', [PostController::class, 'index']);       // Read
Route::post('/posts', [PostController::class, 'store']);      // Create
Route::put('/posts/{id}', [PostController::class, 'update']); // Full update
Route::patch('/posts/{id}', [PostController::class, 'patch']);// Partial update
Route::delete('/posts/{id}', [PostController::class, 'destroy']); // Delete
Route::options('/posts', [PostController::class, 'options']); // CORS preflight
```

### HTTP Status Code Classes

| Range | Class | Meaning |
|-------|-------|---------|
| 1xx | Informational | Request received, continuing |
| 2xx | Success | Request understood and accepted |
| 3xx | Redirection | Further action needed |
| 4xx | Client Error | Request contains bad syntax |
| 5xx | Server Error | Server failed to fulfill request |

### Important Status Codes

```php
100 Continue         // Continue sending body (HTTP/1.1+)
101 Switching Protocols // Upgrade to WebSocket (HTTP/1.1+)
103 Early Hints      // Preload hints before final response (HTTP/2+)

200 OK               // Standard success
201 Created          // Resource created
202 Accepted         // Request accepted for async processing
204 No Content       // Delete success, no body
206 Partial Content  // Range request success

301 Moved Permanently  // SEO-friendly redirect
302 Found              // Temporary redirect
304 Not Modified       // Cached resource still valid
307 Temporary Redirect // Preserves HTTP method
308 Permanent Redirect // Preserves HTTP method

400 Bad Request        // Malformed request
401 Unauthorized       // Authentication required
403 Forbidden          // Authenticated but not authorized
404 Not Found          // Resource doesn't exist
405 Method Not Allowed // Wrong HTTP method
409 Conflict           // Resource state conflict
413 Payload Too Large  // Request body too big (HTTP/1.1+)
422 Unprocessable      // Validation errors
429 Too Many Requests  // Rate limit exceeded

500 Internal Server Error  // Generic server error
502 Bad Gateway            // Upstream server error
503 Service Unavailable    // Server overloaded or down
504 Gateway Timeout        // Upstream timeout
```

### Protocol-Specific Status Codes

```php
// HTTP/1.1: 100 Continue — used with Expect header
POST /api/upload HTTP/1.1
Expect: 100-continue
Content-Length: 50000000

HTTP/1.1 100 Continue
// → Client sends body

// HTTP/2+: 103 Early Hints — preload critical resources
HTTP/2 103 Early Hints
Link: </style.css>; rel=preload; as=style
Link: </script.js>; rel=preload; as=script

HTTP/2 200 OK
Content-Type: text/html

<html>...
// Browser already started fetching style.css and script.js
```

**Follow-up:**
- What is the difference between 301, 302, 307, and 308 redirects?
- When should you use 202 Accepted vs 201 Created?
- How does the 103 Early Hints status code improve performance in HTTP/2?

**Key Points:**
- Methods are version-independent (same semantics in HTTP/1.1, 2, 3)
- Status codes are extensible; new codes can be added without breaking clients
- 1xx codes are informational and protocol-specific (100, 101, 103)
- 304 Not Modified enables efficient caching
- 429 for rate limiting prevents server overload

---

## Question 3: How does HTTP caching work across protocol versions?

**Answer:**

Caching is critical for web performance. Each HTTP version refined caching mechanisms.

### Cache Headers

```php
// HTTP/1.0 — basic caching
Cache-Control: no-cache
Pragma: no-cache       // HTTP/1.0 backward compatibility
Expires: Wed, 21 Oct 2024 07:28:00 GMT  // Absolute expiration

// HTTP/1.1+ — modern caching
Cache-Control: public, max-age=3600, must-revalidate
Cache-Control: private, no-store, no-cache
Cache-Control: s-maxage=600  // Shared cache (CDN) override
ETag: "abc123"               // Validation token
Last-Modified: Mon, 18 Jul 2024 02:00:00 GMT
```

### Cache Validation Strategies

```php
// Strong validation — ETag (content hash)
// Request:
GET /style.css
If-None-Match: "abc123"

// Response (not modified):
HTTP/1.1 304 Not Modified
ETag: "abc123"  // Same hash → use cached copy

// Weak validation — Last-Modified
// Request:
GET /style.css
If-Modified-Since: Mon, 18 Jul 2024 02:00:00 GMT

// Response (not modified):
HTTP/1.1 304 Not Modified
// No body, just use cached copy
```

### Cache Directives

| Directive | Meaning |
|-----------|---------|
| `public` | Any cache (browser, CDN, proxy) can cache |
| `private` | Only browser cache (no CDN/proxy) |
| `no-cache` | Must revalidate with origin before serving cached |
| `no-store` | Don't cache at all |
| `max-age=N` | Cache for N seconds from response time |
| `s-maxage=N` | Override max-age for shared caches (CDN) |
| `must-revalidate` | Must check origin if cached entry is stale |
| `immutable` | Resource won't change during freshness lifetime |
| `stale-while-revalidate=N` | Serve stale while revalidating in background |

### Version-Specific Caching

```php
// HTTP/1.1 — Vary header for content negotiation
Vary: Accept-Encoding          // Cache varies by encoding
Vary: Accept-Language, Cookie  // Cache varies by language and auth

// HTTP/2 — no new caching mechanisms (same semantics)
// HTTP/3 — no new caching mechanisms

// Cache busting strategy (version-agnostic)
<link href="/build/style.a1b2c3.css" rel="stylesheet">
// Hash changes when content changes → new cache entry
```

### Laravel Caching Implementation

```php
// Setting cache headers in Laravel
class PostController {
    public function show(Post $post) {
        $response = response()->json(
            new PostResource($post)
        );

        // Public cache for 1 hour
        $response->setCache([
            'public' => true,
            'max_age' => 3600,
            'etag' => md5($post->updated_at . $post->id),
        ]);

        // Conditional request handling
        if ($response->isNotModified($request)) {
            return $response->setStatusCode(304);
        }

        return $response;
    }

    // Private cache (user-specific)
    public function dashboard() {
        return response()->json([
            'notifications' => $this->getNotifications(),
            'stats' => $this->getStats(),
        ])->setCache([
            'private' => true,
            'max_age' => 60,
            'no_cache' => true,  // Must revalidate
        ]);
    }
}
```

**Follow-up:**
- How does CDN caching differ from browser caching?
- What is the difference between `no-cache` and `no-store`?
- How does `stale-while-revalidate` improve perceived performance?

**Key Points:**
- HTTP/1.0: Expires and Pragma (limited)
- HTTP/1.1+: Cache-Control, ETag, Vary (full control)
- HTTP/2 and HTTP/3 inherit HTTP/1.1 caching semantics unchanged
- 304 Not Modified responses are bodyless — minimal bandwidth
- Cache busting with content hashes avoids cache invalidation complexity

---

## Question 4: Explain HTTP connection lifecycle and how it differs between versions

**Answer:**

### TCP Connection Lifecycle

```
Three-way handshake (HTTP/1.0, 1.1, 2):
Client                          Server
  │                                │
  ├────────── SYN ────────────────►│
  │◄──────── SYN-ACK ─────────────┤
  ├────────── ACK ────────────────►│  ← Connection established
  │                                │
  ├────────── Request ────────────►│
  │◄──────── Response ────────────┤
  │                                │
  ├────────── FIN ────────────────►│
  │◄──────── ACK ─────────────────┤  ← Connection closed
  │◄──────── FIN ─────────────────┤
  ├────────── ACK ────────────────►│

Minimum latency: 1 RTT
```

### HTTP/1.0 — Short-lived connections

```
Download 5 resources:
┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐
│TCP+Req │  │TCP+Req │  │TCP+Req │  │TCP+Req │  │TCP+Req │
│← Resp  │  │← Resp  │  │← Resp  │  │← Resp  │  │← Resp  │
│TCP FIN │  │TCP FIN │  │TCP FIN │  │TCP FIN │  │TCP FIN │
└────────┘  └────────┘  └────────┘  └────────┘  └────────┘

Total: 5 × (2 RTT + download)
```

### HTTP/1.1 — Persistent connections

```
Download 5 resources (1 connection):
┌─────────────────────────────────────────────────┐
│TCP handshake (1 RTT)                            │
│Req1 ← Resp1                                     │
│Req2 ← Resp2                                     │
│Req3 ← Resp3                                     │
│Req4 ← Resp4                                     │
│Req5 ← Resp5                                     │
│TCP FIN                                          │
└─────────────────────────────────────────────────┘

Total: 1 RTT + 5 × (request + download)
But: sequential — each request waits for previous response
```

### HTTP/1.1 with multiple connections (browser workaround)

```
Download 5 resources (6 connections):
Connection 1: │TCP│← Req1 Resp1                    │
Connection 2: │TCP│← Req2 Resp2                    │
Connection 3: │TCP│← Req3 Resp3                    │
Connection 4: │TCP│← Req4 Resp4                    │
Connection 5: │TCP│← Req5 Resp5                    │

Total: 1 RTT + max(request + download) per resource
Parallel, but: 5 TCP handshakes, TCP slow start per connection
```

### HTTP/2 — Multiplexed streams

```
Download 5 resources (1 connection):
┌─────────────────────────────────────────────────┐
│TCP handshake (1 RTT)                            │
│Stream 1: ──Req1──Resp1─────────────────────    │
│Stream 2: ──Req2────Resp2───────────────────    │
│Stream 3: ──Req3──────Resp3─────────────────    │  ← interleaved
│Stream 4: ──Req4────────Resp4───────────────    │
│Stream 5: ──Req5──────────Resp5─────────────    │
└─────────────────────────────────────────────────┘

Total: 1 RTT + max(individual resource time)
All resources downloaded in parallel over ONE connection
```

### HTTP/3 — QUIC connection lifecycle

```
0-RTT Connection (resumed):
Client                          Server
  │                                │
  ├────────── HTTP Request ────────►│  ← Immediate data!
  │◄──────── HTTP Response ────────┤
  │                                │
  No TCP handshake (UDP + cached session)

Connection Migration (IP change):
Client (WiFi)                  Server
  │                                │
  ├────────── Request (IP A) ─────►│
  │◄──────── Response (IP A) ─────┤
  │  ▲ WiFi drops, switch to 4G   │
  │  ▼ New IP: B                  │
  ├────────── Request (IP B) ─────►│  ← Same connection ID
  │◄──────── Response (IP B) ─────┤
  │                                │
  Connection continues without re-handshake!

Graceful shutdown:
Client                          Server
  │                                │
  ├────────── GOAWAY frame ───────►│  → Stop using this connection
  │    (no new streams accepted)   │  ← Start new connection
  │◄──────── GOAWAY frame ────────┤
  │                                │
  Existing streams complete, then connection closes
```

### Connection Pooling (browser behavior by version)

| Version | Connections per domain | Connections total | Features |
|---------|----------------------|-------------------|----------|
| HTTP/1.0 | 1-2 | 2-4 | Short-lived |
| HTTP/1.1 | 6-8 | 30-60 | Persistent, connection reuse |
| HTTP/2 | 1 | 10-20 | Multiplexed streams |
| HTTP/3 | 1 | 10-20 | Multiplexed + connection migration |

**Follow-up:**
- Why do browsers limit connections per domain in HTTP/1.1?
- How does QUIC connection migration work without breaking TLS?
- What is TCP slow start and how does HTTP/2 mitigate it?

**Key Points:**
- Each HTTP/1.0 request = new TCP connection (worst performance)
- HTTP/1.1 persistent connections save handshake overhead but are sequential
- HTTP/2 multiplexing allows parallel streams over one connection
- HTTP/3 QUIC eliminates TCP handshake and survives network changes
- Connection migration is unique to HTTP/3 — no other version supports it

---

## Question 5: Explain HTTP security considerations across protocol versions

**Answer:**

### TLS Evolution

```
HTTP/1.0 — Plaintext (no encryption standard)
    ↓
HTTP/1.1 — Optional TLS (HTTPS on port 443)
    ↓       TLS 1.0 → 1.1 → 1.2
    ↓
HTTP/2   — Strongly recommended TLS (h2c is rare)
    ↓       TLS 1.2 minimum, ALPN for negotiation
    ↓
HTTP/3   — REQUIRED TLS 1.3 (QUIC has TLS built-in)
            No cleartext mode exists
```

### TLS Handshake by Version

```
HTTP/1.1 with TLS 1.3:
TCP SYN     ────►
TCP SYN-ACK ◄────
TCP ACK     ────►
ClientHello ────►
ServerHello ◄────+  ← 1 RTT for TLS
Finished    ────►
HTTP Req    ────►
HTTP Resp   ◄────
Total: 2 RTTs

HTTP/3 with QUIC (TLS 1.3 built-in):
Initial (includes ClientHello)  ────►
Handshake + ServerHello + Done ◄────  ← 1 RTT for everything
HTTP Request                    ────►
HTTP Response                   ◄────
Total: 1 RTT

0-RTT (resumed):
HTTP Request + Cached params ────►
HTTP Response                 ◄────  ← 0 RTT!
```

### Common Security Headers

```php
// Security headers — version-agnostic
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'self'
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=()

// Laravel middleware for security headers
class SecurityHeaders {
    public function handle($request, Closure $next) {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

### Protocol-Specific Security Considerations

```
HTTP/1.1:
  - Plaintext still common (need to enforce HTTPS)
  - Request smuggling via header parsing ambiguities
  - Content-Length vs Transfer-Encoding conflicts
  - No mandatory encryption

HTTP/2:
  - Encryption strongly recommended (practically required by browsers)
  - Stream multiplexing changes threat model
  - HPACK bomb attack (compressed header → decompression bomb)
  - Request smuggling harder but still possible
  - Server push privacy concerns (server knows what client will request)

HTTP/3:
  - Mandatory TLS 1.3 (no cleartext)
  - QUIC uses connection IDs instead of IP:port pairs
  - Privacy: connection ID rotation prevents tracking
  - 0-RTT replay attacks (mitigated by idempotency checks)
  - Amplification attacks (mitigated by server-side flow control)
```

### CSRF Protection (Protocol-Agnostic)

```php
// Laravel CSRF protection — works with all HTTP versions
<form method="POST" action="/posts">
    @csrf
    <input name="title">
    <button type="submit">Create</button>
</form>

// API — token-based CSRF for SPA
// Set custom header (SameSite cookies handle this)
axios.post('/api/posts', data, {
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
    },
    withCredentials: true,
});
```

**Follow-up:**
- What is HTTP request smuggling and which versions are affected?
- How does HTTP/3 handle NAT rebinding differently from TCP?
- Why is 0-RTP (zero round trip time) replay a security concern?

**Key Points:**
- HTTP/1.1 can be plaintext; HTTP/2 strongly encrypted; HTTP/3 always encrypted
- HSTS forces HTTPS and prevents downgrade attacks
- HTTP/3 QUIC connection IDs enable privacy via rotation
- 0-RTT data must be idempotent to prevent replay attacks
- Security headers are version-agnostic — apply to all HTTP versions

---

## Question 6: How do HTTP versions impact web application performance?

**Answer:**

### Performance Characteristics

```php
// Scenario: Loading a page with 30 resources (HTML, CSS, JS, images, fonts)

HTTP/1.1 (6 parallel connections, sequential per connection):
├── Connection 1: [Req1:HTML]  [Req2:CSS]   [Req3:CSS]   [Req4:JS]
│   ← 200       ← 200         ← 200         ← 200            → 4.2s
├── Connection 2: [Req1:IMG]  [Req2:IMG]    [Req3:IMG]
│   ← 200       ← 200         ← 200                            → 3.1s
├── Connection 3: [Req1:IMG]  [Req2:IMG]    [Req3:IMG]
│   ← 200       ← 200         ← 200                            → 2.8s
├── Connection 4: [Req1:JS]   [Req2:FONT]   [Req3:IMG]
│   ← 200       ← 200         ← 200                            → 2.5s
├── Connection 5: [Req1:IMG]  [Req2:IMG]
│   ← 200       ← 200                                           → 1.2s
└── Connection 6: [Req1:IMG]  [Req2:IMG]
    ← 200       ← 200                                           → 0.9s
                                                                 Total: ~4.2s

HTTP/2 (1 connection, multiplexed):
├── Stream 1:  [Req:HTML]  ← 200
├── Stream 2:  [Req:CSS]   ← 200
├── Stream 3:  [Req:CSS]   ← 200
├── Stream 4:  [Req:JS]    ← 200
├── Stream 5:  [Req:IMG]   ← 200
├── Stream 6:  [Req:IMG]   ← 200
├── ...
└── Stream 30: [Req:IMG]   ← 200
                                Total: ~1.8s
```

### Optimization Strategies by Version

```php
// HTTP/1.1 optimizations (minimize connections)
// 1. Concatenation — bundle files
<script src="bundle.all.js"></script>       // One request
<link rel="stylesheet" href="all.min.css" />  // One request

// 2. Image sprites — combine images
.logo { background: url(sprite.png) -10px 0; }
.icon { background: url(sprite.png) -50px 0; }

// 3. Domain sharding — use multiple domains
<link rel="stylesheet" href="http://cdn1.example.com/style.css">
<img src="http://cdn2.example.com/image.jpg">
<img src="http://cdn3.example.com/image.jpg">

// 4. Inline small resources
<script>
    // Small JS inlined in HTML (avoids request)
    function toggleMenu() { ... }
</script>
<style>
    /* Small CSS inlined */
    .btn { color: blue; }
</style>

// HTTP/2 optimizations (reverse HTTP/1.1 workarounds)
// 1. Serve concatenation — DON'T bundle everything
<script src="react.js"></script>             // Each independently cacheable
<script src="lodash.js"></script>
<script src="app.js"></script>

// 2. Image sprites — DON'T use (use individual images with HTTP/2)
// 3. Domain sharding — DON'T use (hurts HTTP/2 multiplexing)
// 4. Inline — DON'T inline (defeats caching)
// 5. Server push — selectively push critical resources
Link: </style.css>; rel=preload; as=style   // HTTP/2 server push or preload
```

### Critical Performance Metrics

| Metric | HTTP/1.1 | HTTP/2 | HTTP/3 |
|--------|----------|--------|--------|
| Connections needed | 6+ | 1 | 1 |
| First Byte (cold) | 2-3 RTT | 2-3 RTT | 1 RTT |
| First Byte (warm) | 1 RTT | 1 RTT | 0 RTT |
| Max concurrent requests | 6 | 100+ | 100+ |
| Packet loss impact | Per-connection | All streams | Per-stream |
| Network change | Reconnect | Reconnect | Migration |

### Load Balancer and Proxy Considerations

```nginx
# nginx — HTTP/2 and HTTP/3 configuration
server {
    listen 443 ssl http2;
    listen 443 quic reuseport;  # HTTP/3 (QUIC)

    ssl_protocols TLSv1.2 TLSv1.3;

    # HPACK compression offloading
    http2_max_concurrent_streams 128;

    # Enable early hints (103 status)
    http2_push_preload on;

    # HTTP/3 specific
    quic_retry on;
    quic_gso on;

    location / {
        proxy_pass http://backend;
        proxy_http_version 1.1;  # Backend speaks HTTP/1.1
        proxy_set_header Connection "";
    }
}
```

**Follow-up:**
- Why does HTTP/2 make domain sharding counterproductive?
- What is the optimal resource loading strategy for HTTP/2?
- How does packet loss affect HTTP/2 vs HTTP/3 differently?

**Key Points:**
- HTTP/1.1 workarounds (bundling, sprites, domain sharding) are ANTI-patterns for HTTP/2
- HTTP/2 favors small, independently cacheable resources delivered via multiplexing
- HTTP/3 eliminates the 1 RTT connection setup, critical for mobile users
- Server push was designed for HTTP/2 but is largely replaced by preload hints
- Load balancers terminate HTTP/2/3 and speak HTTP/1.1 to backends (most common)

---

## Notes

Add more questions covering:
- WebSocket protocol upgrade (101 Switching Protocols)
- Server-Sent Events (SSE) vs WebSocket vs long-polling
- HTTP/2 and HTTP/3 protocol negotiation (ALPN, Alt-Svc)
- gRPC and how it leverages HTTP/2
- Real-world migration strategies from HTTP/1.1 to HTTP/2/3
- HTTP/3 deployment challenges (UDP on corporate firewalls)
