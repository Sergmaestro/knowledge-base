# JavaScript Fundamentals

## Question 1: Explain JavaScript's execution context and call stack.

**Answer:**

The execution context is the environment where JavaScript code executes. Each time code runs, JavaScript creates an execution context.

### Execution Context Types

```javascript
// 1. Global Execution Context
// Created when script loads, contains:
// - Global object (window in browser, global in Node.js)
// - 'this' binding
// - Outer environment reference

// 2. Function Execution Context
// Created each time a function is called
function outer() {
  function inner() {
    console.log('inner'); // Has its own context
  }
  inner();
}
outer();

// 3. Eval Execution Context
// Created when code runs in eval() - avoid in production
eval("console.log('eval context')");
```

### Call Stack (LIFO)

```javascript
function a() {
  console.log('a');
  b(); // Wait for b to complete
  console.log('a done');
}

function b() {
  console.log('b');
  c(); // Wait for c to complete
  console.log('b done');
}

function c() {
  console.log('c');
}

a();
// Stack trace:
// c()
// b()
// a()
// global

// Output: a, b, c, b done, a done
```

### Execution Context Components

```javascript
// Each context has:
const executionContext = {
  variableEnvironment: {
    // var declarations, function declarations
  },
  lexicalEnvironment: {
    // let, const declarations
  },
  thisBinding: {
    // Value of 'this'
  }
};
```

### Scope Chain

```javascript
const globalVar = 'global';

function outer() {
  const outerVar = 'outer';
  
  function inner() {
    const innerVar = 'inner';
    console.log(globalVar); // → 'global' (outer scope)
    console.log(outerVar);  // → 'outer' (outer scope)
    console.log(innerVar);  // → 'inner' (local)
  }
  
  inner();
}
```

**Follow-up:**
- How does hoisting work in JavaScript?
- What is the difference between scope and execution context?
- How does closure relate to execution context?

**Key Points:**
- Execution context: environment for code execution
- Call stack: LIFO structure tracking function calls
- Each function call creates new execution context
- Lexical environment determined by where functions are written

---

## Question 2: Explain JavaScript closures and their practical uses.

**Answer:**

A closure is a function that retains access to variables from its outer scope even after the outer function has returned.

### Basic Closure

```javascript
function createCounter() {
  let count = 0; // Private variable
  
  return function() {
    count++;
    return count;
  };
}

const counter = createCounter();
console.log(counter()); // 1
console.log(counter()); // 2
console.log(counter()); // 3

// count is 'closed over' - still accessible
```

### Closure with Parameters

```javascript
function multiplier(factor) {
  return function(number) {
    return number * factor;
  };
}

const double = multiplier(2);
const triple = multiplier(3);

console.log(double(5)); // 10
console.log(triple(5)); // 15
```

### Practical Uses

```javascript
// 1. Data Privacy (Module Pattern)
const userModule = (function() {
  let _users = []; // Private
  
  function addUser(user) {
    _users.push(user);
  }
  
  function getUsers() {
    return [..._users]; // Return copy
  }
  
  return { addUser, getUsers };
})();

userModule.addUser({ name: 'John' });
console.log(userModule.getUsers());

// 2. Function Factories
function logger(prefix) {
  return function(message) {
    console.log(`[${prefix}] ${message}`);
  };
}

const errorLogger = logger('ERROR');
const infoLogger = logger('INFO');

errorLogger('Something failed'); // [ERROR] Something failed
infoLogger('Something happened'); // [INFO] Something happened

// 3. Currying
const curriedMultiply = (a) => (b) => a * b;
const multiplyBy2 = curriedMultiply(2);
console.log(multiplyBy2(5)); // 10

// 4. Memoization
function memoize(fn) {
  const cache = {};
  return function(...args) {
    const key = JSON.stringify(args);
    if (cache[key]) return cache[key];
    cache[key] = fn.apply(this, args);
    return cache[key];
  };
}

const fibonacci = memoize(function(n) {
  if (n <= 1) return n;
  return fibonacci(n - 1) + fibonacci(n - 2);
});

// 5. Event Handlers
function attachHandler(element, handler) {
  element.addEventListener('click', function(e) {
    handler(e, element); // element captured in closure
  });
}
```

### Common Pitfalls

```javascript
// Problem: Closure in loops
for (var i = 0; i < 3; i++) {
  setTimeout(() => console.log(i), 100); // Prints: 3, 3, 3
}

// Solution 1: Use let
for (let i = 0; i < 3; i++) {
  setTimeout(() => console.log(i), 100); // Prints: 0, 1, 2
}

// Solution 2: IIFE
for (var i = 0; i < 3; i++) {
  ((j) => setTimeout(() => console.log(j), 100))(i);
}

// Solution 3: Closure with function
function createHandler(i) {
  return () => console.log(i);
}
for (var i = 0; i < 3; i++) {
  setTimeout(createHandler(i), 100);
}
```

**Follow-up:**
- What is the scope chain?
- How does garbage collection work with closures?
- What are memory leaks in closures?

**Key Points:**
- Closures allow functions to access outer scope variables
- Used for data privacy, function factories, memoization
- Common pitfall: var in loops (use let)
- closure + execution context = captured variables

---

## Question 3: Explain the difference between var, let, and const.

**Answer:**

| Feature | var | let | const |
|---------|-----|-----|-------|
| Scope | Function | Block | Block |
| Hoisting | Yes (undefined) | Yes (TDZ) | Yes (TDZ) |
| Reassignment | Allowed | Allowed | Not allowed |
| Redeclaration | Allowed | Not allowed | Not allowed |
| Temporal Dead Zone | No | Yes | Yes |

### var (Function Scope)

```javascript
function example() {
  if (true) {
    var x = 10;
  }
  console.log(x); // 10 - accessible outside block
}

example();

// Hoisting behavior
console.log(y); // undefined (not error)
var y = 5;
```

### let (Block Scope)

```javascript
if (true) {
  let x = 10;
  console.log(x); // 10
}
console.log(x); // ReferenceError: x is not defined

// Temporal Dead Zone (TDZ)
{
  console.log(z); // ReferenceError
  let z = 5;
}
```

### const (Block Scope + Immutability)

```javascript
const PI = 3.14159;
PI = 3.14; // TypeError: Assignment to constant

// But for objects/arrays:
const user = { name: 'John' };
user.name = 'Jane'; // Allowed - object reference is const
// user = {}; // Error - can't reassign

// For truly immutable objects:
const frozen = Object.freeze({ name: 'John' });
frozen.name = 'Jane'; // Silently fails (strict mode: TypeError)
```

### When to Use Each

```javascript
// Use const by default
const API_URL = 'https://api.example.com';
const config = { timeout: 5000 };

// Use let when you need to reassign
let count = 0;
count = count + 1;

// Avoid var in modern JavaScript
// - Function scope causes bugs
// - Hoisting can be confusing
// - No block scope
```

### Best Practices

