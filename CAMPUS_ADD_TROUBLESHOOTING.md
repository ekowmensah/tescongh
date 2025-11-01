# Campus Add Issue - Troubleshooting Guide

## Problem
When adding a new campus, the old campus appears instead of the new one.

## Fixes Applied

### 1. Database Query (Campus.php)
- ✅ Removed unnecessary `GROUP BY c.id`
- ✅ Changed ORDER BY to `DESC` (newest first)
- ✅ Added try-catch error handling
- ✅ Added detailed error logging

### 2. Cache Prevention
- ✅ Added cache control headers to `redirect()` function
- ✅ Added cache control headers to `campuses.php`
- ✅ DataTables `stateSave: false` to prevent caching

### 3. Parameter Binding
- ✅ Fixed Campus::create() to explicitly bind all parameters
- ✅ Added error handling for PDO exceptions

### 4. Debug Logging
- ✅ Logs data being inserted
- ✅ Logs insert result
- ✅ Logs new campus ID
- ✅ Logs campuses retrieved on list page

### 5. DataTables Configuration
- ✅ Order by ID DESC (newest first)
- ✅ Disabled state saving
- ✅ Proper destroy before reinit

## How to Diagnose

### Step 1: Add a Campus
Fill out the form and submit.

### Step 2: Check Success Message
Should show: **"Campus added successfully (ID: X)"**
- Note the ID number shown

### Step 3: Check Error Logs
Look for these entries in your PHP error log:

```
Creating campus with data: {"name":"Test Campus","institution_id":1,...}
Campus inserted successfully with ID: 5
Campus create result: {"success":true,"id":5}
New campus created with ID: 5
Total campuses retrieved: 5
First campus: {"id":"5","name":"Test Campus",...}
```

### Step 4: Run Test Script
Visit: `http://localhost/tescongh/test_campus_query.php`

This shows:
- Last 5 campuses in database (newest first)
- Total count
- Raw database data

### Step 5: Check Campuses Page
Visit: `http://localhost/tescongh/campuses.php`

The table should show campuses with highest ID first.

## Possible Issues & Solutions

### Issue 1: Campus IS in database but NOT on page
**Cause:** Browser/JavaScript caching
**Solution:**
- Hard refresh: Ctrl+Shift+F5
- Clear browser cache
- Try incognito/private window

### Issue 2: Wrong campus showing
**Cause:** Form autocomplete filling old data
**Solution:**
- Form has `autocomplete="off"`
- Input fields have `autocomplete="off"`
- Clear browser form data

### Issue 3: Insert failing silently
**Cause:** PDO error not caught
**Solution:**
- Check error logs for "Campus insert error:"
- Error message will show in flash message
- Check database permissions

### Issue 4: DataTables showing cached data
**Cause:** DataTables state saving
**Solution:**
- `stateSave: false` is set
- Table destroys before reinit
- Order is DESC (newest first)

## Files Modified

1. `classes/Campus.php` - Query and error handling
2. `campus_add.php` - Debug logging and redirect
3. `campuses.php` - Cache headers and debug logging
4. `includes/functions.php` - Cache control in redirect
5. `test_campus_query.php` - Test script (NEW)

## Expected Behavior

1. Fill form → Submit
2. See success message with ID
3. Redirect to campuses page
4. New campus appears at TOP of list (highest ID)
5. Flash message shows success

## Debug Checklist

- [ ] Check success message shows correct ID
- [ ] Check error logs for insert confirmation
- [ ] Run test script to verify database
- [ ] Hard refresh campuses page (Ctrl+Shift+F5)
- [ ] Verify newest campus is at top of table
- [ ] Check DataTables is sorting by ID DESC

## Still Not Working?

If after all these steps the issue persists:

1. **Share error log entries** - Copy the log entries from campus creation
2. **Share test script output** - What does test_campus_query.php show?
3. **Check database directly** - Run SQL: `SELECT * FROM campuses ORDER BY id DESC LIMIT 5`
4. **Browser console** - Check for JavaScript errors
5. **Network tab** - Verify the redirect is happening

## Quick Test

```sql
-- Run this in phpMyAdmin to see last 3 campuses
SELECT id, name, institution_id, location, created_at 
FROM campuses 
ORDER BY id DESC 
LIMIT 3;
```

Compare the IDs with what shows on the campuses page.
