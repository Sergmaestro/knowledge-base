# Vue Performance Optimization

## Question 1: What are key strategies for optimizing Vue application performance?

**Answer:**

### 1. Code Splitting and Lazy Loading

```javascript
// Route-level code splitting
const routes = [
  {
    path: '/dashboard',
    component: () => import('./views/Dashboard.vue') // Lazy loaded
  },
  {
    path: '/users',
    component: () => import('./views/Users.vue')
  }
];

// Component lazy loading
<script setup>
import { defineAsyncComponent } from 'vue';

const HeavyComponent = defineAsyncComponent(() =>
  import('./components/HeavyComponent.vue')
);
</script>

// With loading and error states
const HeavyComponent = defineAsyncComponent({
  loader: () => import('./components/HeavyComponent.vue'),
  loadingComponent: LoadingSpinner,
  errorComponent: ErrorDisplay,
  delay: 200, // Show loading after 200ms
  timeout: 3000 // Error after 3s
});
```

### 2. Virtual Scrolling for Large Lists

```vue
<script setup>
import { VirtualList } from 'vue-virtual-scroller';

const items = ref(Array.from({ length: 10000 }, (_, i) => ({
  id: i,
  name: `Item ${i}`
})));
</script>

<template>
  <!-- Only renders visible items -->
  <VirtualList
    :items="items"
    :item-height="50"
    class="list"
  >
    <template #default="{ item }">
      <div class="item">{{ item.name }}</div>
    </template>
  </VirtualList>
</template>
```

### 3. Computed vs Methods

```vue
<script setup>
import { ref, computed } from 'vue';

const items = ref([1, 2, 3, 4, 5]);

// ✅ Good: Cached, only recomputes when items change
const filteredItems = computed(() => {
  console.log('Computing filtered items');
  return items.value.filter(item => item > 2);
});

// ❌ Bad: Executes on every render
function getFilteredItems() {
  console.log('Getting filtered items');
  return items.value.filter(item => item > 2);
}
</script>

<template>
  <div v-for="item in filteredItems" :key="item">{{ item }}</div>
  <!-- vs -->
  <div v-for="item in getFilteredItems()" :key="item">{{ item }}</div>
</template>
```

### 4. v-show vs v-if

```vue
<template>
  <!-- v-if: Conditionally render (DOM add/remove) -->
  <!-- Use for: Rarely toggled -->
  <div v-if="isVisible">
    Expensive component
  </div>

  <!-- v-show: Toggle CSS display -->
  <!-- Use for: Frequently toggled -->
  <div v-show="isVisible">
    Modal or dropdown
  </div>
</template>
```

### 5. KeepAlive for Component Caching

```vue
<template>
  <!-- Cache inactive components -->
  <KeepAlive :max="10">
    <component :is="currentView" />
  </KeepAlive>

  <!-- Include/Exclude specific components -->
  <KeepAlive :include="['Dashboard', 'Profile']">
    <RouterView />
  </KeepAlive>

  <!-- RegExp pattern -->
  <KeepAlive :include="/User.*/">
    <RouterView />
  </KeepAlive>
</template>

<script setup>
import { onActivated, onDeactivated } from 'vue';

// Lifecycle hooks for kept-alive components
onActivated(() => {
  console.log('Component activated');
});

onDeactivated(() => {
  console.log('Component deactivated');
});
</script>
```

### 6. Avoid Reactive Overhead

```javascript
// ❌ Don't make everything reactive
const state = reactive({
  data: largeDataset, // Don't make large static data reactive
  config: immutableConfig
});

// ✅ Use shallowRef for large objects
import { shallowRef } from 'vue';

const largeData = shallowRef(massiveArray);
// Only top-level is reactive, nested changes don't trigger

// ✅ Use markRaw for non-reactive data
import { markRaw } from 'vue';

const state = reactive({
  chart: markRaw(new Chart()) // Third-party instance, doesn't need reactivity
});

// ✅ Use shallowReactive
const state = shallowReactive({
  nested: { data: 'value' }
});
// Only first level is reactive
```

### 7. Optimize v-for with Keys

```vue
<template>
  <!-- ✅ Good: Unique stable keys -->
  <div v-for="item in items" :key="item.id">
    {{ item.name }}
  </div>

  <!-- ❌ Bad: Index as key (can cause issues) -->
  <div v-for="(item, index) in items" :key="index">
    {{ item.name }}
  </div>

  <!-- ❌ Never: No key -->
  <div v-for="item in items">
    {{ item.name }}
  </div>
</template>
```

### 8. Debounce User Input

