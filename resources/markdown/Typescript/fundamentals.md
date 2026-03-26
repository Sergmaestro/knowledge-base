# TypeScript Fundamentals

## Question 1: Explain TypeScript's type system and type inference.

**Answer:**

TypeScript adds static typing to JavaScript while preserving JavaScript's dynamic nature through type inference.

### Basic Type Annotations

```typescript
// Primitive types
let name: string = 'John';
let age: number = 30;
let isActive: boolean = true;
let nothing: null = null;
let notDefined: undefined = undefined;

// Arrays
let numbers: number[] = [1, 2, 3];
let strings: Array<string> = ['a', 'b', 'c'];

// Objects
let user: { name: string; age: number } = {
  name: 'John',
  age: 30
};

// Type aliases
type User = {
  name: string;
  age: number;
  email?: string; // Optional property
};

const user: User = { name: 'John', age: 30 };
```

### Type Inference

```typescript
// TypeScript infers types from initial values
let x = 10; // inferred as number
x = 'hello'; // Error: Type 'string' is not assignable to type 'number'

// Function return type inference
function add(a: number, b: number) {
  return a + b; // inferred as number
}

// Contextual typing
const numbers = [1, 2, 3];
numbers.map(n => n * 2); // n inferred as number
```

### Union and Intersection Types

```typescript
// Union types
type StringOrNumber = string | number;
let value: StringOrNumber = 'hello';
value = 42; // OK

// Type guards
function processValue(val: StringOrNumber) {
  if (typeof val === 'string') {
    return val.toUpperCase(); // val is string here
  }
  return val.toFixed(2); // val is number here
}

// Intersection types
type Named = { name: string };
type Aged = { age: number };
type Person = Named & Aged;

const person: Person = { name: 'John', age: 30 };
```

### Generic Type Inference

```typescript
// Generic functions
function first<T>(arr: T[]): T | undefined {
  return arr[0];
}

const nums = first([1, 2, 3]); // inferred as number
const strs = first(['a', 'b', 'c']); // inferred as string

// Generic classes
class Box<T> {
  content: T;
  
  constructor(content: T) {
    this.content = content;
  }
  
  getContent(): T {
    return this.content;
  }
}

const box = new Box<string>('hello');
const box2 = new Box(123); // TypeScript infers number
```

### Mapped Types

```typescript
type Readonly<T> = {
  readonly [P in keyof T]: T[P];
};

type Partial<T> = {
  [P in keyof T]?: T[P];
};

type User = { name: string; age: number };
type ReadonlyUser = Readonly<User>;
type PartialUser = Partial<User>;
```

**Follow-up:**
- What is the difference between type aliases and interfaces?
- How does TypeScript handle structural typing?
- What are conditional types?

**Key Points:**
- TypeScript uses structural typing (compatible structure matters)
- Type inference reduces explicit type annotations
- Union types for values of multiple types
- Intersection types combine multiple types
- Generics provide reusable type-safe code

---

## Question 2: Explain TypeScript's interfaces vs type aliases.

**Answer:**

Both interfaces and type aliases can define object shapes, but they have different capabilities.

### Interface Basics

```typescript
interface User {
  id: number;
  name: string;
  email: string;
}

// Extending interfaces
interface Admin extends User {
  role: 'admin' | 'super_admin';
  permissions: string[];
}

// Interface declaration merging
interface User {
  avatar?: string;
}

// Now User has: id, name, email, avatar
```

### Type Alias Basics

```typescript
type User = {
  id: number;
  name: string;
  email: string;
};

// Union types - only with type
type Status = 'pending' | 'active' | 'completed';

// Tuple types - only with type
type Point = [number, number];

// Intersection types
type Admin = User & {
  role: string;
  permissions: string[];
};
```

### When to Use Each

```typescript
// Use interface for:
// - Object shapes that might be extended
// - Class implementers
// - Declaration merging
interface Animal {
  name: string;
  speak(): void;
}

interface Dog extends Animal {
  breed: string;
}

class Labrador implements Dog {
  name = 'Max';
  breed = 'Labrador';
  speak() {
    console.log('Woof!');
  }
}

// Use type alias for:
// - Union types
// - Tuple types
// - Utility types
type Result<T> = {
  success: boolean;
  data?: T;
  error?: string;
};

type Coordinates = [number, number, number?]; // Tuple with optional
```

### Practical Examples

```typescript
// Interface for API response
interface ApiResponse<T> {
  data: T;
  status: number;
  message: string;
  timestamp: Date;
}

// Type for error states
type ErrorState = {
  error: string;
  code?: number;
};

// Combining both
interface ApiEndpoint {
  path: string;
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
}

type ApiHandler<T> = (request: T) => Promise<ApiResponse<unknown>>;
```

**Follow-up:**
- What is declaration merging in TypeScript?
- Can interfaces extend union types?
- What are the performance differences?

**Key Points:**
- Interfaces: better for objects, supports inheritance, declaration merging
- Type aliases: more flexible (unions, tuples, primitives)
- Interface extends preferred for object inheritance
- Type alias with union preferred for combined types

---

## Question 3: Explain TypeScript's generics and their practical uses.

**Answer:**

Generics enable creating reusable, type-safe components that work with any data type while maintaining type safety.

### Generic Functions

```typescript
// Basic generic function
function identity<T>(value: T): T {
  return value;
}

// Generic with constraints
interface Lengthwise {
  length: number;
}

function logLength<T extends Lengthwise>(arg: T): T {
  console.log(arg.length);
  return arg;
}

logLength('hello'); // OK - string has length
logLength([1, 2, 3]); // OK - arrays have length
logLength({ length: 10 }); // OK - object with length property

// Multiple type parameters
function map<T, U>(array: T[], fn: (item: T) => U): U[] {
  return array.map(fn);
}

const result = map([1, 2, 3], n => n.toString());
// result: string[]
```

### Generic Classes

```typescript
class Stack<T> {
  private items: T[] = [];
  
  push(item: T): void {
    this.items.push(item);
  }
  
  pop(): T | undefined {
    return this.items.pop();
  }
  
  peek(): T | undefined {
    return this.items[this.items.length - 1];
  }
}

const numberStack = new Stack<number>();
numberStack.push(1);
numberStack.push(2);
console.log(numberStack.pop()); // 2

const stringStack = new Stack<string>();
stringStack.push('hello');
```

### Generic Constraints