```javascript
// ✅ Good
const API_ENDPOINTS = {
  users: '/api/users',
  posts: '/api/posts'
};

let retryCount = 0;
const maxRetries = 3;

while (retryCount < maxRetries) {
  retryCount++;
}

// ❌ Avoid var
var data = []; // Use const or let instead
```

**Follow-up:**
- What is the Temporal Dead Zone?
- How does hoisting differ between var and let?
- What is the difference between const and Object.freeze()?

**Key Points:**
- Use const by default, let when reassignment needed
- var: function scope, hoisted with undefined
- let/const: block scope, TDZ until declaration
- const prevents reassignment but not mutation

---

## Question 4: Explain JavaScript's prototype chain and prototypal inheritance.

**Answer:**

JavaScript uses prototypal inheritance - objects can inherit from other objects through the prototype chain.

### Prototype Chain

```javascript
// Every object has a prototype
const obj = {};
console.log(obj.__proto__ === Object.prototype); // true

// Chain: obj → Object.prototype → null
```

### Prototype Inheritance

```javascript
// Constructor function
function Animal(name) {
  this.name = name;
}

Animal.prototype.speak = function() {
  console.log(`${this.name} makes a sound`);
};

const dog = new Animal('Dog');
dog.speak(); // 'Dog makes a sound'

// dog → Animal.prototype → Object.prototype → null
```

### ES6 Class Inheritance

```javascript
class Animal {
  constructor(name) {
    this.name = name;
  }
  
  speak() {
    console.log(`${this.name} makes a sound`);
  }
}

class Dog extends Animal {
  constructor(name, breed) {
    super(name); // Call parent constructor
    this.breed = breed;
  }
  
  speak() {
    console.log(`${this.name} barks`);
  }
}

const dog = new Dog('Buddy', 'Labrador');
dog.speak(); // 'Buddy barks'
```

### Prototype Methods

```javascript
const person = {
  greet() {
    return `Hello, I'm ${this.name}`;
  }
};

const john = Object.create(person);
john.name = 'John';

console.log(john.greet()); // 'Hello, I'm John'
console.log(Object.getPrototypeOf(john) === person); // true
```

### prototype vs __proto__

```javascript
function Foo() {}
const foo = new Foo();

// foo.__proto__ === Foo.prototype
// Both refer to the same object

// Modifying prototype
Foo.prototype.x = 10;
console.log(foo.x); // 10

// Own property shadows prototype
foo.x = 20;
console.log(foo.x); // 20
console.log(Foo.prototype.x); // 10
```

### Object.create for Inheritance

```javascript
const vehicle = {
  type: 'vehicle',
  start() {
    console.log('Starting...');
  }
};

const car = Object.create(vehicle);
car.drive = function() {
  console.log('Driving...');
};

car.start(); // Inherited
car.drive(); // Own

// car → vehicle → Object.prototype → null
```

### hasOwnProperty and enumerable

```javascript
const parent = { a: 1 };
const child = Object.create(parent);
child.b = 2;

console.log(child.hasOwnProperty('a')); // false (inherited)
console.log(child.hasOwnProperty('b')); // true (own)

for (let key in child) {
  console.log(key); // b, a (including inherited)
}

for (let key in child) {
  if (child.hasOwnProperty(key)) {
    console.log(key); // b (only own)
  }
}
```

**Follow-up:**
- How does `new` keyword work?
- What is the difference between prototype and __proto__?
- How do you check if a property is inherited?

**Key Points:**
- Objects inherit from prototypes via [[Prototype]]
- Prototype chain: object → prototype → prototype → null
- ES6 classes are syntactic sugar for prototypes
- Object.create() for prototypal inheritance without constructors

---

## Question 5: Explain the event loop in JavaScript.

**Answer:**

The event loop continuously checks if the call stack is empty and processes tasks from the task queue and microtask queue.

### Call Stack

```javascript
function a() {
  console.log('a');
  b();
  console.log('a done');
}

function b() {
  console.log('b');
}

a();

// Execution:
// 1. a() pushed to stack
// 2. console.log('a') → executed → popped
// 3. b() pushed to stack
// 4. console.log('b') → executed → popped
// 5. b() popped
// 6. console.log('a done') → executed → popped
// 7. a() popped
```

### Event Loop Components

```
┌─────────────────────────────────────────┐
│                 Call Stack              │
│  (synchronous code executes here)      │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│              Microtask Queue           │
│  (Promises, queueMicrotask)            │
│  - Processed after current task        │
│  - All microtasks complete before next │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│               Task Queue                │
│  (setTimeout, setInterval, I/O)        │
│  - Processed after microtasks           │
└────────────────────┬────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────┐
│              Event Loop                 │
│  (checks if stack is empty, moves      │
│   tasks from queues to stack)           │
└─────────────────────────────────────────┘
```

### setTimeout vs Microtasks

```javascript
console.log('1: start');

setTimeout(() => console.log('2: timeout'), 0);

Promise.resolve()
  .then(() => console.log('3: promise'));

console.log('4: end');

// Output:
// 1: start
// 4: end
// 3: promise
// 2: timeout

// Reason: microtasks (promises) run before macrotasks (setTimeout)
```

### Detailed Example

```javascript
console.log('1');

setTimeout(() => console.log('2'), 0);

Promise.resolve().then(() => {
  console.log('3');
  Promise.resolve().then(() => console.log('4'));
});

console.log('5');

// Output:
// 1
// 5
// 3
// 4
// 2

// Explanation:
// 1. print 1
// 2. setTimeout queued (task queue)
// 3. promise.then queued (microtask queue)
// 4. print 5
// 5. event loop checks stack - empty
// 6. process all microtasks: print 3, then queue 4, print 4
// 7. process task queue: print 2
```

### queueMicrotask

```javascript
Promise.resolve().then(() => console.log('promise'));

queueMicrotask(() => console.log('microtask'));

// Both are microtasks, but queueMicrotask has lower priority
// Output: promise, microtask
```

### Blocking the Event Loop

```javascript
// BAD: Blocking operation
function heavyCalculation() {
  let sum = 0;
  for (let i = 0; i < 1e10; i++) {
    sum += i;
  }
  return sum;
}

// UI freezes - synchronous code blocks everything
// heavyCalculation();

// GOOD: Use Web Workers or break into chunks
function chunkedCalculation() {
  let sum = 0;
  let i = 0;
  
  function processChunk() {
    const chunkSize = 1e6;
    const end = Math.min(i + chunkSize, 1e10);
    
    for (; i < end; i++) {
      sum += i;
    }
    
    if (i < 1e10) {
      setTimeout(processChunk, 0); // Yield to event loop
    } else {
      console.log('Done:', sum);
    }
  }
  
  processChunk();
}
```

### async/await

```javascript
async function example() {
  console.log('1: start');
  
  await Promise.resolve().then(() => console.log('2: microtask'));
  
  console.log('3: after await');
}

