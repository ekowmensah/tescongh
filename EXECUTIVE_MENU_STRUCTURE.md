# Executive Menu Structure - Complete Guide

## Overview
Executives see a campus-specific menu with access only to their campus data.

---

## Executive Menu (Complete)

### **Dashboard Section**
```
✓ Dashboard (General overview)
✓ My Executive Team (Campus-specific executive dashboard)
```

### **Management Section**
```
✓ Members (Campus-specific - only their campus members)
✓ Executives (Campus-specific - only their campus executives)
✓ Patrons (Campus-specific - only their campus patrons)
```

### **Finance Section**
```
✓ Dues (View all)
✓ Payments (Campus-specific - only their campus payments)
```

### **Communication Section**
```
✓ SMS (Campus-specific - send to their campus only)
✓ Events (View all)
```

---

## Access Control Matrix

| Menu Item | Admin | Executive | Patron | Member |
|-----------|:-----:|:---------:|:------:|:------:|
| **Dashboard** | ✅ All | ✅ Campus | ✅ Own | ✅ Own |
| **My Executive Team** | ❌ | ✅ Campus | ❌ | ❌ |
| **Members** | ✅ All | ✅ Campus | ❌ | ❌ |
| **Executives** | ✅ All | ✅ Campus | ❌ | ❌ |
| **Patrons** | ✅ All | ✅ Campus | ❌ | ❌ |
| **Institutions** | ✅ | ❌ | ❌ | ❌ |
| **Campuses** | ✅ | ❌ | ❌ | ❌ |
| **Regions** | ✅ | ❌ | ❌ | ❌ |
| **Constituencies** | ✅ | ❌ | ❌ | ❌ |
| **Positions** | ✅ | ❌ | ❌ | ❌ |
| **Payments** | ✅ All | ✅ Campus | ❌ | ❌ |
| **SMS** | ✅ All | ✅ Campus | ❌ | ❌ |
| **Events** | ✅ | ✅ | ✅ | ✅ |
| **Users** | ✅ | ❌ | ❌ | ❌ |
| **Reports** | ✅ | ❌ | ❌ | ❌ |

---

## Campus-Specific Filtering

### **How It Works**

**Step 1: Get Executive's Campus**
```php
// In each page
$userId = $_SESSION['user_id'];

// Get member record
$memberQuery = "SELECT id, campus_id FROM members WHERE user_id = :user_id";
$memberStmt = $db->prepare($memberQuery);
$memberStmt->bindParam(':user_id', $userId);
$memberStmt->execute();
$currentMember = $memberStmt->fetch();

$campusId = $currentMember['campus_id'];
```

**Step 2: Filter Data by Campus**
```php
// For Executives: Filter by their campus
if (hasRole('Executive') && !hasRole('Admin')) {
    $query .= " AND m.campus_id = :campus_id";
    $params['campus_id'] = $campusId;
}
```

---

## Implementation by Page

### **1. members.php**

**Admin:** See all members
**Executive:** See only members from their campus

```php
<?php
if (hasRole('Executive') && !hasRole('Admin')) {
    // Get executive's campus
    $memberQuery = "SELECT campus_id FROM members WHERE user_id = :user_id";
    $memberStmt = $db->prepare($memberQuery);
    $memberStmt->bindParam(':user_id', $_SESSION['user_id']);
    $memberStmt->execute();
    $execMember = $memberStmt->fetch();
    
    // Add campus filter
    $filters['campus_id'] = $execMember['campus_id'];
}

$members = $member->getAll($recordsPerPage, $offset, $filters);
?>
```

---

### **2. campus_executives.php**

**Admin:** See all campus executives
**Executive:** See only executives from their campus

```php
<?php
if (hasRole('Executive') && !hasRole('Admin')) {
    // Auto-filter to executive's campus
    $filterCampusId = $execMember['campus_id'];
}
?>
```

---

### **3. patrons.php**