```typescript
// extends keyword for constraints
interface HasId {
  id: number;
}

function findById<T extends HasId>(items: T[], id: number): T | undefined {
  return items.find(item => item.id === id);
}

type User = { id: number; name: string };
const user = findById<User>([{ id: 1, name: 'John' }], 1);

// keyof constraint
function getProperty<T, K extends keyof T>(obj: T, key: K): T[K] {
  return obj[key];
}

const user = { name: 'John', age: 30 };
getProperty(user, 'name'); // 'John'
getProperty(user, 'email'); // Error: 'email' not in user
```

### Built-in Utility Types

```typescript
// Partial - all properties optional
interface User {
  id: number;
  name: string;
  email: string;
}

type PartialUser = Partial<User>;
// { id?: number; name?: string; email?: string }

// Required - all properties required
type RequiredUser = Required<PartialUser>;

// Readonly - all properties readonly
type ReadonlyUser = Readonly<User>;

// Pick - select specific properties
type UserPreview = Pick<User, 'id' | 'name'>;

// Omit - exclude specific properties
type UserWithoutEmail = Omit<User, 'email'>;

// Record - create object type with specific keys
type UserRoles = Record<string, 'admin' | 'user' | 'guest'>;

// Extract and Exclude
type T = Extract<'a' | 'b' | 'c', 'a' | 'f'>; // 'a'
type E = Exclude<'a' | 'b' | 'c', 'a'>; // 'b' | 'c'
```

### Conditional Types

```typescript
// Basic conditional type
type IsString<T> = T extends string ? true : false;
type Test1 = IsString<string>; // true
type Test2 = IsString<number>; // false

// Infer for return types
type ReturnType<T> = T extends (...args: any[]) => infer R ? R : never;
type Fn = () => number;
type R = ReturnType<Fn>; // number

// Parameters
type Parameters<T> = T extends (...args: infer P) => any ? P : never;
```

**Follow-up:**
- What is the difference between generic constraints and conditional types?
- How do you create a generic hook in React?
- What are some performance considerations with generics?

**Key Points:**
- Generics create reusable, type-safe code
- Constraints limit types with extends
- Built-in utilities use generics extensively
- Conditional types enable type-level programming

---

## Question 4: Explain TypeScript's advanced type system features.

**Answer:**

TypeScript's type system supports advanced patterns for complex type manipulation.

### Discriminated Unions

```typescript
interface Loading {
  state: 'loading';
}

interface Success {
  state: 'success';
  data: string[];
}

interface Error {
  state: 'error';
  error: Error;
}

type ApiResult = Loading | Success | Error;

function handleResult(result: ApiResult) {
  switch (result.state) {
    case 'loading':
      return 'Loading...';
    case 'success':
      return result.data.length; // TypeScript knows data exists
    case 'error':
      return result.error.message; // TypeScript knows error exists
  }
}
```

### Template Literal Types

```typescript
type EventName = `on${string}`;
type CSSProperty = `${string}-${string}`;

type Handler = `on${Capitalize<string>}`;
// 'onClick' | 'onBlur' | ...

// Practical example
type Route = 
  | '/users'
  | '/users/:id'
  | '/posts'
  | '/posts/:id';

type RouteParams<T extends Route> = 
  T extends '/users/:id' ? { id: string } :
  T extends '/posts/:id' ? { id: string } :
  {};
```

### Mapped Types

```typescript
type Props = {
  name: string;
  age: number;
  email: string;
};

// Make all optional
type Optional<T> = {
  [P in keyof T]?: T[P];
};

// Make all readonly
type Frozen<T> = {
  readonly [P in keyof T]: T[P];
};

// Transform keys
type Getters<T> = {
  [P in keyof T]: () => T[P];
};

type Greetable = {
  name: string;
  greet(): void;
};

type WithGetters = Getters<Greetable>;
// { name: () => string; greet: () => void }
```

### Type Guards

```typescript
// typeof guard
function process(val: string | number) {
  if (typeof val === 'string') {
    return val.toUpperCase(); // val is string
  }
  return val.toFixed(2); // val is number
}

// instanceof guard
class Dog { bark() {} }
class Cat { meow() {} }

function makeSound(animal: Dog | Cat) {
  if (animal instanceof Dog) {
    animal.bark();
  } else {
    animal.meow();
  }
}

// Custom type guard
interface Fish {
  swim(): void;
}

interface Bird {
  fly(): void;
}

function isFish(pet: Fish | Bird): pet is Fish {
  return (pet as Fish).swim !== undefined;
}
```

### Never Type

```typescript
// Never used for exhaustive checks
type Color = 'red' | 'green' | 'blue';

function handleColor(color: Color) {
  switch (color) {
    case 'red':
      return 'stop';
    case 'green':
      return 'go';
    // If we add 'blue' but forget case:
    default:
      const _exhaustive: never = color;
      return _exhaustive;
  }
}
```

### Template Union Types

```typescript
type Color = 'red' | 'green' | 'blue';
type Style = `${Color}-${'solid' | 'dashed'}`;
// 'red-solid' | 'red-dashed' | 'green-solid' | ...

type Method = 'get' | 'post' | 'put' | 'delete';
type Endpoint = `/${Method}-${string}`;
```

**Follow-up:**
- What are the limitations of TypeScript's type system?
- How do you handle complex union types?
- What is the never type used for?

**Key Points:**
- Discriminated unions: type-safe state handling
- Template literal types: string manipulation at type level
- Mapped types: transform existing types
- Custom type guards: runtime type checking
- never: exhaustive pattern matching

---

## Question 5: Explain TypeScript's module system and resolution.

**Answer:**

TypeScript supports various module systems with configurable resolution strategies.

### ES Modules

```typescript
// Named exports
export interface User {
  id: number;
  name: string;
}

export class UserService {
  async getUser(id: number): Promise<User> {
    const response = await fetch(`/api/users/${id}`);
    return response.json();
  }
}

// Default export
export default class ApiClient {
  // ...
}

// Re-export
export { User, UserService } from './types';
export * from './utils';
```

### Namespace (Legacy)

```typescript
// Before ES modules
namespace Utils {
  export function formatDate(date: Date): string {
    return date.toISOString();
  }
  
  export function formatCurrency(amount: number): string {
    return `$${amount.toFixed(2)}`;
  }
}

Utils.formatDate(new Date());
```

### Module Resolution Strategies

