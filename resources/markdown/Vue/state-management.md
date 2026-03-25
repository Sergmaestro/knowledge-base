# Vue State Management

## Question 1: What is Pinia and how does it differ from Vuex?

**Answer:**

Pinia is the official state management library for Vue 3, replacing Vuex.

### Basic Pinia Store

```javascript
// stores/counter.js
import { defineStore } from 'pinia';

export const useCounterStore = defineStore('counter', {
  // State
  state: () => ({
    count: 0,
    name: 'Counter'
  }),

  // Getters (computed)
  getters: {
    doubleCount: (state) => state.count * 2,
    doubleCountPlusOne(): number {
      return this.doubleCount + 1; // Access other getters
    }
  },

  // Actions (methods)
  actions: {
    increment() {
      this.count++;
    },
    async fetchCount() {
      const response = await fetch('/api/count');
      this.count = await response.json();
    }
  }
});

// Component usage
<script setup>
import { useCounterStore } from '@/stores/counter';

const counter = useCounterStore();

// Access state
console.log(counter.count);

// Access getters
console.log(counter.doubleCount);

// Call actions
counter.increment();

// Reactive destructuring
import { storeToRefs } from 'pinia';
const { count, doubleCount } = storeToRefs(counter);
const { increment } = counter; // Actions don't need storeToRefs
</script>
```

### Composition API Style (Setup Stores)

```javascript
// stores/counter.js
import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export const useCounterStore = defineStore('counter', () => {
  // State
  const count = ref(0);
  const name = ref('Counter');

  // Getters
  const doubleCount = computed(() => count.value * 2);

  // Actions
  function increment() {
    count.value++;
  }

  async function fetchCount() {
    const response = await fetch('/api/count');
    count.value = await response.json();
  }

  return {
    count,
    name,
    doubleCount,
    increment,
    fetchCount
  };
});
```

### Pinia vs Vuex

| Feature | Pinia | Vuex |
|---------|-------|------|
| Mutations | ❌ No | ✅ Yes |
| Actions | ✅ Can be sync or async | ✅ Async only |
| Modules | ❌ No (stores are modular by default) | ✅ Yes |
| TypeScript | ✅ Excellent | ⚠️ Complex |
| DevTools | ✅ Yes | ✅ Yes |
| Composition API | ✅ First-class | ⚠️ Added later |
| Bundle size | ✅ Smaller | Larger |

### Multiple Stores

```javascript
// stores/user.js
export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    isAuthenticated: false
  }),
  actions: {
    async login(credentials) {
      this.user = await api.login(credentials);
      this.isAuthenticated = true;
    },
    logout() {
      this.user = null;
      this.isAuthenticated = false;
    }
  }
});

// stores/cart.js
export const useCartStore = defineStore('cart', {
  state: () => ({
    items: []
  }),
  getters: {
    total: (state) => {
      return state.items.reduce((sum, item) => sum + item.price, 0);
    }
  },
  actions: {
    addItem(item) {
      this.items.push(item);
    }
  }
});

// Using multiple stores
<script setup>
const user = useUserStore();
const cart = useCartStore();
</script>
```

### Store Communication

```javascript
// One store can use another
export const useCartStore = defineStore('cart', {
  actions: {
    async checkout() {
      const userStore = useUserStore();

      if (!userStore.isAuthenticated) {
        throw new Error('Must be logged in');
      }

      await api.checkout(this.items, userStore.user);
    }
  }
});
```

### Plugins

```javascript
// main.js
import { createPinia } from 'pinia';

const pinia = createPinia();

// Plugin to persist state
pinia.use(({ store }) => {
  // Load from localStorage
  const saved = localStorage.getItem(store.$id);
  if (saved) {
    store.$patch(JSON.parse(saved));
  }

  // Save to localStorage on change
  store.$subscribe((mutation, state) => {
    localStorage.setItem(store.$id, JSON.stringify(state));
  });
});

app.use(pinia);
```

**Follow-up:**
- Why did Vue move from Vuex to Pinia?
- How do you handle async actions?
- Can Pinia stores communicate with each other?

**Key Points:**
- Pinia is simpler than Vuex (no mutations)
- Better TypeScript support
- Each store is a separate file
- Composition or Options API style
- Stores can use other stores

---

## Question 2: Explain Vuex and when you'd still use it.

**Answer:**

Vuex is Vue 2's state management pattern (still works in Vue 3).

### Basic Vuex Store

```javascript
// store/index.js
import { createStore } from 'vuex';

export default createStore({
  state: {
    count: 0,
    user: null
  },

  mutations: {
    INCREMENT(state) {
      state.count++;
    },
    SET_USER(state, user) {
      state.user = user;
    }
  },

  actions: {
    increment({ commit }) {
      commit('INCREMENT');
    },
    async fetchUser({ commit }, userId) {
      const user = await api.getUser(userId);
      commit('SET_USER', user);
    }
  },

  getters: {
    doubleCount: (state) => state.count * 2,
    isAuthenticated: (state) => state.user !== null
  }
});

// Component usage (Options API)
export default {
  computed: {
    ...mapState(['count', 'user']),
    ...mapGetters(['doubleCount', 'isAuthenticated'])
  },
  methods: {
    ...mapActions(['increment', 'fetchUser']),
    ...mapMutations(['SET_USER'])
  }
};

// Composition API
import { useStore } from 'vuex';

const store = useStore();
store.state.count;
store.getters.doubleCount;
store.dispatch('increment');
store.commit('SET_USER', user);
```