// For async function, code after await is like promise.then
// It's a macrotask, not microtask
console.log('0: before async');
example();
console.log('4: sync code');

// Output:
// 0: before async
// 1: start
// 4: sync code
// 2: microtask
// 3: after await
```

**Follow-up:**
- What is the difference between microtasks and macrotasks?
- How does async/await relate to the event loop?
- What causes the event loop to block?

**Key Points:**
- Event loop moves tasks from queues to call stack
- Microtasks (promises) run before macrotasks (setTimeout)
- await pauses async function execution until promise resolves
- Synchronous code blocks the event loop

---

## Question 6: Explain JavaScript's this keyword and how its value is determined.

**Answer:**

`this` refers to the object executing the current function. Its value depends on how a function is called.

### this in Different Contexts

```javascript
// 1. Global Context
console.log(this); // window (browser) or global (Node.js)

// 2. Regular Function (non-strict)
function showThis() {
  console.log(this);
}
showThis(); // window (browser)

// 3. Regular Function (strict mode)
'use strict';
function showThisStrict() {
  console.log(this); // undefined
}

// 4. Object Method
const user = {
  name: 'John',
  greet() {
    console.log(this.name); // 'John' - refers to user object
  }
};
user.greet();

// 5. Arrow Function
const person = {
  name: 'Jane',
  greet: () => {
    console.log(this.name); // undefined (inherits from outer)
  },
  // Arrow function as method
  regularGreet() {
    const arrow = () => console.log(this.name);
    arrow(); // 'Jane' - inherits 'this' from regular function
  }
};
```

### this in Constructors

```javascript
function Person(name, age) {
  this.name = name;
  this.age = age;
}

const john = new Person('John', 30);
console.log(john.name); // 'John' - 'this' refers to new instance

// With class
class Animal {
  constructor(name) {
    this.name = name;
  }
  
  speak() {
    console.log(`${this.name} speaks`);
  }
}
```

### this with Event Handlers

```javascript
// In DOM event handlers, 'this' refers to element
document.querySelector('button').addEventListener('click', function() {
  console.log(this.tagName); // 'BUTTON'
});

// Arrow function inherits from surrounding context
document.querySelector('button').addEventListener('click', () => {
  console.log(this); // window (or outer scope)
});
```

### Explicit this Binding

```javascript
function introduce(greeting) {
  console.log(`${greeting}, I'm ${this.name}`);
}

const person = { name: 'John' };

// bind() - creates new function with bound 'this'
const boundIntro = introduce.bind(person);
boundIntro('Hello'); // 'Hello, I'm John'

// call() - invokes immediately with 'this'
introduce.call(person, 'Hi'); // 'Hi, I'm John'

// apply() - like call, arguments as array
introduce.apply(person, ['Hey']); // 'Hey, I'm John'
```

### this in Callbacks

```javascript
const counter = {
  count: 0,
  increment() {
    // Arrow function preserves 'this'
    setTimeout(() => {
      this.count++;
      console.log(this.count);
    }, 100);
  },
  incrementBad() {
    // Regular function loses 'this'
    setTimeout(function() {
      this.count++; // TypeError: Cannot read property 'count' of undefined
    }, 100);
  }
};
```

### Practical Patterns

```javascript
// 1. Constructor with methods
function Calculator(initial) {
  this.value = initial;
  
  // Methods bound in constructor
  this.add = this.add.bind(this);
  this.subtract = this.subtract.bind(this);
}

Calculator.prototype.add = function(n) {
  this.value += n;
  return this; // Enable chaining
};

// 2. Class fields and arrow methods
class Counter {
  count = 0;
  
  // Arrow function as class field - auto-bound
  increment = () => {
    this.count++;
  };
  
  // Regular method, bound in constructor
  decrement() {
    this.count--;
  }
}

// 3. Method extraction loses 'this'
const obj = {
  value: 10,
  getValue() {
    return this.value;
  }
};

const getValue = obj.getValue;
console.log(getValue()); // undefined - 'this' is lost

// Fix with bind
const getValueBound = obj.getValue.bind(obj);
console.log(getValueBound()); // 10

// Fix with arrow
const getValueArrow = () => obj.getValue();
```

**Follow-up:**
- What is the difference between call, apply, and bind?
- How does this behave in arrow functions?
- How do you preserve this in event handlers?

**Key Points:**
- this = object executing current function
- Arrow functions don't have their own this
- bind/call/apply for explicit binding
- Regular functions lose this when passed as callbacks

---

## Question 7: Explain JavaScript's async/await and Promise patterns.

**Answer:**

async/await provides cleaner syntax for working with Promises, making asynchronous code look synchronous.

### async Function

```javascript
async function fetchData() {
  // Automatically returns a Promise
  return { data: 'some data' };
}

// Usage
fetchData().then(result => console.log(result));
```

### await Expression

```javascript
function fetchUser() {
  return new Promise(resolve => {
    setTimeout(() => resolve({ id: 1, name: 'John' }), 100);
  });
}

async function getUser() {
  const user = await fetchUser();
  console.log(user.name); // 'John' - no .then() chains
}
```

### Error Handling

```javascript
// try/catch
async function getData() {
  try {
    const response = await fetch('/api/data');
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error; // Re-throw for caller
  }
}

// Promise.allSettled for multiple requests
async function fetchAll() {
  const results = await Promise.allSettled([
    fetch('/api/users').then(r => r.json()),
    fetch('/api/posts').then(r => r.json())
  ]);
  
  results.forEach((result, index) => {
    if (result.status === 'fulfilled') {
      console.log(`Request ${index}:`, result.value);
    } else {
      console.error(`Request ${index} failed:`, result.reason);
    }
  });
}
```

### Sequential vs Parallel Execution

```javascript
// Sequential - each waits for previous
async function sequential() {
  const user = await fetchUser();
  const posts = await fetchPosts(user.id);
  const comments = await fetchComments(posts[0].id);
}

// Parallel - all start together
async function parallel() {
  const [users, posts, comments] = await Promise.all([
    fetchUsers(),
    fetchPosts(),
    fetchComments()
  ]);
}

// Parallel with Promise.all - one fails, all fail
async function parallelWithCatch() {
  const results = await Promise.all([
    fetch('/api/users').then(r => r.json()).catch(e => ({ error: e })),
    fetch('/api/posts').then(r => r.json()).catch(e => ({ error: e }))
  ]);
}
```

### Promise Patterns

```javascript
// Promise.all - all must succeed
Promise.all([
  Promise.resolve(1),
  Promise.resolve(2),
  Promise.resolve(3)
]).then(values => console.log(values)); // [1, 2, 3]

// Promise.race - first to settle wins
Promise.race([
  new Promise(r => setTimeout(() => r('fast'), 50)),
  new Promise(r => setTimeout(() => r('slow'), 100))
]).then(value => console.log(value)); // 'fast'

