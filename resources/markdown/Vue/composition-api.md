# Vue 3 Composition API

## Question 1: What is the Composition API and why was it introduced?

**Answer:**

The Composition API is a new way to organize component logic in Vue 3, addressing limitations of the Options API.

### Problems with Options API

```javascript
// Options API - logic scattered across options
export default {
  data() {
    return {
      // Feature A data
      searchQuery: '',
      searchResults: [],
      // Feature B data
      sortOrder: 'asc',
      sortedItems: []
    };
  },
  computed: {
    // Feature A computed
    hasResults() { return this.searchResults.length > 0; },
    // Feature B computed
    sortedList() { return this.sortedItems; }
  },
  methods: {
    // Feature A methods
    search() { /* ... */ },
    // Feature B methods
    sort() { /* ... */ }
  },
  mounted() {
    // Feature A setup
    this.setupSearch();
    // Feature B setup
    this.setupSort();
  }
};
// Hard to extract and reuse logic!
```

### Composition API Solution

```javascript
// Composition API - organize by feature
import { ref, computed, onMounted } from 'vue';

export default {
  setup() {
    // Feature A: Search
    const { searchQuery, searchResults, hasResults, search } = useSearch();

    // Feature B: Sorting
    const { sortOrder, sortedItems, sort } = useSort();

    return {
      searchQuery,
      searchResults,
      hasResults,
      search,
      sortOrder,
      sortedItems,
      sort
    };
  }
};

// useSearch.js - Reusable composable
function useSearch() {
  const searchQuery = ref('');
  const searchResults = ref([]);

  const hasResults = computed(() => searchResults.value.length > 0);

  const search = async () => {
    searchResults.value = await api.search(searchQuery.value);
  };

  onMounted(() => {
    // Search-specific setup
  });

  return { searchQuery, searchResults, hasResults, search };
}
```

### Script Setup (Syntactic Sugar)

```vue
<script setup>
import { ref, computed } from 'vue';

// No need for setup(), return, etc.
const count = ref(0);
const doubled = computed(() => count.value * 2);

function increment() {
  count.value++;
}

// Everything is automatically exposed to template
</script>

<template>
  <div>{{ count }} × 2 = {{ doubled }}</div>
  <button @click="increment">Increment</button>
</template>
```

### Benefits

1. **Better Code Organization**
2. **Logic Reuse** (composables)
3. **Better TypeScript Support**
4. **Smaller Bundle Size**
5. **More Flexible**

**Key Points:**
- Composition API organizes code by feature, not option type
- Composables enable logic reuse
- `<script setup>` is concise syntax
- Better TypeScript inference
- Can use alongside Options API

---

## Question 2: What are Composables and how do you create them?

**Answer:**

Composables are reusable functions that encapsulate stateful logic using Composition API.

### Basic Composable

```javascript
// composables/useMouse.js
import { ref, onMounted, onUnmounted } from 'vue';

export function useMouse() {
  const x = ref(0);
  const y = ref(0);

  function update(event) {
    x.value = event.pageX;
    y.value = event.pageY;
  }

  onMounted(() => window.addEventListener('mousemove', update));
  onUnmounted(() => window.removeEventListener('mousemove', update));

  return { x, y };
}

// Usage in component
<script setup>
import { useMouse } from '@/composables/useMouse';

const { x, y } = useMouse();
</script>

<template>
  <div>Mouse position: {{ x }}, {{ y }}</div>
</template>
```

### Async Composable

```javascript
// composables/useFetch.js
import { ref, watchEffect, toValue } from 'vue';

export function useFetch(url) {
  const data = ref(null);
  const error = ref(null);
  const loading = ref(false);

  watchEffect(async () => {
    loading.value = true;
    data.value = null;
    error.value = null;

    try {
      const res = await fetch(toValue(url));
      data.value = await res.json();
    } catch (e) {
      error.value = e;
    } finally {
      loading.value = false;
    }
  });

  return { data, error, loading };
}

// Usage
<script setup>
const url = ref('/api/users');
const { data, error, loading } = useFetch(url);
</script>

<template>
  <div v-if="loading">Loading...</div>
  <div v-else-if="error">Error: {{ error.message }}</div>
  <div v-else>{{ data }}</div>
</template>
```

### Composable with Parameters

```javascript
// composables/useCounter.js
import { ref, computed } from 'vue';

export function useCounter(initialValue = 0, step = 1) {
  const count = ref(initialValue);

  const doubled = computed(() => count.value * 2);

  function increment() {
    count.value += step;
  }

  function decrement() {
    count.value -= step;
  }

  function reset() {
    count.value = initialValue;
  }

  return {
    count,
    doubled,
    increment,
    decrement,
    reset
  };
}

// Usage
const { count, increment, reset } = useCounter(0, 5);
```

### LocalStorage Composable