### Modules

```javascript
// store/modules/user.js
export default {
  namespaced: true,
  state: () => ({
    profile: null
  }),
  mutations: {
    SET_PROFILE(state, profile) {
      state.profile = profile;
    }
  },
  actions: {
    async loadProfile({ commit }) {
      const profile = await api.getProfile();
      commit('SET_PROFILE', profile);
    }
  }
};

// store/index.js
import user from './modules/user';
import cart from './modules/cart';

export default createStore({
  modules: {
    user,
    cart
  }
});

// Usage
store.state.user.profile;
store.dispatch('user/loadProfile');
```

### When to Use Vuex

- Legacy Vue 2 projects
- Large teams already familiar with Vuex
- Need time-travel debugging
- Complex module nesting requirements

**Key Points:**
- Vuex = mutations (sync) + actions (async)
- Pinia is simpler and recommended for new projects
- Vuex has modules, Pinia has separate stores
- Both integrate with Vue DevTools

---

## Question 3: How do you manage local component state vs global state?

**Answer:**

### Decision Matrix

```
Local State:
- UI state (modal open, tab selected)
- Form inputs (before submission)
- Component-specific data
- Temporary/derived data

Global State:
- User authentication
- App-wide settings
- Shared data (shopping cart)
- Data used by multiple components
```

### Local State Patterns

```vue
<script setup>
import { ref, reactive } from 'vue';

// Simple local state
const isModalOpen = ref(false);
const selectedTab = ref('profile');

// Form state
const form = reactive({
  email: '',
  password: '',
  errors: {}
});

// Derived local state
const isFormValid = computed(() => {
  return form.email && form.password && !Object.keys(form.errors).length;
});
</script>
```

### Global State with Pinia

```javascript
// For data shared across components
const userStore = useUserStore();
const cartStore = useCartStore();
```

### Composable for Reusable State

```javascript
// composables/useModal.js
import { ref } from 'vue';

export function useModal() {
  const isOpen = ref(false);

  function open() {
    isOpen.value = true;
  }

  function close() {
    isOpen.value = false;
  }

  return { isOpen, open, close };
}

// Can be reused across components with independent state
```

### Shared Composable (Singleton)

```javascript
// composables/useTheme.js
import { ref } from 'vue';

// Outside function = shared across all usages
const theme = ref('light');

export function useTheme() {
  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
  }

  return { theme, toggleTheme };
}

// All components using useTheme() share same state
```

**Key Points:**
- Local state: Component-specific, temporary
- Global state: Shared, persistent across components
- Use composables for reusable local state
- Use Pinia for truly global state

---

## Question 4: How do you handle API calls and data fetching in Vue?

**Answer:**

### Basic Data Fetching

```vue
<script setup>
import { ref, onMounted } from 'vue';

const users = ref([]);
const loading = ref(false);
const error = ref(null);

async function fetchUsers() {
  loading.value = true;
  try {
    const response = await fetch('/api/users');
    users.value = await response.json();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchUsers();
});
</script>

<template>
  <div v-if="loading">Loading...</div>
  <div v-else-if="error">Error: {{ error }}</div>
  <ul v-else>
    <li v-for="user in users" :key="user.id">
      {{ user.name }}
    </li>
  </ul>
</template>
```

### Reusable Fetch Composable

```javascript
// composables/useFetch.js
import { ref, watchEffect, toValue } from 'vue';

export function useFetch(url) {
  const data = ref(null);
  const error = ref(null);
  const loading = ref(false);

  async function refetch() {
    loading.value = true;
    error.value = null;

    try {
      const response = await fetch(toValue(url));
      data.value = await response.json();
    } catch (e) {
      error.value = e;
    } finally {
      loading.value = false;
    }
  }

  watchEffect(() => {
    refetch();
  });

  return { data, error, loading, refetch };
}

// Usage
const { data: users, loading, error } = useFetch('/api/users');
```

### Store-based Data Fetching

```javascript
// stores/users.js
export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchUsers() {
      this.loading = true;
      try {
        const response = await fetch('/api/users');
        this.users = await response.json();
      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },

    async createUser(userData) {
      const response = await fetch('/api/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
      });
      const user = await response.json();
      this.users.push(user);
    }
  }
});
```

### With Vue Query (TanStack Query)

```vue
<script setup>
import { useQuery, useMutation } from '@tanstack/vue-query';

// Fetch data
const { data: users, isLoading, error } = useQuery({
  queryKey: ['users'],
  queryFn: () => fetch('/api/users').then(r => r.json())
});

// Mutations
const createUserMutation = useMutation({
  mutationFn: (userData) => {
    return fetch('/api/users', {
      method: 'POST',
      body: JSON.stringify(userData)
    });
  },
  onSuccess: () => {
    // Invalidate and refetch
    queryClient.invalidateQueries({ queryKey: ['users'] });
  }
});

function handleSubmit(userData) {
  createUserMutation.mutate(userData);
}
</script>
```

**Key Points:**
- Handle loading, error, and data states
- Create reusable fetch composables
- Store API data in Pinia for shared state
- Consider Vue Query for advanced caching
- Always handle errors gracefully

---

## Notes

Add more questions covering:
- State persistence (localStorage, sessionStorage)
- Server state vs client state
- Optimistic updates
- Caching strategies
- Real-time state (WebSocket, SSE)
- State hydration for SSR
- Testing state management