// Promise.any - first to fulfill wins
Promise.any([
  Promise.reject(new Error('failed')),
  Promise.resolve('success'),
  Promise.resolve('also success')
]).then(value => console.log(value)); // 'success'

// Promise.allSettled - wait for all to settle
Promise.allSettled([
  Promise.resolve(1),
  Promise.reject(new Error('failed'))
]).then(results => {
  results.forEach(result => {
    if (result.status === 'fulfilled') {
      console.log(result.value);
    } else {
      console.error(result.reason);
    }
  });
});
```

### Practical Examples

```javascript
// Retry pattern
async function withRetry(fn, maxAttempts = 3) {
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await fn();
    } catch (error) {
      if (attempt === maxAttempts) throw error;
      console.log(`Attempt ${attempt} failed, retrying...`);
      await new Promise(r => setTimeout(r, 1000 * attempt));
    }
  }
}

// Timeout pattern
function withTimeout(promise, ms) {
  return Promise.race([
    promise,
    new Promise((_, reject) => 
      setTimeout(() => reject(new Error('Timeout')), ms)
    )
  ]);
}

// Queue pattern
class AsyncQueue {
  constructor(concurrency = 1) {
    this.concurrency = concurrency;
    this.running = 0;
    this.queue = [];
  }
  
  add(task) {
    return new Promise((resolve, reject) => {
      this.queue.push({ task, resolve, reject });
      this.process();
    });
  }
  
  async process() {
    if (this.running >= this.concurrency || this.queue.length === 0) return;
    
    this.running++;
    const { task, resolve, reject } = this.queue.shift();
    
    try {
      const result = await task();
      resolve(result);
    } catch (e) {
      reject(e);
    } finally {
      this.running--;
      this.process();
    }
  }
}
```

**Follow-up:**
- What is the difference between Promise.all and Promise.allSettled?
- How does async/await handle errors compared to .then()?
- How do you implement retry logic with async/await?

**Key Points:**
- async functions return Promises automatically
- await pauses until Promise resolves
- try/catch for error handling
- Promise.all/allSettled/race for parallel operations

---

## Question 8: Explain JavaScript's array methods (map, filter, reduce, etc.).

**Answer:**

JavaScript provides powerful array methods for functional programming patterns.

### map() - Transform

```javascript
const numbers = [1, 2, 3, 4, 5];

// Transform each element
const doubled = numbers.map(n => n * 2);
console.log(doubled); // [2, 4, 6, 8, 10]

// With objects
const users = [
  { name: 'John', age: 30 },
  { name: 'Jane', age: 25 }
];

const names = users.map(user => user.name);
console.log(names); // ['John', 'Jane']
```

### filter() - Selection

```javascript
const numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

const evens = numbers.filter(n => n % 2 === 0);
console.log(evens); // [2, 4, 6, 8, 10]

const adults = users.filter(user => user.age >= 18);
```

### reduce() - Aggregation

```javascript
const numbers = [1, 2, 3, 4, 5];

// Sum
const sum = numbers.reduce((acc, n) => acc + n, 0);
console.log(sum); // 15

// Max
const max = numbers.reduce((a, b) => a > b ? a : b, 0);
console.log(max); // 5

// Grouping
const orders = [
  { id: 1, status: 'pending' },
  { id: 2, status: 'completed' },
  { id: 3, status: 'pending' }
];

const grouped = orders.reduce((acc, order) => {
  const status = order.status;
  (acc[status] = acc[status] || []).push(order);
  return acc;
}, {});

console.log(grouped);
// { pending: [order1, order3], completed: [order2] }
```

### find() / findIndex()

```javascript
const users = [
  { id: 1, name: 'John' },
  { id: 2, name: 'Jane' }
];

const user = users.find(u => u.id === 2);
console.log(user); // { id: 2, name: 'Jane' }

const index = users.findIndex(u => u.id === 2);
console.log(index); // 1
```

### some() / every()

```javascript
const numbers = [1, 2, 3, 4, 5];

// some - at least one passes
const hasEven = numbers.some(n => n % 2 === 0);
console.log(hasEven); // true

// every - all pass
const allPositive = numbers.every(n => n > 0);
console.log(allPositive); // true
```

### Chaining Methods

```javascript
const numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

const result = numbers
  .filter(n => n % 2 === 0)    // [2, 4, 6, 8, 10]
  .map(n => n * 2)             // [4, 8, 12, 16, 20]
  .reduce((a, b) => a + b, 0); // 60
```

### flat() / flatMap()

```javascript
const nested = [[1, 2], [3, 4], [5]];

const flat = nested.flat();
console.log(flat); // [1, 2, 3, 4, 5]

const flatMap = nested.flatMap(arr => arr.map(n => n * 2));
console.log(flatMap); // [2, 4, 6, 8, 10]
```

### forEach() vs map()

```javascript
const numbers = [1, 2, 3];

// forEach - for side effects, returns undefined
const forEachResult = numbers.forEach(n => console.log(n));
console.log(forEachResult); // undefined

// map - for transformation, returns new array
const mapResult = numbers.map(n => n * 2);
console.log(mapResult); // [2, 4, 6]
```

### Performance Considerations

```javascript
// for loop - fastest
for (let i = 0; i < arr.length; i++) { }

// for...of - cleaner, similar performance
for (const item of arr) { }

// reduce - can be slower but more functional
arr.reduce((acc, val) => { }, []);

// Avoid in hot paths
// 1. Chaining multiple maps
arr.map(x => x * 2).map(x => x + 1);
// Better: arr.map(x => (x * 2) + 1)

// 2. Nested loops in reduce
arr.reduce((acc, val) => {
  nested.forEach(n => acc.push(n)); // O(n*m)
  return acc;
}, []);
```

**Follow-up:**
- When should you use reduce vs forEach?
- What are the performance differences between array methods?
- How do you handle errors in chained array methods?

**Key Points:**
- map: transform each element
- filter: select elements meeting condition
- reduce: aggregate to single value
- Method chaining for complex operations

---

## Question 9: Explain JavaScript's object and array destructuring.

**Answer:**

Destructuring provides syntax for extracting values from arrays and objects.

### Object Destructuring

```javascript
const user = {
  name: 'John',
  age: 30,
  email: 'john@example.com'
};

// Basic
const { name, age } = user;
console.log(name, age); // 'John', 30

// Renaming
const { name: userName, age: userAge } = user;

// Default values
const { name, country = 'USA' } = user;

// Nested destructuring
const person = {
  profile: {
    name: 'John',
    address: { city: 'NYC', country: 'USA' }
  }
};

const { profile: { address: { city } } } = person;
console.log(city); // 'NYC'
```

### Array Destructuring

```javascript
const colors = ['red', 'green', 'blue', 'yellow'];

// Basic
const [first, second] = colors;
console.log(first, second); // 'red', 'green'

// Skip elements
const [, , third] = colors;
console.log(third); // 'blue'

// Rest pattern
const [head, ...tail] = colors;
console.log(head); // 'red'
console.log(tail); // ['green', 'blue', 'yellow']