```javascript
// composables/useLocalStorage.js
import { ref, watch } from 'vue';

export function useLocalStorage(key, defaultValue) {
  const storedValue = localStorage.getItem(key);
  const value = ref(storedValue ? JSON.parse(storedValue) : defaultValue);

  watch(value, (newValue) => {
    localStorage.setItem(key, JSON.stringify(newValue));
  }, { deep: true });

  return value;
}

// Usage
const theme = useLocalStorage('theme', 'light');
const user = useLocalStorage('user', null);
```

### Event Listener Composable

```javascript
// composables/useEventListener.js
import { onMounted, onUnmounted } from 'vue';

export function useEventListener(target, event, callback) {
  onMounted(() => target.addEventListener(event, callback));
  onUnmounted(() => target.removeEventListener(event, callback));
}

// Usage
<script setup>
import { useEventListener } from '@/composables/useEventListener';

useEventListener(window, 'resize', () => {
  console.log('Window resized');
});

useEventListener(document, 'keydown', (e) => {
  if (e.key === 'Escape') {
    closeModal();
  }
});
</script>
```

### Composable Best Practices

```javascript
// ✅ Good: Use 'use' prefix
export function useFetch() { /* ... */ }

// ❌ Bad: No clear naming
export function fetchData() { /* ... */ }

// ✅ Good: Return reactive refs/computed
export function useCounter() {
  const count = ref(0);
  return { count };
}

// ❌ Bad: Return plain values
export function useCounter() {
  let count = 0;
  return { count }; // Not reactive
}

// ✅ Good: Accept refs or values
export function useFetch(url) {
  watchEffect(() => {
    fetch(toValue(url)); // Works with ref or plain value
  });
}

// ✅ Good: Side effects in lifecycle hooks
export function useMouse() {
  onMounted(() => { /* setup */ });
  onUnmounted(() => { /* cleanup */ });
}
```

**Follow-up:**
- How are composables different from mixins?
- Can composables use other composables?
- When should you create a composable?

**Key Points:**
- Composables = reusable stateful logic
- Naming: `use{Name}` convention
- Return reactive refs/computed
- Clean up in `onUnmounted`
- Can compose multiple composables

---

## Question 3: Explain ref vs reactive and when to use each.

**Answer:**

### ref()

```javascript
import { ref } from 'vue';

// For primitives
const count = ref(0);
const message = ref('Hello');
const isActive = ref(true);

// Access with .value in script
count.value++;
console.log(message.value);

// Auto-unwrapped in template
<template>
  <div>{{ count }}</div> <!-- No .value needed -->
</template>

// For objects (wraps entire object)
const user = ref({ name: 'John', age: 30 });
user.value = { name: 'Jane', age: 25 }; // Replace entire object
user.value.name = 'Jane'; // Modify property
```

### reactive()

```javascript
import { reactive } from 'vue';

// For objects only
const state = reactive({
  count: 0,
  user: {
    name: 'John',
    age: 30
  }
});

// No .value needed
state.count++;
state.user.name = 'Jane';

// Deep reactivity
state.user.name = 'Updated'; // Reactive

// ❌ Cannot reassign
state = { count: 5 }; // Loses reactivity!

// ❌ Destructuring loses reactivity
const { count } = state;
count++; // Not reactive!

// ✅ Use toRefs() to destructure
import { toRefs } from 'vue';
const { count } = toRefs(state);
count.value++; // Reactive
```

### When to Use Each

```javascript
// ✅ ref: Primitives
const count = ref(0);
const name = ref('John');
const isVisible = ref(false);

// ✅ reactive: Related state
const form = reactive({
  username: '',
  email: '',
  password: ''
});

// ✅ ref: Single object that might be replaced
const user = ref(null);
user.value = await fetchUser(); // Replace entire object

// ✅ reactive: Complex nested state
const state = reactive({
  user: { name: '', email: '' },
  settings: { theme: 'dark', lang: 'en' },
  cart: { items: [], total: 0 }
});

// ❌ reactive: Don't use for primitives
const count = reactive(0); // Error!

// ❌ Don't destructure reactive
const { user } = state; // Loses reactivity
// ✅ Do this instead
const { user } = toRefs(state); // Maintains reactivity
```

### Comparison

| Feature | ref | reactive |
|---------|-----|----------|
| Data types | Any | Objects only |
| Access | `.value` | Direct |
| Template | Auto-unwrap | Direct |
| Reassign | ✅ Yes | ❌ No |
| Destructure | ✅ Yes | ❌ Loses reactivity |
| TypeScript | Better inference | OK |

### Advanced Patterns

