# Vue.js Fundamentals

## Question 1: Explain Vue's reactivity system. How does it work?

**Answer:**

Vue's reactivity system automatically tracks dependencies and updates the DOM when data changes.

### Vue 3 Reactivity (Proxy-based)

```javascript
import { reactive, ref, computed, watch } from 'vue';

// reactive() for objects
const state = reactive({
  count: 0,
  user: {
    name: 'John',
    age: 30
  }
});

state.count++; // Automatically triggers updates

// ref() for primitives (and objects)
const count = ref(0);
const message = ref('Hello');

count.value++; // Access via .value in script
// In template: {{ count }} (auto-unwrapped)

// computed() for derived state
const doubleCount = computed(() => count.value * 2);

// watch() for side effects
watch(count, (newValue, oldValue) => {
  console.log(`Count changed from ${oldValue} to ${newValue}`);
});

// watchEffect() - automatic dependency tracking
watchEffect(() => {
  console.log(`Count is: ${count.value}`);
  // Automatically re-runs when count changes
});
```

### Vue 2 Reactivity (Object.defineProperty)

```javascript
// Vue 2 uses Object.defineProperty
export default {
  data() {
    return {
      count: 0,
      items: []
    };
  },
  methods: {
    increment() {
      this.count++; // Reactive
    },
    addItem() {
      // Vue 2 caveats:
      this.items.push('new'); // Reactive
      this.items[0] = 'updated'; // NOT reactive in Vue 2
      this.$set(this.items, 0, 'updated'); // Reactive way

      // Adding new properties
      this.newProp = 'value'; // NOT reactive in Vue 2
      this.$set(this, 'newProp', 'value'); // Reactive way
    }
  }
};
```

### How It Works

```javascript
// Vue 3 uses Proxy
const target = { count: 0 };
const handler = {
  get(target, key, receiver) {
    track(target, key); // Track dependency
    return Reflect.get(target, key, receiver);
  },
  set(target, key, value, receiver) {
    const result = Reflect.set(target, key, value, receiver);
    trigger(target, key); // Trigger updates
    return result;
  }
};

const proxy = new Proxy(target, handler);

// When you access proxy.count:
// 1. track() records which component is using it
// 2. When proxy.count changes:
// 3. trigger() updates all components that depend on it
```

### Reactivity Patterns

```javascript
// Deep reactivity
const state = reactive({
  nested: {
    deep: {
      value: 1
    }
  }
});

state.nested.deep.value++; // Reactive at all levels

// Shallow reactivity (Vue 3)
import { shallowReactive, shallowRef } from 'vue';

const state = shallowReactive({
  nested: { count: 0 }
});

state.nested = { count: 1 }; // Reactive
state.nested.count++; // NOT reactive

// readonly
import { readonly } from 'vue';

const original = reactive({ count: 0 });
const copy = readonly(original);

copy.count++; // Warning: readonly
```

### toRef and toRefs

```javascript
import { reactive, toRef, toRefs } from 'vue';

const state = reactive({
  count: 0,
  name: 'John'
});

// toRef - create ref to single property
const count = toRef(state, 'count');
count.value++; // Updates state.count

// toRefs - convert all properties to refs
const { count, name } = toRefs(state);
// Maintains reactivity after destructuring
```

**Follow-up:**
- What's the difference between Vue 2 and Vue 3 reactivity?
- When should you use `ref()` vs `reactive()`?
- What are the limitations of Vue 2's reactivity?

**Key Points:**
- Vue 3 uses Proxy (no caveats)
- Vue 2 uses Object.defineProperty (has caveats)
- `reactive()` for objects, `ref()` for primitives
- `computed()` for derived state
- `watch()` for side effects

---

## Question 2: Explain Vue component lifecycle hooks.

**Answer:**

Lifecycle hooks allow you to run code at specific stages of a component's lifecycle.

### Vue 3 Composition API

