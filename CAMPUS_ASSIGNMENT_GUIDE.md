# Campus Assignment Guide - Executives & Patrons

## How the System Attaches People to Campuses

### **Three Levels of Campus Association**

```
1. General Affiliation (All Members)
   members.campus_id → Campus they study at

2. Executive Assignment (Executives Only)
   campus_executives table → Campus they lead + Position

3. Patron Assignment (Patrons)
   members.campus_id → Campus they support
```

---

## For Executives

### **Database Structure**

```sql
campus_executives
├── id
├── campus_id       -- Which campus they lead
├── member_id       -- Link to member record
├── position_id     -- Their specific position (President, etc.)
└── appointed_at    -- When they took office
```

### **How It Works**

**Step 1: Create Executive Member**
```php
// Create user with Executive role
$user->register($email, $password, 'Executive');

// Create member profile
$member->create([
    'user_id' => $userId,
    'position' => 'Executive',
    'campus_id' => $campusId,  // Where they study
    // ... other fields
]);
```

**Step 2: Assign to Campus Position**
```php
// Assign them to a specific executive position
INSERT INTO campus_executives (campus_id, member_id, position_id)
VALUES ($campusId, $memberId, $positionId);
```

### **Example: Adding a Campus President**

```
1. Fill Executive Form:
   - Name: John Doe
   - Email: john@gmail.com
   - Campus: UG Main Campus - Legon
   - Position: President
   
2. System Creates:
   ✓ User account (role=Executive)
   ✓ Member record (position=Executive, campus_id=1)
   ✓ Campus executive (campus_id=1, position_id=1)
   
3. Result:
   John is now the President of UG Main Campus
```

---

## For Patrons

### **Database Structure**

```sql
members
├── campus_id  -- Campus they're affiliated with
└── position = 'Patron'
```

### **How It Works**

**Simple Affiliation:**
```php
// Create patron
$member->create([
    'user_id' => $userId,
    'position' => 'Patron',
    'campus_id' => $campusId,  // Campus they support
    'student_id' => null,      // No student ID
    'year' => null,            // No year
    'program' => null          // No program
]);
```

### **Example: Adding a Faculty Patron**

```
1. Fill Patron Form:
   - Name: Dr. Jane Smith
   - Email: dr.smith@ug.edu.gh
   - Campus: UG Main Campus - Legon
   - Position: Senior Patron
   
2. System Creates:
   ✓ User account (role=Patron)
   ✓ Member record (position=Patron, campus_id=1)
   
3. Result:
   Dr. Smith is now a patron of UG Main Campus
```

---

## Viewing Campus Assignments

### **Campus Executives Page**

**URL:** `campus_executives.php`

**Features:**
- View all executives by campus
- Filter by specific campus
- See position hierarchy
- View contact information
- Remove from position (Admin only)

**Display:**
```
University of Ghana - Main Campus (5 Executives)
┌────────────────────────────────────────────────┐
│ Position      │ Name         │ Contact         │
├────────────────────────────────────────────────┤
│ President     │ John Doe     │ 0241234567     │
│ Vice President│ Jane Smith   │ 0209876543     │
│ Secretary     │ Bob Johnson  │ 0551234567     │
│ Treasurer     │ Alice Brown  │ 0261234567     │
│ Organizer     │ Tom Wilson   │ 0271234567     │
└────────────────────────────────────────────────┘
```

---

## Files Created

### **1. Executive Add Form**
- `executive_add.php`
  - Specialized form for executives
  - Includes position dropdown
  - Creates campus_executives entry
  - Sets role to "Executive"

### **2. Campus Executives View**
- `campus_executives.php`
  - Lists all campus executives
  - Grouped by campus
  - Shows position hierarchy
  - Filter by campus

---

## Complete Flow Diagrams

### **Executive Assignment Flow**

```
User Fills Form
    ↓
Select Campus: UG Main Campus
    ↓
Select Position: President
    ↓
Submit Form
    ↓
┌─────────────────────────────────────┐
│ 1. Create User (role=Executive)    │
│    users table                      │
└─────────────────────────────────────┘
    ↓
┌─────────────────────────────────────┐
│ 2. Create Member (position=Exec)   │
│    members table                    │
│    campus_id = UG Main Campus       │
└─────────────────────────────────────┘
    ↓
┌─────────────────────────────────────┐
│ 3. Assign Position                  │
│    campus_executives table          │
│    campus_id + position_id          │
└─────────────────────────────────────┘
    ↓
Executive Assigned! ✓
```

### **Patron Assignment Flow**

```
User Fills Form
    ↓
Select Campus: UG Main Campus
    ↓
Select Position: Senior Patron
    ↓
Submit Form
    ↓
┌─────────────────────────────────────┐
│ 1. Create User (role=Patron)       │
│    users table                      │
└─────────────────────────────────────┘
    ↓
┌─────────────────────────────────────┐
│ 2. Create Member (position=Patron) │
│    members table                    │
│    campus_id = UG Main Campus       │
│    student_id = NULL                │
└─────────────────────────────────────┘
    ↓
Patron Assigned! ✓
```