```typescript
// tsconfig.json
{
  "compilerOptions": {
    // Classic (default in TS 1.6-)
    // "moduleResolution": "classic"
    
    // Node resolution (CommonJS)
    "moduleResolution": "node",
    
    // Node16/Nodenext (modern Node.js)
    "moduleResolution": "node16",
    
    // Bundler resolution (for webpack/vite)
    "moduleResolution": "bundler",
    
    "baseUrl": "./src",
    "paths": {
      "@/*": ["./*"],
      "@components/*": ["components/*"],
      "@utils/*": ["utils/*"]
    }
  }
}
```

### Path Mapping

```typescript
// tsconfig.json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      // Exact match
      "my-package": ["./packages/my-package/dist/index.d.ts"],
      
      // Wildcard patterns
      "hooks/*": ["src/hooks/*"],
      "components/*": ["src/components/*/*/index.ts"]
    }
  }
}
```

### Declaration Files

```typescript
// my-library.d.ts
declare module 'my-library' {
  export function doSomething(): void;
  export class MyClass {
    constructor(options: Options);
  }
  
  export interface Options {
    debug?: boolean;
  }
}

// Usage
import { doSomething, MyClass } from 'my-library';
```

### Triple-Slash Directives

```typescript
/// <reference types="node" />
/// <reference path="./types.d.ts" />
/// <reference lib="es2015" />
```

**Follow-up:**
- What is the difference between moduleResolution strategies?
- How do you create type definitions for JavaScript libraries?
- What are the best practices for project structure?

**Key Points:**
- ES modules: standard TypeScript approach
- Path mapping: configure import aliases
- moduleResolution: node vs bundler for different setups
- Declaration files: .d.ts for JavaScript types

---

## Question 6: Explain TypeScript's strict mode and configuration options.

**Answer:**

TypeScript's strict mode enables comprehensive type checking for better type safety.

### Strict Mode Configuration

```typescript
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,
    
    // Individual strict options (enabled by strict: true):
    "strictNullChecks": true,
    "strictFunctionTypes": true,
    "strictBindCallApply": true,
    "strictPropertyInitialization": true,
    "noImplicitAny": true,
    "noImplicitThis": true,
    "alwaysStrict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noImplicitReturns": true,
    "noFallthroughCasesInSwitch": true,
    "noUncheckedIndexedAccess": true
  }
}
```

### strictNullChecks

```typescript
// Without strictNullChecks - can assign null to anything
let name: string = null; // No error

// With strictNullChecks - must explicitly handle null
function greet(name: string | null) {
  // name could be null
  if (name !== null) {
    console.log(`Hello, ${name}`);
  }
  
  // Optional chaining
  console.log(`Hello, ${name ?? 'Guest'}`);
}

// Return type with null
function findUser(id: number): User | undefined {
  return users.find(u => u.id === id);
}
```

### strictFunctionTypes

```typescript
interface Animal {
  name: string;
}

interface Dog extends Animal {
  breed: string;
}

let animalFn: (a: Animal) => void = (a) => {};
let dogFn: (d: Dog) => void = (d) => {};

// Without strictFunctionTypes: assignment allowed (bivariance)
// With strictFunctionTypes: error - function types are contravariant
animalFn = dogFn; // Error in strict mode
```

### noImplicitAny

```typescript
// Error: Implicit any
function add(a, b) {  // Parameters implicitly 'any'
  return a + b;
}

// Must be explicit
function addExplicit(a: number, b: number): number {
  return a + b;
}

// any type (explicit)
function addAny(a: any, b: any): any {
  return a + b;
}
```

### noImplicitReturns

```typescript
// Error: Not all code paths return a value
function getValue(x: number): number {
  if (x > 0) {
    return x;
  }
  // Error: Not all code paths return a number
}

// Fix
function getValueFixed(x: number): number {
  if (x > 0) {
    return x;
  }
  return 0;
}
```

### noUncheckedIndexedAccess

```typescript
const arr = [1, 2, 3];

// Without: returns number
const val = arr[0]; // number

// With: returns number | undefined
const valStrict = arr[0]; // number | undefined

// Must handle undefined
console.log(valStrict?.toFixed(2));
```

### Best Practices

```typescript
// tsconfig.json - recommended for new projects
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noImplicitReturns": true,
    "noFallthroughCasesInSwitch": true,
    
    // Also recommended
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true
  }
}
```

**Follow-up:**
- What is the difference between strict and individual options?
- How do you migrate code to strict mode incrementally?
- What are common strict mode errors?

**Key Points:**
- strict: true enables all strict checks
- strictNullChecks: handle null/undefined explicitly
- noImplicitAny: require explicit types
- noImplicitReturns: all paths must return

---

## Question 7: Explain TypeScript's decorators and their practical uses.

**Answer:**

Decorators enable meta-programming by modifying classes, methods, properties, or parameters at design time.

### Class Decorators

```typescript
// Simple decorator
function sealed(constructor: Function) {
  Object.seal(constructor);
  Object.seal(constructor.prototype);
}

@sealed
class User {
  name: string;
  constructor(name: string) {
    this.name = name;
  }
}

// Decorator factory (with options)
function logClass(options: { log: boolean }) {
  return function <T extends Function>(constructor: T): T {
    const original = constructor;
    
    return class extends original {
      constructor(...args: any[]) {
        super(...args);
        if (options.log) {
          console.log(`Created instance of ${constructor.name}`);
        }
      }
    };
  };
}

@logClass({ log: true })
class Person {
  constructor(public name: string) {}
}
```

### Method Decorators

```typescript
function logMethod(
  target: Object,
  propertyKey: string,
  descriptor: PropertyDescriptor
) {
  const original = descriptor.value;
  
  descriptor.value = function (...args: any[]) {
    console.log(`Calling ${propertyKey} with`, args);
    const result = original.apply(this, args);
    console.log(`Result:`, result);
    return result;
  };
  
  return descriptor;
}

class Calculator {
  @logMethod
  add(a: number, b: number): number {
    return a + b;
  }
}
```

### Property Decorators

```typescript
function readonly(
  target: Object,
  propertyKey: string
) {
  Object.defineProperty(target, propertyKey, {
    writable: false,
    configurable: true
  });
}

class Config {
  @readonly
  API_URL = 'https://api.example.com';
}

// Property accessor decorators
function observable(
  target: Object,
  propertyKey: string,
  descriptor: PropertyDescriptor
) {
  const getter = descriptor.get;
  
  descriptor.get = function() {
    console.log(`Getting ${propertyKey}`);
    return getter?.call(this);
  };
  
  return descriptor;
}

class State {
  @observable
  get value() {
    return this._value;
  }
  
  private _value = 0;
}
```

### Parameter Decorators