```javascript
import {
  onBeforeMount,
  onMounted,
  onBeforeUpdate,
  onUpdated,
  onBeforeUnmount,
  onUnmounted
} from 'vue';

export default {
  setup() {
    console.log('1. Setup - runs first');

    onBeforeMount(() => {
      console.log('2. Before Mount - before DOM rendered');
      // Component exists but not in DOM yet
    });

    onMounted(() => {
      console.log('3. Mounted - component in DOM');
      // Access DOM, make API calls, setup listeners
      fetchData();
      setupEventListeners();
    });

    onBeforeUpdate(() => {
      console.log('4. Before Update - before reactive data changes applied');
      // Access DOM before update
    });

    onUpdated(() => {
      console.log('5. Updated - after reactive data changes applied');
      // DOM has been updated
    });

    onBeforeUnmount(() => {
      console.log('6. Before Unmount - before component destroyed');
      // Cleanup: remove listeners, cancel requests
      cleanupEventListeners();
      cancelPendingRequests();
    });

    onUnmounted(() => {
      console.log('7. Unmounted - component destroyed');
      // Final cleanup
    });

    return {};
  }
};
```

### Vue 2/3 Options API

```javascript
export default {
  // 1. Creation phase
  beforeCreate() {
    // Data and methods not available yet
    console.log('Before Create');
  },

  created() {
    // Data and methods available, DOM not available
    console.log('Created');
    this.fetchData(); // Good place for API calls
  },

  // 2. Mounting phase
  beforeMount() {
    // Template compiled, not yet rendered
    console.log('Before Mount');
  },

  mounted() {
    // Component mounted to DOM
    console.log('Mounted');
    this.$refs.input.focus(); // Can access DOM
    this.setupChart(); // Initialize third-party libraries
  },

  // 3. Update phase (when reactive data changes)
  beforeUpdate() {
    // Before DOM re-rendered
    console.log('Before Update');
  },

  updated() {
    // After DOM re-rendered
    console.log('Updated');
    // Be careful: updating data here can cause infinite loop
  },

  // 4. Destruction phase
  beforeUnmount() { // beforeDestroy in Vue 2
    // Cleanup before component removed
    console.log('Before Unmount');
    window.removeEventListener('resize', this.handleResize);
    clearInterval(this.timer);
  },

  unmounted() { // destroyed in Vue 2
    console.log('Unmounted');
  }
};
```

### Common Use Cases

```javascript
export default {
  setup() {
    const chart = ref(null);
    const timer = ref(null);
    const data = ref([]);

    // API calls
    onMounted(async () => {
      data.value = await fetchData();
    });

    // Third-party library initialization
    onMounted(() => {
      chart.value = new Chart(document.getElementById('chart'), {
        // chart config
      });
    });

    // Event listeners
    onMounted(() => {
      window.addEventListener('resize', handleResize);
    });

    onBeforeUnmount(() => {
      window.removeEventListener('resize', handleResize);
    });

    // Timers/Intervals
    onMounted(() => {
      timer.value = setInterval(() => {
        console.log('Tick');
      }, 1000);
    });

    onBeforeUnmount(() => {
      clearInterval(timer.value);
    });

    // WebSocket cleanup
    let socket;
    onMounted(() => {
      socket = new WebSocket('ws://localhost:8080');
    });

    onBeforeUnmount(() => {
      socket.close();
    });

    return { data };
  }
};
```

### Lifecycle Diagram

```
┌─────────────────────────────────────┐
│         Component Creation          │
├─────────────────────────────────────┤
│  beforeCreate / setup()             │
│  created                            │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Template Compilation         │
├─────────────────────────────────────┤
│  beforeMount                        │
│  mounted ← API calls, DOM access    │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Reactive Updates            │
├─────────────────────────────────────┤
│  beforeUpdate                       │
│  updated                            │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Component Unmount           │
├─────────────────────────────────────┤
│  beforeUnmount ← Cleanup            │
│  unmounted                          │
└─────────────────────────────────────┘
```

**Follow-up:**
- When should you use `created` vs `mounted`?
- What's the difference between `beforeUnmount` and `unmounted`?
- Can lifecycle hooks be async?

