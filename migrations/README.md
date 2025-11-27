# Database Migrations

This folder contains SQL migration scripts for database schema changes.

## How to Apply Migrations

1. Connect to your MySQL database
2. Run the SQL scripts in chronological order (by date in filename)
3. Verify the changes were applied successfully

## Migration History

### 2025-11-25: Allow NULL member_id in payments table
**File:** `allow_null_member_id_in_payments.sql`

**Purpose:** Preserve payment records even when members are deleted

**Changes:**
- Dropped existing foreign key constraint `payments_ibfk_1` (which had ON DELETE CASCADE)
- Modified `payments.member_id` column to allow NULL values
- Recreated foreign key constraint with `ON DELETE SET NULL` behavior
- When a member is deleted, their payment records are automatically preserved with `member_id` set to NULL

**How to apply:**
```bash
mysql -u your_username -p your_database < migrations/allow_null_member_id_in_payments.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Copy and paste the contents of `allow_null_member_id_in_payments.sql`
5. Click "Go"

**Verification:**
```sql
-- Check if the column allows NULL
DESCRIBE payments;
-- The member_id column should show "YES" in the Null column
```