```typescript
function validate(
  target: Object,
  propertyKey: string,
  parameterIndex: number
) {
  // Store validation metadata
}

function required(
  target: Object,
  propertyKey: string,
  parameterIndex: number
) {
  const validator = function (...args: any[]) {
    if (args[parameterIndex] === undefined || args[parameterIndex] === null) {
      throw new Error(`Parameter ${parameterIndex} is required`);
    }
  };
  
  // Store validator to run before method
}

// Usage
class UserService {
  createUser(
    @required name: string,
    @required email: string
  ) {
    // Create user
  }
}
```

### Decorator Composition

```typescript
// Multiple decorators
@logClass({ log: true })
@sealed
class MyClass {}

// Execution order: bottom to top
// 1. sealed (applied first)
// 2. logClass (applied second)
// Results are composed
```

### Practical Examples

```typescript
// Singleton decorator
function singleton<T extends Function>(constructor: T): T {
  let instance: any;
  
  return function (...args: any[]) {
    if (!instance) {
      instance = new constructor(...args);
    }
    return instance;
  };
}

// Auto-bind decorator
function autobind(
  target: Object,
  propertyKey: string,
  descriptor: PropertyDescriptor
) {
  const getter = descriptor.get;
  
  descriptor.get = function() {
    const bound = getter?.call(this);
    Object.defineProperty(this, propertyKey, {
      value: bound,
      configurable: true,
      writable: true
    });
    return bound;
  };
  
  return descriptor;
}

class Controller {
  @autobind
  handleClick() {
    console.log(this); // Always refers to instance
  }
}
```

**Follow-up:**
- How do decorators compare to JavaScript proxies?
- What is the experimental decorators flag?
- When should you use decorators vs mixins?

**Key Points:**
- Decorators: modify class/method/property at design time
- Decorator factories: return functions accepting options
- Order matters: applied bottom to top
- Use for: logging, validation, caching, auto-binding

---

## Question 8: Explain TypeScript's integration with JavaScript libraries.

**Answer:**

TypeScript can type-check JavaScript code through declaration files and JSDoc comments.

### Type Declaration Files (.d.ts)

```typescript
// Adding types to existing JavaScript

// my-lib.js
export function init(options) {
  // initialization
  return { ready: true };
}

export default class Client {
  constructor(apiKey) {
    this.apiKey = apiKey;
  }
  
  request(endpoint) {
    return fetch(`${endpoint}`, {
      headers: { 'Authorization': this.apiKey }
    });
  }
}

// my-lib.d.ts
export function init(options: { debug?: boolean }): { ready: boolean };

export default class Client {
  constructor(apiKey: string);
  request<T = any>(endpoint: string): Promise<T>;
}
```

### JSDoc Comments

```typescript
/**
 * Adds two numbers
 * @param a - First number
 * @param b - Second number  
 * @returns Sum of a and b
 */
function add(a, b) {
  return a + b;
}

/** @type {string} */
let myName = 'John';

/** @typedef {{id: number, name: string}} User */

/**
 * @param {User} user
 * @returns {string}
 */
function greet(user) {
  return `Hello, ${user.name}`;
}
```

### @ts-check for JavaScript Files

```javascript
// In JavaScript files:
// @ts-check

// TypeScript will check these
const x = 1;
x = 'string'; // Error

// @ts-nocheck - disable checking
// @ts-ignore - ignore next line
```

### typeRoots and types

```typescript
// tsconfig.json
{
  "compilerOptions": {
    // Custom type definitions
    "typeRoots": [
      "./node_modules/@types",
      "./src/types"
    ],
    
    // Only include specific packages
    "types": ["node", "express"]
  }
}
```

### Ambient Declarations

```typescript
// Global variable declarations
declare const MY_GLOBAL: string;
declare function myGlobalFunction(): void;

// Global type
interface GlobalConfig {
  debug: boolean;
}

declare const CONFIG: GlobalConfig;

// Use:
// In any file: console.log(CONFIG.debug);
```

### Declaration Merging

```typescript
// Augment existing module
import { Moment } from 'moment';

declare module 'moment' {
  interface Moment {
    startOfWeek(): Moment;
    endOfWeek(): Moment;
  }
}

// Now Moment has startOfWeek and endOfWeek
```

### Working with Third-Party Libraries

```typescript
// 1. Package includes types
import _ from 'lodash';

// 2. Install @types package
import express from 'express'; // npm install @types/express

// 3. No types available - declare module
declare module 'some-untyped-lib' {
  export function doSomething(config: any): any;
}

// 4. Use JSDoc
// In plain JS file with @ts-check
/** @type {import('axios').AxiosInstance} */
const api = axios.create({
  baseURL: 'https://api.example.com'
});
```

**Follow-up:**
- What is the difference between @ts-check and tsconfig?
- How do you create declaration files for npm packages?
- How do you handle library version mismatches?

**Key Points:**
- .d.ts files: type definitions for JavaScript
- JSDoc: inline type annotations in JavaScript
- @ts-check: enable type checking in JS files
- declare module: extend existing modules

---

## Question 9: Explain TypeScript's error handling and type narrowing.

**Answer:**

TypeScript's type narrowing enables precise type checking in conditional branches.

### typeof Narrowing

```typescript
function process(value: string | number) {
  if (typeof value === 'string') {
    // TypeScript knows value is string
    return value.toUpperCase();
  }
  
  // TypeScript knows value is number
  return value.toFixed(2);
}
```

### instanceof Narrowing

```typescript
class Dog {
  bark() { console.log('Woof!'); }
}

class Cat {
  meow() { console.log('Meow!'); }
}

function makeSound(animal: Dog | Cat) {
  if (animal instanceof Dog) {
    animal.bark();
  } else {
    animal.meow();
  }
}
```

### Truthiness Narrowing

```typescript
function printLength(str: string | null) {
  // falsey check narrows to null
  if (str == null) {
    console.log('No string provided');
    return;
  }
  
  // Now string
  console.log(str.length);
}

// Array narrowing
function processArray(arr: string[] | null) {
  if (arr && arr.length > 0) {
    // arr is string[] (not null/undefined, not empty)
    arr[0].toUpperCase();
  }
}
```

### Equality Narrowing

```typescript
function handleStatus(status: 'loading' | 'success' | 'error') {
  if (status === 'loading') {
    return 'Loading...';
  }
  
  if (status === 'success') {
    return 'Done!';
  }
  
  // TypeScript knows remaining case is 'error'
  return 'Error occurred';
}
```