**Key Points:**
- `mounted`: DOM available, API calls, setup listeners
- `beforeUnmount`: Cleanup listeners, timers, subscriptions
- Composition API: `onMounted()`, `onBeforeUnmount()`
- Options API: `mounted()`, `beforeUnmount()`
- Don't update reactive data in `updated()` (infinite loop)

---

## Question 3: Explain component communication patterns in Vue.

**Answer:**

### 1. Props (Parent → Child)

```vue
<!-- Parent.vue -->
<template>
  <ChildComponent
    :message="greeting"
    :user="currentUser"
    :count="42"
  />
</template>

<script setup>
import ChildComponent from './ChildComponent.vue';

const greeting = ref('Hello');
const currentUser = ref({ name: 'John' });
</script>

<!-- ChildComponent.vue -->
<script setup>
// Vue 3 Composition API
const props = defineProps({
  message: {
    type: String,
    required: true
  },
  user: {
    type: Object,
    default: () => ({ name: 'Guest' })
  },
  count: Number
});

console.log(props.message); // 'Hello'
</script>

<!-- Options API -->
<script>
export default {
  props: {
    message: String,
    user: Object,
    count: Number
  }
};
</script>
```

### 2. Events (Child → Parent)

```vue
<!-- ChildComponent.vue -->
<template>
  <button @click="handleClick">Click me</button>
</template>

<script setup>
const emit = defineEmits(['update', 'delete']);

const handleClick = () => {
  emit('update', { id: 1, name: 'Updated' });
};
</script>

<!-- Parent.vue -->
<template>
  <ChildComponent
    @update="handleUpdate"
    @delete="handleDelete"
  />
</template>

<script setup>
const handleUpdate = (data) => {
  console.log('Updated:', data);
};
</script>
```

### 3. v-model (Two-way binding)

```vue
<!-- ChildComponent.vue -->
<template>
  <input
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>

<script setup>
defineProps(['modelValue']);
defineEmits(['update:modelValue']);
</script>

<!-- Parent.vue -->
<template>
  <ChildComponent v-model="text" />
  <!-- Shorthand for:
    <ChildComponent
      :modelValue="text"
      @update:modelValue="text = $event"
    />
  -->
</template>

<script setup>
const text = ref('');
</script>

<!-- Multiple v-models -->
<template>
  <ChildComponent
    v-model:title="title"
    v-model:content="content"
  />
</template>
```

### 4. Provide/Inject (Ancestor → Descendant)

```vue
<!-- Grandparent.vue -->
<script setup>
import { provide, ref } from 'vue';

const theme = ref('dark');
const updateTheme = (newTheme) => {
  theme.value = newTheme;
};

provide('theme', theme);
provide('updateTheme', updateTheme);
</script>

<!-- Deeply nested Child.vue -->
<script setup>
import { inject } from 'vue';

const theme = inject('theme');
const updateTheme = inject('updateTheme');

const toggleTheme = () => {
  updateTheme(theme.value === 'dark' ? 'light' : 'dark');
};
</script>

<!-- Options API -->
<script>
export default {
  provide() {
    return {
      theme: this.theme,
      updateTheme: this.updateTheme
    };
  },
  inject: ['theme', 'updateTheme']
};
</script>
```

### 5. Global State (Pinia/Vuex)

```javascript
// stores/user.js (Pinia)
import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    isAuthenticated: false
  }),
  actions: {
    setUser(user) {
      this.user = user;
      this.isAuthenticated = true;
    },
    logout() {
      this.user = null;
      this.isAuthenticated = false;
    }
  },
  getters: {
    fullName: (state) => `${state.user?.firstName} ${state.user?.lastName}`
  }
});

// Component.vue
<script setup>
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();

// Access state
console.log(userStore.user);
console.log(userStore.fullName);

// Modify state
userStore.setUser({ firstName: 'John', lastName: 'Doe' });
</script>
```

### 6. Event Bus (Vue 2 pattern, avoid in Vue 3)

