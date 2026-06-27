# Panglong ERP Development Prompt - Iteration 5

**Date:** 2026-06-27
**Priority:** HIGH
**Focus:** Query Caching, Pagination, and Code Quality Improvements
**Status:** Iteration 4 Complete - Input Validation Extended

---

## Iteration 4 Summary

### Completed Tasks
- ✅ **Input Validation - Quotations**: Added validation for items, quantities, prices, dates, valid_until
- ✅ **Input Validation - Stock Transfers**: Added validation for from/to warehouse, items, quantities, transfer_date
- ✅ **Input Validation - Fixed Assets**: Added validation for name, acquisition_cost, salvage_value, useful_life_months, depreciation_method
- ✅ **Testing**: All 48 Playwright tests passing

### Files Modified
- `frontend/ajax.php` - Added input validation to quotations, stock-transfers, fixed-assets POST endpoints

---

## Iteration 5 Summary

### Completed Tasks
- ✅ **Query Caching Functions**: Added getCache, setCache, clearCache functions to db.php
- ✅ **Products Caching**: Applied 5-minute TTL caching to products GET endpoint (non-search queries)
- ✅ **Products Cache Invalidation**: Added cache clearing on products POST/PUT/DELETE operations
- ✅ **Customers Caching**: Applied 5-minute TTL caching to customers GET endpoint (non-search queries)
- ✅ **Customers Cache Invalidation**: Added cache clearing on customers POST/PUT operations
- ✅ **Suppliers Caching**: Applied 5-minute TTL caching to suppliers GET endpoint (non-search queries)
- ✅ **Suppliers Cache Invalidation**: Added cache clearing on suppliers POST/DELETE operations
- ✅ **Testing**: All 11 relevant Playwright tests passing

### Files Modified
- `frontend/db.php` - Added caching functions (getCache, setCache, clearCache)
- `frontend/ajax.php` - Applied caching to products, customers, suppliers endpoints with invalidation

### Lessons Learned
- Caching should only apply to non-search queries to avoid stale data
- Cache keys should include tenant_id for multi-tenant isolation
- Cache invalidation must happen on all write operations
- Session-based caching is simple and effective for single-server setups

### Remaining Tasks from Iteration 5
- Apply caching to remaining endpoints (warehouses, categories, payment-methods)
- Add pagination to all list endpoints
- Code quality improvements

---

## Development Tasks

### Phase 1: Query Result Caching (HIGH)

#### Task 1.1: Implement Simple In-Memory Caching
**Priority:** HIGH
**Files to check:** db.php, ajax.php

**Actions:**
1. Add caching functions to db.php:
   ```php
   function getCache($key, $ttl = 300) {
       if (!isset($_SESSION['_cache'])) $_SESSION['_cache'] = [];
       if (isset($_SESSION['_cache'][$key])) {
           $item = $_SESSION['_cache'][$key];
           if (time() - $item['time'] < $ttl) {
               return $item['data'];
           }
       }
       return null;
   }
   
   function setCache($key, $data) {
       if (!isset($_SESSION['_cache'])) $_SESSION['_cache'] = [];
       $_SESSION['_cache'][$key] = ['data' => $data, 'time' => time()];
   }
   
   function clearCache($pattern = null) {
       if (!isset($_SESSION['_cache'])) return;
       if ($pattern) {
           foreach (array_keys($_SESSION['_cache']) as $key) {
               if (strpos($key, $pattern) !== false) {
                   unset($_SESSION['_cache'][$key]);
               }
           }
       } else {
           $_SESSION['_cache'] = [];
       }
   }
   ```
2. Cache frequently accessed data in ajax.php:
   - Product lists (5 min TTL) - endpoint: products GET
   - Customer lists (5 min TTL) - endpoint: customers GET
   - Supplier lists (5 min TTL) - endpoint: suppliers GET
   - Warehouse lists (10 min TTL) - endpoint: warehouses GET
   - Categories (30 min TTL) - endpoint: categories GET
   - Payment methods (30 min TTL) - endpoint: payment-methods GET
3. Invalidate cache on write operations:
   - Clear product cache when product is created/updated/deleted
   - Clear customer cache when customer is created/updated/deleted
   - Clear supplier cache when supplier is created/updated/deleted
   - Clear warehouse cache when warehouse is created/updated/deleted
4. Add cache statistics logging (optional)

**Expected outcome:** Reduced database load, faster page loads for reference data

---

### Phase 2: Pagination for List Endpoints (HIGH)