// Default values
const [a, b, c, d = 'black'] = colors;
```

### Function Parameter Destructuring

```javascript
// Object parameter
function greet({ name, age }) {
  return `Hello ${name}, you are ${age}`;
}

greet({ name: 'John', age: 30 });

// Array parameter
function getFirst([first]) {
  return first;
}

getFirst([1, 2, 3]);

// With defaults
function process({
  data = [],
  config = {}
} = {}) {
  return data.map(item => item.id);
}
```

### Practical Patterns

```javascript
// Swap variables
let a = 1, b = 2;
[a, b] = [b, a];
console.log(a, b); // 2, 1

// Extract from imports
import { useState, useEffect } from 'vue';

// Parse API response
const response = {
  data: {
    users: [
      { id: 1, name: 'John', email: 'john@example.com' }
    ],
    meta: { page: 1, total: 100 }
  },
  status: 200
};

const { data: { users, meta }, status } = response;

// Use in React
function UserCard({ user: { name, email, avatar } }) {
  return (
    <div>
      <img src={avatar} alt={name} />
      <h3>{name}</h3>
      <p>{email}</p>
    </div>
  );
}
```

### Combined Destructuring

```javascript
const config = {
  api: {
    baseUrl: 'https://api.example.com',
    endpoints: {
      users: '/users',
      posts: '/posts'
    }
  },
  settings: {
    theme: 'dark'
  }
};

const {
  api: { baseUrl, endpoints: { users, posts } },
  settings: { theme }
} = config;
```

### Common Mistakes

```javascript
// Forgetting default for optional objects
function bad({ name }) { } // Error if undefined passed
function good({ name = 'Guest' } = {}) { } // Safe

// Trying to destructure null/undefined
const obj = null;
// const { a } = obj; // TypeError
const { a } = obj || {}; // Safe
```

**Follow-up:**
- What is the rest pattern in destructuring?
- How do default values work in destructuring?
- What happens when you try to destructure undefined?

**Key Points:**
- Extract values with cleaner syntax
- Rename with colon syntax
- Default values with equals
- Rest pattern for remaining elements

---

## Question 10: Explain JavaScript's ES6+ features (classes, modules, symbols, etc.).

**Answer:**

ES6+ introduced significant features that modernized JavaScript.

### Classes

```javascript
class Animal {
  static count = 0; // Class property
  
  constructor(name) {
    this.name = name;
    Animal.count++;
  }
  
  speak() {
    return `${this.name} makes a sound`;
  }
  
  // Getter
  get info() {
    return `${this.name} (Total: ${Animal.count})`;
  }
  
  // Setter
  set info(value) {
    this.name = value;
  }
  
  // Static method
  static create(name) {
    return new Animal(name);
  }
}

class Dog extends Animal {
  constructor(name, breed) {
    super(name);
    this.breed = breed;
  }
  
  speak() {
    return `${this.name} barks`;
  }
}

const dog = new Dog('Buddy', 'Labrador');
console.log(dog.speak()); // 'Buddy barks'
```

### Modules

```javascript
// math.js (named exports)
export const add = (a, b) => a + b;
export const subtract = (a, b) => a - b;
export default multiply; // Default export

// main.js
import multiply, { add, subtract } from './math.js';
import * as Math from './math.js'; // Namespace import

// Re-exporting
export { add, subtract } from './math.js';
export { default } from './other.js';
```

### Symbols

```javascript
// Create unique symbol
const sym = Symbol('description');
console.log(sym); // Symbol(description)

// Use as object key
const obj = {
  [Symbol('a')]: 1,
  [Symbol('a')]: 2 // Different symbol!
};

// Symbol.for - global symbol registry
const sym1 = Symbol.for('myKey');
const sym2 = Symbol.for('myKey');
console.log(sym1 === sym2); // true

// Well-known symbols
// Symbol.iterator - custom iteration
// Symbol.toStringTag - custom toString behavior
// Symbol.hasInstance - custom instanceof
```

### Promises

```javascript
// Promise creation
const promise = new Promise((resolve, reject) => {
  const success = true;
  if (success) {
    resolve('Success!');
  } else {
    reject(new Error('Failed'));
  }
});

// Promise chaining
fetchData()
  .then(data => process(data))
  .then(processed => save(processed))
  .catch(error => handleError(error))
  .finally(() => cleanup());
```

### Generators

```javascript
function* numberGenerator() {
  yield 1;
  yield 2;
  yield 3;
  return 'done';
}

const gen = numberGenerator();
console.log(gen.next()); // { value: 1, done: false }
console.log(gen.next()); // { value: 2, done: false }
console.log(gen.next()); // { value: 3, done: false }
console.log(gen.next()); // { value: 'done', done: true }

// Generator with input
function* fibonacci() {
  let [prev, curr] = [0, 1];
  while (true) {
    const input = yield curr;
    [prev, curr] = [curr, prev + curr];
    if (input) prev = input;
  }
}
```

### Proxies

```javascript
const handler = {
  get(target, property) {
    console.log(`Accessing ${property}`);
    return target[property];
  },
  set(target, property, value) {
    console.log(`Setting ${property} to ${value}`);
    target[property] = value;
    return true;
  }
};

const proxy = new Proxy({}, handler);
proxy.name = 'John'; // Setting name to John
console.log(proxy.name); // Accessing name, John
```

### Reflect

```javascript
const obj = { name: 'John' };

// Reflect.get
console.log(Reflect.get(obj, 'name'));

// Reflect.set
Reflect.set(obj, 'age', 30);

// Reflect.has
console.log(Reflect.has(obj, 'name'));

// Reflect.deleteProperty
Reflect.deleteProperty(obj, 'age');
```

### BigInt

```javascript
const bigNumber = 9007199254740991n + 1n;
console.log(bigNumber); // 9007199254740992n

// Methods
console.log(bigNumber.toString());
console.log(BigInt.asUintN(32, bigNumber));
```

### Optional Chaining & Nullish Coalescing

```javascript
const user = {
  profile: {
    address: null
  }
};

// Optional chaining
const city = user?.profile?.address?.city;
const method = user?.profile?.sayHello?.();

// Nullish coalescing
const value = null ?? 'default'; // 'default'
const zero = 0 ?? 'default'; // 0 (not nullish)
const empty = '' ?? 'default'; // '' (not nullish)
```

### Spread & Rest Operators

```javascript
// Spread in objects
const obj1 = { a: 1 };
const obj2 = { ...obj1, b: 2 };

// Spread in arrays
const arr1 = [1, 2, 3];
const arr2 = [...arr1, 4, 5];

