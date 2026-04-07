# Coding: Algorithms & Problem Solving

This section covers common problem-solving approaches for LeetCode-style coding problems, implemented in PHP. Each approach includes detailed explanations, when to use it, common patterns, and hints for implementation.

## Question 1: What is the Two Pointers technique and when should I use it?

**Answer:**

Two Pointers is a technique that uses two pointers (indices) to iterate through a data structure, typically a sorted array. The key insight is that by moving pointers strategically, you can find answers in O(n) time that would otherwise require O(n²) brute force.

**Why it works:**
In a sorted array, if you need to find two numbers that sum to a target, you can start with the smallest and largest numbers. If their sum is too small, move the left pointer (smaller) to increase the sum. If too large, move the right pointer (larger) to decrease the sum. This works because the array is sorted.

**When to Use:**
- Sorted arrays where you need to find pairs/triplets with specific sum
- Palindrome problems (comparing from both ends)
- Removing duplicates in-place
- Problems involving "twosum", "threesum", "trapping rain water"

**Step-by-Step Example:**
Given sorted array `[1, 2, 3, 4, 5, 6]` and target `7`:
1. Start: left=0 (1), right=5 (6), sum=7 → Found! Return [0, 5]
2. This avoids checking every pair (which would be 15 comparisons)

**Implementation Pattern:**

```php
function twoPointers(array $nums, int $target): array {
    $left = 0;
    $right = count($nums) - 1;
    
    while ($left < $right) {
        $sum = $nums[$left] + $nums[$right];
        
        if ($sum === $target) {
            return [$left, $right];
        } elseif ($sum < $target) {
            $left++;        // Need bigger sum, move left up
        } else {
            $right--;       // Need smaller sum, move right down
        }
    }
    
    return [];  // No pair found
}
```

**Variations:**
- **Left + Right pointers**: Both start at ends, move toward each other (sorted arrays)
- **Fast + Slow pointers**: Both start at beginning, move at different speeds (cycle detection, linked lists)
- **Same direction**: Both start at beginning, maintain a window (sliding window)

**Follow-up:**
- How does it compare to brute force O(n²)? → Reduces to O(n) by eliminating pairs
- Can it be used with unsorted arrays? → Only if you sort first, or for specific problems like cycle detection

**Key Points:**
- Requires sorted array (usually)
- Reduces time complexity from O(n²) to O(n)
- Space complexity: O(1) - just two pointers
- The pointers always move in one direction, never backtrack

---

## Question 2: When should I use Sliding Window technique?

**Answer:**

Sliding Window is a technique for processing contiguous subsequences of an array or string. Instead of creating new subarrays for each position, you "slide" a window of fixed or variable size, updating only the elements that enter or leave the window.

**Why it works:**
When you need to find something common to all subarrays of size k, you can maintain a window and update it incrementally. This avoids the O(n×k) cost of recalculating everything for each position.

**When to Use:**
- Maximum/minimum sum of k consecutive elements
- Longest substring with k distinct characters
- Average of all subarrays of size k
- String containing all characters of pattern

**Example:**
Array `[1, 3, 2, 5, 4]`, k=3:
- Window [1,3,2] → sum=6
- Window [3,2,5] → update: remove 1, add 5 (O(1) instead of O(k))
- Window [2,5,4] → update: remove 3, add 4

**Implementation Pattern - Fixed Window:**

```php
function maxSumSubarray(array $nums, int $k): int {
    $n = count($nums);
    if ($n < $k) return 0;
    
    // Calculate sum of first window
    $windowSum = array_sum(array_slice($nums, 0, $k));
    $maxSum = $windowSum;
    
    // Slide the window
    for ($i = $k; $i < $n; $i++) {
        $windowSum = $windowSum - $nums[$i - $k] + $nums[$i];
        $maxSum = max($maxSum, $windowSum);
    }
    
    return $maxSum;
}
```

**Implementation Pattern - Deque for Max Window:**

```php
function slidingWindowMax(array $nums, int $k): array {
    $result = [];
    $deque = [];  // Store indices, maintain decreasing order
    
    for ($i = 0; $i < count($nums); $i++) {
        // Remove indices outside the window
        while (!empty($deque) && $deque[0] < $i - $k + 1) {
            array_shift($deque);
        }
        
        // Remove indices of smaller elements (they'll never be max)
        while (!empty($deque) && $nums[$deque[count($deque) - 1]] < $nums[$i]) {
            array_pop($deque);
        }
        
        $deque[] = $i;
        
        // Start adding results once window is full
        if ($i >= $k - 1) {
            $result[] = $nums[$deque[0]];
        }
    }
    
    return $result;
}
```