### in Operator Narrowing

```typescript
interface Fish {
  swim(): void;
}

interface Bird {
  fly(): void;
}

function move(animal: Fish | Bird) {
  if ('swim' in animal) {
    animal.swim();
  } else {
    animal.fly();
  }
}
```

### Custom Type Guards

```typescript
interface Fish {
  swim(): void;
}

interface Bird {
  fly(): void;
}

// Returns type predicate
function isFish(animal: Fish | Bird): animal is Fish {
  return (animal as Fish).swim !== undefined;
}

// Usage
if (isFish(pet)) {
  pet.swim();
}
```

### Discriminated Unions

```typescript
interface Loading {
  kind: 'loading';
}

interface Success {
  kind: 'success';
  data: string[];
}

interface Error {
  kind: 'error';
  message: string;
}

type State = Loading | Success | Error;

function handle(state: State) {
  switch (state.kind) {
    case 'loading':
      return 'Loading...';
    case 'success':
      // state is Success here
      return state.data.length;
    case 'error':
      // state is Error here
      return state.message;
  }
}
```

### Assertion Functions

```typescript
function assertIsString(value: unknown): asserts value is string {
  if (typeof value !== 'string') {
    throw new Error('Expected string');
  }
}

function process(value: unknown) {
  assertIsString(value);
  // TypeScript knows value is string here
  return value.toUpperCase();
}
```

**Follow-up:**
- What is type widening?
- How does TypeScript handle nullable types?
- What are the limitations of type narrowing?

**Key Points:**
- typeof: narrow by primitive type
- instanceof: narrow by class
- Custom guards: predicate functions
- Discriminated unions: switch statements with common property
- assert: enforce conditions with assertion functions

---

## Question 10: Explain TypeScript's utility types and their practical uses.

**Answer:**

TypeScript provides built-in utility types for common type transformations.

### Partial and Required

```typescript
interface User {
  id: number;
  name: string;
  email: string;
}

// All properties become optional
type PartialUser = Partial<User>;
// { id?: number; name?: email?: string; }

// All properties become required
type RequiredUser = Required<PartialUser>;
// All optional properties now required
```

### Readonly and Mutable

```typescript
interface User {
  name: string;
  age: number;
}

// All properties become readonly
type ReadonlyUser = Readonly<User>;
// { readonly name: string; readonly age: number; }

// Reverse readonly (TypeScript 4.5+)
type Mutable<T> = {
  -readonly [P in keyof T]: T[P];
};

type MutableUser = Mutable<ReadonlyUser>;
// { name: string; age: number; }
```

### Pick and Omit

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  password: string;
  createdAt: Date;
}

// Select specific properties
type UserPreview = Pick<User, 'id' | 'name'>;
// { id: number; name: string; }

// Exclude specific properties
type UserWithoutSensitive = Omit<User, 'password'>;
// { id: number; name: string; email: string; createdAt: Date; }
```

### Record

```typescript
// Create object type with specific keys and value type
type UserRoles = Record<string, 'admin' | 'user' | 'guest'>;

// { [key: string]: 'admin' | 'user' | 'guest' }

const roles: UserRoles = {
  john: 'admin',
  jane: 'user',
  guest: 'guest'
};

// Practical: keyed object
type UserById = Record<number, User>;
const users: UserById = {
  1: { id: 1, name: 'John', email: 'john@example.com' }
};
```

### Extract and Exclude

```typescript
// Extract: keep types that extend T
type T = Extract<'a' | 'b' | 'c', 'a' | 'f'>;
// 'a'

// Exclude: remove types that extend T
type E = Exclude<'a' | 'b' | 'c', 'a'>;
// 'b' | 'c'

// Practical usage
type EventCallback = (event: Event) => void;
type MouseEventCallback = Extract<EventCallback, (event: MouseEvent) => void>;
```

### NonNullable

```typescript
// Remove null and undefined
type T = NonNullable<string | null | undefined>;
// string

// Practical: filter from array
type NonNullableArray<T> = NonNullable<T>[];
```

### ReturnType and Parameters

```typescript
function fetchUser(id: number) {
  return { id, name: 'John' };
}

// Get return type
type UserReturn = ReturnType<typeof fetchUser>;
// { id: number; name: string; }

// Get parameter types
type FetchParams = Parameters<typeof fetchUser>;
// [number]

// Get instance type (for classes)
type DateInstance = InstanceType<typeof Date>;
```

### Awaited (TypeScript 4.5+)

```typescript
// Get resolved type of Promise
async function getUser() {
  return { id: 1, name: 'John' };
}

type AwaitedUser = Awaited<ReturnType<typeof getUser>>;
// { id: number; name: string; }

// Nested promises
type NestedPromise = Promise<Promise<string>>;
type Resolved = Awaited<NestedPromise>;
// string (unwraps all levels)
```

### Practical Examples

```typescript
// Create update type from entity
type UpdateDTO<T> = Partial<Omit<T, 'id' | 'createdAt'>>;

// Create response wrapper
type ApiResponse<T, E = Error> = 
  | { success: true; data: T }
  | { success: false; error: E };

// Create filter type
type FilterKeys<T> = {
  [K in keyof T]?: T[K];
};

// Create form type from model
type UserForm = Omit<User, 'id' | 'createdAt'>;
```

**Follow-up:**
- How do custom utility types work?
- What is the difference between Pick and Omit?
- How do you create conditional utility types?

**Key Points:**
- Partial/Required: optional/required properties
- Pick/Omit: select/exclude properties
- Record: create keyed object types
- ReturnType/Parameters: function type inference
- Awaited: unwrap Promise types

---

## Question 11: Explain TypeScript's namespace and module organization best practices.

**Answer:**

Organizing TypeScript code effectively improves maintainability and type safety.

### Project Structure

```
src/
├── components/
│   ├── Button/
│   │   ├── Button.tsx
│   │   ├── Button.css
│   │   └── index.ts          # Barrel exports
│   └── Input/
│       ├── Input.tsx
│       └── index.ts
├── hooks/
│   ├── useAuth.ts
│   └── useFetch.ts
├── services/
│   ├── api/
│   │   ├── client.ts
│   │   └── endpoints/
│   └── auth/
│       └── service.ts
├── types/
│   ├── user.ts
│   ├── api.ts
│   └── index.ts
└── utils/
    ├── format.ts
    └── validation.ts
```

### Barrel Exports (index.ts)

```typescript
// components/index.ts
export { Button } from './Button';
export { Input } from './Input';
export { Select } from './Select';