// Rest parameters
function sum(...numbers) {
  return numbers.reduce((a, b) => a + b, 0);
}
```

**Follow-up:**
- What is the difference between import and import()?
- How do you create custom iterables?
- What are the use cases for Proxies?

**Key Points:**
- Classes: syntactic sugar for prototypal inheritance
- Modules: ES6 native import/export
- Symbols: unique object keys
- async/await: cleaner Promise handling
- Optional chaining: safe property access

---

## Question 11: Explain JavaScript's garbage collection and memory management.

**Answer:**

JavaScript automatically manages memory using garbage collection, but understanding it helps prevent memory leaks.

### Memory Lifecycle

```javascript
// 1. Allocation
const obj = { name: 'John' }; // Memory allocated

// 2. Usage
console.log(obj.name); // Read
obj.age = 30; // Write

// 3. Release (automatic)
obj = null; // Old object becomes eligible for GC
```

### Garbage Collection Algorithms

```javascript
// Reference Counting (older, has issues)
const obj = { name: 'John' };
const ref = obj; // Two references

ref = null; // One reference remains
obj = null; // Zero references - eligible for GC

// Problem: Circular references
function createCircular() {
  const objA = {};
  const objB = {};
  
  objA.ref = objB; // objA references objB
  objB.ref = objA; // objB references objA
  
  return { objA, objB };
}

// Modern JS uses Mark and Sweep
function outer() {
  const inner = { data: 'test' };
  // Objects created in outer() are marked
  // When outer() finishes, unmarked
}
// Objects inside outer() are collected
```

### Memory Leaks

```javascript
// 1. Global variables
function leak() {
  hugeData = 'This leaks!'; // Becomes global
}
// Fix: 'use strict' or const/let

// 2. Closures
function createLeak() {
  const bigArray = new Array(1000000);
  
  return function() {
    return bigArray[0]; // bigArray never released
  };
}

// 3. Detached DOM nodes
function leakDOM() {
  const elem = document.createElement('div');
  document.body.appendChild(elem);
  
  elem.addEventListener('click', () => {
    console.log(elem); // elem referenced in closure
  });
  
  document.body.removeChild(elem); // DOM removed but reference remains
}

// Fix: elem.removeEventListener() or use { once: true }

// 4. Timers
function leakWithTimer() {
  const data = { id: 1 };
  
  setInterval(() => {
    console.log(data); // 'data' can't be GC'd
  }, 1000);
  
  // Fix: clearInterval when done
}

// 5. Event listeners
class Component {
  constructor() {
    window.addEventListener('resize', this.handleResize);
  }
  
  handleResize() {
    // Uses 'this'
  }
  
  destroy() {
    window.removeEventListener('resize', this.handleResize);
  }
}
```

### WeakRef and FinalizationRegistry

```javascript
// WeakRef - hold reference without preventing GC
let ref;
{
  const obj = { data: 'important' };
  ref = new WeakRef(obj);
  console.log(ref.deref()); // { data: 'important' }
}
// obj eligible for GC now
console.log(ref.deref()); // undefined (might be collected)

// FinalizationRegistry - callback when objects GC'd
const registry = new FinalizationRegistry((name) => {
  console.log(`${name} was garbage collected`);
});

let obj = { id: 1 };
registry.register(obj, 'myObject');
obj = null; // 'myObject was garbage collected'
```

### Memory Profiling

```javascript
// Performance memory snapshots
// Chrome DevTools: Memory tab

// console.memory (Chrome only)
if (console.memory) {
  console.log(console.memory);
}

// WeakRef example for understanding
function createCache() {
  const cache = new Map();
  
  return {
    get(key) {
      const ref = cache.get(key);
      return ref?.deref();
    },
    set(key, value) {
      cache.set(key, new WeakRef(value));
    }
  };
}
```

### Best Practices

```javascript
// 1. Nullify references when done
const largeObject = { data: new Array(1000000) };
// Process...
largeObject = null;

// 2. Clean up event listeners
element.addEventListener('click', handler);
element.removeEventListener('click', handler);

// 3. Use weak references for caches
const cache = new Map(); // Can cause memory issues
const weakCache = new WeakMap(); // Allows GC of keys

// 4. Clear intervals/timeouts
const interval = setInterval(() => {}, 1000);
clearInterval(interval);

// 5. Modularize to avoid global pollution
(function() {
  // Private scope
})();
```

**Follow-up:**
- What is the difference between Map and WeakMap?
- How does the mark and sweep algorithm work?
- What are signs of memory leaks in production?

**Key Points:**
- JavaScript uses automatic garbage collection
- Common leaks: globals, closures, detached DOM, timers
- Use WeakMap/WeakSet for caches
- Profile with Chrome DevTools to find leaks

---

## Question 12: Explain JavaScript's Web APIs and common browser APIs.

**Answer:**

Web APIs extend JavaScript's capabilities for browser development.

### Fetch API

```javascript
// Basic GET
fetch('/api/users')
  .then(response => {
    if (!response.ok) throw new Error('Network error');
    return response.json();
  })
  .then(data => console.log(data))
  .catch(error => console.error(error));

// POST with JSON
fetch('/api/users', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ name: 'John' })
})
  .then(r => r.json())
  .then(data => console.log(data));

// AbortController for cancellation
const controller = new AbortController();
fetch('/api/users', { signal: controller.signal })
  .then(r => r.json());

controller.abort(); // Cancel request
```

### Local Storage / Session Storage

```javascript
// Local Storage (persistent)
localStorage.setItem('user', JSON.stringify({ name: 'John' }));
const user = JSON.parse(localStorage.getItem('user'));
localStorage.removeItem('user');
localStorage.clear();

// Session Storage (session-based)
sessionStorage.setItem('temp', 'value');
const temp = sessionStorage.getItem('temp');

// Limitations: Only strings, 5MB limit
```

### IndexedDB

```javascript
// Open database
const request = indexedDB.open('myDB', 1);

request.onerror = () => console.error('Error');

request.onupgradeneeded = (event) => {
  const db = event.target.result;
  
  if (!db.objectStoreNames.contains('users')) {
    const store = db.createObjectStore('users', { keyPath: 'id' });
    store.createIndex('name', 'name', { unique: false });
  }
};

request.onsuccess = (event) => {
  const db = event.target.result;
  
  // Add data
  const transaction = db.transaction(['users'], 'readwrite');
  const store = transaction.objectStore('users');
  store.add({ id: 1, name: 'John', email: 'john@example.com' });
  
  // Query data
  const getRequest = store.get(1);
  getRequest.onsuccess = () => console.log(getRequest.result);
};
```

### Intersection Observer

```javascript
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        console.log('Element visible!');
        entry.target.classList.add('visible');
        // observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.5 }
);

document.querySelectorAll('.lazy').forEach(el => observer.observe(el));
```

### Mutation Observer

```javascript
const observer = new MutationObserver((mutations) => {
  mutations.forEach(mutation => {
    console.log(mutation.type, mutation.target);
  });
});

observer.observe(document.body, {
  childList: true,
  subtree: true,
  attributes: true,
  characterData: true
});

// observer.disconnect();
```

### Web Workers

```javascript
// main.js
const worker = new Worker('worker.js');