**Variations:**
- **Fixed window**: Same size throughout (sum of k consecutive)
- **Dynamic window**: Size changes based on condition (longest substring without repeat)
- **Deque optimization**: For max/min of all windows in O(n)

**Follow-up:**
- What's the difference between fixed and dynamic windows? → Fixed has constant size, dynamic expands/shrinks based on condition
- When is deque needed in sliding window? → When you need O(1) max/min retrieval from window

**Key Points:**
- Time complexity: O(n) - each element enters and leaves window once
- Space complexity: O(k) for window storage, O(1) for deque optimization
- Perfect for "contiguous" or "subarray" problems
- Often combined with hash maps for character tracking

---

## Question 3: How does Binary Search work and what problems is it best for?

**Answer:**

Binary Search repeatedly divides a sorted array in half, discarding the half that cannot contain the target. Each comparison halves the search space, giving O(log n) time complexity.

**Why it works:**
If the array is sorted and you check the middle element, you know which half the target must be in (if it's not the middle itself). By repeating this process, you find the target in logarithmic time.

**When to Use:**
- Search in sorted arrays
- Find insertion position (lower bound / upper bound)
- Find minimum/maximum in rotated sorted array
- Optimize mathematical functions (monotonic functions)
- Search in rotated arrays, matrix search

**Implementation Pattern - Classic Binary Search:**

```php
function binarySearch(array $nums, int $target): int {
    $left = 0;
    $right = count($nums) - 1;
    
    while ($left <= $right) {
        // Prevent overflow in other languages
        $mid = $left + intdiv($right - $left, 2);
        
        if ($nums[$mid] === $target) {
            return $mid;  // Found!
        } elseif ($nums[$mid] < $target) {
            $left = $mid + 1;   // Search right half
        } else {
            $right = $mid - 1;  // Search left half
        }
    }
    
    return -1;  // Not found
}
```

**Implementation - Lower Bound (First >= target):**

```php
function lowerBound(array $nums, int $target): int {
    $left = 0;
    $right = count($nums);
    
    while ($left < $right) {
        $mid = $left + intdiv($right - $left, 2);
        
        if ($nums[$mid] < $target) {
            $left = $mid + 1;
        } else {
            $right = $mid;
        }
    }
    
    return $left;  // First position where nums[i] >= target
}
```

**Implementation - Search in Rotated Array:**

```php
function searchRotated(array $nums, int $target): int {
    $left = 0;
    $right = count($nums) - 1;
    
    while ($left <= $right) {
        $mid = $left + intdiv($right - $left, 2);
        
        if ($nums[$mid] === $target) {
            return $mid;
        }
        
        // Determine which half is sorted
        if ($nums[$left] <= $nums[$mid]) {
            // Left half is sorted
            if ($target >= $nums[$left] && $target < $nums[$mid]) {
                $right = $mid - 1;
            } else {
                $left = $mid + 1;
            }
        } else {
            // Right half is sorted
            if ($target > $nums[$mid] && $target <= $nums[$right]) {
                $left = $mid + 1;
            } else {
                $right = $mid - 1;
            }
        }
    }
    
    return -1;
}
```

**Follow-up:**
- How do you handle duplicates for first/last occurrence? → Use modified binary search (lower/upper bound)
- What makes rotated array search tricky? → Need to determine which half is sorted each iteration

**Key Points:**
- Requires sorted array (or monotonic condition)
- Time complexity: O(log n)
- Space complexity: O(1)
- Critical: Use `left + (right - left) / 2` to prevent overflow
- Many variants: lower bound, upper bound, search in rotated array, peak finding

---

## Question 4: When should I use BFS vs DFS?

**Answer:**

Both BFS and DFS are graph/tree traversal algorithms. The choice depends on what you're looking for:
- **BFS**: Best for shortest path, level-order operations
- **DFS**: Best for exploring all paths, backtracking, deep traversal

**BFS (Breadth-First Search):**
- Uses a queue
- Explores level by level
- Finds shortest path in unweighted graphs
- More memory (stores entire level)

**DFS (Depth-First Search):**
- Uses recursion or stack
- Goes deep before exploring siblings
- Good for path finding, backtracking
- Less memory (only stores one path)

**When to Use BFS:**
- Shortest path in unweighted graph (minimum number of steps)
- Level-order tree traversal
- Finding shortest transformation (word ladder)
- Graph connectivity components

**When to Use DFS:**
- Tree/graph traversal
- Finding any path to target
- Backtracking problems
- Topological sorting
- Detecting cycles

**BFS Implementation - Level Order Traversal:**

```php
function levelOrder(TreeNode $root): array {
    if (!$root) return [];
    
    $result = [];
    $queue = new SplQueue();
    $queue->enqueue($root);
    
    while (!$queue->isEmpty()) {
        $levelSize = $queue->count();
        $currentLevel = [];
        
        for ($i = 0; $i < $levelSize; $i++) {
            $node = $queue->dequeue();
            $currentLevel[] = $node->val;
            
            if ($node->left) $queue->enqueue($node->left);
            if ($node->right) $queue->enqueue($node->right);
        }
        
        $result[] = $currentLevel;
    }
    
    return $result;
}
```

**DFS Implementation - Path Sum:**

```php
function hasPathSum(TreeNode $root, int $target): bool {
    if (!$root) return false;
    
    if (!$root->left && !$root->right) {
        return $root->val === $target;
    }
    
    return hasPathSum($root->left, $target - $root->val) 
        || hasPathSum($root->right, $target - $root->val);
}
```

**Follow-up:**
- When is BFS better than DFS? → When you need shortest path or level-by-level processing
- Which uses more memory? → BFS typically uses more (queue stores more nodes)

**Key Points:**
- Both visit all nodes: O(V + E) time complexity
- BFS: O(width) memory, good for shortest path
- DFS: O(depth) memory, good for backtracking
- BFS uses queue, DFS uses recursion/stack

---

## Question 5: How do I approach Dynamic Programming problems?

**Answer:**

Dynamic Programming (DP) solves problems by:
1. Breaking them into overlapping subproblems
2. Storing results to avoid recomputation
3. Building solution from smaller subproblems

DP is essentially "smart recursion" - instead of recalculating the same subproblems multiple times, store and reuse the results.

**Two Approaches:**
1. **Top-down (Memoization)**: Recursive with caching
2. **Bottom-up (Tabulation)**: Iterative, build from base cases

**When to Use:**
- Optimal substructure: solution can be built from optimal solutions of subproblems
- Overlapping subproblems: same subproblems solved multiple times
- Examples: Fibonacci, knapsack, LIS, edit distance, grid problems

**How to Identify DP:**
- Ask: "What's the minimum/maximum/best way to..."
- Count: "How many ways to..."
- The problem has optimal substructure

**Fibonacci Example:**

```php
// Naive recursive: O(2^n) - exponential!
function fibRecursive(int $n): int {
    if ($n <= 1) return $n;
    return fibRecursive($n - 1) + fibRecursive($n - 2);
}

// Top-down with memoization: O(n)
function fibMemo(int $n, array &$memo = []): int {
    if (isset($memo[$n])) return $memo[$n];
    if ($n <= 1) return $n;
    
    $memo[$n] = fibMemo($n - 1, $memo) + fibMemo($n - 2, $memo);
    return $memo[$n];
}

// Bottom-up tabulation: O(n)
function fibTab(int $n): int {
    if ($n <= 1) return $n;
    
    $dp = array_fill(0, $n + 1, 0);
    $dp[1] = 1;
    
    for ($i = 2; $i <= $n; $i++) {
        $dp[$i] = $dp[$i - 1] + $dp[$i - 2];
    }
    
    return $dp[$n];
}

// Space optimized: O(n) time, O(1) space
function fibOptimized(int $n): int {
    if ($n <= 1) return $n;
    
    $prev2 = 0;
    $prev1 = 1;
    
    for ($i = 2; $i <= $n; $i++) {
        $current = $prev1 + $prev2;
        $prev2 = $prev1;
        $prev1 = $current;
    }
    
    return $prev1;
}
```

**Common DP Patterns:**

```php
// 1D DP - Climbing Stairs
function climbStairs(int $n): int {
    if ($n <= 2) return $n;
    
    $dp = array_fill(0, $n + 1, 0);
    $dp[1] = 1;
    $dp[2] = 2;
    
    for ($i = 3; $i <= $n; $i++) {
        $dp[$i] = $dp[$i - 1] + $dp[$i - 2];
    }
    
    return $dp[$n];
}

// 2D DP - Unique Paths in Grid
function uniquePaths(int $m, int $n): int {
    $dp = array_fill(0, $m, array_fill(0, $n, 0));
    
    for ($i = 0; $i < $m; $i++) {
        for ($j = 0; $j < $n; $j++) {
            if ($i === 0 || $j === 0) {
                $dp[$i][$j] = 1;
            } else {
                $dp[$i][j] = $dp[$i - 1][$j] + $dp[$i][$j - 1];
            }
        }
    }
    
    return $dp[$m - 1][$n - 1];
}
```

**Follow-up:**
- Top-down vs bottom-up: which to choose? → Top-down easier to implement; bottom-up more efficient
- When can space be optimized? → When transition only depends on previous few states

**Key Points:**
- Identify state (what parameter changes?) and transition (how to compute next state)
- Time complexity: often O(n) or O(n²)
- Space optimization often possible - only keep previous states
- Start by writing recurrence relation, then implement

---

## Question 6: What problems is Hash Map best suited for?

**Answer:**

Hash Map (or Hash Table) provides O(1) average time complexity for:
- Insert
- Lookup
- Delete

This is essential when you need fast lookups or want to trade space for time.

**When to Use:**
- Find duplicates or unique elements
- Count frequencies
- Two sum / complement problems (find if complement exists)
- Anagram grouping
- Cache implementation

**Why O(1)?**
Hash maps use a hash function to compute an index from a key. This maps directly to array position, avoiding search through the entire data structure.

**Implementation Pattern - Two Sum:**

```php
function twoSum(array $nums, int $target): array {
    $map = [];  // value => index
    
    foreach ($nums as $index => $num) {
        $complement = $target - $num;
        
        if (isset($map[$complement])) {
            return [$map[$complement], $index];
        }
        
        $map[$num] = $index;
    }
    
    return [];
}
```

**Implementation - Group Anagrams:**

```php
function groupAnagrams(array $strings): array {
    $map = [];
    
    foreach ($strings as $string) {
        // Sort characters to create canonical form
        $sorted = str_split($string);
        sort($sorted);
        $key = implode('', $sorted);
        
        if (!isset($map[$key])) {
            $map[$key] = [];
        }
        $map[$key][] = $string;
    }
    
    return array_values($map);
}
```

**Implementation - Longest Consecutive Sequence:**

```php
function longestConsecutive(array $nums): int {
    if (empty($nums)) return 0;
    
    $set = array_flip($nums);
    $maxLen = 0;
    
    foreach ($nums as $num) {
        // Only start counting from beginning of sequence
        if (!isset($set[$num - 1])) {
            $current = $num;
            $len = 1;
            
            while (isset($set[$current + 1])) {
                $current++;
                $len++;
            }
            
            $maxLen = max($maxLen, $len);
        }
    }
    
    return $maxLen;
}
```

**Follow-up:**
- What about string anagrams? → Sort characters to create canonical key
- When is Hash Set better? → When you only need to check existence, not map to values

**Key Points:**
- Average O(1), worst O(n) for collisions
- Use `isset()` for fast lookups in PHP
- Space trade-off: O(n) space for O(1) time
- Perfect for complement problems: "find number that complements to X"

---

## Question 7: When should I use Stack data structure?

**Answer:**

Stack follows LIFO (Last In First Out) - think of a stack of plates. The last element added is the first one removed.

**When to Use:**
- Parentheses matching (valid string, nested expressions)
- Monotonic stacks (next greater/smaller element)
- Expression evaluation (infix to postfix)
- Backtracking with state
- Function call management (recursion uses call stack)

**Why it works for matching:**
When processing `"({[]})"`:
1. Push opening brackets: `(`, `{`, `[`
2. When closing `]` seen, pop and check if matches `[`
3. Continue - stack empty means valid

**Implementation Pattern - Valid Parentheses:**

```php
function isValid(string $s): bool {
    $stack = new SplStack();
    $map = [')' => '(', ']' => '[', '}' => '{'];
    
    for ($i = 0; $i < strlen($s); $i++) {
        $char = $s[$i];
        
        if (isset($map[$char])) {
            // Closing bracket - check if matches top of stack
            if ($stack->isEmpty() || $stack->pop() !== $map[$char]) {
                return false;
            }
        } else {
            // Opening bracket - push to stack
            $stack->push($char);
        }
    }
    
    return $stack->isEmpty();
}
```

**Monotonic Stack - Next Greater Element:**

```php
function nextGreaterElements(array $nums): array {
    $n = count($nums);
    $result = array_fill(0, $n, -1);
    $stack = [];  // Store indices
    
    // Process twice for circular array
    for ($i = 0; $i < $n * 2; $i++) {
        $current = $nums[$i % $n];
        
        // Pop smaller elements and set their next greater
        while (!empty($stack) && $nums[$stack[count($stack) - 1]] < $current) {
            $idx = array_pop($stack);
            $result[$idx] = $current;
        }
        
        // Only push indices from first pass
        if ($i < $n) {
            $stack[] = $i;
        }
    }
    
    return $result;
}
```

**Follow-up:**
- What's monotonic stack used for? → Next greater/smaller, largest rectangle in histogram
- When is queue better than stack? → When you need FIFO (first in first out), like BFS

**Key Points:**
- Time complexity: O(1) for push/pop
- Perfect for "matching pairs" problems
- Monotonic stack finds next greater/smaller in O(n)
- Use `SplStack` in PHP or simple array with `array_pop()`/`array_push()`

---

## Question 8: How do I approach Backtracking problems?

**Answer:**

Backtracking is a systematic way to explore all possible solutions by trying partial solutions and abandoning those that don't work. It's like exploring a maze - try a path, if it dead-ends, backtrack and try another.

**The Pattern:**
1. **Choose**: Make a decision (add element to current solution)
2. **Explore**: Recursively try to complete the solution
3. **Unchoose**: Remove the decision (backtrack) to try other options

**When to Use:**
- Generate all permutations/combinations
- Solve puzzles (N-Queens, Sudoku)
- Find all possible solutions
- Subset generation
- Path finding in matrix

**Implementation Pattern - Generate Permutations:**

```php
function permute(array $nums): array {
    $result = [];
    
    $backtrack = function (array &$result, array &$current, array $nums) use (&$backtrack) {
        // Base case: solution complete
        if (count($current) === count($nums)) {
            $result[] = $current;
            return;
        }
        
        // Try each remaining element
        foreach ($nums as $num) {
            if (!in_array($num, $current)) {
                // Choose
                $current[] = $num;
                // Explore
                $backtrack($result, $current, $nums);
                // Unchoose (backtrack)
                array_pop($current);
            }
        }
    };
    
    $current = [];
    $backtrack($result, $current, $nums);
    
    return $result;
}
```

**Implementation - Subsets:**

```php
function subsets(array $nums): array {
    $result = [];
    
    $backtrack = function (array &$result, array &$current, int $start, array $nums) use (&$backtrack) {
        $result[] = $current;
        
        for ($i = $start; $i < count($nums); $i++) {
            $current[] = $nums[$i];
            $backtrack($result, $current, $i + 1, $nums);
            array_pop($current);
        }
    };
    
    $current = [];
    $backtrack($result, $current, 0, $nums);
    
    return $result;
}
```

**Pruning Example - N-Queens:**

```php
function solveNQueens(int $n): array {
    $solutions = [];
    $queens = array_fill(0, $n, -1);
    
    $isValid = function (int $row) use ($queens, $n): bool {
        for ($i = 0; $i < $row; $i++) {
            // Same column or diagonal
            if ($queens[$i] === $queens[$row] 
                || abs($queens[$i] - $queens[$row]) === $row - $i) {
                return false;
            }
        }
        return true;
    };
    
    $placeQueens = function (int $row) use ($queens, $n, &$solutions, &$isValid, &$placeQueens) {
        if ($row === $n) {
            $solutions[] = $queens;
            return;
        }
        
        for ($col = 0; $col < $n; $col++) {
            $queens[$row] = $col;
            if ($isValid($row)) {
                $placeQueens($row + 1);
            }
            $queens[$row] = -1;
        }
    };
    
    $placeQueens(0);
    return $solutions;
}
```

**Follow-up:**
- What is pruning and when to use it? → Skip branches that can't lead to valid solution (like N-Queens diagonal check)
- How do you handle duplicates? → Sort input and skip duplicates during recursion

**Key Points:**
- Exponential time complexity in worst case
- Always has a base case (solution complete)
- Three steps: Choose → Explore → Unchoose
- Pruning can dramatically reduce search space
- Use used array/set to avoid duplicates

---

## Question 9: When is Greedy algorithm the right approach?

**Answer:**

Greedy algorithms make locally optimal choices at each step, hoping these choices lead to a globally optimal solution. Unlike DP which considers all possibilities, greedy picks what looks best now.

**When to Use:**
- When local optimum equals global optimum
- When problem has "greedy choice property"
- Interval scheduling problems
- Huffman coding
- Fractional knapsack
- Minimum spanning tree

**How to Prove Greedy Works:**
1. Prove greedy choice property: making local optimal choice leads to optimal solution
2. Use exchange argument: any optimal solution can be transformed to greedy solution without losing optimality

**Interval Scheduling Example:**

```php
function eraseOverlapIntervals(array $intervals): int {
    if (empty($intervals)) return 0;
    
    // Sort by end time
    usort($intervals, fn($a, $b) => $a[1] <=> $b[1]);
    
    $count = 0;
    $end = PHP_INT_MIN;
    
    foreach ($intervals as $interval) {
        if ($interval[0] >= $end) {
            $end = $interval[1];
        } else {
            $count++;
        }
    }
    
    return $count;
}
```

**Jump Game Example:**

```php
function jump(array $nums): int {
    $n = count($nums);
    if ($n <= 1) return 0;
    
    $jumps = 0;
    $currentEnd = 0;  // End of current reachable range
    $farthest = 0;    // Farthest we can reach
    
    for ($i = 0; $i < $n - 1; $i++) {
        // Update farthest we can reach
        $farthest = max($farthest, $i + $nums[$i]);
        
        // Must make a jump when at the edge
        if ($i === $currentEnd) {
            $jumps++;
            $currentEnd = $farthest;
        }
    }
    
    return $jumps;
}
```

**When Greedy Fails:**
Greedy doesn't work when local optimum doesn't lead to global optimum. Example: coin change (greedy takes largest first, but may not minimize coins).

```php
// Greedy fails for coins [1, 3, 4] and target 6
// Greedy: 4 + 1 + 1 = 3 coins
// Optimal: 3 + 3 = 2 coins
function coinChangeGreedy(array $coins, int $amount): int {
    sort($coins, SORT_DESC);
    $count = 0;
    
    foreach ($coins as $coin) {
        $count += intdiv($amount, $coin);
        $amount %= $coin;
    }
    
    return $amount === 0 ? $count : -1;
}
```

**Follow-up:**
- How do I prove greedy works? → Use exchange argument or induction
- When does greedy fail? → When optimal substructure doesn't exist

**Key Points:**
- Simpler than DP
- Time complexity often O(n log n) due to sorting
- Need to prove correctness - try greedy first, verify with DP
- Works for: interval scheduling, Huffman coding, some knapsack variants

---

## Question 10: What are common Bit Manipulation tricks?

**Answer:**

Bit manipulation uses binary operations for elegant, O(1) solutions to specific problems. These operations work directly on bits, making them extremely efficient.

**Common Operations:**
- `&` (AND): 1 if both 1
- `|` (OR): 1 if either 1
- `^` (XOR): 1 if different
- `>>` (Right shift): Divide by 2
- `<<` (Left shift): Multiply by 2
- `~` (NOT): Flip all bits

**When to Use:**
- Power of 2 checks
- Single number (with pairs)
- Bit counting
- Swapping without temp
- Mask operations

**Check Power of 2:**

```php
function isPowerOfTwo(int $n): bool {
    // Power of 2 has only one bit set
    // n-1 flips all bits below the single bit
    return $n > 0 && ($n & ($n - 1)) === 0;
}
```

**Find Single Number (all pairs):**

```php
function singleNumber(array $nums): int {
    // XOR properties:
    // a ^ a = 0
    // a ^ 0 = a
    // XOR is commutative and associative
    $result = 0;
    foreach ($nums as $num) {
        $result ^= $num;
    }
    return $result;
}
```

**Count Bits (DP):**

```php
function countBits(int $n): array {
    $result = array_fill(0, $n + 1, 0);
    
    for ($i = 1; $i <= $n; $i++) {
        // DP: bits(i) = bits(i/2) + (i % 2)
        $result[$i] = $result[$i >> 1] + ($i & 1);
    }
    
    return $result;
}
```

**Swap Two Numbers:**

```php
function swap(int &$a, int &$b): void {
    $a = $a ^ $b;
    $b = $a ^ $b;  // b = (a^b) ^ b = a
    $a = $a ^ $b;  // a = (a^b) ^ a = b
}
```

**Brian Kernighan's Algorithm (Count Set Bits):**

```php
function countSetBits(int $n): int {
    $count = 0;
    while ($n > 0) {
        $n = $n & ($n - 1);  // Clear lowest set bit
        $count++;
    }
    return $count;
}
```

**Follow-up:**
- When to use bit manipulation? → Performance-critical code, power of 2, single number problems
- What's Brian Kernighan's algorithm? → Clears lowest set bit each iteration, O(k) where k is number of set bits

**Key Points:**
- O(1) operations
- `$n & ($n - 1)`: Clears lowest set bit
- `^`: Cancels pairs, finds unique element
- `>> 1`: Divide by 2, `<< 1`: Multiply by 2
- Works with integers (32-bit or 64-bit)

---

## Question 11: How do I solve Linked List problems?

**Answer:**

Linked lists store data in nodes with pointers to next (singly) or both next and previous (doubly). Unlike arrays, they don't provide random access but excel at insertions and deletions.

**When to Use:**
- Frequent insertions/deletions at beginning or end
- Unknown size at compile time
- Implementing stacks/queues
- Polynomial arithmetic

**Node Structure:**

```php
class ListNode {
    public $val;
    public $next;
    
    public function __construct(int $val = 0) {
        $this->val = $val;
        $this->next = null;
    }
}
```

**Reverse Linked List:**

```php
function reverseList(ListNode $head): ListNode {
    $prev = null;
    $current = $head;
    
    while ($current) {
        $next = $current->next;  // Save next
        $current->next = $prev;   // Reverse pointer
        $prev = $current;         // Move prev forward
        $current = $next;         // Move current forward
    }
    
    return $prev;  // prev is now the new head
}
```

**Find Middle (Fast/Slow Pointers):**

```php
function middleNode(ListNode $head): ListNode {
    $slow = $head;
    $fast = $head;
    
    while ($fast && $fast->next) {
        $slow = $slow->next;
        $fast = $fast->next->next;
    }
    
    return $slow;  // Middle node
}
```

**Detect Cycle (Floyd's Algorithm):**

```php
function hasCycle(ListNode $head): bool {
    if (!$head || !$head->next) return false;
    
    $slow = $head;
    $fast = $head;
    
    while ($fast && $fast->next) {
        $slow = $slow->next;
        $fast = $fast->next->next;
        
        if ($slow === $fast) {
            return true;  // Cycle detected
        }
    }
    
    return false;
}
```

**Merge Two Sorted Lists:**

```php
function mergeTwoLists(ListNode $l1, ListNode $l2): ListNode {
    $dummy = new ListNode(0);
    $current = $dummy;
    
    while ($l1 && $l2) {
        if ($l1->val <= $l2->val) {
            $current->next = $l1;
            $l1 = $l1->next;
        } else {
            $current->next = $l2;
            $l2 = $l2->next;
        }
        $current = $current->next;
    }
    
    $current->next = $l1 ?: $l2;
    return $dummy->next;
}
```

**Key Patterns:**
- **Dummy node**: Avoids special case for head manipulation
- **Fast/slow pointers**: Find middle, detect cycle
- **Three pointers**: Reverse list in place

**Follow-up:**
- How do you detect a cycle? → Floyd's algorithm (tortoise and hare)
- When is linked list better than array? → Frequent insertions at beginning, unknown size

**Key Points:**
- O(n) insertion/deletion at beginning
- No random access - must traverse
- Uses more memory than arrays (pointer overhead)
- Common operations: reverse, merge, detect cycle, find middle

---

## Question 12: What tree traversal should I use?

**Answer:**

Tree traversals visit each node in a specific order. The choice depends on what information you need:

- **Inorder**: BST → sorted order
- **Preorder**: Copy tree, prefix expression
- **Postorder**: Delete tree, postfix expression
- **Level-order**: Level by level (BFS)

**When to Use:**
- **Inorder**: Get elements in sorted order (BST)
- **Preorder**: Serialize/deserialize tree, prefix notation
- **Postorder**: Delete tree (children before parent)
- **Level-order**: Find minimum depth, level visualization

**Inorder Traversal (Iterative):**

```php
function inorderTraversal(TreeNode $root): array {
    $result = [];
    $stack = [];
    $current = $root;
    
    while ($current || !empty($stack)) {
        // Go to leftmost node
        while ($current) {
            $stack[] = $current;
            $current = $current->left;
        }
        
        // Process current node
        $current = array_pop($stack);
        $result[] = $current->val;
        
        // Move to right subtree
        $current = $current->right;
    }
    
    return $result;
}
```

**Preorder Traversal:**

```php
function preorderTraversal(TreeNode $root): array {
    if (!$root) return [];
    
    $result = [];
    $stack = [$root];
    
    while (!empty($stack)) {
        $node = array_pop($stack);
        $result[] = $node->val;
        
        // Push right first so left is processed first
        if ($node->right) $stack[] = $node->right;
        if ($node->left) $stack[] = $node->left;
    }
    
    return $result;
}
```

**Level Order (BFS):**

```php
function levelOrder(TreeNode $root): array {
    if (!$root) return [];
    
    $result = [];
    $queue = new SplQueue();
    $queue->enqueue($root);
    
    while (!$queue->isEmpty()) {
        $levelSize = $queue->count();
        $level = [];
        
        for ($i = 0; $i < $levelSize; $i++) {
            $node = $queue->dequeue();
            $level[] = $node->val;
            
            if ($node->left) $queue->enqueue($node->left);
            if ($node->right) $queue->enqueue($node->right);
        }
        
        $result[] = $level;
    }
    
    return $result;
}
```

**Node Structure:**

```php
class TreeNode {
    public $val;
    public $left;
    public $right;
    
    public function __construct(int $val = 0) {
        $this->val = $val;
        $this->left = null;
        $this->right = null;
    }
}
```

**Follow-up:**
- When to use recursive vs iterative? → Recursive simpler but uses call stack; iterative avoids stack overflow
- How to do level order? → Use queue (BFS)

**Key Points:**
- **Inorder**: Left → Root → Right (sorted for BST)
- **Preorder**: Root → Left → Right (copy tree)
- **Postorder**: Left → Right → Root (delete tree)
- **Level-order**: BFS with queue
- All traversals: O(n) time, O(h) space where h = height

---

## Question 13: What is the general problem-solving framework?

**Answer:**

A systematic approach to solve any coding problem, from easy to hard.

### Step 1: Understand the Problem

Ask these questions:
- What are the inputs? (types, ranges, constraints)
- What are the outputs? (type, format, range)
- Are there edge cases? (empty input, single element, extremes)
- What's the goal? (find something, count something, optimize)

**Example Analysis:**
```
Input: [2,7,11,15], target = 9
Output: [0,1] (indices of 2 and 7)
Constraints: exactly one solution, each input used once
Edge cases: empty array, single element, negative numbers?
```

### Step 2: Choose the Right Approach

| Problem Pattern | Recommended Approach |
|-----------------|---------------------|
| Sorted array + Search | Binary search, Two pointers |
| Shortest path (unweighted) | BFS |
| All paths / combinations | DFS + Backtracking |
| Optimization / maximize | DP, Greedy |
| Fast lookups | Hash map |
| Contiguous subarray | Sliding window |

### Step 3: Algorithm Selection

1. **Brute force** → Always start here (O(n²) or O(n³))
2. **Optimize** → Can we do better?
   - Sorting helps? → Two pointers, binary search
   - Hash map? → O(1) lookups
   - Divide & conquer? → Recursion
3. **Time/Space tradeoff** → Memoization / caching

**Optimization Checklist:**
- [ ] Can we sort first? (often O(n log n))
- [ ] Can we use hash map? (O(1) lookups)
- [ ] Is there a two-pointer solution?
- [ ] Can we use sliding window?
- [ ] Is this a DP problem?

### Step 4: Code Implementation

```php
function solve(array $input): mixed {
    // 1. Handle edge cases
    if (empty($input)) return [];
    
    // 2. Choose algorithm
    // 3. Implement
    // 4. Return result
}
```

### Step 5: Test with Cases

**Basic Cases:**
```php
solve([1,2,3], 5);  // Basic test
solve([], 5);       // Empty
solve([5], 5);      // Single element
solve([1,1,1], 2);  // Duplicates
solve([1,2,3], 100); // No solution
```

**Complex Cases:**
- Large input (performance)
- All same elements
- Already sorted / reverse sorted
- Negative numbers

### Common Pitfalls

1. **Off-by-one errors**: Be careful with indices
2. **Mutation**: Don't modify input unless allowed
3. **Type coercion**: Watch for `==` vs `===` in PHP
4. **Memory**: Large arrays consume memory

**Follow-up:**
- How to handle timeouts? → Look for O(n²) → O(n) optimizations
- When to use brute force first? → Always start simple, optimize after

**Key Points:**
- Start with brute force, then optimize
- Know your time and space complexity
- Practice pattern recognition
- Test edge cases

---

## Bonus: PHP-Specific Tips

### Performance Considerations
- Use built-in functions when possible (`array_*`, `str_*`)
- Avoid recursive solutions if depth is large (PHP has limited stack)
- Use `SplQueue`, `SplStack` for stack/queue operations
- Consider `SplMinHeap` for priority queue

### Common Pitfalls
- **Reference vs value**: Arrays in PHP are copied by value
- **Type coercion**: PHP loosely typed, watch for `==` vs `===`
- **Memory**: Large arrays consume memory

### Useful Built-ins
- `array_fill()`, `array_merge()`, `array_map()`, `array_filter()`
- `usort()`, `uasort()` with custom comparator
- `array_count_values()`, `array_flip()`
- `range()` for generating sequences

---

## Recommended Practice Order

1. **Easy (20-30 problems)**: Two pointers, Hash map, Binary search basics
2. **Medium (40-50 problems)**: BFS/DFS, DP basics, Sliding window
3. **Hard (20-30 problems)**: Advanced DP, Graph algorithms, Advanced trees

### Focus Areas by Priority
1. Arrays & Strings → Always appear
2. Trees & Graphs → Common in interviews
3. DP → Often in hard problems
4. Backtracking → For generating problems