**Admin:** See all patrons
**Executive:** See only patrons from their campus

```php
<?php
$query = "SELECT m.*, ... FROM members m WHERE m.position = 'Patron'";

if (hasRole('Executive') && !hasRole('Admin')) {
    $query .= " AND m.campus_id = :campus_id";
}
?>
```

---

### **4. payments.php**

**Admin:** See all payments
**Executive:** See only payments from their campus members

```php
<?php
$query = "SELECT p.*, m.fullname, m.campus_id 
          FROM payments p
          INNER JOIN members m ON p.member_id = m.id";

if (hasRole('Executive') && !hasRole('Admin')) {
    $query .= " WHERE m.campus_id = :campus_id";
}
?>
```

---

### **5. sms.php**

**Admin:** Send to all members
**Executive:** Send only to their campus members

```php
<?php
// Member selection dropdown
$query = "SELECT id, fullname, phone FROM members WHERE 1=1";

if (hasRole('Executive') && !hasRole('Admin')) {
    $query .= " AND campus_id = :campus_id";
}
?>
```

---

## Add Member/Executive/Patron Buttons

### **For Executives**

**members.php:**
```php
<a href="member_add.php?campus_id=<?php echo $campusId; ?>" class="btn btn-primary">
    <i class="cil-plus"></i> Add Member
</a>
```

**campus_executives.php:**
```php
<a href="campus_assign_executive.php?campus_id=<?php echo $campusId; ?>" class="btn btn-success">
    <i class="cil-star"></i> Assign Executive
</a>
```

**patrons.php:**
```php
<a href="campus_assign_patron.php?campus_id=<?php echo $campusId; ?>" class="btn btn-info">
    <i class="cil-user-follow"></i> Assign Patron
</a>
```

---

## Navigation Structure

### **Admin Menu**
```
Dashboard
Management
├── Members (All)
├── Executives (All)
├── Patrons (All)
├── Institutions
├── Campuses
├── Regions
├── Constituencies
└── Positions

Finance
├── Dues
└── Payments (All)

Communication
├── SMS (All)
└── Events

Administration
├── Users
└── Reports
```

### **Executive Menu** ← UPDATED
```
Dashboard
└── My Executive Team (Campus only)

Management
├── Members (Campus only)
├── Executives (Campus only)
└── Patrons (Campus only)

Finance
├── Dues
└── Payments (Campus only)

Communication
├── SMS (Campus only)
└── Events
```

### **Patron Menu**
```
Dashboard

Communication
└── Events
```

### **Member Menu**
```
Dashboard

Communication
└── Events
```

---

## Helper Function

Create a helper function to get executive's campus:

```php
// In includes/functions.php
function getExecutiveCampusId() {
    if (!hasRole('Executive')) {
        return null;
    }
    
    global $db;
    $userId = $_SESSION['user_id'];
    
    $query = "SELECT campus_id FROM members WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $member = $stmt->fetch();
    
    return $member ? $member['campus_id'] : null;
}
```

**Usage:**
```php
$campusId = getExecutiveCampusId();
if ($campusId) {
    $filters['campus_id'] = $campusId;
}
```

---

## Summary

**✅ Executive Menu Structure:**

1. ✅ Dashboard
2. ✅ My Executive Team (Campus-specific)
3. ✅ Members (Campus-specific)
4. ✅ Executives (Campus-specific)
5. ✅ Patrons (Campus-specific)
6. ✅ Payments (Campus-specific)
7. ✅ SMS (Campus-specific)

**❌ Hidden from Executives:**
- Institutions
- Campuses
- Regions
- Constituencies
- Positions
- Users
- Reports

**Status:** ✅ Menu Structure Updated!

**Next Steps:**
1. Update each page to filter by campus for executives
2. Add campus_id parameter to add/assign buttons
3. Test executive access restrictions

---

**Version:** 1.5.0  
**Date:** January 23, 2025