worker.postMessage({ type: 'compute', data: [1, 2, 3] });

worker.onmessage = (event) => {
  console.log('Result:', event.data);
};

worker.terminate();

// worker.js
self.onmessage = (event) => {
  if (event.data.type === 'compute') {
    const result = event.data.data.reduce((a, b) => a + b, 0);
    self.postMessage(result);
  }
};
```

### Other Web APIs

```javascript
// Geolocation
navigator.geolocation.getCurrentPosition(
  (position) => console.log(position.coords),
  (error) => console.error(error)
);

// Drag and Drop
element.addEventListener('dragstart', (e) => {
  e.dataTransfer.setData('text/plain', 'data');
});

element.addEventListener('drop', (e) => {
  const data = e.dataTransfer.getData('text/plain');
});

// Broadcast Channel
const channel = new BroadcastChannel('my-channel');
channel.postMessage({ type: 'update', data: 'something' });
channel.onmessage = (event) => console.log(event.data);

// WebSocket
const socket = new WebSocket('wss://example.com');
socket.onopen = () => socket.send('hello');
socket.onmessage = (event) => console.log(event.data);
```

**Follow-up:**
- What are the limitations of localStorage?
- How do you handle CORS with Fetch API?
- When would you use Web Workers vs Service Workers?

**Key Points:**
- Fetch API: HTTP requests
- Local/Session Storage: key-value storage
- IndexedDB: structured storage
- Intersection Observer: lazy loading, infinite scroll
- Web Workers: background processing

---

## Question 13: Explain JavaScript's functional programming patterns.

**Answer:**

Functional programming uses functions as first-class citizens to create declarative, composable code.

### Pure Functions

```javascript
// Pure - same input always gives same output, no side effects
function add(a, b) {
  return a + b;
}

// Impure - side effects
let count = 0;
function increment() {
  count++; // Modifies external state
  return count;
}

// Pure version
function incrementPure(currentCount) {
  return currentCount + 1;
}
```

### Immutability

```javascript
// Immutable update patterns
const state = { name: 'John', age: 30 };

// Bad - mutation
state.age = 31;

// Good - create new object
const newState = { ...state, age: 31 };

// Array immutability
const numbers = [1, 2, 3, 4, 5];

// Bad
numbers.push(6);
numbers.splice(0, 1);
numbers[0] = 10;

// Good
const added = [...numbers, 6];
const removed = numbers.slice(1);
const updated = numbers.map((n, i) => i === 0 ? 10 : n);

// Deep immutability
const nested = { a: { b: { c: 1 } } };
const updatedNested = {
  ...nested,
  a: {
    ...nested.a,
    b: { ...nested.a.b, c: 2 }
  }
};
```

### Higher-Order Functions

```javascript
// Functions that take/return functions
function compose(...fns) {
  return (value) => 
    fns.reduceRight((acc, fn) => fn(acc), value);
}

function addOne(x) { return x + 1; }
function double(x) { return x * 2; }

const addOneThenDouble = compose(double, addOne);
console.log(addOneThenDouble(5)); // 12

// Function currying
const curriedAdd = (a) => (b) => a + b;
const addFive = curriedAdd(5);
console.log(addFive(3)); // 8

// Function partial application
const partial = (fn, ...args) => (...rest) => fn(...args, ...rest);
const multiply = (a, b) => a * b;
const double = partial(multiply, 2);
console.log(double(5)); // 10
```

### Functors and Monads

```javascript
// Functor - implements map
const functor = {
  value: 5,
  map(fn) {
    return functor.of(fn(this.value));
  },
  static of(value) {
    return { value, map: functor.map };
  }
};

// Maybe monad - handles null/undefined
const Maybe = {
  of(value) {
    return value == null 
      ? { just: null, isNothing: true }
      : { just: value, isNothing: false };
  },
  map(fn) {
    return this.isNothing ? this : Maybe.of(fn(this.just));
  },
  flatMap(fn) {
    return this.isNothing ? this : fn(this.just);
  },
  getOrElse(defaultValue) {
    return this.isNothing ? defaultValue : this.just;
  }
};

// Either monad - handles errors
const Either = {
  left(value) {
    return { isLeft: true, value };
  },
  right(value) {
    return { isLeft: false, value };
  },
  map(fn) {
    return this.isLeft ? this : Either.right(fn(this.value));
  }
};
```

### Composition Patterns

```javascript
// Pipe - left to right
const pipe = (...fns) => (value) => 
  fns.reduce((acc, fn) => fn(acc), value);

// Compose - right to left  
const compose = (...fns) => (value) =>
  fns.reduceRight((acc, fn) => fn(acc), value);

// Practical example
const processUser = pipe(
  validateUser,
  normalizeUser,
  saveToDatabase,
  sendWelcomeEmail
);
```

### Practical FP Utilities

```javascript
// Curry utility
const curry = (fn) => 
  (...args) => 
    args.length >= fn.length 
      ? fn(...args) 
      : (...more) => fn(...args, ...more);

// Memoize
const memoize = (fn) => {
  const cache = new Map();
  return (...args) => {
    const key = JSON.stringify(args);
    if (cache.has(key)) return cache.get(key);
    const result = fn(...args);
    cache.set(key, result);
    return result;
  };
};

// Pipe with error handling
const pipeWith = (...fns) => (value) => {
  return fns.reduce((result, fn) => {
    if (result.isError) return result;
    try {
      return { value: fn(result.value), isError: false };
    } catch (e) {
      return { value: e, isError: true };
    }
  }, { value, isError: false });
};
```

**Follow-up:**
- What are the benefits of immutability?
- How do you handle errors in functional pipelines?
- What is the difference between currying and partial application?

**Key Points:**
- Pure functions: no side effects, same input = same output
- Immutability: create new objects instead of mutating
- Higher-order functions: functions as arguments/return values
- Composition: combine small functions for complex logic

---

## Question 14: Explain JavaScript's error handling patterns.

**Answer:**

Proper error handling prevents application crashes and provides debugging information.

### Try/Catch

```javascript
// Basic
try {
  const result = riskyOperation();
  console.log(result);
} catch (error) {
  console.error('Error:', error.message);
}

// Finally
try {
  const data = fetchData();
} catch (e) {
  console.error(e);
} finally {
  cleanup(); // Always runs
}
```

### Error Types

```javascript
// Built-in error types
try {
  throw new TypeError('Expected a number');
} catch (e) {
  if (e instanceof TypeError) {
    console.log('Type error:', e.message);
  } else if (e instanceof RangeError) {
    console.log('Range error:', e.message);
  }
}

// Custom errors
class AppError extends Error {
  constructor(message, code, statusCode = 500) {
    super(message);
    this.name = this.constructor.name;
    this.code = code;
    this.statusCode = statusCode;
    Error.captureStackTrace(this, this.constructor);
  }
}

class ValidationError extends AppError {
  constructor(message, errors = []) {
    super(message, 'VALIDATION_ERROR', 400);
    this.errors = errors;
  }
}