```vue
<script setup>
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const searchQuery = ref('');
const results = ref([]);

const debouncedSearch = debounce(async (query) => {
  results.value = await api.search(query);
}, 300);

watch(searchQuery, (newQuery) => {
  debouncedSearch(newQuery);
});
</script>

<template>
  <input v-model="searchQuery" placeholder="Search..." />
</template>
```

**Follow-up:**
- When should you use v-show vs v-if?
- What is the purpose of KeepAlive?
- How does virtual scrolling improve performance?

**Key Points:**
- Lazy load routes and heavy components
- Use computed for cached calculations
- Virtual scroll for large lists
- KeepAlive for frequently switched components
- Debounce expensive operations
- v-show for frequent toggles, v-if for rare

---

## Question 2: How do you optimize bundle size in Vue applications?

**Answer:**

### 1. Analyze Bundle Size

```bash
# Build with analysis
npm run build -- --report

# Or use vite-plugin-visualizer
npm install -D rollup-plugin-visualizer

# vite.config.js
import { visualizer } from 'rollup-plugin-visualizer';

export default {
  plugins: [
    visualizer({
      open: true,
      gzipSize: true,
      brotliSize: true
    })
  ]
};
```

### 2. Tree Shaking

```javascript
// ❌ Bad: Imports entire library
import _ from 'lodash';
_.debounce(fn, 300);

// ✅ Good: Import only what you need
import debounce from 'lodash-es/debounce';
debounce(fn, 300);

// ❌ Bad
import * as moment from 'moment';

// ✅ Good: Use date-fns (tree-shakeable)
import { format, addDays } from 'date-fns';
```

### 3. Dynamic Imports

```javascript
// Import libraries only when needed
async function exportToExcel() {
  const { utils, writeFile } = await import('xlsx');
  // Use XLSX only when user clicks export
}

// Conditional imports
if (isDevelopment) {
  const { devtools } = await import('@vue/devtools');
  devtools.connect();
}
```

### 4. Remove Unused Dependencies

```bash
# Find unused dependencies
npm install -g depcheck
depcheck

# Remove
npm uninstall unused-package
```

### 5. Use Production Builds

```javascript
// Automatically strips out:
// - Vue DevTools support
// - Warning messages
// - Debug code

// Build command
npm run build // Uses NODE_ENV=production
```

### 6. Component Library Optimization

```javascript
// ❌ Bad: Import entire UI library
import ElementPlus from 'element-plus';
app.use(ElementPlus);

// ✅ Good: Import only used components
import { ElButton, ElInput } from 'element-plus';
app.component(ElButton.name, ElButton);
app.component(ElInput.name, ElInput);

// Or use auto-import plugin
// vite.config.js
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';

export default {
  plugins: [
    Components({
      resolvers: [ElementPlusResolver()]
    })
  ]
};
```

### 7. CSS Optimization

```javascript
// vite.config.js
export default {
  build: {
    cssCodeSplit: true, // Split CSS per component
  }
};

// Remove unused CSS with PurgeCSS
import purgecss from '@fullhuman/postcss-purgecss';

export default {
  css: {
    postcss: {
      plugins: [
        purgecss({
          content: ['./index.html', './src/**/*.{vue,js,ts}']
        })
      ]
    }
  }
};
```

### 8. Compression

```javascript
// vite.config.js
import viteCompression from 'vite-plugin-compression';

export default {
  plugins: [
    viteCompression({
      algorithm: 'gzip',
      threshold: 10240 // Only compress files > 10kb
    }),
    viteCompression({
      algorithm: 'brotliCompress',
      ext: '.br'
    })
  ]
};
```

**Key Points:**
- Analyze bundle with visualizer
- Tree shake with ES modules imports
- Lazy load heavy libraries
- Auto-import UI components
- Remove unused dependencies
- Enable gzip/brotli compression

---

## Question 3: What are best practices for rendering optimization?

**Answer:**

### 1. Memoization with memo()

```vue
<script setup>
import { ref, computed } from 'vue';

const props = defineProps(['items']);

// ✅ Memoized computation
const expensiveResult = computed(() => {
  return props.items.map(item => {
    // Expensive calculation
    return heavyOperation(item);
  });
});
</script>
```

### 2. Functional Components

```vue
<!-- Stateless components are cheaper -->
<script setup>
// Functional component (no instance)
defineProps(['title', 'content']);
</script>

<template>
  <div class="card">
    <h3>{{ title }}</h3>
    <p>{{ content }}</p>
  </div>
</template>
```

### 3. v-once for Static Content

```vue
<template>
  <!-- Render once, never update -->
  <div v-once>
    <h1>{{ staticTitle }}</h1>
    <p>{{ staticDescription }}</p>
  </div>

  <!-- Static children -->
  <ul v-once>
    <li v-for="item in staticList" :key="item.id">
      {{ item.name }}
    </li>
  </ul>
</template>
```