---

## Database Queries

### **Get All Executives for a Campus**

```sql
SELECT 
    m.fullname,
    p.name as position_name,
    p.level,
    u.email,
    m.phone
FROM campus_executives ce
INNER JOIN members m ON ce.member_id = m.id
INNER JOIN users u ON m.user_id = u.id
INNER JOIN positions p ON ce.position_id = p.id
WHERE ce.campus_id = ?
ORDER BY p.level ASC;
```

### **Get All Patrons for a Campus**

```sql
SELECT 
    m.fullname,
    u.email,
    m.phone
FROM members m
INNER JOIN users u ON m.user_id = u.id
WHERE m.campus_id = ?
  AND m.position = 'Patron'
ORDER BY m.fullname ASC;
```

### **Check if Position is Filled**

```sql
SELECT COUNT(*) as filled
FROM campus_executives
WHERE campus_id = ?
  AND position_id = ?;
```

---

## Key Differences

| Aspect | Executive | Patron |
|--------|-----------|--------|
| **Table** | campus_executives | members only |
| **Position Tracking** | ✅ Yes (position_id) | ⚠️ Simple flag |
| **Multiple Campuses** | ❌ One at a time | ❌ One at a time |
| **Specific Role** | ✅ President, Secretary, etc. | ⚠️ Just "Patron" |
| **Term Tracking** | ✅ appointed_at | ❌ No |
| **Hierarchy** | ✅ Yes (level) | ❌ No |

---

## Benefits of This Approach

### **For Executives:**

✅ **Clear Structure**
- Each campus has defined positions
- Hierarchy is clear (President > VP > Secretary)
- Easy to see who holds what position

✅ **Historical Tracking**
- Can track when someone was appointed
- Can add end_date for term limits
- Position history available

✅ **Prevents Conflicts**
- One person per position per campus
- Can't have two presidents

✅ **Reporting**
- Easy to generate executive lists
- Can see vacant positions
- Campus-by-campus breakdown

### **For Patrons:**

✅ **Simplicity**
- Just affiliated with a campus
- No complex position tracking needed
- Flexible assignment

✅ **Multiple Patrons**
- Campus can have many patrons
- No limit on numbers
- Easy to add/remove

---

## Usage Examples

### **Adding an Executive**

```php
// In executive_add.php
if ($_POST) {
    // 1. Create user
    $userResult = $user->register($email, $password, 'Executive');
    $userId = $userResult['user_id'];
    
    // 2. Create member
    $memberResult = $member->create([
        'user_id' => $userId,
        'position' => 'Executive',
        'campus_id' => $_POST['campus_id'],
        // ... other fields
    ]);
    $memberId = $memberResult['member_id'];
    
    // 3. Assign to campus position
    $query = "INSERT INTO campus_executives 
              (campus_id, member_id, position_id) 
              VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_POST['campus_id'],
        $memberId,
        $_POST['position_id']  // From positions table
    ]);
}
```

### **Viewing Campus Executives**

```php
// In campus_executives.php
$query = "SELECT 
            m.fullname,
            p.name as position,
            p.level,
            c.name as campus
          FROM campus_executives ce
          JOIN members m ON ce.member_id = m.id
          JOIN positions p ON ce.position_id = p.id
          JOIN campuses c ON ce.campus_id = c.id
          ORDER BY c.name, p.level";
```

---

## Navigation Updates

**Sidebar Menu:**
```
Members
├── All Members
├── Add Member
├── Add Executive  ← NEW!
└── Campus Executives  ← NEW!
```

---

## Next Steps

### **1. Update Sidebar**
Add links to:
- `executive_add.php`
- `campus_executives.php`

### **2. Create Patron Form**
Similar to executive_add.php but:
- No position dropdown
- Academic fields optional
- No campus_executives entry

### **3. Add Term Management**
```sql
ALTER TABLE campus_executives
ADD COLUMN term_start DATE,
ADD COLUMN term_end DATE;
```

### **4. Add Transition Tracking**
Keep history when executives change:
```sql
CREATE TABLE executive_history (
    id INT PRIMARY KEY,
    campus_id INT,
    member_id INT,
    position_id INT,
    started_at DATE,
    ended_at DATE
);
```

---

## Summary

**✅ Executives:**
- Attached via `campus_executives` table
- Have specific positions (President, etc.)
- One position per person per campus
- Full tracking and hierarchy

**✅ Patrons:**
- Attached via `members.campus_id`
- Simple affiliation
- Multiple patrons per campus
- No complex tracking needed

**✅ Files Created:**
- `executive_add.php` - Add executives
- `campus_executives.php` - View executives by campus

**Status:** ✅ Ready to Use!

---

**Version:** 1.2.1  
**Date:** January 23, 2025