```javascript
// Vue 2 - Event Bus
// eventBus.js
import Vue from 'vue';
export const EventBus = new Vue();

// Component A
EventBus.$emit('user-updated', user);

// Component B
EventBus.$on('user-updated', (user) => {
  console.log(user);
});

// Vue 3 alternative - use mitt or tiny-emitter
import mitt from 'mitt';
export const emitter = mitt();

// Component A
emitter.emit('user-updated', user);

// Component B
emitter.on('user-updated', (user) => {
  console.log(user);
});
```

### 7. Template Refs (Parent accesses Child)

```vue
<!-- Parent.vue -->
<template>
  <ChildComponent ref="childRef" />
  <button @click="callChildMethod">Call Child Method</button>
</template>

<script setup>
import { ref } from 'vue';

const childRef = ref(null);

const callChildMethod = () => {
  childRef.value.childMethod();
};
</script>

<!-- ChildComponent.vue -->
<script setup>
const childMethod = () => {
  console.log('Called from parent');
};

// Expose method to parent
defineExpose({
  childMethod
});
</script>
```

**Follow-up:**
- When should you use provide/inject vs props?
- What are the downsides of event buses?
- How do you implement v-model in custom components?

**Key Points:**
- Props: Parent → Child (one-way data flow)
- Events: Child → Parent communication
- v-model: Two-way binding shorthand
- Provide/Inject: Skip intermediate components
- Pinia/Vuex: Global state management
- Template refs: Parent accesses child methods

---

## Question 4: What are Vue directives and how do you create custom ones?

**Answer:**

Directives are special attributes that apply reactive behavior to DOM.

### Built-in Directives

```vue
<template>
  <!-- v-if: Conditional rendering -->
  <div v-if="isVisible">Visible</div>
  <div v-else-if="isAlternate">Alternate</div>
  <div v-else>Hidden</div>

  <!-- v-show: Toggle display (CSS) -->
  <div v-show="isVisible">Toggle display</div>

  <!-- v-for: List rendering -->
  <li v-for="item in items" :key="item.id">
    {{ item.name }}
  </li>

  <!-- v-bind: Bind attributes (shorthand :) -->
  <img v-bind:src="imageSrc" />
  <img :src="imageSrc" />
  <div :class="{ active: isActive, 'text-danger': hasError }"></div>
  <div :style="{ color: textColor, fontSize: fontSize + 'px' }"></div>

  <!-- v-on: Event listeners (shorthand @) -->
  <button v-on:click="handleClick">Click</button>
  <button @click="handleClick">Click</button>
  <input @keyup.enter="submit" />

  <!-- v-model: Two-way binding -->
  <input v-model="text" />
  <input v-model.lazy="text" /> <!-- Update on change, not input -->
  <input v-model.number="age" /> <!-- Type cast to number -->
  <input v-model.trim="name" /> <!-- Trim whitespace -->

  <!-- v-html: Raw HTML (XSS risk!) -->
  <div v-html="rawHtml"></div>

  <!-- v-text: Text content -->
  <span v-text="message"></span>

  <!-- v-once: Render once, no updates -->
  <span v-once>{{ staticContent }}</span>

  <!-- v-pre: Skip compilation -->
  <span v-pre>{{ This will not be compiled }}</span>
</template>
```

### Custom Directives (Vue 3)

```javascript
// Global directive
const app = createApp(App);

app.directive('focus', {
  mounted(el) {
    el.focus();
  }
});

// Usage: <input v-focus />

// Directive with value
app.directive('color', {
  mounted(el, binding) {
    el.style.color = binding.value;
  },
  updated(el, binding) {
    el.style.color = binding.value;
  }
});

// Usage: <div v-color="'red'">Red text</div>
```

### Directive Hooks