// Re-export from subdirectory
export * from './Button';

// Now import from single location:
// import { Button, Input } from '@/components';
```

### Namespace for Constants

```typescript
// Constants namespace
namespace HttpStatus {
  export const OK = 200;
  export const CREATED = 201;
  export const BAD_REQUEST = 400;
  export const UNAUTHORIZED = 401;
  export const NOT_FOUND = 404;
  export const INTERNAL_SERVER_ERROR = 500;
}

namespace ApiEndpoints {
  export const USERS = '/api/users';
  export const POSTS = '/api/posts';
}

// Usage
if (status === HttpStatus.OK) {
  // handle success
}
```

### Type Organization

```typescript
// types/user.ts

// Domain types
export interface User {
  id: number;
  name: string;
  email: string;
}

// DTOs for API
export interface CreateUserDTO {
  name: string;
  email: string;
  password: string;
}

export interface UpdateUserDTO = Partial<CreateUserDTO>;

// Response types
export interface UserResponse {
  data: User;
  meta: PaginationMeta;
}

// Enum
export enum UserRole {
  ADMIN = 'admin',
  USER = 'user',
  GUEST = 'guest'
}
```

### Feature-Based Organization

```typescript
// features/users/
// ├── types.ts           # All user-related types
// ├── api.ts            # User API calls
// ├── hooks.ts          # User-related hooks
// ├── components.tsx   # User components
// └── index.ts          # Public exports

// features/users/types.ts
export interface UserState {
  users: User[];
  selectedUser: User | null;
  loading: boolean;
  error: string | null;
}

export type UserFilter = {
  search?: string;
  role?: UserRole;
};
```

### Utility Types Organization

```typescript
// utils/types.ts

// Generic utilities
export type ValueOf<T> = T[keyof T];

export type KeysOf<T> = keyof T;

export type Maybe<T> = T | null;

export type MaybeAsync<T> = T | Promise<T>;

// CRUD types
export type CreateDTO<T> = Omit<T, 'id' | 'createdAt'>;
export type UpdateDTO<T> = Partial<CreateDTO<T>>;
export type ResponseDTO<T> = { data: T };
export type ListResponseDTO<T> = { data: T[]; meta: Pagination };
```

### Best Practices

```typescript
// 1. Use consistent naming conventions
interface UserProps {}       // Props for component
interface UserDTO {}         // Data Transfer Object
interface UserEntity {}      // Database entity
interface UserModel {}       // Domain model

// 2. Co-locate types with usage
// components/Button/
// ├── Button.tsx
// └── types.ts (specific to Button)

// 3. Use barrel files
// exports/index.ts for each module

// 4. Avoid circular dependencies
// Use interfaces instead of imports where possible

// 5. Separate public and internal types
// public.ts - types for consumers
// internal.ts - types for implementation
```

**Follow-up:**
- What is the best project structure for large applications?
- How do you handle circular dependencies in TypeScript?
- What are barrel files and why use them?

**Key Points:**
- Organize by feature/domain
- Use barrel exports (index.ts)
- Separate public/internal APIs
- Co-locate related types
- Consistent naming conventions

---

## Question 12: Explain TypeScript's testing integration.

**Answer:**

TypeScript provides excellent testing support with type-aware testing utilities.

### Testing with Jest

```typescript
// User.ts
export class User {
  constructor(
    public readonly id: number,
    public name: string,
    public email: string
  ) {}
  
  updateName(name: string): void {
    if (name.length < 2) {
      throw new Error('Name too short');
    }
    this.name = name;
  }
}

// User.test.ts
import { User } from './User';

describe('User', () => {
  let user: User;
  
  beforeEach(() => {
    user = new User(1, 'John', 'john@example.com');
  });
  
  describe('updateName', () => {
    it('should update name when valid', () => {
      user.updateName('Jane');
      expect(user.name).toBe('Jane');
    });
    
    it('should throw when name is too short', () => {
      expect(() => user.updateName('J')).toThrow('Name too short');
    });
  });
});
```

### Type Testing

```typescript
// Testing that types are correct
import { expectType } from 'tsd';

type Result = { data: string };

// This will fail if Result ever changes incorrectly
declare const result: Result;
expectType<string>(result.data);

// Test generic types
function createPair<T>(a: T, b: T): [T, T] {
  return [a, b];
}

declare const pair: ReturnType<typeof createPair>;
expectType<[string, string]>(pair);
```

### Mocking with TypeScript

```typescript
// Service
interface UserService {
  getUser(id: number): Promise<User>;
  createUser(data: CreateUserDTO): Promise<User>;
}

// Test with mocks
const mockUserService = {
  getUser: jest.fn().mockResolvedValue({ id: 1, name: 'John' }),
  createUser: jest.fn().mockResolvedValue({ id: 1, name: 'John' })
};

// Using with typed mock library (ts-mockito)
import { mock, verify } from 'ts-mockito';

const mockedService = mock<UserService>(UserServiceImpl);
when(mockedService.getUser(1)).thenResolve({ id: 1, name: 'John' });

// Type-safe verification
verify(mockedService.getUser(1)).called();
```

### Testing Generic Functions

```typescript
// Generic function
function processItems<T>(items: T[], processor: (item: T) => T): T[] {
  return items.map(processor);
}

// Test with specific types
describe('processItems', () => {
  it('should process number arrays', () => {
    const result = processItems([1, 2, 3], n => n * 2);
    expect(result).toEqual([2, 4, 6]);
  });
  
  it('should process string arrays', () => {
    const result = processItems(['a', 'b'], s => s.toUpperCase());
    expect(result).toEqual(['A', 'B']);
  });
  
  // Type test - this won't compile if types are wrong
  it('should preserve types', () => {
    const nums = processItems([1, 2, 3], n => n);
    const typeCheck: number[] = nums;
  });
});
```

### Integration Testing Types

```typescript
// API test types
interface ApiTestContext {
  app: Express;
  request: supertest.SuperTest<supertest.Test>;
}

interface TestUser {
  id: string;
  name: string;
  email: string;
}

describe('POST /users', () => {
  let userData: Omit<TestUser, 'id'>;
  
  beforeEach(() => {
    userData = {
      name: 'John',
      email: 'john@example.com'
    };
  });
  
  it('should create user', async () => {
    const response = await request
      .post('/api/users')
      .send(userData)
      .expect(201);
    
    // TypeScript knows response structure
    const created: TestUser = response.body;
    expect(created.id).toBeDefined();
    expect(created.name).toBe(userData.name);
  });
});
```

### Vitest (Modern Alternative)

```typescript
import { describe, it, expect, vi } from 'vitest';
import { UserService } from './UserService';