```javascript
// Unwrapping behavior
const count = ref(0);
const state = reactive({
  count // Auto-unwrapped in reactive
});

console.log(state.count); // 0 (no .value)
state.count++; // Updates ref

// Ref unwrapping in arrays (Vue 3.3+)
const books = reactive([ref('Vue Handbook')]);
console.log(books[0]); // Still need .value

// shallowRef / shallowReactive
import { shallowRef, shallowReactive } from 'vue';

const state = shallowReactive({
  nested: { count: 0 }
});

state.nested = { count: 1 }; // Reactive
state.nested.count++; // NOT reactive

// readonly
import { readonly } from 'vue';
const original = reactive({ count: 0 });
const copy = readonly(original);
copy.count++; // Warning
```

**Follow-up:**
- Why does `ref` need `.value`?
- Can you use reactive with arrays?
- What is `toRefs()` used for?

**Key Points:**
- `ref()`: Primitives and objects that need reassignment
- `reactive()`: Complex related state
- `ref` needs `.value`, reactive doesn't
- Can't destructure reactive (use `toRefs()`)
- Both support deep reactivity

---

## Question 4: How do you handle side effects with watch and watchEffect?

**Answer:**

### watchEffect

```javascript
import { ref, watchEffect } from 'vue';

const count = ref(0);
const doubled = ref(0);

// Automatically tracks dependencies
watchEffect(() => {
  console.log(`Count is ${count.value}`);
  doubled.value = count.value * 2;
  // Runs immediately and whenever count changes
});

// Cleanup
const stop = watchEffect((onCleanup) => {
  const timer = setTimeout(() => {
    console.log(count.value);
  }, 1000);

  onCleanup(() => {
    clearTimeout(timer);
  });
});

// Stop watching
stop();
```

### watch

```javascript
import { ref, watch } from 'vue';

const count = ref(0);

// Watch single ref
watch(count, (newValue, oldValue) => {
  console.log(`Count changed from ${oldValue} to ${newValue}`);
});

// Watch multiple sources
watch([count, name], ([newCount, newName], [oldCount, oldName]) => {
  console.log(`Count: ${newCount}, Name: ${newName}`);
});

// Watch reactive object
const state = reactive({ count: 0, name: '' });

watch(
  () => state.count, // Getter function
  (newValue, oldValue) => {
    console.log(`Count: ${newValue}`);
  }
);

// Watch entire reactive object
watch(
  state,
  (newValue, oldValue) => {
    console.log('State changed');
  },
  { deep: true } // Required for nested properties
);
```

### Options

```javascript
// Immediate execution
watch(
  count,
  (newValue) => {
    console.log(newValue);
  },
  { immediate: true } // Run immediately with initial value
);

// Deep watching
watch(
  state,
  () => {
    console.log('Nested property changed');
  },
  { deep: true }
);

// Flush timing
watch(
  count,
  () => {
    // Access updated DOM
  },
  { flush: 'post' } // 'pre' (default), 'post', 'sync'
);

// Once (Vue 3.4+)
watch(
  count,
  () => {
    console.log('Runs only once');
  },
  { once: true }
);
```

### Practical Examples

```javascript
// Debounced search
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const searchQuery = ref('');
const searchResults = ref([]);

const debouncedSearch = debounce(async (query) => {
  searchResults.value = await api.search(query);
}, 300);

watch(searchQuery, (newQuery) => {
  debouncedSearch(newQuery);
});

// Data fetching
const userId = ref(1);
const userData = ref(null);

watch(userId, async (newId) => {
  userData.value = await fetchUser(newId);
}, { immediate: true });

// Form validation
const email = ref('');
const emailError = ref('');

watch(email, (newEmail) => {
  if (!newEmail.includes('@')) {
    emailError.value = 'Invalid email';
  } else {
    emailError.value = '';
  }
});

// LocalStorage sync
watch(
  settings,
  (newSettings) => {
    localStorage.setItem('settings', JSON.stringify(newSettings));
  },
  { deep: true }
);
```

### watch vs watchEffect

| Feature | watch | watchEffect |
|---------|-------|-------------|
| Dependencies | Explicit | Auto-tracked |
| Old value | Yes | No |
| Lazy | Yes (unless immediate) | No (runs immediately) |
| Timing control | More options | Less control |
| Use case | Specific dependencies | Auto-track multiple |

```javascript
// watch: Explicit dependencies
watch([a, b], () => {
  console.log('a or b changed');
});

// watchEffect: Auto-track
watchEffect(() => {
  // Automatically tracks a and b
  console.log(a.value + b.value);
});
```

**Follow-up:**
- When should you use `watch` vs `watchEffect`?
- How do you stop a watcher?
- What is the flush timing option?

**Key Points:**
- `watchEffect`: Auto-tracks dependencies, runs immediately
- `watch`: Explicit dependencies, access old/new values
- Use `watch` for specific side effects
- Use `watchEffect` for automatic tracking
- Options: immediate, deep, flush, once

---

## Notes

Add more questions covering:
- defineProps with TypeScript
- defineEmits with validation
- useSlots and useAttrs
- Composables patterns and best practices
- Async setup and Suspense
- EffectScope
- Custom ref (customRef)