// Usage
try {
  throw new ValidationError('Invalid input', [
    { field: 'email', message: 'Invalid format' }
  ]);
} catch (e) {
  if (e instanceof ValidationError) {
    console.log(e.errors);
  }
}
```

### Async Error Handling

```javascript
// async/await with try/catch
async function fetchData() {
  try {
    const response = await fetch('/api/data');
    if (!response.ok) throw new Error('HTTP error');
    return await response.json();
  } catch (error) {
    console.error('Fetch failed:', error);
    throw error;
  }
}

// Promise.catch
fetchData()
  .then(data => console.log(data))
  .catch(error => console.error(error));

// Top-level await (ES2022)
try {
  const data = await fetchData();
} catch (e) {
  console.error(e);
}

// Global error handler
window.addEventListener('unhandledrejection', (event) => {
  console.error('Unhandled promise rejection:', event.reason);
});

window.addEventListener('error', (event) => {
  console.error('Uncaught error:', event.error);
});
```

### Error Boundaries (React Pattern)

```javascript
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }
  
  static getDerivedStateFromError(error) {
    return { hasError: true };
  }
  
  componentDidCatch(error, errorInfo) {
    logError(error, errorInfo);
  }
  
  render() {
    if (this.state.hasError) {
      return this.props.fallback || <div>Something went wrong</div>;
    }
    return this.props.children;
  }
}

// Usage
<ErrorBoundary fallback={<ErrorPage />}>
  <MyComponent />
</ErrorBoundary>
```

### Result Pattern

```javascript
// Result type for error handling without exceptions
class Result {
  constructor(value, error) {
    this.value = value;
    this.error = error;
  }
  
  static ok(value) {
    return new Result(value, null);
  }
  
  static fail(error) {
    return new Result(null, error);
  }
  
  isOk() {
    return this.error === null;
  }
  
  map(fn) {
    return this.isOk() 
      ? Result.ok(fn(this.value))
      : this;
  }
  
  getOrElse(defaultValue) {
    return this.isOk() ? this.value : defaultValue;
  }
}

// Usage
function divide(a, b) {
  if (b === 0) {
    return Result.fail(new Error('Division by zero'));
  }
  return Result.ok(a / b);
}

const result = divide(10, 2);
console.log(result.getOrElse(0)); // 5
```

### Retry Logic

```javascript
async function retry(fn, maxAttempts = 3, delay = 1000) {
  let lastError;
  
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await fn();
    } catch (error) {
      lastError = error;
      console.log(`Attempt ${attempt} failed, retrying in ${delay}ms`);
      if (attempt < maxAttempts) {
        await new Promise(r => setTimeout(r, delay));
        delay *= 2; // Exponential backoff
      }
    }
  }
  
  throw lastError;
}

// Usage
const data = await retry(() => fetch('/api/data').then(r => r.json()));
```

**Follow-up:**
- What is the difference between try/catch and Result pattern?
- How do you handle errors in async/await?
- What are best practices for error logging?

**Key Points:**
- Use try/catch for synchronous code
- Handle async errors with .catch() or try/catch in async functions
- Custom error classes for domain-specific errors
- Consider Result pattern for expected error flows

---

## Question 15: Explain JavaScript's module systems and bundling.

**Answer:**

JavaScript has evolved to support modular code organization through various module systems.

### ES Modules (ESM)

```javascript
// named exports
export const API_URL = 'https://api.example.com';
export function fetchUsers() { }
export class UserService { }

// default export
export default class ApiClient { }

// importing
import ApiClient, { API_URL, fetchUsers } from './api.js';
import * as Api from './api.js'; // namespace import
```

### CommonJS (Node.js)

```javascript
// exports
module.exports = { name: 'myModule' };

// or
exports.myFunction = function() { };

// require
const myModule = require('./myModule');
```

### Dynamic Import

```javascript
// Lazy load module
const { heavyFunction } = await import('./heavy.js');

// Conditional load
if (isFeatureEnabled('newFeature')) {
  const module = await import('./newFeature.js');
  module.init();
}
```

### Module Resolution

```javascript
// Node.js module resolution algorithm
// 1. /root/node_modules/package.json -> main
// 2. /root/node_modules/package/index.js
// 3. /node_modules/package.json -> main
// 4. /node_modules/package/index.js

// Import with extension (required in ESM)
import './utils.js'; // must include .js
```

### Bundling with Webpack

```javascript
// webpack.config.js
module.exports = {
  entry: './src/index.js',
  output: {
    filename: 'bundle.js',
    path: __dirname + '/dist'
  },
  resolve: {
    extensions: ['.js', '.jsx', '.ts', '.tsx']
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: 'babel-loader'
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      }
    ]
  },
  plugins: [
    new HtmlWebpackPlugin(),
    new MiniCssExtractPlugin()
  ],
  optimization: {
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          chunks: 'all'
        }
      }
    }
  }
};
```

### Vite (Modern Bundler)

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': '/src'
    }
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['vue', 'vue-router'],
          'utils': ['lodash', 'date-fns']
        }
      }
    }
  },
  server: {
    proxy: {
      '/api': 'http://localhost:3000'
    }
  }
});
```

### Tree Shaking

```javascript
// math.js
export const add = (a, b) => a + b;
export const subtract = (a, b) => a - b;
export const multiply = (a, b) => a * b; // Unused, will be removed

// main.js
import { add } from './math.js';
// multiply is tree-shaken - not included in bundle
```

### Code Splitting

```javascript
// Route-based splitting
const Home = () => import('./Home.js');
const About = () => import('./About.js');

const router = createRouter({
  routes: [
    { path: '/', component: Home },
    { path: '/about', component: About }
  ]
});

// Component-level splitting
const HeavyChart = React.lazy(() => import('./HeavyChart.js'));

<Suspense fallback={<Loading />}>
  <HeavyChart data={data} />
</Suspense>
```

### Module Best Practices

```javascript
// barrel pattern - re-export from index
// utils/index.js
export { formatDate } from './date.js';
export { formatCurrency } from './currency.js';
export { capitalize, truncate } from './string.js';

// Named exports over default
// ✅ Good
export function fetchUsers() { }
export function fetchPosts() { }

// ❌ Avoid default
export default function fetchData() { }

// Use path aliases
// tsconfig.json
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./src/*"],
      "@components/*": ["./src/components/*"]
    }
  }
}
```

**Follow-up:**
- What are the differences between ESM and CommonJS?
- How does tree shaking work?
- When should you use code splitting?

**Key Points:**
- ES Modules: standard JavaScript module system
- Dynamic import: lazy loading
- Bundlers: Webpack, Vite, Rollup
- Tree shaking: remove unused code
- Code splitting: split bundles for better loading

---

## Notes

Add more questions covering:
- Type coercion and equality
- DOM manipulation
- Design patterns in JavaScript
- Testing JavaScript code
- Performance optimization
- Security best practices