```javascript
app.directive('example', {
  // Called before element is inserted into DOM
  created(el, binding, vnode, prevVnode) {
    console.log('created');
  },

  // Called before parent component is mounted
  beforeMount(el, binding, vnode, prevVnode) {
    console.log('beforeMount');
  },

  // Called when parent component is mounted
  mounted(el, binding, vnode, prevVnode) {
    console.log('mounted');
    console.log(binding.value); // Directive value
    console.log(binding.arg); // Argument (v-example:arg)
    console.log(binding.modifiers); // Modifiers (v-example.mod1.mod2)
  },

  // Called before parent component is updated
  beforeUpdate(el, binding, vnode, prevVnode) {
    console.log('beforeUpdate');
  },

  // Called after parent component is updated
  updated(el, binding, vnode, prevVnode) {
    console.log('updated');
  },

  // Called before parent component is unmounted
  beforeUnmount(el, binding, vnode, prevVnode) {
    console.log('beforeUnmount');
  },

  // Called when parent component is unmounted
  unmounted(el, binding, vnode, prevVnode) {
    console.log('unmounted');
  }
});
```

### Practical Custom Directives

```javascript
// Click outside directive
app.directive('click-outside', {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
});

// Usage
<div v-click-outside="closeModal">Modal content</div>

// Tooltip directive
app.directive('tooltip', {
  mounted(el, binding) {
    el.setAttribute('title', binding.value);

    // Or use a library like Tippy.js
    tippy(el, {
      content: binding.value,
      placement: binding.arg || 'top'
    });
  }
});

// Usage
<button v-tooltip:bottom="'Click to submit'">Submit</button>

// Lazy load images
app.directive('lazy', {
  mounted(el, binding) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          el.src = binding.value;
          observer.unobserve(el);
        }
      });
    });
    observer.observe(el);
  }
});

// Usage
<img v-lazy="imageUrl" />

// Permission directive
app.directive('permission', {
  mounted(el, binding) {
    const { value } = binding;
    const permissions = store.getters['user/permissions'];

    if (!permissions.includes(value)) {
      el.parentNode?.removeChild(el);
    }
  }
});

// Usage
<button v-permission="'admin'">Delete User</button>
```

### Local Directives

```vue
<script setup>
// Component-specific directive
const vFocus = {
  mounted: (el) => el.focus()
};
</script>

<template>
  <input v-focus />
</template>

<!-- Options API -->
<script>
export default {
  directives: {
    focus: {
      mounted(el) {
        el.focus();
      }
    }
  }
};
</script>
```

**Follow-up:**
- When should you use v-if vs v-show?
- What are directive modifiers?
- How do you pass arguments to custom directives?

**Key Points:**
- Directives apply reactive behavior to DOM
- Built-in: v-if, v-for, v-bind, v-on, v-model
- Custom directives for DOM manipulation
- Hooks: mounted, updated, unmounted
- Use for: focus, tooltips, permissions, lazy loading

---

---

## Question 5: What are the main differences between Vue 2 and Vue 3?

**Answer:**

See [Vue/composition-api.md → Vue 2 vs Vue 3] for detailed comparison.

### Quick Summary

| Feature | Vue 2 | Vue 3 |
|---------|-------|-------|
| Reactivity | Object.defineProperty | Proxy |
| API | Options API | Options + Composition API |
| Performance | Good | 2x faster |
| Bundle size | ~32KB | ~16KB |
| TypeScript | Partial | First-class |
| Fragments | Single root | Multiple roots |

```javascript
// Vue 2 - Reactivity limitations
this.items[0] = newItem; // NOT reactive
Vue.set(this.items, 0, newItem); // Reactive

// Vue 3 - No limitations
items.value[0] = newItem; // Reactive
```

**Key Points:**
- Vue 3: Faster, smaller, better TypeScript
- Composition API for better code organization
- Proxy-based reactivity (no caveats)
- Multiple root nodes (fragments)
- Breaking changes: global API, filters removed

---

## Notes

Add more questions covering:
- Virtual DOM and rendering
- Slots and scoped slots
- Dynamic components
- Async components and code splitting
- Teleport (Vue 3)
- Suspense (Vue 3)
- Keep-alive
- Mixins vs Composables