#### Task 2.1: Add Pagination to All List Endpoints
**Priority:** HIGH
**Files to check:** ajax.php

**Actions:**
1. Review and add pagination to list endpoints that don't have it:
   - customers (currently has LIMIT 100, convert to proper pagination)
   - suppliers (currently has LIMIT 100, convert to proper pagination)
   - warehouses (currently no pagination, add it)
   - purchase-orders (currently has LIMIT 100, convert to proper pagination)
   - deliveries (currently has LIMIT 100, convert to proper pagination)
   - stock-adjustments (currently has LIMIT 100, convert to proper pagination)
   - quotations (currently has LIMIT 100, convert to proper pagination)
   - stock-transfers (currently has LIMIT 100, convert to proper pagination)
   - fixed-assets (currently has LIMIT 100, convert to proper pagination)
   - sales-returns (check and add if needed)
   - categories (check and add if needed)
   - brands (check and add if needed)
2. Use standard pagination pattern:
   ```php
   $per_page = (int)($_GET['per_page'] ?? 50);
   $page = (int)($_GET['page'] ?? 1);
   $offset = ($page - 1) * $per_page;
   $per_page = min(max($per_page, 1), 100); // Clamp between 1-100
   ```
3. Return total count for pagination controls:
   ```php
   $countSql = "SELECT COUNT(*) FROM table_name WHERE ...";
   $total = $d->query($countSql)->fetchColumn();
   ok($data, ['total' => (int)$total, 'per_page' => $per_page, 'current_page' => $page, 'last_page' => (int)ceil($total / $per_page)]);
   ```
4. Update frontend to use pagination controls (if needed)

**Expected outcome:** Consistent pagination across all list endpoints, reduced data transfer

---

### Phase 3: Code Quality Improvements (MEDIUM)

#### Task 3.1: Remove Commented-Out Code
**Priority:** MEDIUM
**Files to check:** All PHP files

**Actions:**
1. Search for commented-out code patterns (//, /* */)
2. Remove unused commented code
3. Keep only meaningful comments
4. Ensure code is clean and readable

**Expected outcome:** Cleaner codebase

---

#### Task 3.2: Standardize Error Messages
**Priority:** LOW
**Files to check:** ajax.php

**Actions:**
1. Review all error messages in fail() calls
2. Ensure consistent format (capitalize first letter, end with period)
3. Make messages user-friendly
4. Add context where helpful

**Expected outcome:** Better user experience

---

#### Task 3.3: Add Function Documentation
**Priority:** LOW
**Files to check:** auth.php, db.php, config.php

**Actions:**
1. Add PHPDoc comments to all functions
2. Document parameters and return values
3. Add usage examples where helpful

**Expected outcome:** Better code documentation

---

#### Task 3.4: Remove Unused Variables
**Priority:** LOW
**Files to check:** All PHP files

**Actions:**
1. Search for unused variables
2. Remove or use them
3. Clean up code

**Expected outcome:** Cleaner code

---

## Success Criteria

### Performance
- [ ] Query caching implemented for reference data
- [ ] Cache invalidation working correctly
- [ ] All list endpoints have pagination
- [ ] Response payloads optimized

### Code Quality
- [ ] No commented-out code
- [ ] Consistent error messages
- [ ] Functions documented with PHPDoc
- [ ] No unused variables

### Testing
- [ ] All existing tests passing
- [ ] Manual testing of pagination
- [ ] Manual testing of cache invalidation
- [ ] Performance benchmarks improved

---

## Implementation Order

1. **Phase 1 (HIGH):** Query result caching
2. **Phase 2 (HIGH):** Pagination for list endpoints
3. **Phase 3 (MEDIUM/LOW):** Code quality improvements

---

## Testing Strategy

### Before Each Change
1. Run existing Playwright tests
2. Manual test affected features
3. Measure baseline performance

### After Each Change
1. Run Playwright tests again
2. Manual regression testing
3. Test pagination
4. Test cache invalidation
5. Measure performance improvement

### Final Testing
1. Full test suite run
2. Performance benchmarking
3. Load testing
4. Security audit

---

## Notes

- Always maintain backward compatibility
- Test with different user roles
- Keep database schema stable
- Use existing patterns and conventions
- Prioritize stability over new features
- Document all changes
- Measure performance before and after
- The goal is perfection, not just completion
- Each task should be completed and tested before moving to the next

---

## Next Steps

After completing this iteration:
1. Document all changes made
2. Update this prompt based on lessons learned
3. Create iteration-6 prompt focusing on remaining issues
4. Continue cycle until application is perfect