// Mocking with vi
const mockApi = {
  get: vi.fn().mockResolvedValue({ data: [] }),
  post: vi.fn()
};

describe('UserService', () => {
  it('should fetch users', async () => {
    const service = new UserService(mockApi);
    const users = await service.getAll();
    
    expect(users).toEqual([]);
    expect(mockApi.get).toHaveBeenCalledWith('/users');
  });
});
```

**Follow-up:**
- What are the best testing practices for TypeScript?
- How do you test type definitions?
- What tools help with TypeScript testing?

**Key Points:**
- Jest/Vitest: comprehensive testing
- Type testing: ensure types stay correct
- Mocking: maintain type safety
- Test generic functions with multiple types

---

## Question 13: Explain TypeScript's type compatibility and structural typing.

**Answer:**

TypeScript uses structural typing - types are compatible if their structures match, not by name.

### Structural Typing Basics

```typescript
interface Point {
  x: number;
  y: number;
}

interface Point3D {
  x: number;
  y: number;
  z: number;
}

function distance(p: Point): number {
  return Math.sqrt(p.x * p.x + p.y * p.y);
}

// Point3D has more properties than Point
// but is structurally compatible
const p3d: Point3D = { x: 1, y: 2, z: 3 };
distance(p3d); // OK - structural compatibility
```

### Function Parameter Compatibility

```typescript
interface Handler {
  (input: string): void;
}

const handleString: Handler = (s) => console.log(s);

// Error: parameter type is bivariant in non-strict mode
// but invariant in strictFunctionTypes
// In strict mode: parameter must be exactly compatible

interface Event {
  timestamp: Date;
}

interface MouseEvent extends Event {
  clientX: number;
  clientY: number;
}

// Strict mode: this fails
const handler: Handler = (e: Event) => {}; // Error
```

### Type Compatibility Rules

```typescript
// Object types
interface A {
  x: number;
}

interface B {
  x: number;
  y: number;
}

let a: A = { x: 1 };
let b: B = { x: 1, y: 2 };

a = b; // Error - B has extra property
b = a; // OK - A is subset of B

// Arrays
let numArr: number[] = [1, 2, 3];
let strArr: string[] = ['a', 'b'];

// numArr = strArr; // Error
// strArr = numArr; // Error
```

### Generics and Compatibility

```typescript
interface Box<T> {
  value: T;
}

let boxOfNumber: Box<number> = { value: 1 };
let boxOfAny: Box<any> = { value: 'hello' };

// Box<any> is compatible with Box<number>
boxOfNumber = boxOfAny; // OK (but loses info)
boxOfAny = boxOfNumber; // OK

// But with unknown
let boxOfUnknown: Box<unknown> = { value: 'test' };
// boxOfNumber = boxOfUnknown; // Error
```

### Using `as` for Casting

```typescript
interface A {
  a: number;
}

interface B {
  a: number;
  b: string;
}

const b: B = { a: 1, b: 'hello' };
const a: A = b as A; // Cast to compatible type

// For unrelated types
interface Dog {
  name: string;
}

interface Cat {
  name: string;
  meow(): void;
}

const cat: Cat = {
  name: 'Whiskers',
  meow: () => console.log('Meow')
};

// cat as Dog works because both have 'name'
const dog = cat as Dog;
```

### Type Compatibility Pitfalls

```typescript
// 1. Extra properties
interface Config {
  debug?: boolean;
}

function configure(config: Config) {
  // uses config.debug
}

configure({ debug: true, unknown: 'value' }); // Error in strict mode

// 2. Method compatibility
interface Animal {
  speak(): void;
}

interface Dog {
  speak(): void;
  fetch(): void;
}

let animal: Animal = {
  speak: () => {}
};

let dog: Dog = {
  speak: () => {},
  fetch: () => {}
};

// In strictFunctionTypes:
// animal = dog; // Error - dog has extra method
// dog = animal; // OK - animal has subset

// 3. Return type covariance
interface Factory {
  create(): { id: number };
}

interface SpecializedFactory {
  create(): { id: number; name: string };
}

// SpecializedFactory.create returns more specific type
// In strict mode: covariant return types allowed
```

**Follow-up:**
- What is the difference between type and interface?
- How does TypeScript handle method signatures?
- What is covariance and contravariance?

**Key Points:**
- Structural typing: compatibility by structure
- Subtype relationship: extra properties cause incompatibility
- Function parameters: contravariant in strict mode
- Use type assertions for explicit conversions

---

## Question 14: Explain TypeScript's configuration and build process.

**Answer:**

Understanding TypeScript's build configuration is essential for production applications.

### tsconfig.json Basics

```typescript
{
  "compilerOptions": {
    // Target JavaScript version
    "target": "ES2020",
    
    // Module system
    "module": "ESNext",
    "moduleResolution": "bundler",
    
    // Output
    "outDir": "./dist",
    "rootDir": "./src",
    "declaration": true,
    "declarationMap": true,
    "sourceMap": true,
    
    // Type checking
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    
    // Compatibility
    "esModuleInterop": true,
    "forceConsistentCasingInFileNames": true,
    "skipLibCheck": true,
    
    // Paths
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"]
    }
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
```

### Build Modes

```typescript
// Development
// tsconfig.dev.json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "sourceMap": true,
    "declaration": false,
    "strict": true
  }
}

// Production
// tsconfig.prod.json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "declaration": true,
    "declarationMap": true,
    "sourceMap": false,
    "removeComments": true,
    "optimize": true,
    "strict": true
  }
}
```

### Project References

```typescript
// tsconfig.json (root)
{
  "files": [],
  "references": [
    { "path": "./packages/common" },
    { "path": "./packages/client" },
    { "path": "./packages/server" }
  ]
}

// packages/common/tsconfig.json
{
  "compilerOptions": {
    "composite": true,
    "outDir": "./dist",
    "rootDir": "./src"
  },
  "include": ["src/**/*"]
}
```

### Monorepo Configuration

```typescript
// Root tsconfig.json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      "@shared/*": ["packages/shared/src/*"]
    },
    "strict": true
  },
  "references": [
    { "path": "./packages/shared" },
    { "path": "./packages/app" }
  ]
}