### 4. v-memo (Vue 3.2+)

```vue
<template>
  <!-- Only re-render when userId changes -->
  <div v-memo="[userId]">
    <UserProfile :user-id="userId" />
    <UserStats :user-id="userId" />
  </div>

  <!-- List optimization -->
  <div
    v-for="item in list"
    :key="item.id"
    v-memo="[item.id, item.selected]"
  >
    <!-- Only updates if id or selected changes -->
    {{ item.name }} - {{ item.selected ? '✓' : '' }}
  </div>
</template>
```

### 5. Avoid Inline Functions in Templates

```vue
<script setup>
// ❌ Bad: Creates new function on every render
</script>

<template>
  <button @click="() => handleClick(item)">Click</button>
</template>

<script setup>
// ✅ Good: Stable reference
const handleItemClick = (item) => {
  handleClick(item);
};
</script>

<template>
  <button @click="handleItemClick(item)">Click</button>
</template>
```

### 6. Batch DOM Updates

```javascript
import { nextTick } from 'vue';

async function updateMultiple() {
  state.a = 1;
  state.b = 2;
  state.c = 3;

  // Wait for DOM update
  await nextTick();

  // All updates batched into single re-render
  console.log('DOM updated');
}
```

### 7. Avoid Deep Watchers When Possible

```javascript
// ❌ Expensive: Deep watch entire object
watch(
  state,
  () => {
    // Runs on any nested change
  },
  { deep: true }
);

// ✅ Better: Watch specific property
watch(
  () => state.user.name,
  (newName) => {
    // Only runs when name changes
  }
);
```

**Key Points:**
- Use computed for memoization
- v-once for truly static content
- v-memo for conditional re-rendering
- Avoid inline functions in templates
- Batch updates with nextTick
- Watch specific properties, not deep objects

---

## Question 4: How do you measure and profile Vue application performance?

**Answer:**

### 1. Vue DevTools Performance Tab

```javascript
// Enable performance tracking
app.config.performance = true;

// View in Vue DevTools:
// - Component render time
// - Update time
// - Lifecycle hook duration
```

### 2. Chrome DevTools

```javascript
// Performance profiler
// 1. Open DevTools > Performance
// 2. Record interaction
// 3. Look for:
//    - Long tasks (> 50ms)
//    - Layout thrashing
//    - Forced reflows

// Lighthouse audit
// 1. DevTools > Lighthouse
// 2. Analyze:
//    - First Contentful Paint
//    - Time to Interactive
//    - Largest Contentful Paint
```

### 3. Custom Performance Marks

```javascript
// Mark performance points
performance.mark('component-render-start');

// ... component renders

performance.mark('component-render-end');

performance.measure(
  'component-render',
  'component-render-start',
  'component-render-end'
);

const measure = performance.getEntriesByName('component-render')[0];
console.log(`Render took ${measure.duration}ms`);
```

### 4. Vue Performance Plugin

```javascript
// Track component render times
import { createApp } from 'vue';

const app = createApp(App);

app.mixin({
  beforeCreate() {
    this.$options._renderStart = performance.now();
  },
  mounted() {
    const renderTime = performance.now() - this.$options._renderStart;
    if (renderTime > 16) {
      console.warn(`${this.$options.name} took ${renderTime}ms to render`);
    }
  }
});
```

### 5. Bundle Analysis

```bash
# Analyze bundle size
npm run build -- --report

# Check tree-shaking effectiveness
# webpack-bundle-analyzer
# or rollup-plugin-visualizer for Vite
```

### 6. Real User Monitoring (RUM)

```javascript
// Track real user metrics
import { onLCP, onFID, onCLS } from 'web-vitals';

onLCP(console.log); // Largest Contentful Paint
onFID(console.log); // First Input Delay
onCLS(console.log); // Cumulative Layout Shift

// Send to analytics
function sendToAnalytics(metric) {
  gtag('event', metric.name, {
    value: Math.round(metric.value),
    metric_id: metric.id
  });
}

onLCP(sendToAnalytics);
```

**Key Points:**
- Use Vue DevTools performance tab
- Profile with Chrome DevTools
- Set performance budget (< 16ms per frame)
- Monitor Core Web Vitals
- Analyze bundle size regularly
- Track real user metrics in production

---

## Notes

Add more questions covering:
- SSR vs CSR vs SSG performance
- Image optimization (lazy loading, responsive images)
- Font loading strategies
- Service Workers and caching
- CDN usage
- HTTP/2 and HTTP/3 optimizations
- Lighthouse CI for continuous monitoring