// packages/app/tsconfig.json
{
  "extends": "../../tsconfig.json",
  "compilerOptions": {
    "outDir": "./dist",
    "rootDir": "./src"
  },
  "references": [
    { "path": "../shared" }
  ]
}
```

### Build Scripts

```json
{
  "scripts": {
    "build": "tsc",
    "build:watch": "tsc --watch",
    "build:prod": "tsc -p tsconfig.prod.json",
    "typecheck": "tsc --noEmit",
    "typecheck:all": "tsc -b packages/*"
  }
}
```

### Build Performance

```typescript
{
  "compilerOptions": {
    // Incremental builds
    "incremental": true,
    "tsBuildInfoFile": ".tsbuildinfo",
    
    // Skip lib check (faster builds)
    "skipLibCheck": true,
    
    // Parallel compilation
    "composite": true
  }
}
```

### CI/CD Integration

```yaml
# GitHub Actions
- name: TypeScript Check
  run: |
    npm install
    npm run typecheck
    npm run build

# Or with tsc --build
- name: Build with Project References
  run: |
    npx tsc -b --verbose
```

**Follow-up:**
- What is the difference between tsc and bundler?
- How do project references work?
- What are incremental builds?

**Key Points:**
- tsconfig.json: configure TypeScript compilation
- Project references: build multiple projects
- Strict mode: recommended for production
- Skip lib check: faster builds

---

## Question 15: Explain TypeScript's advanced patterns and best practices.

**Answer:**

Senior-level TypeScript uses advanced patterns for maintainable, type-safe applications.

### Builder Pattern with Types

```typescript
class QueryBuilder<T extends Record<string, any> = {}> {
  private conditions: string[] = [];
  private params: any[] = [];
  
  where<K extends keyof T>(field: K, value: T[K]): this {
    this.conditions.push(`${String(field)} = ?`);
    this.params.push(value);
    return this;
  }
  
  whereIn<K extends keyof T>(field: K, values: T[K][]): this {
    const placeholders = values.map(() => '?').join(', ');
    this.conditions.push(`${String(field)} IN (${placeholders})`);
    this.params.push(...values);
    return this;
  }
  
  build(): { sql: string; params: any[] } {
    return {
      sql: `SELECT * FROM users WHERE ${this.conditions.join(' AND ')}`,
      params: this.params
    };
  }
}

// Usage with full type safety
const query = new QueryBuilder<User>()
  .where('role', 'admin')
  .whereIn('status', ['active', 'pending'])
  .build();
```

### State Machines

```typescript
type State =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; data: string[] }
  | { status: 'error'; error: Error };

function transition(state: State): State {
  switch (state.status) {
    case 'idle':
      return { status: 'loading' };
    case 'loading':
      return { status: 'success', data: ['item'] };
    case 'success':
      return { status: 'idle' };
    case 'error':
      return { status: 'idle' };
  }
}

// Exhaustiveness check
function handleState(state: State): string {
  switch (state.status) {
    case 'idle': return 'Idle';
    case 'loading': return 'Loading';
    case 'success': return `Loaded ${state.data.length} items`;
    case 'error': return `Error: ${state.error.message}`;
  }
}
```

### Type-Safe Event Bus

```typescript
interface EventMap {
  user_created: { id: number; name: string };
  user_updated: { id: number; changes: Partial<{ name: string; email: string }> };
  user_deleted: { id: number };
}

class TypedEventEmitter<T extends Record<string, any>> {
  private listeners: {
    [K in keyof T]?: ((data: T[K]) => void)[];
  } = {};
  
  on<K extends keyof T>(event: K, callback: (data: T[K]) => void): void {
    this.listeners[event] = (this.listeners[event] || []).concat(callback);
  }
  
  emit<K extends keyof T>(event: K, data: T[K]): void {
    (this.listeners[event] || []).forEach(cb => cb(data));
  }
}

const emitter = new TypedEventEmitter<EventMap>();

emitter.on('user_created', (data) => {
  // data is { id: number; name: string }
  console.log(`Created user: ${data.name}`);
});

emitter.emit('user_created', { id: 1, name: 'John' });
```

### Type-Safe SQL Builder

```typescript
type Columns<T> = {
  [K in keyof T]: T[K] extends string ? string :
    T[K] extends number ? number :
    T[K] extends boolean ? boolean : any;
};

function select<T extends Record<string, any>, K extends keyof T>(
  table: string,
  columns: K[]
): { sql: string; params: any[] } {
  const cols = columns.join(', ');
  return {
    sql: `SELECT ${cols} FROM ${table}`,
    params: []
  };
}

function where<T extends Record<string, any>, K extends keyof T>(
  query: { sql: string; params: any[] },
  column: K,
  value: T[K]
): { sql: string; params: any[] } {
  return {
    sql: `${query.sql} WHERE ${String(column)} = ?`,
    params: [...query.params, value]
  };
}

// Usage - fully typed
const query = select('users', ['id', 'name'])
  .pipe(
    q => where(q, 'id', 1),
    q => where(q, 'name', 'John')
  );
```

### Dependency Injection

```typescript
// Service interface
interface Logger {
  log(message: string): void;
}

// Concrete implementations
class ConsoleLogger implements Logger {
  log(message: string): void {
    console.log(message);
  }
}

class FileLogger implements Logger {
  log(message: string): void {
    // write to file
  }
}

// Container with type safety
type ServiceContainer = {
  logger: Logger;
  userService: UserService;
  database: Database;
};

const container: ServiceContainer = {
  logger: new ConsoleLogger(),
  userService: new UserService(container),
  database: new Database()
};

// Factory with injection
function createUserService(container: ServiceContainer): UserService {
  return new UserService(container);
}
```

### Best Practices Summary

```typescript
// 1. Enable strict mode
// 2. Use interfaces for object shapes
// 3. Prefer union types over enums (for values)
// 4. Use type inference where clear
// 5. Avoid 'any' - use unknown
// 6. Use 'as' sparingly - prefer type guards
// 7. Extract reusable types
// 8. Use utility types (Partial, Pick, Omit)
// 9. Test with actual types
// 10. Document complex type patterns
```

**Follow-up:**
- What is the builder pattern in TypeScript?
- How do you implement dependency injection?
- What are the best practices for complex types?

**Key Points:**
- Advanced patterns enable complex type-safe operations
- Builder pattern: fluent API with type safety
- Event systems: type-safe event handling
- Dependency injection: container with types

---

## Notes

Add more questions covering:
- TypeScript with React (hooks, generics)
- TypeScript with Node.js
- Performance optimization
- Migration from JavaScript
- Advanced generic patterns
- Decorators and metadata reflection
